<?php
/**
 * Test suite for config.php and config-prod.php
 * Validates that all required constants are properly defined and accessible
 */

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

echo "=== Config Constants Test Suite ===\n\n";

// Test 1: Load config
echo "Loading config.php...\n";
require_once('config.php');
echo "Config loaded successfully.\n\n";

// Test 2: Check config namespace constants are defined
echo "Test Group 1: Config Namespace Constants\n";
assert_test(
    "config\\SITENAME is defined",
    defined('\config\SITENAME'),
    "BBSEngine6 getsmarty() needs this"
);

assert_test(
    "config\\SITEURL is defined",
    defined('\config\SITEURL'),
    "Required for site configuration"
);

assert_test(
    "config\\DOCUMENTROOT is defined",
    defined('\config\DOCUMENTROOT'),
    "Required for file paths"
);

assert_test(
    "config\\SMARTYTEMPLATESDIR is defined",
    defined('\config\SMARTYTEMPLATESDIR'),
    "CRITICAL: Required by bbsengine6's getsmarty()"
);

assert_test(
    "config\\SMARTYCOMPILEDTEMPLATESDIR is defined",
    defined('\config\SMARTYCOMPILEDTEMPLATESDIR'),
    "CRITICAL: Required for Smarty compiled templates"
);

assert_test(
    "config\\SMARTYPLUGINSDIR is defined",
    defined('\config\SMARTYPLUGINSDIR'),
    "Required for Smarty plugin path"
);

assert_test(
    "config\\LOGENTRYPREFIX is defined",
    defined('\config\LOGENTRYPREFIX'),
    "Required for logging"
);

// Test 3: Check global namespace constants are defined (for backward compatibility)
echo "\nTest Group 2: Global Namespace Constants (Backward Compatibility)\n";
assert_test(
    "SITENAME is defined globally",
    defined('SITENAME'),
    "Code that uses SITENAME directly needs this"
);

assert_test(
    "SITEURL is defined globally",
    defined('SITEURL'),
    "index.php accesses SITEURL directly"
);

assert_test(
    "DOCUMENTROOT is defined globally",
    defined('DOCUMENTROOT'),
    "Application code needs this"
);

assert_test(
    "SKINDIR is defined globally",
    defined('SKINDIR'),
    "Application code needs this"
);

assert_test(
    "SITECSS is defined globally",
    defined('SITECSS'),
    "Application code needs this"
);

// Test 4: Check constant values
echo "\nTest Group 3: Constant Values\n";

$sitename = \config\SITENAME;
assert_test(
    "config\\SITENAME has correct value",
    is_string($sitename) && strlen($sitename) > 0,
    "Got: " . var_export($sitename, true)
);

$templates_dir = \config\SMARTYTEMPLATESDIR;
assert_test(
    "config\\SMARTYTEMPLATESDIR is an array",
    is_array($templates_dir),
    "Got type: " . gettype($templates_dir)
);

assert_test(
    "config\\SMARTYTEMPLATESDIR has entries",
    is_array($templates_dir) && count($templates_dir) > 0,
    "Count: " . count($templates_dir)
);

// Test 5: Check first template directory exists
echo "\nTest Group 4: Template Directories Exist\n";
if (is_array($templates_dir) && isset($templates_dir[0])) {
    $tmpl_path = $templates_dir[0];
    assert_test(
        "First template directory exists: $tmpl_path",
        is_dir($tmpl_path),
        realpath($tmpl_path) ?: "Path does not exist"
    );
    
    // Check for index.tmpl
    $index_tmpl = $tmpl_path . 'index.tmpl';
    assert_test(
        "index.tmpl exists at $index_tmpl",
        file_exists($index_tmpl),
        file_exists($index_tmpl) ? "File found" : "File NOT found"
    );
}

// Test 6: Check Smarty can be instantiated with these paths
echo "\nTest Group 5: Smarty Integration\n";

try {
    if (!class_exists('\Smarty')) {
        echo "Smarty class not available, skipping instantiation tests\n";
    } else {
        $smarty = new \Smarty();
        assert_test(
            "Smarty instance created",
            $smarty instanceof \Smarty,
            "Smarty instantiation failed"
        );
        
        // Try to set template dir
        try {
            $smarty->setTemplateDir(\config\SMARTYTEMPLATESDIR);
            assert_test(
                "Smarty::setTemplateDir() accepts config\\SMARTYTEMPLATESDIR",
                true,
                "Successfully set template directories"
            );
        } catch (Exception $e) {
            assert_test(
                "Smarty::setTemplateDir() accepts config\\SMARTYTEMPLATESDIR",
                false,
                $e->getMessage()
            );
        }
    }
} catch (Exception $e) {
    echo "Smarty not loaded: " . $e->getMessage() . "\n";
}

// Test 7: Verify global and config constants match
echo "\nTest Group 6: Constants Consistency\n";
assert_test(
    "Global SITENAME matches config\\SITENAME",
    SITENAME === \config\SITENAME,
    "Global: " . var_export(SITENAME, true) . " vs Config: " . var_export(\config\SITENAME, true)
);

assert_test(
    "Global SITEURL matches config\\SITEURL",
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
    exit(0);
} else {
    echo "✗ SOME TESTS FAILED\n";
    exit(1);
}
