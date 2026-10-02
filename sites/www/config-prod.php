<?php

require_once('/srv/www/bbsengine6/php/bootstrap.php');
require_once('util.php');

\bbsengine6\util\add_include_paths([
    '/srv/www/zoid6/php/',
    '/srv/www/bbsengine6/php/',
    '/srv/www/smarty/'
]);

// Define configuration constants in config namespace (required by bbsengine6)
// Using config\ prefix makes them accessible via defined('\config\CONSTANT')

// Site identification and metadata
define("config\SITENAME", "www");
define("config\SITETITLE", "Zoid Technologies - Custom Information Systems");
define("config\SITEURL", "https://zoidtechnologies.com/");
define("config\SITEDESCRIPTION", "Design, build, operate, and maintain custom information systems for your intranet, extranet, or public website");

// Paths and URLs
define("config\VHOSTDIR", "/srv/www/vhosts/zoidtechnologies.com/html/");
define("config\DOCUMENTROOT", \config\VHOSTDIR);
define("config\SKINDIR", \config\DOCUMENTROOT . "skin/");
define("config\SKINURL", \config\SITEURL . "skin/");
define("config\JSURL", "/engine/js/");
define("config\IMAGESURL", "https://zoidtechnologies.com/static/");

// URL configuration for shared and engine resources
define("config\ENGINEURL", "/engine/");
define("config\ENGINESKINURL", "/engine/skin/");
define("config\SHAREDSKINURL", "/shared/skin/");

// @since 2026-10-01 — explicit require mirrors teos/config.php:36.
// zoid6config.php now (also) loads bbsengine6config.php
// transitively as of this commit, but pinning it here too makes
// the vhost self-documenting and survives any future change to
// zoid6config.php's load order. Required so \config\SHAREDTMPLDIR
// (defined at the top of bbsengine6config.php) is available
// before the SMARTYTEMPLATESDIR array below references it.
//
// The 2026-09-24 docblock that previously sat above the
// zoid6config.php require_once was incorrect — it claimed
// zoid6config.php transitively loaded bbsengine6config.php, but
// the require was missing until ff6be4f landed today. Replaced
// with a corrected comment that points at this commit and ff6be4f
// for the full history.
//
// Bare require_once resolves via include_path. add_include_paths()
// above already prepends /srv/www/bbsengine6/php/, so the
// absolute path isn't needed (and would lock the vhost into
// a single install layout).
require_once("bbsengine6config.php");

// @since 2026-10-01 — reordered to match teos's layout: SMARTY*
// defines and the refresh call now come AFTER
// require_once('zoid6config.php'). Earlier revisions put them
// before so zoid6config.php's eager alias block could see them
// at load time, but that required calling
// \zoid6\refresh_smarty_aliases() before the function was
// defined (it lives inside zoid6config.php), which 500s with
// "Call to undefined function". Teos's layout — same as this
// commit — puts SMARTY* defines after the zoid6config.php
// require and uses the explicit refresh call as the canonical
// late-binding entry point.
require_once('zoid6config.php');

// Path array allows fallback search: site-specific → shared → framework
// @since 2026-09-24 — simplified: 3 entries (bbsengine6/skin/tmpl/
// is auto-appended by bbsengine6config.php as the engine fallback;
// duplicate shared-skin-tmpl entry at old index 3 dedup'd).
define("config\SMARTYTEMPLATESDIR", [
    0 => \config\VHOSTDIR . "skin/tmpl/",           // www-specific templates (new structure)
    1 => \config\VHOSTDIR . "tmpl/",                // www-specific templates (legacy structure)
    2 => \config\SHAREDTMPLDIR,                    // Shared zoid6 templates
]);
define("config\SMARTYCOMPILEDTEMPLATESDIR", \config\VHOSTDIR . "templates_c");
define("config\SMARTYPLUGINSDIR", [
    0 => \config\VHOSTDIR . "smarty/",
    1 => "/srv/www/zoid6/smarty/"
]);

// @since 2026-10-01 — refresh bare SMARTY* aliases from the
// namespaced defines above. zoid6config.php's eager alias block
// ran before these defines were in place (see the layout note
// above), so the bare aliases need to be created here. This
// call must come AFTER require_once('zoid6config.php') because
// the function is defined inside zoid6config.php. Idempotent
// (guarded with !defined() in the function body). Mirrors
// teos's identical pattern.
\zoid6\refresh_smarty_aliases();

// Logging and analytics
define("config\LOGENTRYPREFIX", "wwwprod");
define("config\GOOGLEANALYTICSACCOUNT", "UA-23705021-1");

// Create global aliases for non-SMARTY constants
// (SMARTY* aliases are created by zoid6config.php)
define("SITENAME", \config\SITENAME);
define("SITEURL", \config\SITEURL);
define("VHOSTDIR", \config\VHOSTDIR);
define("DOCUMENTROOT", \config\DOCUMENTROOT);
define("SKINDIR", \config\SKINDIR);
define("SKINURL", \config\SKINURL);
define("JSURL", \config\JSURL);
define("IMAGESURL", \config\IMAGESURL);
define("ENGINEURL", \config\ENGINEURL);
define("ENGINESKINURL", \config\ENGINESKINURL);
define("SHAREDSKINURL", \config\SHAREDSKINURL);
define("STATICSKINURL", \config\SHAREDSKINURL);
define("LOGENTRYPREFIX", \config\LOGENTRYPREFIX);

?>
