#!/usr/bin/env bash
# test_php_lint_deploy_targets.sh
#
# Lints all PHP files that get rsync'd to the live vhost via
# the zoid6/sites/www/Makefile, bbsengine6/*/Makefile, and
# any other deploy Makefiles. A parse error in any of these
# files would 500 on the live vhost.
#
# Background: on 2026-09-10 a missing semicolon in
# zoid6/sites/www/php/blurb.php:80 was causing
# /engine/router.php to 500 (PHP 8.x parse error). The
# bug shipped in the working tree and in the live vhost.
# This test guards against re-introducing parse errors in
# the deploy set.
#
# What this test checks:
#   [1] zoid6/sites/www/php/*.php all lint clean
#   [2] bbsengine6/engine/*.php all lint clean
#   [3] bbsengine6/php/*.php all lint clean
#   [4] bbsengine6/www/*/php/*.php all lint clean (legacy)

set -u

WORKTREE="/home/opencode/data/work"
PHP_BIN="${PHP_BIN:-php}"
pass=0
fail=0
diagnosis=""

ok()  { echo "  ok   $*"; pass=$((pass+1)); }
bad() { echo "  FAIL $*"; fail=$((fail+1)); diagnosis="$diagnosis\n  - $*"; }

# --- preconditions ---
echo "[0] preconditions"
if ! command -v "$PHP_BIN" >/dev/null 2>&1; then
  bad "php CLI not available on PATH"
  echo "    cannot continue"
  exit 1
fi
ok "php CLI available ($($PHP_BIN -v | head -1))"
echo

# --- helper ---
lint_dir() {
  local label="$1" dir="$2"
  local file result count=0 errors=0
  if [ ! -d "$dir" ]; then
    bad "$label: directory not found: $dir"
    return
  fi
  for file in "$dir"/*.php; do
    [ -e "$file" ] || continue
    count=$((count+1))
    result=$("$PHP_BIN" -l "$file" 2>&1)
    if echo "$result" | grep -q "Errors parsing"; then
      errors=$((errors+1))
      bad "$label: parse error in $file"
      echo "$result" | sed 's/^/      /'
    fi
  done
  if [ "$errors" = "0" ] && [ "$count" -gt 0 ]; then
    ok "$label: $count files lint clean"
  elif [ "$count" = "0" ]; then
    bad "$label: no .php files found in $dir"
  fi
}

# --- 1. zoid6/sites/www/php/ ---
echo "[1] zoid6/sites/www/php/"
lint_dir "zoid6/sites/www/php" "$WORKTREE/zoid6/sites/www/php"
echo

# --- 2. bbsengine6/engine/ ---
echo "[2] bbsengine6/engine/"
lint_dir "bbsengine6/engine" "$WORKTREE/bbsengine6/engine"
echo

# --- 3. bbsengine6/php/ ---
echo "[3] bbsengine6/php/"
lint_dir "bbsengine6/php" "$WORKTREE/bbsengine6/php"
echo

# --- 4. bbsengine6/www/*/php/ (legacy org bbsengine4.php, etc.) ---
echo "[4] bbsengine6/www/*/php/"
for d in "$WORKTREE"/bbsengine6/www/*/php; do
  [ -d "$d" ] || continue
  lint_dir "bbsengine6/www/$(basename $(dirname "$d"))/php" "$d"
done
echo

# --- summary ---
echo "=== summary ==="
echo "passed: $pass"
echo "failed: $fail"
if [ "$fail" -gt 0 ]; then
  echo
  echo "diagnosis:"
  printf "%b\n" "$diagnosis"
  echo
  echo "to re-run: $0"
  exit 1
fi
echo
echo "all deploy-targeted PHP files lint clean."
exit 0
