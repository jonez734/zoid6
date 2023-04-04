<?php

require_once("config.php");
require_once("zoidweb2.php");
require_once("bbsengine4.php");

startsession();
$page = getpage("testlink");
$page->addBodyContent("<a href='/testlink.php' class='zoidweb'>test</a>");
$pageprotocol = getpageprotocol();
if ($pageprotocol === "enhanced")
{
    print encodejson($page->toHTML())
}
elseif ($pageprotocol === "standard")
{
    $page->display();
}
else
{
    print "errm?";
}
?>
