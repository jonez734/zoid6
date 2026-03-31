<?php
/**
 * Integration test for config.php
 * Tests that config constants work with the actual config file
 * 
 * Usage: cd /srv/www/vhosts/zoidtechnologies.com/html && php config.php test_config_integration.php
 * Or:    cd /home/opencode/data/work/zoid6/sites/www && php config-prod.php test_config_integration.php
 */

echo "=== Config Integration Test ===\n\n";

$tests = [];
$passed = 0;
$failed = 0;

function assert_test($name, $condition, $details = "") {
    global $passed, $failed, $tests;
    if ($condition) {
        $passed++;
        $tests[] = "✓ PASS: $name";
    } else {
        $failed++;
        $tests[] = "✗ FAIL: $name" . ($details ? " - $details" : "");
    }
}

echo "Test Group 1: Required config namespace constants\n";

assert_test(
    "config\\SITENAME is defined",
    defined('\config\SITENAME'),
    "BBSEngine6 compatibility check"
);

assert_test(
    "config\\SITEURL is defined",
    defined('\config\SITEURL')
);

assert_test(
    "config\\DOCUMENTROOT is defined",
    defined('\config\DOCUMENTROOT')
);

assert_test(
    "config\\SMARTYTEMPLATESDIR is defined",
    defined('\config\SMARTYTEMPLATESDIR'),
    "CRITICAL: Required by bbsengine6"
);

assert_test(
    "config\\SMARTYCOMPILEDTEMPLATESDIR is defined",
    defined('\config\SMARTYCOMPILEDTEMPLATESDIR')
);

assert_test(
    "config\\SMARTYPLUGINSDIR is defined",
    defined('\config\SMARTYPLUGINSDIR')
);

assert_test(
    "config\\LOGENTRYPREFIX is defined",
    defined('\config\LOGENTRYPREFIX')
);

echo "\nTest Group 2: Global namespace constants (backward compatibility)\n";

assert_test(
    "SITENAME is defined globally",
    defined('SITENAME'),
    "For direct access"
);

assert_test(
    "SITEURL is defined globally",
    defined('SITEURL'),
    "Used by index.php"
);

assert_test(
    "DOCUMENTROOT is defined globally",
    defined('DOCUMENTROOT')
);

assert_test(
    "SKINDIR is defined globally",
    defined('SKINDIR')
);

assert_test(
    "SKINURL is defined globally",
    defined('SKINURL')
);

assert_test(
    "SITECSS is defined globally",
    defined('SITECSS')
);

echo "\nTest Group 3: Constant values are correct\n";

if (defined('\config\SMARTYTEMPLATESDIR')) {
    $tmpl_dir = \config\SMARTYTEMPLATESDIR;
    assert_test(
        "SMARTYTEMPLATESDIR is an array",
        is_array($tmpl_dir),
        "Type: " . gettype($tmpl_dir)
    );
    
    assert_test(
        "SMARTYTEMPLATESDIR has at least one entry",
        count($tmpl_dir) > 0,
        "Count: " . count($tmpl_dir)
    );
    
    if (count($tmpl_dir) > 0) {
        assert_test(
            "First SMARTYTEMPLATESDIR path exists",
            is_dir($tmpl_dir[0]),
            "Path: " . $tmpl_dir[0]
        );
    }
}

echo "\nTest Group 4: Verify getsmarty() compatibility\n";

// Simulate what getsmarty() does
$options = [];
$options["pluginsdir"] = defined('\config\SMARTYPLUGINSDIR') ? \config\SMARTYPLUGINSDIR : [];
$options["templatedir"] = defined('\config\SMARTYTEMPLATESDIR') ? \config\SMARTYTEMPLATESDIR : [];
$options["compiledir"] = defined('\config\SMARTYCOMPILEDTEMPLATESDIR') ? \config\SMARTYCOMPILEDTEMPLATESDIR : null;
$options["compileid"] = defined('\config\LOGENTRYPREFIX') ? \config\LOGENTRYPREFIX : 'bbsengine6';

assert_test(
    "getsmarty() would find all required constants",
    isset($options["pluginsdir"]) && isset($options["templatedir"]) && isset($options["compiledir"]) && isset($options["compileid"]),
    "All options present"
);

assert_test(
    "getsmarty() templatedir would be array",
    is_array($options["templatedir"]),
    "Type: " . gettype($options["templatedir"])
);

echo "\nTest Group 5: Constants consistency\n";

assert_test(
    "Global SITENAME === config\\SITENAME",
    SITENAME === \config\SITENAME,
    "Global: " . var_export(SITENAME, true) . " vs Config: " . var_export(\config\SITENAME, true)
);

assert_test(
    "Global SITEURL === config\\SITEURL",
    SITEURL === \config\SITEURL,
    "Global: " . var_export(SITEURL, true) . " vs Config: " . var_export(\config\SITEURL, true)
);

// Print results
echo "\n=== Test Results ===\n";
echo "Passed: $passed\n";
echo "Failed: $failed\n";
echo "Total: " . ($passed + $failed) . "\n\n";

foreach ($tests as $test) {
    echo $test . "\n";
}

echo "\n";
if ($failed === 0) {
    echo "✓ ALL TESTS PASSED!\n";
    echo "Config is properly set up for bbsengine6 and application compatibility.\n";
    exit(0);
} else {
    echo "✗ SOME TESTS FAILED\n";
    exit(1);
}
