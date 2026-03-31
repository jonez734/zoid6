<?php

namespace config {

/**
 * http://pear.php.net/manual/en/package.database.db.intro-dsn.php
 */
// define("SYSTEMDSN", "pgsql://apache@localhost/zoidweb2");

/**
 * define the base url for the site. 
 * THIS VALUE MUST BE TERMINATED WITH A TRAILING SLASH ("/")
 */
define("SITEURL", "//wwwdev.zoidtechnologies.com/");
define("SITETITLE", "Zoid Technologies - Custom Information Systems");
define("SITEKEYWORDS", "intranet, extranet, public website, custom information systems");
define("SITEDESCRIPTION", "design, build, operate, and maintain custom information systems for your intranet, extranet, or public website");
define("FACEBOOKAPPID", "1680719402198764");

define("IMAGESURL", "//static.zoidtechnologies.com/");

define("SYSOPEMAILADDRESS", "jam@zoidtechnologies.com");

define("SITENAME", "zoid technologies dev");

/**
 * strftime() compatible date format string.
 */
// define("DATEFORMAT", "%A %Y-%m-%d %I:%M %p %Z"); // <--- MOVED TO zoidweb.php

define("UPLOADDIR", "files/");

define("VHOSTDIR", "/srv/www/vhosts/wwwdev.zoidtechnologies.com/");
define("DOCUMENTROOT", VHOSTDIR . "80/html/");

define("SKINDIR", DOCUMENTROOT . "skin/");
define("SKINURL", SITEURL . "skin/");

// define("COMMONTEMPLATEDIR", "/srv/www/zoidweb2/skin/tmpl/");

define("SMARTYCOMPILEDTEMPLATESDIR", VHOSTDIR . "templates_c/");
define("SMARTYPLUGINSDIR", VHOSTDIR . "smarty/");
define("SMARTYTEMPLATESDIR", SKINDIR . "tmpl/");

define("LOGENTRYPREFIX", "wwwdotzoiddev");

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

define("USESHOPPINGCART", False);

}

namespace {

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

}
