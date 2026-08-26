#!/usr/bin/env python3
"""Signal Logs v0 — AI-crawler & traffic analysis from Nexcess transfer logs.

Usage:
  python3 signal.py fetch     # rsync new rotated transfer-log zips from each Nexcess site
  python3 signal.py ingest    # parse data/raw/<site>/* into data/signal.db
  python3 signal.py verify    # fetch published crawler IP ranges, verify bot claims
  python3 signal.py report    # write reports/<site>.md + reports/index.md
  python3 signal.py push      # POST new events to signal.newblood.com/api/import (Neon)

Bot signatures live in ~/Herd/signal-ingest/lib/bots.json (shared with the TS
rollup cron) — the tables below are a fallback if that file is missing.
"""
import sys, os, re, json, sqlite3, zipfile, ipaddress, socket, urllib.request, subprocess
from urllib.parse import urlparse
from collections import defaultdict
from datetime import datetime

ROOT = os.path.dirname(os.path.abspath(__file__))
DB = os.path.join(ROOT, 'data', 'signal.db')
RAW = os.path.join(ROOT, 'data', 'raw')
REPORTS = os.path.join(ROOT, 'reports')
RANGES_CACHE = os.path.join(ROOT, 'data', 'ip-ranges.json')

# Nexcess collection: SSH+rsync new rotated transfer-log zips into data/raw/<site>/.
# Creds live in ~/Herd/<site>/.nexcess-credentials (NEXCESS_HOST/USER/PASS/PORT; optional
# NEXCESS_LOG_DIR, default 'logs' relative to the SSH home — Nexcess puts transfer.log there).
HERD = os.path.expanduser('~/Herd')
NEXCESS_SITES = ['akta', 'ohdbalt', 'dadabilities', 'newblood', 'lomalindamarket', 'rookgame']

# --- bot signature table: substring (lowercased) -> (bot_id, family, kind) ---
# kind: ai-train = AI training crawler, ai-search = AI search indexer,
#       ai-live  = live fetch on behalf of an AI user (the money metric),
#       search   = classic search engine, other = misc known bot
BOTS = [
    ('gptbot',             'GPTBot',            'OpenAI',     'ai-train'),
    ('oai-searchbot',      'OAI-SearchBot',     'OpenAI',     'ai-search'),
    ('chatgpt-user',       'ChatGPT-User',      'OpenAI',     'ai-live'),
    ('claudebot',          'ClaudeBot',         'Anthropic',  'ai-train'),
    ('claude-searchbot',   'Claude-SearchBot',  'Anthropic',  'ai-search'),
    ('claude-web',         'Claude-Web',        'Anthropic',  'ai-live'),
    ('claude-user',        'Claude-User',       'Anthropic',  'ai-live'),
    ('perplexitybot',      'PerplexityBot',     'Perplexity', 'ai-search'),
    ('perplexity-user',    'Perplexity-User',   'Perplexity', 'ai-live'),
    ('google-extended',    'Google-Extended',   'Google',     'ai-train'),
    ('googleother',        'GoogleOther',       'Google',     'ai-train'),
    ('googlebot',          'Googlebot',         'Google',     'search'),
    ('bingbot',            'Bingbot',           'Microsoft',  'search'),
    ('applebot-extended',  'Applebot-Extended', 'Apple',      'ai-train'),
    ('applebot',           'Applebot',          'Apple',      'search'),
    ('amazonbot',          'Amazonbot',         'Amazon',     'ai-train'),
    ('meta-externalagent', 'Meta-External',     'Meta',       'ai-train'),
    ('bytespider',         'Bytespider',        'ByteDance',  'ai-train'),
    ('ccbot',              'CCBot',             'CommonCrawl','ai-train'),
    ('duckduckbot',        'DuckDuckBot',       'DuckDuckGo', 'search'),
    ('yandex',             'YandexBot',         'Yandex',     'search'),
    ('ahrefsbot',          'AhrefsBot',         'Ahrefs',     'other'),
    ('semrushbot',         'SemrushBot',        'Semrush',    'other'),
    ('mj12bot',            'MJ12bot',           'Majestic',   'other'),
]
AI_REFERRERS = {
    'chatgpt.com': 'ChatGPT', 'chat.openai.com': 'ChatGPT',
    'perplexity.ai': 'Perplexity', 'www.perplexity.ai': 'Perplexity',
    'gemini.google.com': 'Gemini', 'copilot.microsoft.com': 'Copilot',
    'claude.ai': 'Claude', 'www.bing.com/chat': 'Copilot',
}
# published crawler IP ranges (fetched by `verify`)
RANGE_SOURCES = {
    'OpenAI': ['https://openai.com/gptbot.json', 'https://openai.com/searchbot.json',
               'https://openai.com/chatgpt-user.json'],
    'Google': ['https://developers.google.com/static/search/apis/ipranges/googlebot.json',
               'https://developers.google.com/static/search/apis/ipranges/special-crawlers.json'],
    'Perplexity': ['https://www.perplexity.com/perplexitybot.json',
                   'https://www.perplexity.com/perplexity-user.json'],
}
RDNS_FAMILIES = {'Microsoft': ('.search.msn.com',), 'Apple': ('.applebot.apple.com',),
                 'Anthropic': ()}  # Anthropic publishes no ranges; rDNS rarely set → stays 'claimed'

