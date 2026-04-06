<?php

define("SITEURL", "https://engine.zoidtechnologies.com/");

// define("SYSTEMDSN", "pgsql://apache@localhost/zoidweb2"); 
define("LOGENTRYPREFIX", "engine");

define("VHOSTDIR", "/srv/www/vhosts/engine.zoidtechnologies.com/");
define("DOCUMENTROOT", VHOSTDIR . "html/");

define("SKINDIR", DOCUMENTROOT . "skin/");
define("SKINURL", SITEURL . "skin/");

define("SMARTYCOMPILEDTEMPLATESDIR", VHOSTDIR . "templates_c/");
define("SMARTYPLUGINSDIR", VHOSTDIR . "smarty/");
define("SMARTYTEMPLATESDIR", [ 0 => SKINDIR . "tmpl/", 1 => "/srv/www/zoid6/shared/skin/tmpl/" ]);

/*
define("SYSTEMDSN", "pgsql://apache@localhost/zoidweb3");

if (defined("SESSIONCOOKIEDOMAIN") === False)
{
  define("SESSIONCOOKIEEXPIRE", 60*60*12);
  define("SESSIONCOOKIEDOMAIN", ".zoidtechnologies.com");
  define("SESSIONCOOKIEPATH", "/");
}
*/

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
