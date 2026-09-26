<?php
/**
 * zoid6config.php
 *
 * Two responsibilities:
 *
 *   1. Smarty-related global aliases (SMARTYTEMPLATESDIR,
 *      SMARTYPLUGINSDIR, SMARTYCOMPILEDTEMPLATESDIR) created
 *      in the global namespace for backward compatibility
 *      with code that doesn't use the \config\* form.
 *
 *   2. Shared URL/path constants (ENGINEURL, ENGINESKINURL,
 *      SHAREDSKINURL, TEOSURL, TEOSDIR, CAPTCHA_*, SYSTEMDSN)
 *      used by every zoid6 site.
 *
 * IMPORTANT: this file must be included AFTER the project
 * defines \config\SMARTYTEMPLATESDIR, \config\SMARTYCOMPILEDTEMPLATESDIR,
 * and \config\SMARTYPLUGINSDIR in its config-prod.php.
 *
 * The companion \zoid6\template namespace (below) provides
 * zoid6's canonical template-dir and plugin-dir contributions
 * to the \bbsengine6\template registry. bbsengine6's
 * bbsengine6config.php auto-registers \zoid6\template\extra
 * and \zoid6\plugin_extra() at PRIORITY_APP_INTEGRATION when
 * this file loads.
 */

namespace {

// Global aliases for backward compatibility with code that doesn't use namespace
// Only define if the config constants exist (some callers may not have them)
if (defined('\config\SMARTYTEMPLATESDIR')) {
    define("SMARTYTEMPLATESDIR", \config\SMARTYTEMPLATESDIR);
}
if (defined('\config\SMARTYCOMPILEDTEMPLATESDIR')) {
    define("SMARTYCOMPILEDTEMPLATESDIR", \config\SMARTYCOMPILEDTEMPLATESDIR);
}
if (defined('\config\SMARTYPLUGINSDIR')) {
    $plugins = \config\SMARTYPLUGINSDIR;
    $bbsengine6smarty = "/srv/www/bbsengine6/smarty/";
    if (is_array($plugins) && !in_array($bbsengine6smarty, $plugins)) {
        $plugins[] = $bbsengine6smarty;
    }
    define("SMARTYPLUGINSDIR", $plugins);
}

// Shared URL constants - used by all zoid6 sites
if (!defined("ENGINEURL")) define("ENGINEURL", "/engine/");
if (!defined("ENGINESKINURL")) define("ENGINESKINURL", "/engine/skin/");
if (!defined("SHAREDSKINURL")) define("SHAREDSKINURL", "/shared/skin/");
// TEOSURL: env wins. The bbsengine.org handbook vhost publishes
// TEOSURL=/handbook/<v>/ via its htaccess-prod SetEnv so the
// handbook directory listing and breadcrumbs resolve to
// /handbook/<v>/specs/... instead of the teos default. Sites
// that don't set the env var (zoidtechnologies.com teos vhost,
// every other zoid6 consumer) keep the legacy "/teos/" default.
if (!defined("config\\TEOSURL")) {
  $env_teosurl = getenv("TEOSURL");
  if (is_string($env_teosurl) && $env_teosurl !== "") {
    define("config\\TEOSURL", $env_teosurl);
  } else {
    define("config\\TEOSURL", "/teos/");
  }
}
if (!defined("TEOSURL")) define("TEOSURL", \config\TEOSURL);

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

} // namespace

namespace zoid6 {

/**
 * \zoid6\plugin_extra() — zoid6's canonical Smarty plugin-dir
 * contribution. Auto-registered at PRIORITY_APP_INTEGRATION in
 * \bbsengine6\template\plugin by bbsengine6config.php when this
 * function exists. Returns the path to zoid6's own smarty/
 * directory, which contains plugin functions shared across all
 * zoid6 sites.
 */
function plugin_extra(): array {
    return ["/srv/www/zoid6/smarty/"];
}

} // namespace zoid6

/**
 * \zoid6\template — zoid6's template-dir registry and resolver.
 *
 * Mirrors \bbsengine6\template's shape so the two namespaces
 * are intentionally symmetric. bbsengine6config.php auto-registers
 * \zoid6\template\extra at PRIORITY_APP_INTEGRATION when this
 * namespace is loaded, contributing zoid6's shared
 * `shared/skin/tmpl/` directory to any vhost's template search
 * path.
 *
 * normalize($raw) is a thin wrapper that delegates to
 * \bbsengine6\template\normalize() with this namespace's
 * registry and engine-fallback path. That means bbsengine6 must
 * be loaded before any caller invokes \zoid6\template\normalize().
 * Loading order is already guaranteed: vhosts do
 * require_once('zoid6config.php') which loads bbsengine6config.php
 * transitively (because zoid6config.php requires engine.php's
 * bbsengine6config.php at the top of its include chain).
 *
 * PRIORITY_ENGINE is reserved for the engine's own canonical
 * extra(); apps and vhosts register at APP or APP_INTEGRATION.
 */
namespace zoid6\template {

const PRIORITY_APP             = 'app';
const PRIORITY_APP_INTEGRATION = 'app-integration';
const PRIORITY_ENGINE          = 'engine';

static $_zoid6_template_registry = null;
function _registry(): array {
    global $_zoid6_template_registry;
    if ($_zoid6_template_registry === null) {
        $_zoid6_template_registry = [
            PRIORITY_APP             => [],
            PRIORITY_APP_INTEGRATION => [],
            PRIORITY_ENGINE          => [],
        ];
    }
    return $_zoid6_template_registry;
}

/**
 * Register a hook at the given priority. Idempotent against
 * duplicate registration of the same callable. Throws
 * RuntimeException on unknown priority.
 */
function register(string $priority, callable $hook): void {
    _registry();
    global $_zoid6_template_registry;
    if (!isset($_zoid6_template_registry[$priority])) {
        throw new \RuntimeException(
            "zoid6\\template\\register: unknown priority '$priority'. "
          . "Valid: app, app-integration, engine."
        );
    }
    if (!in_array($hook, $_zoid6_template_registry[$priority], true)) {
        $_zoid6_template_registry[$priority][] = $hook;
    }
}

/**
 * Canonical zoid6 template-dir contribution. Self-registered at
 * PRIORITY_ENGINE in this file.
 *
 * @since 2026-09-26 — the original return value
 * (/srv/www/zoid6/shared/skin/tmpl/) pointed at a path that did
 * not exist on the zoidtechnologies.com vhost layout; live
 * shared templates live under the vhost's html/shared/skin/tmpl/
 * (the same path \config\SHAREDTMPLDIR points to in
 * bbsengine6config.php). Returning the wrong path made the
 * registry silently shadow the correct one and broke every
 * vhost that dropped the explicit \config\SHAREDTMPLDIR
 * reference in its SMARTYTEMPLATESDIR.
 *
 * Sites with a different shared-tmpl layout should override
 * this via a hook registered at PRIORITY_APP before bbsengine6
 * loads, or by defining a custom hook in their vhost config.
 */
function extra(): array {
    return ["/srv/www/vhosts/zoidtechnologies.com/html/shared/skin/tmpl/"];
}

/**
 * Public wrapper. Delegates to \bbsengine6\template\normalize
 * with this namespace's registry and engine-fallback path.
 */
function normalize(mixed $raw): array {
    return \bbsengine6\template\normalize(
        $raw,
        \zoid6\template\_registry(),
        \zoid6\template\extra()[0],
        "zoid6\\template"
    );
}

\zoid6\template\register(
    \zoid6\template\PRIORITY_ENGINE,
    '\zoid6\template\extra'
);

} // namespace zoid6\template
?>