# --- shared bot truth: prefer bots.json from the signal-ingest repo ---
INGEST_REPO = '/Users/jeremyoms/Herd/signal-ingest'
_BOTS_JSON = os.path.join(INGEST_REPO, 'lib', 'bots.json')
if os.path.exists(_BOTS_JSON):
    try:
        _bj = json.load(open(_BOTS_JSON))
        BOTS = [(b['sig'], b['bot'], b['family'], b['kind']) for b in _bj['bots']]
        AI_REFERRERS = _bj['aiReferrers']
        RANGE_SOURCES = _bj['rangeSources']
        RDNS_FAMILIES = {f: tuple(s) for f, s in _bj['rdnsFamilies'].items()}
        RDNS_FAMILIES.setdefault('Anthropic', ())
    except Exception as e:
        print(f'warn: could not load {_BOTS_JSON} ({e}); using embedded tables')

LOG_RE = re.compile(
    r'(?P<ip>\S+) \S+ \S+ \[(?P<ts>[^\]]+)\] "(?P<method>\S+) (?P<path>\S+)[^"]*" '
    r'(?P<status>\d{3}) (?P<bytes>\S+) "(?P<ref>[^"]*)" "(?P<ua>[^"]*)"')

def ai_referrer(ref):
    """AI assistant referral label for a human visit, or None.

    Matches on the referrer's HOST, never a raw substring. A substring test
    counted a site's own utm-tagged URL as an inbound AI visit -- e.g.
    'https://lomalindamarket.com/?utm_source=chatgpt.com' contains
    'chatgpt.com', so one real ChatGPT arrival inflated into an AI referral for
    every internal click that followed (557 counted vs 3 real, 2026-08-20).

    A few table keys carry a path ('www.bing.com/chat'); those must match the
    host AND that path prefix, so plain Bing search is not labelled Copilot.
    Fails closed: an unparseable referrer is not an AI referral.
    Mirrors aiReferrer() in signal-ingest/lib/bots.ts -- keep both in step.
    """
    if not ref or ref == '-':
        return None
    try:
        u = urlparse(ref)
        host = (u.hostname or '').lower()
    except Exception:
        return None
    if not host:
        return None
    path = (u.path or '').lower()
    for dom, label in AI_REFERRERS.items():
        dom_host, slash, dom_path = dom.partition('/')
        dom_host = dom_host.lower()
        # Exact host, or a subdomain of it -- never a suffix collision like
        # "notchatgpt.com" against "chatgpt.com".
        if host != dom_host and not host.endswith('.' + dom_host):
            continue
        if slash:
            prefix = '/' + dom_path.lower()
            if path != prefix and not path.startswith(prefix + '/'):
                continue
        return label
    return None


def classify(ua):
    low = ua.lower()
    for sig, bot_id, family, kind in BOTS:
        if sig in low:
            return bot_id, family, kind
    return None, None, None

def open_db():
    os.makedirs(os.path.dirname(DB), exist_ok=True)
    db = sqlite3.connect(DB)
    db.executescript('''
      CREATE TABLE IF NOT EXISTS events(
        site TEXT, day TEXT, ip TEXT, method TEXT, path TEXT, status INT,
        ref TEXT, ua TEXT, bot TEXT, family TEXT, kind TEXT, verified INT DEFAULT 0);
      CREATE INDEX IF NOT EXISTS ix_events ON events(site, day, bot);
      CREATE TABLE IF NOT EXISTS ingested(site TEXT, file TEXT, PRIMARY KEY(site, file));
    ''')
    return db

