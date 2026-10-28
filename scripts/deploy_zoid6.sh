#!/usr/bin/env bash
# deploy_zoid6.sh -- deploy bed, bbsengine6 and letteredolive for zoid6.
#
# 1. Service venv (zoid6 account): needs the sync first, then deploytool's zoid6.venv.
# 2. Shell/owner venv (jam): bed, bbsengine6, letteredolive.
# 3. Owner install against the zoid6 database (bed --install).
# 4. Prints the restart and verification steps for you to run.
#
# The wheels are resolved from the staged directory by resolve_wheels.py (the newest build
# on each version line). No wheel filename is written here.
#
# Run as the 'jam' user (the owner role). Do NOT run this with sudo: the sync and the
# restart are printed for you to run, and the script never sudos itself.
#
# Usage:
#   sudo sh <wheels>/sync-zoid6-wheels.sh      # step 1a, as root
#   SYNCED=1 scripts/deploy_zoid6.sh           # steps 1b-4, as jam
#   scripts/deploy_zoid6.sh --dry-run          # print the commands, change nothing
#   scripts/deploy_zoid6.sh --wheels DIR       # resolve wheels from DIR instead
#
# Exits on the first failed step. Never creates a database: the owner install
# targets 'zoid6', which must already exist.

set -euo pipefail

# Directory holding the staged wheels. Each package is resolved from it by
# scripts/resolve_wheels.py: the newest build on the package's version line. No wheel
# filename is written in this script.
WHEELS_DIR="/home/opencode/data/work/dist-wheels"
HERE="$(cd "$(dirname "$0")" && pwd)"
RESOLVE="$HERE/resolve_wheels.py"
# ZOID6_HOME: the zoid6 account's home (zoid6's Makefile: VENV_DIR ?= /home/zoid6/venv).
# The service venv is $ZOID6_HOME/venv. Override either with the environment.
ZOID6_HOME="${ZOID6_HOME:-/home/zoid6}"
SHELL_PY="${SHELL_PY:-/home/jam/.venv/bin/python}"
SERVICE_BED="$ZOID6_HOME/venv/bin/bed"
DB="zoid6"
ROUTER="zoid6.api.handler.MessageRouter"


DRY_RUN=0

usage() {
    sed -n '2,15p' "$0" | sed 's/^# \{0,1\}//'
}

while [ $# -gt 0 ]; do
    case "$1" in
        --wheels)   WHEELS_DIR="${2:?--wheels needs a directory}"; shift 2 ;;
        --dry-run)  DRY_RUN=1; shift ;;
        -h|--help)  usage; exit 0 ;;
        *)          echo "unknown argument: $1" >&2; usage >&2; exit 2 ;;
    esac
done

if [ "$(id -un)" = "opencode" ]; then
    echo "refusing to run as 'opencode': the installs and owner run must be done as 'jam'." >&2
    exit 1
fi

# Resolve every wheel before anything is installed. resolve_wheels.py exits non-zero, with
# a message, when a package has no build on its version line.
if ! mapfile -t WHEELS < <(python3 "$RESOLVE" "$WHEELS_DIR" bed bbsengine6 letteredolive); then
    echo "could not resolve the wheels in $WHEELS_DIR" >&2
    exit 1
fi
[ "${#WHEELS[@]}" -eq 3 ] || { echo "expected 3 wheels, resolved ${#WHEELS[@]}" >&2; exit 1; }
BED_PATH="${WHEELS[0]}"
BBS_PATH="${WHEELS[1]}"
LO_PATH="${WHEELS[2]}"
# A NEEDINFO placeholder means a path was never set. Name the variable and stop, rather than
# run with a path that does not exist.
for setting in ZOID6_HOME SHELL_PY; do
    value="${!setting}"
    case "$value" in
        *NEEDINFO*)
            echo "deploy_zoid6: $setting is not set (value: $value). Set it in the environment and re-run." >&2
            exit 1 ;;
    esac
done

# Each interpreter must exist and be executable. Say which variable controls it.
check_python() {
    local py="$1" var="$2"
    if [ ! -e "$py" ]; then
        echo "deploy_zoid6: no interpreter at $py ($var). Create the venv or set $var." >&2
        exit 1
    fi
    if [ ! -x "$py" ]; then
        echo "deploy_zoid6: $py ($var) is not executable by $(id -un)." >&2
        exit 1
    fi
}
check_python "$SHELL_PY" "SHELL_PY (shell/owner venv)"

run() {
    echo "+ $*"
    if [ "$DRY_RUN" -eq 0 ]; then
        "$@"
    fi
}

# 1. Service venv. Owned by the zoid6 account, so it is installed as that account by
#    deploytool's zoid6.venv (make install-wheels as zoid6). Jam cannot write it, and the
#    account cannot read the staged wheels, so the wheels go through the sync first:
#    sync-zoid6-wheels.sh copies exactly the manifest's wheels into /home/zoid6/dist-wheels.
#    The sync needs root, so it is printed for the operator to run; this script does not sudo.
echo "== 1/4 service venv (zoid6 account): sync the manifest wheels, then install"
echo "  step 1a needs root; run it first, then re-run this script with SYNCED=1:"
echo "    sudo sh $WHEELS_DIR/sync-zoid6-wheels.sh"
if [ "$DRY_RUN" -eq 0 ] && [ "${SYNCED:-0}" -ne 1 ]; then
    echo "  (SYNCED is not 1: stopping before the account install.)" >&2
    exit 1
fi
if command -v deploy >/dev/null 2>&1; then
    run deploy zoid6.venv
else
    run env PYTHONPATH="$HERE/../../deploytool/src" python3 -m deploytool zoid6.venv
fi

echo "== 2/4 shell/owner venv: bed + bbsengine6 + letteredolive"
run "$SHELL_PY" -m pip install --force-reinstall --no-deps "$BED_PATH" "$BBS_PATH" "$LO_PATH"

echo "== 3/4 owner install against '$DB' (no database is created by this step)"
run "$SERVICE_BED" --install --databasename "$DB" --router "$ROUTER"

echo "== 4/4 restart and verify (run these yourself; sudo is not available to this script)"
cat <<EOF
  sudo systemctl restart zoid6
  shell
  psql -d $DB -c "\\df engine.bed_count_unread"
  psql -l
EOF

if [ "$DRY_RUN" -eq 1 ]; then
    echo "(dry run: no commands were executed)"
fi
