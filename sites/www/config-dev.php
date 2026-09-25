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
define("config\SITEURL", "http://localhost/");
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

// Define SMARTY constants before including zoid6config.php so it can create global aliases
// Path array allows fallback search: site-specific → shared → framework
define("config\SMARTYCOMPILEDTEMPLATESDIR", \config\VHOSTDIR . "templates_c");
define("config\SMARTYPLUGINSDIR", [
    0 => \config\VHOSTDIR . "smarty/",
    1 => "/srv/www/zoid6/smarty/"
]);

// @since 2026-09-24 — load zoid6config.php first (transitively loads
// bbsengine6config.php, defining config\SHAREDTMPLDIR) before the
// SMARTY* defines that reference it.
require_once('zoid6config.php');

// @since 2026-09-24 — simplified (bbsengine6/skin/tmpl/ auto-appended;
// duplicate shared-skin-tmpl dedup'd).
define("config\SMARTYTEMPLATESDIR", [
    0 => \config\VHOSTDIR . "skin/tmpl/",
    1 => \config\VHOSTDIR . "tmpl/",
    2 => \config\SHAREDTMPLDIR,
]);

// Logging and analytics
define("config\LOGENTRYPREFIX", "wwwdev");
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
