<?php

require_once("config.php");
require_once("zoidweb2.php");
require_once("bbsengine4.php");

class mauxdocs
{
  function main()
  {
    startsession();
    $page = getpage("MAUX Docs");
    $page->addBodyContent(fetchpageheader("MAUX Docs"));
    $page->addBodyContent(fetchtopbar());
    $page->addBodyContent(fetchsidebar());
    $page->addBodyContent("OK");
    $page->addBodyContent(fetchpagefooter());
    $page->display();
  }
};

$foo = new mauxdocs();
$bar = $foo->main();
if ($bar === false)
{
  logentry("mauxdocs.9000: " . $bar->toString());
  displayerrorpage($bar->getMessage());
}
exit;
?>