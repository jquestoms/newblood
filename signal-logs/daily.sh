#!/bin/bash
# Signal daily pipeline (launchd: com.newblood.signal-push):
# fetch new Nexcess logs over SSH → ingest → verify bot IPs → push to Neon →
# refresh md reports → trigger the dashboard rollup (also a daily safety net for
# the hourly Vercel cron). PATH set so launchd finds sshpass/rsync (Homebrew).
cd "$(dirname "$0")" || exit 1
export PATH="/opt/homebrew/bin:/usr/local/bin:/usr/bin:/bin"
# Wait for the network before doing anything. launchd fires this at 6:15am, which
# is often mid-wake with the lid shut — DNS isn't up yet, so every rsync and every
# bot-range fetch fails instantly and the run logs a misleading "+0 events".
# (Root-caused 2026-08-14: six silent days, 8/8–8/14.) Poll for up to 10 minutes.
wait_for_network() {
  local tries=40                      # 40 x 15s = 10 min
  while [ $tries -gt 0 ]; do
    if /usr/bin/nc -z -G 5 1.1.1.1 443 >/dev/null 2>&1 \
       && /usr/bin/dscacheutil -q host -a name openai.com 2>/dev/null | grep -q ip_address; then
      return 0
    fi
    sleep 15
    tries=$((tries - 1))
  done
  return 1
}

{
  echo "=== signal daily $(date '+%Y-%m-%d %H:%M:%S') ==="
  if ! wait_for_network; then
    echo "SKIPPED: no network after 10 min (machine likely asleep/offline). Nothing fetched."
    echo "=== end (skipped) ==="
    exit 0
  fi
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
