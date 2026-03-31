<?php

namespace config {

require_once('/srv/www/bbsengine6/php/bootstrap.php');
require_once('util.php');
\bbsengine6\util\add_include_paths(['/srv/www/zoid6/php/', '/srv/www/bbsengine6/php/', '/srv/www/smarty/']);

    define("SITENAME", "zoid technologies");

    define("VHOSTDIR", "/srv/www/vhosts/zoidtechnologies.com/html/");
    
    define("SITEURL", "https://zoidtechnologies.com/");

    define("DOCUMENTROOT", VHOSTDIR);

    define("SKINDIR", DOCUMENTROOT . "skin/");
    define("SKINURL", SITEURL . "skin/");

    define("JSURL", "/engine/js/");

    define("SMARTYCOMPILEDTEMPLATESDIR", VHOSTDIR . "templates_c");
    define("SMARTYPLUGINSDIR", [ 0 => VHOSTDIR . "smarty/", 1 => "/srv/www/zoid6/smarty/"]);
    define("SMARTYTEMPLATESDIR", [ 0 => VHOSTDIR . "skin/tmpl/", 1=> "/srv/www/zoid6/skin/tmpl/", 2 => "/srv/www/bbsengine6/skin/tmpl/"]);

require_once("zoid6.php");

    define("LOGENTRYPREFIX", "zoid6www");

    define("SITECSS", SKINURL . "css/www.css");

}

namespace {

$PAGEMETADATA = [
    ["tag" => "meta", "name" => "description", "content" => "custom information systems for your intranet, extranet, or public website" ],
    ["tag" => "link", "rel" => "canonical", "href" => "https://zoidtechnologies.com/" ],
    // <meta property="og:locale" content="en_US" />
    ["tag" => "meta", "property" => "og:locale", "content" => "en_US" ],
    // <meta property="og:type" content="website" />
    ["tag" => "meta", "property" => "og:type", "content" => "website"],
];

}