def parse_lines(fh, site, db):
    n = 0
    rows = []
    for raw in fh:
        if isinstance(raw, bytes):
            raw = raw.decode('utf-8', 'replace')
        m = LOG_RE.match(raw)
        if not m:
            continue
        d = m.groupdict()
        try:
            day = datetime.strptime(d['ts'].split(':')[0], '%d/%b/%Y').strftime('%Y-%m-%d')
        except ValueError:
            continue
        bot, family, kind = classify(d['ua'])
        rows.append((site, day, d['ip'], d['method'], d['path'][:300], int(d['status']),
                     d['ref'][:300], d['ua'][:300], bot, family, kind))
        n += 1
        if len(rows) >= 5000:
            db.executemany('INSERT INTO events VALUES(?,?,?,?,?,?,?,?,?,?,?,0)', rows); rows = []
    if rows:
        db.executemany('INSERT INTO events VALUES(?,?,?,?,?,?,?,?,?,?,?,0)', rows)
    return n

def _read_creds(path):
    creds = {}
    for line in open(path):
        line = line.strip()
        if not line or line.startswith('#') or '=' not in line:
            continue
        k, v = line.split('=', 1)
        creds[k.strip()] = v.strip().strip('"').strip("'")
    return creds

def discover():
    """Read-only: SSH each Nexcess site and locate its transfer.log files, so we know what
    to set NEXCESS_LOG_DIR to. Prints the remote home + any transfer.log* paths found."""
    for site in NEXCESS_SITES:
        cred_path = os.path.join(HERD, site, '.nexcess-credentials')
        if not os.path.exists(cred_path):
            print(f'{site}: no creds'); continue
        c = _read_creds(cred_path)
        host, user, pw = c.get('NEXCESS_HOST'), c.get('NEXCESS_USER'), c.get('NEXCESS_PASS')
        port = c.get('NEXCESS_PORT', '22')
        env = dict(os.environ, SSHPASS=pw or '')
        remote = ("echo HOME=$HOME; "
                  "echo '--- ls -la ~/logs ---'; ls -la ~/logs 2>/dev/null | head -40; "
                  "echo '--- any *log* under ~/logs ---'; ls -la ~/logs/* 2>/dev/null | head -20; "
                  "echo '--- find access/transfer logs (depth 6) ---'; "
                  "find ~ -maxdepth 6 \\( -iname '*transfer*' -o -iname '*access*log*' -o -iname '*.log' -o -iname '*.log.*' \\) 2>/dev/null | head -20")
        cmd = ['sshpass', '-e', 'ssh', '-p', port, '-o', 'StrictHostKeyChecking=no',
               '-o', 'ConnectTimeout=25', f'{user}@{host}', remote]
        print(f'\n=== {site} ({user}@{host}:{port}) ===')
        try:
            r = subprocess.run(cmd, env=env, capture_output=True, text=True, timeout=60)
            if r.stdout.strip():
                print(r.stdout.strip())
            if r.returncode != 0:
                print(f'[rc={r.returncode}] {(r.stderr or "").strip().splitlines()[-1] if r.stderr.strip() else ""}')
        except subprocess.TimeoutExpired:
            print('  timed out')

