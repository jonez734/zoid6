<?php
/**
 * zoid6config.php
 * Creates global aliases for Smarty template directory constants
 *
 * This file creates global (non-namespaced) aliases for the SMARTY* constants
 * that are defined in each project's config-prod.php file.
 *
 * IMPORTANT: This file must be included AFTER the project defines:
 *   - config\SMARTYTEMPLATESDIR
 *   - config\SMARTYCOMPILEDTEMPLATESDIR
 *   - config\SMARTYPLUGINSDIR
 *
 * These constants should be defined in the project's config-prod.php before
 * including this file.
 */

// Global aliases for backward compatibility with code that doesn't use namespace
define("SMARTYTEMPLATESDIR", \config\SMARTYTEMPLATESDIR);
define("SMARTYCOMPILEDTEMPLATESDIR", \config\SMARTYCOMPILEDTEMPLATESDIR);
define("SMARTYPLUGINSDIR", \config\SMARTYPLUGINSDIR);

// Shared URL constants - used by all zoid6 sites
define("ENGINEURL", "/engine/");
define("ENGINESKINURL", "/engine/skin/");
define("SHAREDSKINURL", "/shared/skin/");

?>
