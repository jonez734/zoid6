#!/usr/bin/env bash
# deploy_zoid6_check.sh -- dry-run the zoid6 deploy and report what it would install.
#
# Thin wrapper around zoid6/scripts/deploy_zoid6.sh --dry-run. It changes nothing:
# the real script prints each command and does not run them in dry-run mode.
#
# The real script refuses to run as 'opencode' (installs and the owner run are jam's).
# So run this as jam:
#   /home/opencode/scripts/deploy_zoid6_check.sh
#
# It asserts the resolved wheels are the current builds on their version lines and that
# the script names no hard-coded wheel, then prints the dry-run.

set -euo pipefail

REPO="${ZOID6_REPO:-/home/opencode/data/work/zoid6}"
DEPLOY="$REPO/scripts/deploy_zoid6.sh"
WHEELS="${WHEELS_DIR:-/home/opencode/data/work/dist-wheels}"

if [ ! -f "$DEPLOY" ]; then
    echo "deploy_zoid6_check: $DEPLOY not found" >&2
    exit 1
fi

# 1. The script itself must not name a wheel.
if grep -Eq '[a-z0-9_]+-[0-9]+\.[0-9]+\.[0-9]+\.dev[0-9]+' "$DEPLOY"; then
    echo "deploy_zoid6_check: $DEPLOY hard-codes a wheel filename" >&2
    exit 1
fi

# 2. The resolver must pick one build per package, and each must exist.
mapfile -t RESOLVED < <(python3 "$REPO/scripts/resolve_wheels.py" "$WHEELS" bed bbsengine6 letteredolive)
echo "resolved:"
printf '  %s\n' "${RESOLVED[@]}"
for w in "${RESOLVED[@]}"; do
    [ -f "$w" ] || { echo "deploy_zoid6_check: missing $w" >&2; exit 1; }
done

# 3. The real script, dry-run. It must be run as jam (see above).
"$DEPLOY" --dry-run
