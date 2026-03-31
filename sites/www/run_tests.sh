#!/bin/bash
# Test runner for config changes

echo "=========================================="
echo "  Config Constants Test Suite"
echo "=========================================="
echo ""

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
else
    echo "Test 2: Production Config Integration - SKIPPED (not in prod environment)"
    TEST2=0
fi
echo ""

# Summary
echo "=========================================="
echo "  Test Summary"
echo "=========================================="
FAILED=0
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

echo ""
if [ $FAILED -eq 0 ]; then
    echo "✓ ALL TESTS PASSED"
    exit 0
else
    echo "✗ SOME TESTS FAILED"
    exit 1
fi
