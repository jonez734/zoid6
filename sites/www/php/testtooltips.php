<?php

require_once("config.php");
require_once("zoidweb2.php");
require_once("bbsengine4.php");

startsession();

setcurrentpage("testtooltips");

$page = getpage("test tooltips");
$page->addScript(STATICJAVASCRIPTURL."tooltip.js");
//$page->addBodyContent(fetchpageheader());
//$page->addBodyContent(fetchtopbar());
//$page->addBodyContent(fetchsidebar());
$tmpl = getsmarty();
$page->addBodyContent($tmpl->fetch("testtooltips.tmpl"));
//$page->addBodyContent(fetchpagefooter());
$page->display();

?>