def fetch():
    """rsync newly-rotated transfer-log zips from each Nexcess site into data/raw/<site>/.

    Only the dated, rotated `transfer.log-YYYY-MM-DD.zip` files are pulled — they have
    unique names (so ingest's per-file cursor picks them up) and are complete. The live
    `transfer.log` (today, still growing) is skipped; it arrives as a dated zip after it
    rotates (~1-day lag, fine — the Vercel-drain sites cover real-time). rsync only
    transfers files we don't already have, so this is cheap to run daily.
    """
    if not (subprocess.run(['which', 'sshpass'], capture_output=True).returncode == 0):
        print('fetch: sshpass not installed (brew install sshpass) — aborting'); return
    total_new = 0
    for site in NEXCESS_SITES:
        cred_path = os.path.join(HERD, site, '.nexcess-credentials')
        if not os.path.exists(cred_path):
            print(f'{site}: no creds at {cred_path}, skipping'); continue
        c = _read_creds(cred_path)
        host, user, pw = c.get('NEXCESS_HOST'), c.get('NEXCESS_USER'), c.get('NEXCESS_PASS')
        port = c.get('NEXCESS_PORT', '22')
        # Nexcess access logs live at ~/var/<host>/logs, reachable via the ~/logs/<host> symlink.
        logdir = (c.get('NEXCESS_LOG_DIR') or f'logs/{host}').rstrip('/')
        if not (host and user and pw):
            print(f'{site}: missing NEXCESS_HOST/USER/PASS, skipping'); continue
        dest = os.path.join(RAW, site)
        os.makedirs(dest, exist_ok=True)
        before = set(os.listdir(dest))
        env = dict(os.environ, SSHPASS=pw)
        ssh = f'ssh -p {port} -o StrictHostKeyChecking=no -o ConnectTimeout=25 -o BatchMode=no'
        # remote glob is expanded by the login shell; --ignore-existing keeps it incremental
        src = f'{user}@{host}:{logdir}/transfer.log-*.zip'
        cmd = ['sshpass', '-e', 'rsync', '-az', '--ignore-existing', '-e', ssh, src, dest + '/']
        try:
            r = subprocess.run(cmd, env=env, capture_output=True, text=True, timeout=600)
        except subprocess.TimeoutExpired:
            print(f'{site}: rsync timed out'); continue
        new = sorted(set(os.listdir(dest)) - before)
        if r.returncode != 0 and not new:
            err = (r.stderr or '').strip().splitlines()
            hint = ''
            if any('No such file' in e or 'change_dir' in e for e in err):
                hint = f"  (is the remote path right? set NEXCESS_LOG_DIR in {site}/.nexcess-credentials)"
            print(f'{site}: rsync failed (rc={r.returncode}): {err[-1] if err else "unknown"}{hint}')
            continue
        total_new += len(new)
        print(f'{site}: +{len(new)} new log file(s)' + (f' ({new[-1]})' if new else ''))
    print(f'fetch: {total_new} new file(s) pulled')

def ingest():
    db = open_db()
    for site in sorted(os.listdir(RAW)):
        sdir = os.path.join(RAW, site)
        if not os.path.isdir(sdir):
            continue
        total = 0
        for fn in sorted(os.listdir(sdir)):
            if db.execute('SELECT 1 FROM ingested WHERE site=? AND file=?', (site, fn)).fetchone():
                continue
            fp = os.path.join(sdir, fn)
            if fn.endswith('.zip'):
                with zipfile.ZipFile(fp) as z:
                    for name in z.namelist():
                        with z.open(name) as fh:
                            total += parse_lines(fh, site, db)
            elif fn.endswith('.log') and os.path.getsize(fp) > 0:
                with open(fp, 'rb') as fh:
                    total += parse_lines(fh, site, db)
            db.execute('INSERT OR IGNORE INTO ingested VALUES(?,?)', (site, fn))
            db.commit()
        print(f'{site}: +{total} events')
    db.commit()

def ingest_vercel():
    """Pull drained Vercel events from the signal-ingest export endpoint into the local DB."""
    ing = '/Users/jeremyoms/Herd/signal-ingest'
    secret = open(os.path.join(ing, '.drain-secret')).read().strip()
    bypass = open(os.path.join(ing, '.bypass-secret')).read().strip()
    db = open_db()
    db.execute("CREATE TABLE IF NOT EXISTS vercel_cursor(k TEXT PRIMARY KEY, last_id INTEGER)")
    row = db.execute("SELECT last_id FROM vercel_cursor WHERE k='main'").fetchone()
    last_id = row[0] if row else 0
    req = urllib.request.Request(
        f'https://signal-ingest-new-blood.vercel.app/api/export?since=2026-01-01T00:00:00Z&limit=100000',
        headers={'x-signal-secret': secret, 'x-vercel-protection-bypass': bypass})
    body = urllib.request.urlopen(req, timeout=60).read().decode()
    n, maxid = 0, last_id
    rows = []
    for line in body.split('\n'):
        if not line.strip():
            continue
        ev = json.loads(line)
        rid = int(ev['id'])
        if rid <= last_id:
            continue
        maxid = max(maxid, rid)
        bot, family, kind = classify(ev.get('ua') or '')
        day = (ev.get('ts') or '')[:10]
        rows.append((ev.get('site'), day, ev.get('ip'), ev.get('method'), (ev.get('path') or '')[:300],
                     int(ev.get('status') or 0), (ev.get('ref') or '')[:300], (ev.get('ua') or '')[:300],
                     bot, family, kind))
        n += 1
    if rows:
        db.executemany('INSERT INTO events VALUES(?,?,?,?,?,?,?,?,?,?,?,0)', rows)
    db.execute("INSERT OR REPLACE INTO vercel_cursor VALUES('main', ?)", (maxid,))
    db.commit()
    print(f'vercel: +{n} events (cursor {last_id} → {maxid})')

