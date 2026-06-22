#!/bin/bash
# Signal daily pipeline (launchd: com.newblood.signal-push):
# fetch new Nexcess logs over SSH → ingest → verify bot IPs → push to Neon →
# refresh md reports → trigger the dashboard rollup (also a daily safety net for
# the hourly Vercel cron). PATH set so launchd finds sshpass/rsync (Homebrew).
cd "$(dirname "$0")" || exit 1
export PATH="/opt/homebrew/bin:/usr/local/bin:/usr/bin:/bin"
{
  echo "=== signal daily $(date '+%Y-%m-%d %H:%M:%S') ==="
  /usr/bin/python3 signal.py fetch
  /usr/bin/python3 signal.py ingest
  /usr/bin/python3 signal.py verify
  /usr/bin/python3 signal.py push
  /usr/bin/python3 signal.py report
  # roll up yesterday+today into events_daily (the dashboard's source)
  SECRET=$(cat "$HOME/Herd/signal-ingest/.drain-secret" 2>/dev/null)
  if [ -n "$SECRET" ]; then
    echo "--- rollup ---"
    curl -s -m 290 -H "x-signal-secret: $SECRET" \
      "https://signal.newblood.com/api/cron/rollup"; echo
  fi
} >> data/daily.log 2>&1
