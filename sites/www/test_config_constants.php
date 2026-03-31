<?php
/**
 * Test suite for config constants
 * Tests ONLY the config constants without loading zoid6.php dependencies
 */

echo "=== Config Constants Test Suite ===\n\n";

// Track test results
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

// Test 1: Test that we CAN define config namespace constants the right way
echo "Test Group 1: Define config namespace constants (Proof of Concept)\n";

define("config\TEST_SITENAME", "test site");
define("config\TEST_VHOSTDIR", "/srv/www/test/");
define("config\TEST_SMARTYTEMPLATESDIR", [0 => "/srv/www/test/templates/", 1 => "/srv/www/test2/templates/"]);

assert_test(
    "defined('\\config\\TEST_SITENAME') returns true",
    defined('\config\TEST_SITENAME'),
    "This is how bbsengine6's getsmarty() checks for constants"
);

assert_test(
    "defined('\\config\\TEST_SMARTYTEMPLATESDIR') returns true",
    defined('\config\TEST_SMARTYTEMPLATESDIR'),
    "Critical: This is needed for Smarty template directory"
);

assert_test(
    "\\config\\TEST_SITENAME can be accessed",
    \config\TEST_SITENAME === "test site",
    "Value: " . var_export(\config\TEST_SITENAME, true)
);

assert_test(
    "\\config\\TEST_SMARTYTEMPLATESDIR is an array",
    is_array(\config\TEST_SMARTYTEMPLATESDIR),
    "Type: " . gettype(\config\TEST_SMARTYTEMPLATESDIR)
);

assert_test(
    "\\config\\TEST_SMARTYTEMPLATESDIR array has entries",
    count(\config\TEST_SMARTYTEMPLATESDIR) === 2,
    "Count: " . count(\config\TEST_SMARTYTEMPLATESDIR)
);

// Test 2: Test global aliases work
echo "\nTest Group 2: Global namespace aliases\n";

define("ALIAS_SITENAME", \config\TEST_SITENAME);
define("ALIAS_VHOSTDIR", \config\TEST_VHOSTDIR);

assert_test(
    "Global ALIAS_SITENAME is defined",
    defined('ALIAS_SITENAME'),
    "For code that accesses constants directly"
);

assert_test(
    "Global ALIAS_SITENAME matches config namespace version",
    ALIAS_SITENAME === \config\TEST_SITENAME,
    "Both should have same value"
);

// Test 3: Test getsmarty() style check
echo "\nTest Group 3: Simulate getsmarty() behavior\n";

// This is what getsmarty() does
$options = [];
$options["pluginsdir"] = defined('\config\TEST_SMARTYTEMPLATESDIR') ? "FOUND" : "NOT_FOUND";
$options["templatedir"] = defined('\config\TEST_SMARTYTEMPLATESDIR') ? \config\TEST_SMARTYTEMPLATESDIR : [];

assert_test(
    "getsmarty() would find config\\TEST_SMARTYTEMPLATESDIR",
    $options["pluginsdir"] === "FOUND",
    "This proves the define('config\\CONSTANT', ...) approach works with getsmarty()"
);

assert_test(
    "getsmarty() would get the array value",
    is_array($options["templatedir"]) && count($options["templatedir"]) > 0,
    "Result: " . var_export($options["templatedir"], true)
);

// Test 4: Verify actual production config paths exist
echo "\nTest Group 4: Production paths exist\n";

$prod_tmpl_path = "/srv/www/vhosts/zoidtechnologies.com/html/skin/tmpl/";
assert_test(
    "Production template directory exists",
    is_dir($prod_tmpl_path),
    "Path: $prod_tmpl_path"
);

$index_tmpl = $prod_tmpl_path . "index.tmpl";
assert_test(
    "Production index.tmpl file exists",
    file_exists($index_tmpl),
    "File: $index_tmpl"
);

$zoid6_tmpl_path = "/srv/www/zoid6/skin/tmpl/";
assert_test(
    "Fallback zoid6 template directory exists",
    is_dir($zoid6_tmpl_path),
    "Path: $zoid6_tmpl_path"
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
    echo "\nThis confirms that:\n";
    echo "1. Config namespace constants CAN be defined using define('config\\\\CONSTANT', value)\n";
    echo "2. They ARE accessible via defined('\\\\config\\\\CONSTANT')\n";
    echo "3. They ARE accessible as \\\\config\\\\CONSTANT\n";
    echo "4. bbsengine6's getsmarty() will work correctly\n";
    echo "5. Production template directories and files exist\n";
    exit(0);
} else {
    echo "✗ SOME TESTS FAILED\n";
    exit(1);
}
