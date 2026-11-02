#!/bin/sh
# Run as the zoid6 account, from /home/zoid6/dist-wheels:  sh INSTALL-zoid6-venv.sh
# No sudo: the account owns /home/zoid6. Creates /home/zoid6/venv if missing, then installs the wheels
# that zoid6-wheels.txt names in one pip call (pip orders the dependencies itself), and prints each
# package's installed version.
set -eu
DIST="${DIST:-$(cd "$(dirname "$0")" && pwd)}"
VENV="${VENV:-/home/zoid6/venv}"
PY="${PY:-/usr/bin/python3.12}"

# Install exactly the wheels zoid6-wheels.txt names (written by build_zoid6_wheels.sh). Installing every
# *.whl in the directory fails: the staging directory keeps older builds of the same package, and pip
# cannot resolve two versions of one package at once.
MANIFEST="$DIST/zoid6-wheels.txt"
if [ -f "$MANIFEST" ]; then
    set --
    for name in $(grep -v '^#' "$MANIFEST" | tr -d '\r'); do
        [ -n "$name" ] || continue
        [ -e "$DIST/$name" ] || { echo "install: manifest names $name but it is not in $DIST" >&2; exit 1; }
        set -- "$@" "$DIST/$name"
    done
else
    set -- "$DIST"/*.whl
fi
[ -e "$1" ] || { echo "install: no wheels in $DIST" >&2; exit 1; }

[ -x "$VENV/bin/python" ] || "$PY" -m venv "$VENV"

# pip leaves a '~<name>' directory in site-packages when an uninstall is interrupted (for example,
# a previous casino upgrade). pip then warns "Ignoring invalid distribution ~..." on every run. These
# are pip's own temporary leftovers, never a package, so remove them before installing.
for sp in "$VENV"/lib*/python*/site-packages; do
    [ -d "$sp" ] || continue
    for stale in "$sp"/~*; do
        [ -e "$stale" ] || continue
        echo "removing pip leftover: $stale"
        rm -rf -- "$stale"
    done
done

"$VENV/bin/python" -m pip install --upgrade pip
"$VENV/bin/python" -m pip install --upgrade "$@"

"$VENV/bin/python" -c 'import importlib.metadata as m
for p in ("bbsengine6", "bed", "zoid6", "casino", "letteredolive"):
    print(p, m.version(p))'
