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
// Only define if the config constants exist (some callers may not have them)
if (defined('\config\SMARTYTEMPLATESDIR')) {
    define("SMARTYTEMPLATESDIR", \config\SMARTYTEMPLATESDIR);
}
if (defined('\config\SMARTYCOMPILEDTEMPLATESDIR')) {
    define("SMARTYCOMPILEDTEMPLATESDIR", \config\SMARTYCOMPILEDTEMPLATESDIR);
}
if (defined('\config\SMARTYPLUGINSDIR')) {
    define("SMARTYPLUGINSDIR", \config\SMARTYPLUGINSDIR);
}

// Shared URL constants - used by all zoid6 sites
if (!defined("ENGINEURL")) define("ENGINEURL", "/engine/");
if (!defined("ENGINESKINURL")) define("ENGINESKINURL", "/engine/skin/");
if (!defined("SHAREDSKINURL")) define("SHAREDSKINURL", "/shared/skin/");
define("config\TEOSURL", "/teos/");
define("TEOSURL", \config\TEOSURL);

// TeOS filesystem path - single source of truth
define("config\TEOSDIR", "/srv/www/vhosts/zoidtechnologies.com/html/teos/");
define("TEOSDIR", \config\TEOSDIR);

// CAPTCHA configuration - can be overridden in site config-prod.php
define("config\CAPTCHA_PROVIDER", getenv('CAPTCHA_PROVIDER') ?: 'none');
define("config\TURNSTILE_SITE_KEY", getenv('TURNSTILE_SITE_KEY') ?: '');
define("config\TURNSTILE_SECRET_KEY", getenv('TURNSTILE_SECRET_KEY') ?: '');
define("config\HCAPTCHA_SITE_KEY", getenv('HCAPTCHA_SITE_KEY') ?: '');
define("config\HCAPTCHA_SECRET_KEY", getenv('HCAPTCHA_SECRET_KEY') ?: '');
define("config\RECAPTCHA_SITE_KEY", getenv('RECAPTCHA_SITE_KEY') ?: '');
define("config\RECAPTCHA_SECRET_KEY", getenv('RECAPTCHA_SECRET_KEY') ?: '');

// Database configuration
define("config\SYSTEMDSN", "pgsql:host=127.0.0.1;port=5432;dbname=zoid6");
define("SYSTEMDSN", \config\SYSTEMDSN);

// Global aliases for CAPTCHA config
define("CAPTCHA_PROVIDER", \config\CAPTCHA_PROVIDER);
define("TURNSTILE_SITE_KEY", \config\TURNSTILE_SITE_KEY);
define("TURNSTILE_SECRET_KEY", \config\TURNSTILE_SECRET_KEY);
define("HCAPTCHA_SITE_KEY", \config\HCAPTCHA_SITE_KEY);
define("HCAPTCHA_SECRET_KEY", \config\HCAPTCHA_SECRET_KEY);
define("RECAPTCHA_SITE_KEY", \config\RECAPTCHA_SITE_KEY);
define("RECAPTCHA_SECRET_KEY", \config\RECAPTCHA_SECRET_KEY);

?>
