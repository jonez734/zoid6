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
// before the SMARTYTEMPLATESDIR array at line 51 references it.
//
// The 2026-09-24 docblock above the zoid6config.php require_once
// below claimed that zoid6config.php transitively loaded
// bbsengine6config.php. That assumption was incorrect until the
// 2026-10-01 fix landed the require at the top of zoid6config.php.
// Prior to that, this vhost (and any other vhost that relied on
// the comment) would 500 with "Undefined constant
// \config\SHAREDTMPLDIR" at the SMARTYTEMPLATESDIR define below.
//
// Bare require_once resolves via include_path. add_include_paths()
// above already prepends /srv/www/bbsengine6/php/, so the
// absolute path isn't needed (and would lock the vhost into
// a single install layout).
require_once("bbsengine6config.php");

require_once('zoid6config.php');

// Path array allows fallback search: site-specific → shared → framework
define("config\SMARTYCOMPILEDTEMPLATESDIR", \config\VHOSTDIR . "templates_c");
define("config\SMARTYPLUGINSDIR", [
    0 => \config\VHOSTDIR . "smarty/",
    1 => "/srv/www/zoid6/smarty/"
]);
// @since 2026-09-24 — simplified: 3 entries (bbsengine6/skin/tmpl/
// is auto-appended by bbsengine6config.php as the engine fallback;
// duplicate shared-skin-tmpl entry at old index 3 dedup'd).
define("config\SMARTYTEMPLATESDIR", [
    0 => \config\VHOSTDIR . "skin/tmpl/",           // www-specific templates (new structure)
    1 => \config\VHOSTDIR . "tmpl/",                // www-specific templates (legacy structure)
    2 => \config\SHAREDTMPLDIR,                    // Shared zoid6 templates
]);

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
