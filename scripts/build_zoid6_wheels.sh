#!/bin/sh
# HELPER -- run by `deploy zoid6.wheels` (deploytool), not by hand.
#
# build_zoid6_wheels.sh: build the five zoid6 install wheels from git HEAD, then rewrite the manifest.
#
# Each package is exported from its HEAD into a scratch directory (so uncommitted work and generated
# _version.py files never leak into a build), stamped with scripts/next_version.sh, and built as a
# wheel into the staging directory. zoid6-wheels.txt is then rewritten to name exactly those wheels,
# and wheels in the staging directory that the manifest no longer lists are removed.
#
# Usage: build_zoid6_wheels.sh [STAGE_DIR]     (default: the meta-repo's dist-wheels)
# Needs: git, python3 with the 'build' module, and the meta-repo scripts/next_version.sh.
set -eu

ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
STAGE="${1:-$ROOT/dist-wheels}"
VERSION_SCRIPT="$ROOT/scripts/next_version.sh"
WORK="$(mktemp -d "${TMPDIR:-$HOME}/zoid6-build.XXXXXX")"
trap 'rm -rf "$WORK"' EXIT

# name | source dir in the repo | version prefix | version file (relative to the source dir)
# The prefixes are each package's version line; bump them here when a package starts a new line.
BUILDS="
bed|bed|0.4.0.dev|src/bed/_version.py
bbsengine6|bbsengine6/py|0.4.0.dev|src/bbsengine6/_version.py
casino|casino|0.4.0.dev|src/casino/_version.py
zoid6|zoid6/src|0.4.0.dev|zoid6/_version.py
letteredolive|letteredolive/src|0.0.2.dev|letteredolive/_version.py
"

[ -x "$VERSION_SCRIPT" ] || { echo "build: $VERSION_SCRIPT not found" >&2; exit 1; }
mkdir -p "$STAGE"

echo "$BUILDS" | while IFS='|' read -r name src prefix vfile; do
    [ -n "$name" ] || continue
    repo="$(echo "$src" | cut -d/ -f1)"
    sub="${src#"$repo"}"; sub="${sub#/}"
    out="$WORK/$name"
    mkdir -p "$out/export" "$out/wheel"
    # Export the whole repo: some builds read files beside the package (letteredolive's setup.py reads
    # ../bin), so a subdirectory export is not enough. The package is built from its source dir.
    git -C "$ROOT/$repo" archive HEAD | tar -x -C "$out/export"
    pkg="$out/export/$sub"
    version="$("$VERSION_SCRIPT" "$prefix" "$STAGE")"
    printf '__version__ = "%s"\n__datestamp__ = "%s"\n' "$version" "$version" > "$pkg/$vfile"
    ( cd "$pkg" && python3 -m build --wheel --outdir "$out/wheel" >"$out/build.log" 2>&1 ) \
        || { echo "build: $name failed; see $out/build.log" >&2; cat "$out/build.log" >&2; exit 1; }
    whl="$(ls "$out/wheel"/*.whl)"
    cp "$whl" "$STAGE/"
    echo "built $(basename "$whl")"
done

# Rewrite the manifest to exactly the wheels just built, in package order.
MANIFEST="$STAGE/zoid6-wheels.txt"
{
    echo "# The wheels the zoid6 venv installs: one per package. Rewritten by build_zoid6_wheels.sh."
    echo "$BUILDS" | while IFS='|' read -r name src prefix vfile; do
        [ -n "$name" ] || continue
        ls -t "$STAGE/$name"-*.whl | head -1 | xargs -n1 basename
    done
} > "$MANIFEST.new"
# The manifest may already exist and be read-only to this user (it is group-shared). Replace it
# explicitly with -f, and keep it group-writable so the next run can replace it too.
[ -e "$MANIFEST" ] && chmod g+w "$MANIFEST" 2>/dev/null || true
mv -f "$MANIFEST.new" "$MANIFEST"
chmod 0664 "$MANIFEST" 2>/dev/null || true
echo "manifest: $MANIFEST"
cat "$MANIFEST"

# Prune: a wheel in the staging directory that the manifest does not list is never installed, so it
# is removed. This keeps the staging directory to the current build.
listed="$(grep -v '^#' "$MANIFEST")"
for w in "$STAGE"/*.whl; do
    [ -e "$w" ] || continue
    name="$(basename "$w")"
    case "
$listed
" in *"
$name
"*) ;; *) rm -f "$w"; echo "pruned $name" ;; esac
done
