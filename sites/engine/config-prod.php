<?php

define("SITEURL", "https://zoidtechnologies.com/engine/");

define("config\SYSTEMDSN", "pgsql:host=127.0.0.1;port=5432;dbname=zoid6");

define("config\LOGENTRYPREFIX", "engine");

define("VHOSTDIR", "/srv/www/vhosts/zoidtechnologies.com/");
define("DOCUMENTROOT", VHOSTDIR."html/engine/");

define("SKINDIR", DOCUMENTROOT . "skin/");
define("SKINURL", SITEURL . "skin/");

define("config\SMARTYCOMPILEDTEMPLATESDIR", VHOSTDIR . "templates_c");
define("config\SMARTYPLUGINSDIR", [ 0 => VHOSTDIR . "smarty/"]);

// @since 2026-09-24 — load bbsengine6config.php FIRST to pick up
// config\SHAREDTMPLDIR (single source of truth for the cross-app
// shared tmpl path) before the SMARTY* defines that reference it.
require_once('/srv/www/bbsengine6/php/bbsengine6config.php');

// @since 2026-09-24 — simplified (bbsengine6/skin/tmpl/ auto-appended).
define("config\SMARTYTEMPLATESDIR", [
    0 => DOCUMENTROOT . "skin/tmpl/",
    1 => \config\SHAREDTMPLDIR,
]);

define("config\SESSIONCOOKIEEXPIRE", 60*60*12);
define("config\SESSIONCOOKIEDOMAIN", "zoidtechnologies.com");
define("config\SESSIONCOOKIEPATH", "/");
define("config\SESSIONNAME", "zoid6");

/*
 * @since 20190817
 * @since 20221104
 * @since 20230115 copied from www
 */
$includepath = get_include_path().":/srv/www/zoid6/php:/srv/www/bbsengine6/php:/srv/www/smarty";
if (set_include_path($includepath) === false)
{
    print("include path fail");
}

define("GOOGLEANALYTICSACCOUNT", "UA-23705021-1");

?>