def push():
    """POST new Nexcess events to the signal-ingest /api/import route (Neon).

    Local rows are day-resolution, so each row gets a synthetic intraday second
    offset (midnight + n) to keep genuinely-repeated hits distinct through the
    server's content dedupe. Cursor = sqlite rowid; re-runs only send new rows.
    """
    import gzip as _gzip
    secret = open(os.path.join(INGEST_REPO, '.drain-secret')).read().strip()
    db = open_db()
    db.execute("CREATE TABLE IF NOT EXISTS push_cursor(k TEXT PRIMARY KEY, last_rowid INTEGER)")
    row = db.execute("SELECT last_rowid FROM push_cursor WHERE k='main'").fetchone()
    last = row[0] if row else 0
    rows = db.execute('''SELECT rowid, site, day, ip, method, path, status, ref, ua
                         FROM events WHERE rowid > ? ORDER BY rowid''', (last,)).fetchall()
    if not rows:
        print('push: nothing new')
        return
    sent, offsets = 0, defaultdict(int)
    for i in range(0, len(rows), 5000):
        batch = rows[i:i + 5000]
        lines = []
        for rid, site, day, ip, method, path, status, ref, ua in batch:
            n = offsets[(site, day)] = (offsets[(site, day)] + 1) % 86400
            ts = f'{day}T{n // 3600:02d}:{(n % 3600) // 60:02d}:{n % 60:02d}Z'
            lines.append(json.dumps({'ts': ts, 'site': site, 'ip': ip, 'method': method,
                                     'path': path, 'status': status, 'ref': ref, 'ua': ua}))
        payload = _gzip.compress('\n'.join(lines).encode())
        req = urllib.request.Request('https://signal.newblood.com/api/import', data=payload,
                                     headers={'x-signal-secret': secret, 'content-encoding': 'gzip',
                                              'content-type': 'application/x-ndjson'})
        resp = json.load(urllib.request.urlopen(req, timeout=120))
        sent += resp.get('inserted', 0)
        db.execute("INSERT OR REPLACE INTO push_cursor VALUES('main', ?)", (batch[-1][0],))
        db.commit()
        print(f'  batch {i // 5000 + 1}: {resp}')
    print(f'push: {sent} new events inserted upstream (cursor → {rows[-1][0]})')

def fetch_ranges():
    nets = {}
    for family, urls in RANGE_SOURCES.items():
        cidrs = []
        for url in urls:
            try:
                req = urllib.request.Request(url, headers={'User-Agent': 'signal-logs/0.1'})
                data = json.load(urllib.request.urlopen(req, timeout=20))
                for p in data.get('prefixes', []):
                    cidrs.append(p.get('ipv4Prefix') or p.get('ipv6Prefix'))
            except Exception as e:
                print(f'  warn: {url}: {e}')
        nets[family] = [c for c in cidrs if c]
        print(f'{family}: {len(nets[family])} ranges')
    json.dump(nets, open(RANGES_CACHE, 'w'))
    return nets

def verify():
    nets_raw = fetch_ranges()
    nets = {f: [ipaddress.ip_network(c) for c in cs] for f, cs in nets_raw.items()}
    db = open_db()
    ips = db.execute("SELECT DISTINCT ip, family FROM events WHERE bot IS NOT NULL").fetchall()
    rdns_cache, ok, bad = {}, 0, 0
    for ip, family in ips:
        v = 0
        if family in nets:
            try:
                addr = ipaddress.ip_address(ip)
                v = int(any(addr in n for n in nets[family]))
            except ValueError:
                v = 0
        elif family in RDNS_FAMILIES and RDNS_FAMILIES[family]:
            if ip not in rdns_cache:
                try:
                    host = socket.gethostbyaddr(ip)[0]
                    rdns_cache[ip] = any(host.endswith(s) for s in RDNS_FAMILIES[family])
                except OSError:
                    rdns_cache[ip] = False
            v = int(rdns_cache[ip])
        else:
            v = -1  # no verification source published (e.g. Anthropic) — claimed only
        db.execute('UPDATE events SET verified=? WHERE ip=? AND family=?', (v, ip, family))
        ok += v == 1
        bad += v == 0
    db.commit()
    print(f'verified {ok} bot IPs, {bad} failed/unknown, of {len(ips)} unique claimed')

def q(db, sql, *args):
    return db.execute(sql, args).fetchall()

