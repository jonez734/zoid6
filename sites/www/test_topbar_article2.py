#!/usr/bin/env python3
"""
Verify that the "article2" choice is registered in the topbar
navigation source.

Reads php/zoid6.php and asserts that buildchoices() includes an
entry for "article2" pointing at "/article2/". This is the source
of truth for what gets rendered into the topbar at request time.

Run standalone:
    ./test_topbar_article2.py

Wired into ./run_tests.sh as Test 3. Skipped (exit 0) by
run_tests.sh if python3 is missing.

Exit codes:
    0 - article2 entry found in source
    1 - article2 entry missing or malformed in source
    2 - source file unreadable
"""
import re
import sys
from pathlib import Path

# php/zoid6.php relative to this script:
# sites/www/test_topbar_article2.py -> ../../php/zoid6.php
SOURCE_FILE = (
    Path(__file__).resolve().parent.parent.parent / "php" / "zoid6.php"
)

# Match the buildchoices() entry: name => "article2", ... url => "/article2/"
ARTICLE2_PATTERN = re.compile(
    r'\[\s*"name"\s*=>\s*"article2"\s*,\s*[^]]*"url"\s*=>\s*"/article2/"',
    re.MULTILINE,
)


def main():
    if not SOURCE_FILE.is_file():
        print(f"FAIL: source file not found: {SOURCE_FILE}", file=sys.stderr)
        return 2

    try:
        content = SOURCE_FILE.read_text(encoding="utf-8")
    except OSError as e:
        print(f"FAIL: cannot read {SOURCE_FILE}: {e}", file=sys.stderr)
        return 2

    match = ARTICLE2_PATTERN.search(content)
    if match is None:
        print(f"FAIL: article2 topbar entry not found in {SOURCE_FILE}", file=sys.stderr)
        print(f"  expected pattern: {ARTICLE2_PATTERN.pattern}", file=sys.stderr)
        return 1

    print(f"PASS: article2 topbar entry found in {SOURCE_FILE}")
    print(f"  matched: {match.group(0).strip()}")
    return 0


if __name__ == "__main__":
    sys.exit(main())
