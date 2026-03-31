<?php
/**
 * zoid6config.php
 * Shared configuration constants for Smarty template directories and related settings
 * This file should be required by all projects that inherit templates from zoid6 and bbsengine6
 */

// Shared Smarty configuration constants
// These apply to all projects that inherit templates from zoid6 and bbsengine6

// NOTE: SMARTYTEMPLATESDIR is now defined in each project's config-prod.php file
// after SKINDIR has been properly defined, avoiding circular dependencies

// SMARTYCOMPILEDTEMPLATESDIR - compiled template cache directory
if (!defined("config\SMARTYCOMPILEDTEMPLATESDIR")) {
    define("config\SMARTYCOMPILEDTEMPLATESDIR", \config\VHOSTDIR . "templates_c");
}

// SMARTYPLUGINSDIR - plugin directories for custom Smarty functions and modifiers
if (!defined("config\SMARTYPLUGINSDIR")) {
    define("config\SMARTYPLUGINSDIR", [
        0 => \config\VHOSTDIR . "smarty/",
        1 => "/srv/www/zoid6/smarty/"
    ]);
}

// Global aliases for backward compatibility with code that doesn't use namespace
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
