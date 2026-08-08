#!/usr/bin/env python3
"""
Integration test: verify that the "article2" choice is rendered in the
topbar of the deployed https://zoidtechnologies.com/ homepage.

Run standalone:
    ./test_topbar_article2.py

Wired into ./run_tests.sh as Test 3. Requires network access to the
production URL. Skipped (exit 0) by run_tests.sh if python3 is missing.

Exit codes:
    0 - article2 link found in the topbar block
    1 - topbar block found but article2 link is missing or malformed
    2 - network/HTTP error fetching the URL (unreachable, timeout, etc.)
"""
import re
import socket
import sys
import urllib.error
import urllib.request

URL = "https://zoidtechnologies.com/"
TIMEOUT = 10
USER_AGENT = "zoid6-test/1.0 (+topbar-article2)"

# Exact rendered link, as emitted by bbsengine6/skin/tmpl/topbar-choices.tmpl
ARTICLE2_EXACT = r'<a href="/article2/">article2</a>'
# Belt-and-suspenders: the article2 URL is present anywhere in the topbar
ARTICLE2_URL = r'href="/article2/"'


def main():
    req = urllib.request.Request(URL, headers={"User-Agent": USER_AGENT})
    try:
        with urllib.request.urlopen(req, timeout=TIMEOUT) as resp:
            status = resp.status
            ctype = resp.headers.get("Content-Type", "")
            body = resp.read().decode("utf-8", errors="replace")
    except urllib.error.HTTPError as e:
        print(f"NETWORK FAIL: HTTP {e.code} from {URL}: {e.reason}", file=sys.stderr)
        return 2
    except (urllib.error.URLError, socket.timeout, TimeoutError) as e:
        print(f"NETWORK FAIL: cannot reach {URL}: {e}", file=sys.stderr)
        return 2

    if status != 200:
        print(f"NETWORK FAIL: expected HTTP 200, got {status}", file=sys.stderr)
        return 2
    if not ctype.lower().startswith("text/html"):
        print(f"NETWORK FAIL: expected text/html Content-Type, got {ctype!r}", file=sys.stderr)
        return 2

    # The topbar block ends at the </div> immediately before the next
    # <div class="blurb ..."> in the rendered page.
    block_match = re.search(
        r'<div id="topbar"[^>]*>(.*?)</div>(?=\s*<div class="blurb)',
        body,
        re.DOTALL,
    )
    if not block_match:
        print('CONTENT FAIL: could not locate <div id="topbar"> block', file=sys.stderr)
        return 1
    block = block_match.group(0)

    choices = []
    for m in re.finditer(r'<b>([^<]+)</b>', block):
        choices.append(("CURRENT", m.group(1)))
    for m in re.finditer(r'<a href="([^"]+)">([^<]+)</a>', block):
        choices.append((m.group(1), m.group(2)))

    has_exact = re.search(ARTICLE2_EXACT, block) is not None
    has_url = re.search(ARTICLE2_URL, block) is not None

    print(f"URL: {URL}")
    print(f"Topbar choices observed: {len(choices)}")
    for href, name in choices:
        if href == "CURRENT":
            print(f"  CURRENT: {name!r}")
        else:
            print(f"  LINK:    {name!r:24s} -> {href}")
    print()

    failures = []
    if not has_exact:
        failures.append(f'missing exact substring {ARTICLE2_EXACT!r} in topbar block')
    if not has_url:
        failures.append(f'missing substring {ARTICLE2_URL!r} in topbar block')

    if failures:
        print("CONTENT FAIL:", file=sys.stderr)
        for f in failures:
            print(f"  - {f}", file=sys.stderr)
        return 1

    print("PASS: article2 is rendered in the topbar")
    return 0


if __name__ == "__main__":
    try:
        sys.exit(main())
    except Exception as e:  # noqa: BLE001
        print(f"UNEXPECTED ERROR: {e}", file=sys.stderr)
        sys.exit(2)
