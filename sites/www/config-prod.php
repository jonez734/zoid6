<?php

require_once('/srv/www/bbsengine6/php/bootstrap.php');
require_once('util.php');
\bbsengine6\util\add_include_paths(['/srv/www/zoid6/php/', '/srv/www/bbsengine6/php/', '/srv/www/smarty/']);

// Define configuration constants in config namespace (required by bbsengine6)
// Using backslash prefix makes them accessible via defined('\config\CONSTANT')
define("config\SITENAME", "zoid technologies");
define("config\VHOSTDIR", "/srv/www/vhosts/zoidtechnologies.com/html/");
define("config\SITEURL", "https://zoidtechnologies.com/");
define("config\DOCUMENTROOT", \config\VHOSTDIR);
define("config\SKINDIR", \config\DOCUMENTROOT . "skin/");
define("config\SKINURL", \config\SITEURL . "skin/");
define("config\JSURL", "/engine/js/");
define("config\SMARTYCOMPILEDTEMPLATESDIR", \config\VHOSTDIR . "templates_c");
define("config\SMARTYPLUGINSDIR", [ 0 => \config\VHOSTDIR . "smarty/", 1 => "/srv/www/zoid6/smarty/"]);
define("config\SMARTYTEMPLATESDIR", [ 0 => \config\VHOSTDIR . "skin/tmpl/", 1=> "/srv/www/zoid6/skin/tmpl/", 2 => "/srv/www/bbsengine6/skin/tmpl/"]);
define("config\LOGENTRYPREFIX", "zoid6www");
define("config\SITECSS", \config\SKINURL . "css/www.css");

require_once("zoid6.php");

// Create global aliases for code that references constants directly without namespace
define("SITENAME", \config\SITENAME);
define("SITEURL", \config\SITEURL);
define("DOCUMENTROOT", \config\DOCUMENTROOT);
define("SKINDIR", \config\SKINDIR);
define("SKINURL", \config\SKINURL);
define("JSURL", \config\JSURL);
define("SITECSS", \config\SITECSS);
define("SMARTYTEMPLATESDIR", \config\SMARTYTEMPLATESDIR);
define("SMARTYCOMPILEDTEMPLATESDIR", \config\SMARTYCOMPILEDTEMPLATESDIR);
define("SMARTYPLUGINSDIR", \config\SMARTYPLUGINSDIR);
define("LOGENTRYPREFIX", \config\LOGENTRYPREFIX);

$PAGEMETADATA = [
    ["tag" => "meta", "name" => "description", "content" => "custom information systems for your intranet, extranet, or public website" ],
    ["tag" => "link", "rel" => "canonical", "href" => "https://zoidtechnologies.com/" ],
    // <meta property="og:locale" content="en_US" />
    ["tag" => "meta", "property" => "og:locale", "content" => "en_US" ],
    // <meta property="og:type" content="website" />
    ["tag" => "meta", "property" => "og:type", "content" => "website"],
];

?>