def report():
    db = open_db()
    os.makedirs(REPORTS, exist_ok=True)
    sites = [r[0] for r in q(db, 'SELECT DISTINCT site FROM events ORDER BY 1')]
    span = q(db, 'SELECT MIN(day), MAX(day) FROM events')[0]
    index = [f'# Signal Logs — AI & Crawler Report\n\n_Window: {span[0]} → {span[1]} · generated {datetime.now().strftime("%Y-%m-%d %H:%M")}_\n']
    for site in sites:
        lines = [f'# {site} — Signal report\n\n_Window: {span[0]} → {span[1]}_\n']
        vlabel = {1: '✅ verified', 0: '⚠️ FAILED verification (impostor?)', -1: 'claimed (no public ranges)'}
        lines.append('## AI engines reading this site\n')
        lines.append('| Bot | What it is | Hits | Pages | Verification |')
        lines.append('|---|---|---|---|---|')
        kinds = {'ai-train': 'AI training crawler', 'ai-search': 'AI search indexer',
                 'ai-live': '🔥 live fetch for an AI user', 'search': 'search engine', 'other': 'SEO tool'}
        for bot, kind, hits, pages, ver in q(db, '''
            SELECT bot, kind, COUNT(*), COUNT(DISTINCT path), MAX(verified) FROM events
            WHERE site=? AND kind LIKE 'ai%' GROUP BY bot ORDER BY 3 DESC''', site):
            lines.append(f'| {bot} | {kinds[kind]} | {hits} | {pages} | {vlabel[ver]} |')
        lines.append('\n## Top pages AI engines read\n')
        for path, hits, bots in q(db, '''
            SELECT path, COUNT(*), GROUP_CONCAT(DISTINCT bot) FROM events
            WHERE site=? AND kind LIKE 'ai%' AND verified!=0 AND status=200
            GROUP BY path ORDER BY 2 DESC LIMIT 12''', site):
            lines.append(f'- `{path}` — {hits} hits ({bots})')
        lines.append('\n## Humans arriving FROM an AI assistant\n')
        refs = q(db, '''SELECT ref, COUNT(*) FROM events
            WHERE site=? AND bot IS NULL AND ref!="-" GROUP BY ref''', site)
        ai_refs = defaultdict(int)
        for ref, n in refs:
            label = ai_referrer(ref)
            if label:
                ai_refs[label] += n
        if ai_refs:
            for label, n in sorted(ai_refs.items(), key=lambda x: -x[1]):
                lines.append(f'- **{label}** sent {n} visits')
        else:
            lines.append('- none in this window')
        lines.append('\n## Search engines (classic)\n')
        lines.append('| Bot | Hits | 200s | 404s served | Verification |')
        lines.append('|---|---|---|---|---|')
        for bot, hits, ok2, nf, ver in q(db, '''
            SELECT bot, COUNT(*), SUM(status=200), SUM(status=404), MAX(verified) FROM events
            WHERE site=? AND kind='search' GROUP BY bot ORDER BY 2 DESC''', site):
            lines.append(f'| {bot} | {hits} | {ok2} | {nf} | {vlabel[ver]} |')
        hum = q(db, '''SELECT COUNT(*), COUNT(DISTINCT ip), SUM(status>=500), SUM(status=404)
            FROM events WHERE site=? AND bot IS NULL''', site)[0]
        lines.append(f'\n## Health & humans\n')
        lines.append(f'- Non-bot requests: **{hum[0]}** from **{hum[1]}** unique IPs')
        lines.append(f'- 404s served: {hum[3]} · 5xx errors: {hum[2]}')
        with open(os.path.join(REPORTS, f'{site}.md'), 'w') as f:
            f.write('\n'.join(lines) + '\n')
        ai_total = q(db, "SELECT COUNT(*) FROM events WHERE site=? AND kind LIKE 'ai%'", site)[0][0]
        live = q(db, "SELECT COUNT(*) FROM events WHERE site=? AND kind='ai-live'", site)[0][0]
        index.append(f'- **{site}** — {ai_total} AI-crawler hits ({live} live AI-user fetches) → [{site}.md]({site}.md)')
        print(f'{site}: report written ({ai_total} AI hits, {live} live fetches)')
    with open(os.path.join(REPORTS, 'index.md'), 'w') as f:
        f.write('\n'.join(index) + '\n')

if __name__ == '__main__':
    cmd = sys.argv[1] if len(sys.argv) > 1 else 'report'
    {'discover': discover, 'fetch': fetch, 'ingest': ingest, 'ingest-vercel': ingest_vercel,
     'verify': verify, 'report': report, 'push': push}[cmd]()
