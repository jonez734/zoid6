<?php

/**
 * http://pear.php.net/manual/en/package.database.db.intro-dsn.php
 */
// define("SYSTEMDSN", "pgsql://apache@localhost/zoidweb2");

/**
 * define the base url for the site. 
 * THIS VALUE MUST BE TERMINATED WITH A TRAILING SLASH ("/")
 */
define("SITEURL", "https://www.zoidtechnologies.com/");
define("SITETITLE", "zoid technologies - custom information systems");
define("SITEKEYWORDS", "custom information systems intranet extranet public python");
define("SITEDESCRIPTION", "designs, builds, operates, and maintains custom information systems for your intranet, extranet, or public website");
define("FACEBOOKAPPID", "1680719402198764");

define("IMAGESURL", "//static.zoidtechnologies.com/");

define("SYSOPEMAILADDRESS", "jam@zoidtechnologies.com");

define("SITENAME", "zoid technologies");

/**
 * strftime() compatible date format string.
 */
// define("DATEFORMAT", "%A %Y-%m-%d %I:%M %p %Z"); // <--- MOVED TO zoidweb.php

define("UPLOADDIR", "files/");

define("VHOSTDIR", "/srv/www/vhosts/www.zoidtechnologies.com/");
define("DOCUMENTROOT", VHOSTDIR . "html/");

define("SKINDIR", DOCUMENTROOT . "skin/");
define("SKINURL", SITEURL . "skin/");

// define("COMMONTEMPLATEDIR", "/srv/www/zoidweb2/skin/tmpl/");

define("SMARTYCOMPILEDTEMPLATESDIR", VHOSTDIR . "templates_c/");
define("SMARTYPLUGINSDIR", [ 0 => VHOSTDIR . "smarty/", 1 => "/srv/www/zoidweb6/smarty/"]);
define("SMARTYTEMPLATESDIR", [ 0 => SKINDIR . "tmpl/", 1=> "/srv/www/zoidweb6/skin/tmpl/"]);

define("LOGENTRYPREFIX", "wwwdotzoid");

/**
 * @since 20110601
 */
define("GOOGLEANALYTICSACCOUNT", "UA-23705021-1");

// define("SMARTY3", True);

/**
 * @since 20111215
 */
//define("PEARHTTPSESSION", True);

define("SITECSS", SKINURL . "css/www.css");

$PAGEMETADATA = [
    ["tag" => "meta", "name" => "description", "content" => "custom information systems for your intranet, extranet, or public website" ],
    ["tag" => "link", "rel" => "canonical", "href" => "http://www.zoidtechnologies.com/" ],
    // <meta property="og:locale" content="en_US" />
    ["tag" => "meta", "property" => "og:locale", "content" => "en_US" ],
    // <meta property="og:type" content="website" />
    ["tag" => "meta", "property" => "og:type", "content" => "website!"],
];

/*
 * @since 20190817
 * @since 20221104
 */
$includepath = get_include_path().":/srv/www/zoidweb6/php/:/srv/www/bbsengine6/php/:/srv/www/smarty/";
if (set_include_path($includepath) === false)
{
    print("include path fail");
}

/**
 * @since 20221116
 */
//define("SYSTEMDSN", ["phptype" => "pgsql", "protocol" => "tcp", "hostspec" => "127.0.0.1:5432", "database" => "zoidweb5"]); // , "username" => "apache"]);
define("SYSTEMDSN", "pgsql:host=localhost;port=5432;dbname=zoidweb6");
//define("SYSTEMDSN", "pgsql://apache@127.0.0.1:5432/zoidweb5");
define("STATICSKINURL", "https://static.zoidtechnologies.com/skin/");

define("DATEFORMAT", "%Y-%b-%d %l:%M%p %Z (%a)");

define("SOPHIAURL", "https://sophia.zoidtechnologies.com/");
define("TEOSURL", "https://teos.zoidtechnologies.com/");
define("WWWURL", "https://www.zoidtechnologies.com/");
define("VULCANURL", "https://vulcan.zoidtechnologies.com/");
define("STATICURL", "https://static.zoidtechnologies.com/");
define("REPOURL", "https://repo.zoidtechnologies.com/");
define("PROJECTSURL", "https://projects.zoidtechnologies.com/");
define("TUNEBOXURL", "http://tunebox.zoidtechnologies.com/");
define("ACHILLESURL", "https://achilles.zoidtechnologies.com/");
define("ENGINEURL", "https://engine.zoidtechnologies.com/");
define("PSYCHEURL", "https://psyche.zoidtechnologies.com/");
define("AGORAURL", "https://agora.zoidtechnologies.com/");

define("SESSIONNAME", "zoidweb6");

if (defined("SESSIONCOOKIEDOMAIN") === False)
{
  define("SESSIONCOOKIEEXPIRE", 60*60*12);
  define("SESSIONCOOKIEDOMAIN", ".zoidtechnologies.com");
  define("SESSIONCOOKIEPATH", "/");
}

?>
