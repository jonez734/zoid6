<?php

/**
 * http://pear.php.net/manual/en/package.database.db.intro-dsn.php
 */
// define("SYSTEMDSN", "pgsql://apache@localhost/zoidweb2");

/**
 * define the base url for the site. 
 * THIS VALUE MUST BE TERMINATED WITH A TRAILING SLASH ("/")
 */
// Define configuration constants in config namespace (required by bbsengine6)
// Using backslash prefix makes them accessible via defined('\config\CONSTANT')
define("config\SITEURL", "//wwwdev.zoidtechnologies.com/");
define("config\SITETITLE", "Zoid Technologies - Custom Information Systems");
define("config\SITEKEYWORDS", "intranet, extranet, public website, custom information systems");
define("config\SITEDESCRIPTION", "design, build, operate, and maintain custom information systems for your intranet, extranet, or public website");
define("config\FACEBOOKAPPID", "1680719402198764");

define("config\IMAGESURL", "//static.zoidtechnologies.com/");

define("config\SYSOPEMAILADDRESS", "jam@zoidtechnologies.com");

define("config\SITENAME", "zoid technologies dev");

/**
 * strftime() compatible date format string.
 */
// define("DATEFORMAT", "%A %Y-%m-%d %I:%M %p %Z"); // <--- MOVED TO zoidweb.php

define("config\UPLOADDIR", "files/");

define("config\VHOSTDIR", "/srv/www/vhosts/wwwdev.zoidtechnologies.com/");
define("config\DOCUMENTROOT", \config\VHOSTDIR . "80/html/");

define("config\SKINDIR", \config\DOCUMENTROOT . "skin/");
define("config\SKINURL", \config\SITEURL . "skin/");

// define("COMMONTEMPLATEDIR", "/srv/www/zoidweb2/skin/tmpl/");

define("config\SMARTYCOMPILEDTEMPLATESDIR", \config\VHOSTDIR . "templates_c/");
define("config\SMARTYPLUGINSDIR", \config\VHOSTDIR . "smarty/");
define("config\SMARTYTEMPLATESDIR", \config\SKINDIR . "tmpl/");

define("config\LOGENTRYPREFIX", "wwwdotzoiddev");

/**
 * @since 20110601
 */
define("config\GOOGLEANALYTICSACCOUNT", "UA-23705021-1");

// define("SMARTY3", True);

/**
 * @since 20111215
 */
//define("PEARHTTPSESSION", True);

define("config\SITECSS", \config\SKINURL . "css/www.css");

define("config\USESHOPPINGCART", False);

// Create global aliases for code that references constants directly without namespace
define("SITENAME", \config\SITENAME);
define("SITEURL", \config\SITEURL);
define("DOCUMENTROOT", \config\DOCUMENTROOT);
define("SKINDIR", \config\SKINDIR);
define("SKINURL", \config\SKINURL);
define("SITECSS", \config\SITECSS);

$PAGEMETADATA = [
    ["tag" => "meta", "name" => "description", "content" => "custom information systems for your intranet, extranet, or public website" ],
    ["tag" => "link", "rel" => "canonical", "href" => "http://wwwdev.zoidtechnologies.com/" ],
    // <meta property="og:locale" content="en_US" />
    ["tag" => "meta", "property" => "og:locale", "content" => "en_US" ],
    // <meta property="og:type" content="website" />
    ["tag" => "meta", "property" => "og:type", "content" => "website!"],
];

/*
 * @since 20190608
 */
$includepath = get_include_path();
$zoidweb4 = str_replace("zoidweb3", "zoidweb4", $includepath);
set_include_path($zoidweb4);

?>
