<?php

// @since 2026-09-26 — replaced the legacy file-scope
// \bbsengine6\template\hook_extra_dirs() shim with a dedicated
// \teos\template namespace (see www/php/teos_template.php and
// specs/TEMPLATE_REGISTRY.md for the full design).

namespace {

require_once("/srv/www/bbsengine6/php/bootstrap.php");
require_once("util.php");
\bbsengine6\util\add_include_paths([
    '/srv/www/vhosts/zoidtechnologies.com/html/teos/',
    '/srv/www/zoid6/php/',
    '/srv/www/bbsengine6/php/',
    '/srv/www/smarty/'
]);

// @since 2026-09-26 — define SMARTYCOMPILEDTEMPLATESDIR BEFORE
// the explicit bbsengine6config require below. As of the 2026-09-26
// engine contract change, bbsengine6config.php no longer defines a
// default for \config\SMARTYCOMPILEDTEMPLATESDIR — getsmarty()
// throws RuntimeException when it's unset. Defining here ensures
// the vhost's path wins regardless of the require order. The
// matching VHOSTDIR define moves up alongside it because
// SMARTYCOMPILEDTEMPLATESDIR resolves its path from it.
define("config\VHOSTDIR", "/srv/www/vhosts/zoidtechnologies.com/");
define("config\SMARTYCOMPILEDTEMPLATESDIR", \config\VHOSTDIR . "templates_c");

// @since 2026-09-26 — require bbsengine6config.php early so
// \config\SHAREDTMPLDIR (defined at the top of bbsengine6config
// .php) is available before SMARTYTEMPLATESDIR references it
// below. Belt-and-suspenders: the explicit require also makes
// the vhost self-documenting and survives any future change to
// bbsengine6config.php's auto-load order.
require_once("/srv/www/bbsengine6/php/bbsengine6config.php");

// Define configuration constants in config namespace (required by bbsengine6)
// Using backslash prefix makes them accessible via defined('\config\CONSTANT')
define("config\SITEURL", "https://zoidtechnologies.com/teos/");
define("config\DOCUMENTROOT", \config\VHOSTDIR . "html/teos/");
define("config\SKINDIR", \config\DOCUMENTROOT . "skin/");
define("config\SKINURL", \config\SITEURL . "skin/");
define("config\JSURL", "/teos/skin/js/");
define("config\SITENAME", "teos");

// URL configuration for shared and engine resources
define("config\ENGINEURL", "/engine/");
define("config\ENGINESKINURL", "/engine/skin/");
define("config\SHAREDSKINURL", "/shared/skin/");

// bbsengine6config.php's function_exists() block auto-registers
// \zoid6\template\extra and \teos\template\extra at
// PRIORITY_APP_INTEGRATION at engine load time, contributing
// zoid6's and teos's shared/template dirs to any vhost's
// template search path automatically.
require_once("zoid6config.php");
// @since 2026-09-27 — require zoid6.php (not just zoid6config.php).
// zoid6config.php only creates Smarty-related aliases; zoid6.php
// is what defines FONTAWESOMEURL (the FontAwesome Kit URL) and a
// handful of other constants that shared/skin/tmpl/page.tmpl
// reads via {$smarty.const.*}. The legacy teos entry points
// (index.php, link.php, teos.php) required zoid6.php directly,
// which is why those URLs still render with the FA Kit script.
// Engine-routed paths (engine/router.php → folder.tmpl, page-markdown
// .tmpl, etc.) bypass those legacy files, so without this require
// the shared page.tmpl head block renders <script src=""> for
// FONTAWESOMEURL and the FA icons don't render visually on
// /rec/, /handbook/<v>/, and any other engine-rendered URL.
//
// Note: as of 2026-09-27, FONTAWESOMEURL and GOOGLEANALYTICSACCOUNT
// moved from zoid6.php into zoid6constants.php, which is required
// transitively by zoid6config.php (the require_once at the top of
// that file). So this zoid6.php require is no longer strictly
// needed for FONTAWESOMEURL on engine-routed paths; the explicit
// load is kept here as belt-and-suspenders for any legacy
// \zoid6\* function calls in teos's own code (buildchoices,
// buildcaptchafieldset, etc.) and to preserve the prior behavior
// for any other constants this file relies on.
require_once("zoid6.php");
require_once("teos_template.php");

// @since 2026-09-27 — dynamic config-fingerprint marker.
// zoid6/shared/skin/tmpl/page.tmpl:3 reads
// {$smarty.const.SHAREDPAGEMARKER|default:""} and inlines the
// value as an HTML comment. Pre-deploy, the constant is
// undefined and Smarty renders an empty string (no visible
// marker). Post-deploy, this define fires and the rendered
// page contains the marker comment, which lets a curl probe
// confirm the config-prod.php on merlin has been reloaded.
// The value intentionally includes "zoid6constants.php-required"
// so a grep for that substring proves the FONTAWESOMEURL fix
// landed in the same deploy as the marker. (Renamed from
// "zoid6.php-required" on 2026-09-27 when FONTAWESOMEURL
// and GOOGLEANALYTICSACCOUNT moved from zoid6.php into
// zoid6constants.php, which is now required transitively via
// zoid6config.php.)
define("SHAREDPAGEMARKER", "<!-- teos-config @ 2026-09-27-zoid6constants.php-required -->");

// SMARTYTEMPLATESDIR - vhost slots + explicit shared tmpl. The
// registry appends engine-side \\bbsengine6\\template\\extra()
// at PRIORITY_ENGINE automatically; the shared slot below makes
// the vhost resilient to zoid6 being absent and self-documents
// the shared-tmpl location.
define("config\SMARTYTEMPLATESDIR", [
    0 => \config\SKINDIR . "tmpl/",
    1 => \config\SHAREDTMPLDIR,
]);

// @since 2026-10-01 — refresh bare SMARTY* aliases from the
// namespaced defines above. teos's index.php doesn't currently
// read bare SMARTYTEMPLATESDIR, but if a future entry point does,
// this prevents a 500. Idempotent, guarded with !defined() in
// the function body. Belt-and-suspenders companion to e568138.
\zoid6\refresh_smarty_aliases();

// SMARTYCOMPILEDTEMPLATESDIR - compiled template cache directory.
// Defined above the explicit bbsengine6config require; see the
// note there for why order matters.

// SMARTYPLUGINSDIR - plugin directories for custom Smarty functions and modifiers
define("config\SMARTYPLUGINSDIR", [
    0 => \config\VHOSTDIR . "smarty/",
    1 => "/srv/www/zoid6/smarty/"
]);

define("config\LOGENTRYPREFIX", "zoid6teos");
define("config\VULCANURL", "https://vulcan.zoidtechnologies.com/");
define("config\TEOSLABELPREFIX", "top");

// Create global aliases for non-SMARTY constants
// (SMARTY* aliases are created by zoid6config.php)
define("SITEURL", \config\SITEURL);
define("VHOSTDIR", \config\VHOSTDIR);
define("DOCUMENTROOT", \config\DOCUMENTROOT);
define("SKINDIR", \config\SKINDIR);
define("SKINURL", \config\SKINURL);
define("JSURL", \config\JSURL);
define("LOGENTRYPREFIX", \config\LOGENTRYPREFIX);

// Backward compatibility aliases for engine resources
define("ENGINEURL", \config\ENGINEURL);
define("ENGINESKINURL", \config\ENGINESKINURL);
define("SHAREDSKINURL", \config\SHAREDSKINURL);
define("STATICSKINURL", \config\SHAREDSKINURL);
define("VULCANURL", \config\VULCANURL);
define("TEOSLABELPREFIX", \config\TEOSLABELPREFIX);

} // closes namespace { at top

?>
