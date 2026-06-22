#!/bin/bash
#
# Deploy the New Blood custom theme to Nexcess (newblood.com)
#
# The ONLY custom code in this project is the `newblood` block theme. Every
# plugin (WPForms, Yoast, WooCommerce, Hummingbird, redis-cache, …) and the
# nexcess-mapps mu-plugin are third-party/platform code that AUTO-UPDATES on
# the server, so this script never touches them — it syncs only the theme.
# Because the theme is fully owned by this repo, --delete is safe *within the
# theme directory* (a prod theme file we don't have is stale and should go);
# it can never reach uploads, plugins, mu-plugins, or WP drop-ins because
# those are not in the sync root.
#
# What gets deployed:
#   - wp-content/themes/newblood/   (the whole theme, minus tests/ and cruft)
#
# Post-deploy (run manually, see end of output): the discovery module's
# schema + rewrite rules self-heal on the first request via version-guarded
# migration/flush; a `wp rewrite flush` is belt-and-suspenders.
#
# Usage:
#   ./deploy.sh            # preview (true dry run), confirm, deploy
#   ./deploy.sh --dry-run  # preview only, no changes
#   ./deploy.sh --force    # skip confirmation (CI / repeat deploys)
#

set -euo pipefail

DRY_RUN=false
SKIP_CONFIRM=false
for arg in "$@"; do
    case $arg in
        --dry-run) DRY_RUN=true ;;
        --force)   SKIP_CONFIRM=true ;;
    esac
done

if [ ! -f .nexcess-credentials ]; then
    echo "Error: .nexcess-credentials file not found"
    exit 1
fi
source .nexcess-credentials

# sshpass -e reads the password from SSHPASS, keeping it out of `ps` output
export SSHPASS="$NEXCESS_PASS"
PORT="${NEXCESS_PORT:-22}"
SSH_OPTS="-o StrictHostKeyChecking=no -p $PORT"
SCP_OPTS="-o StrictHostKeyChecking=no -P $PORT"
REMOTE="$NEXCESS_USER@$NEXCESS_HOST"

# Sync root and its remote counterpart. SSH lands in the user's home, so the
# relative path resolves to /home/<user>/public_html/... (proven at launch).
THEME="wp-content/themes/newblood"
REMOTE_THEME="public_html/$THEME"

# Never sync these, in any mode. tests/ is excluded so the standalone PHP-CLI
# test scripts are never exposed on the public server.
PROTECT=(
    '--exclude=.git'
    '--exclude=.DS_Store'
    '--exclude=__MACOSX'
    '--exclude=*.sql'
    '--exclude=*.zip'
    '--exclude=.nexcess-credentials'
    '--exclude=/tests/'
)

# Sync the theme. With --dry-run as $1 this only reports. --delete is correct
# here: the repo fully owns the theme directory.
sync_theme() {
    local mode_flag=$1
    rsync -avz --delete $mode_flag "${PROTECT[@]}" \
        -e "sshpass -e ssh $SSH_OPTS" \
        "$THEME/" "$REMOTE:$REMOTE_THEME/"
}

echo "=== New Blood theme deploy to Nexcess ==="
echo "Host:   $NEXCESS_HOST (port $PORT)"
echo "Target: ${NEXCESS_PRODUCTION_URL:-newblood.com}"
echo "Syncing: $THEME/  ->  $REMOTE_THEME/  (excluding tests/, cruft)"
echo ""

# Preview is ALWAYS a true dry run — nothing touches the server before the prompt.
echo "Previewing changes (dry run)..."
# Strip rsync noise: header line, summary footer, created-dir notices, and the
# bare "./" top entry (always itemized, never a real change).
PREVIEW=$(sync_theme --dry-run 2>/dev/null | sed '1d;/^$/,$d;/^created directory /d;/^\.\/$/d') || true

CHANGES=$(grep -c . <<<"$PREVIEW" 2>/dev/null || true); CHANGES=${CHANGES:-0}
DELETIONS=$(grep -c '^deleting ' <<<"$PREVIEW" 2>/dev/null || true); DELETIONS=${DELETIONS:-0}

if [ "$CHANGES" -eq 0 ]; then
    echo "Nothing to deploy — prod theme already matches local."
    exit 0
fi

echo "Files that will be pushed/updated:"
grep -v '^deleting ' <<<"$PREVIEW" | head -60 || true
if [ "$CHANGES" -gt 60 ]; then echo "... (showing first 60 of $CHANGES)"; fi

# Deletions are destructive — show every one of them, always.
if [ "$DELETIONS" -gt 0 ]; then
    echo ""
    echo "!! Files that will be DELETED on the server (prod-only, not in repo):"
    grep '^deleting ' <<<"$PREVIEW"
fi

echo ""
echo "Total: $CHANGES change(s), $DELETIONS deletion(s)."
echo ""

if [ "$DRY_RUN" = true ]; then
    echo "=== Dry run complete — no changes made ==="
    exit 0
fi

if [ "$SKIP_CONFIRM" = false ]; then
    read -p "Proceed with deployment? (y/N) " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        echo "Deployment cancelled."
        exit 1
    fi
fi

echo "Deploying theme..."
sync_theme ""

echo "Fixing permissions (755 dirs / 644 files)..."
sshpass -e ssh $SSH_OPTS "$REMOTE" \
    "find $REMOTE_THEME -type d -exec chmod 755 {} + 2>/dev/null; \
     find $REMOTE_THEME -type f -exec chmod 644 {} + 2>/dev/null; \
     echo 'Permissions updated'"

echo ""
echo "=== Deploy complete ==="
echo "Site: ${NEXCESS_PRODUCTION_URL:-https://newblood.com}"
echo ""
echo "Post-deploy (self-heals on first request; run to be sure):"
echo "  sshpass -e ssh $SSH_OPTS $REMOTE 'cd public_html && wp rewrite flush'"
echo "  curl -s -o /dev/null -w '%{http_code}\\n' https://newblood.com/discovery/overhead-door"
echo ""
