#!/bin/bash
# Signal daily pipeline (launchd: com.newblood.signal-push) — ingest new raw
# logs, verify bot IPs, push to signal.newblood.com (Neon), refresh md reports.
cd "$(dirname "$0")" || exit 1
{
  echo "=== signal daily $(date '+%Y-%m-%d %H:%M:%S') ==="
  /usr/bin/python3 signal.py ingest
  /usr/bin/python3 signal.py verify
  /usr/bin/python3 signal.py push
  /usr/bin/python3 signal.py report
} >> data/daily.log 2>&1
