<?php

require_once("config.php");
require_once("zoidweb2.php");
require_once("bbsengine4.php");

function main()
{
    $page = getpage("finn's 7th bday");
    $page->addStylesheet("/skin/css/finn.css");
    $page->addStylesheet("//cdnjs.cloudflare.com/ajax/libs/animate.css/3.5.2/animate.min.css");
    $page->addScript("/js/finn.js");
    $tmpl = getsmarty();
    $options = [];
    $options["pagedata"]["body"] = $tmpl->fetch("finn.tmpl");
    $options["pagedata"]["template"] = "finnpage.tmpl";
    displaypage($page, $options);
    return;
}

main();

?>
