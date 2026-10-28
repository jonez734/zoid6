#!/usr/bin/env bash
# deploy_zoid6.sh -- reinstall the bed, bbsengine6 and letteredolive wheels into the
# zoid6 service venv and the shell/owner venv, run the owner install against the
# zoid6 database, then print the restart and verification steps.
#
# Run as the 'jam' user (the owner role). Do NOT run this with sudo: the systemctl
# restart is printed for you to run, because the install venvs are jam's.
#
# Usage:
#   scripts/deploy_zoid6.sh                 # uses the default wheel names below
#   scripts/deploy_zoid6.sh --wheels DIR    # wheels live in DIR instead
#   scripts/deploy_zoid6.sh --dry-run       # print the commands, change nothing
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
# BED_HOME: the bed account's home. Same variable as the bed Makefile; override with the env.
BED_HOME="${BED_HOME:-/home/NEEDINFO}"
SERVICE_PY="$BED_HOME/venv/bin/python"
SHELL_PY="/home/jam/.venv/bin/python"
SERVICE_BED="$BED_HOME/venv/bin/bed"
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
for py in "$SERVICE_PY" "$SHELL_PY"; do
    if [ ! -x "$py" ]; then
        echo "missing interpreter: $py" >&2
        exit 1
    fi
done

run() {
    echo "+ $*"
    if [ "$DRY_RUN" -eq 0 ]; then
        "$@"
    fi
}

echo "== 1/4 service venv: bed + bbsengine6"
run "$SERVICE_PY" -m pip install --force-reinstall --no-deps "$BED_PATH" "$BBS_PATH"

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
