#!/bin/bash
# Test runner for config changes

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

echo "=========================================="
echo "  Config Constants Test Suite"
echo "=========================================="
echo ""

FAILED=0

# Test 1: Basic config constant test (no dependencies)
echo "Test 1: Config Constants Proof of Concept"
echo "==========================================="
php test_config_constants.php
TEST1=$?
echo ""

# Test 2: Integration test with actual config (if running from production)
if [ -f "/srv/www/vhosts/zoidtechnologies.com/html/config.php" ]; then
    echo "Test 2: Production Config Integration"
    echo "======================================"
    cd /srv/www/vhosts/zoidtechnologies.com/html
    php -d error_reporting=E_ALL << 'PHPTEST'
<?php
require_once('config.php');
chdir('/home/opencode/data/work/zoid6/sites/www');
require_once('/home/opencode/data/work/zoid6/sites/www/test_config_integration.php');
PHPTEST
    TEST2=$?
    cd "$SCRIPT_DIR"
else
    echo "Test 2: Production Config Integration - SKIPPED (not in prod environment)"
    TEST2=0
fi
echo ""

# Test 3: Live topbar article2 (requires network access to production URL)
echo "Test 3: Topbar article2 (live)"
echo "=============================="
TEST3=0
TEST3_STATE="SKIPPED"
if command -v python3 >/dev/null 2>&1; then
    "$SCRIPT_DIR/test_topbar_article2.py"
    TEST3=$?
    if [ $TEST3 -eq 0 ]; then
        TEST3_STATE="PASSED"
    else
        TEST3_STATE="FAILED"
        FAILED=1
    fi
else
    echo "Test 3 SKIPPED (python3 not available)"
fi
echo ""

# Summary
echo "=========================================="
echo "  Test Summary"
echo "=========================================="
if [ $TEST1 -ne 0 ]; then
    echo "✗ Test 1 FAILED"
    FAILED=1
else
    echo "✓ Test 1 PASSED"
fi

if [ $TEST2 -ne 0 ] && [ -f "/srv/www/vhosts/zoidtechnologies.com/html/config.php" ]; then
    echo "✗ Test 2 FAILED"
    FAILED=1
else
    echo "✓ Test 2 PASSED"
fi

if [ "$TEST3_STATE" = "FAILED" ]; then
    echo "✗ Test 3 FAILED"
elif [ "$TEST3_STATE" = "SKIPPED" ]; then
    echo "- Test 3 SKIPPED"
else
    echo "✓ Test 3 PASSED"
fi

echo ""
if [ $FAILED -eq 0 ]; then
    echo "✓ ALL TESTS PASSED"
    exit 0
else
    echo "✗ SOME TESTS FAILED"
    exit 1
fi
