<?php
/**
 * zoid6config.php
 * Shared configuration constants for Smarty template directories and related settings
 * This file should be required by all projects that inherit templates from zoid6 and bbsengine6
 */

// Shared Smarty configuration constants
// These apply to all projects that inherit templates from zoid6 and bbsengine6

// NOTE: SMARTY* constants are now defined in each project's config-prod.php file
// after their dependencies (SKINDIR, VHOSTDIR) have been properly defined,
// avoiding circular dependencies and undefined constant errors.

// Global aliases for backward compatibility with code that doesn't use namespace
// (The actual constants are defined in config-prod.php)
if (!defined("SMARTYTEMPLATESDIR")) {
    define("SMARTYTEMPLATESDIR", \config\SMARTYTEMPLATESDIR);
}

if (!defined("SMARTYCOMPILEDTEMPLATESDIR")) {
    define("SMARTYCOMPILEDTEMPLATESDIR", \config\SMARTYCOMPILEDTEMPLATESDIR);
}

if (!defined("SMARTYPLUGINSDIR")) {
    define("SMARTYPLUGINSDIR", \config\SMARTYPLUGINSDIR);
}

?>
