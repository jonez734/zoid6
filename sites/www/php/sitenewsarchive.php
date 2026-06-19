<?php

require_once("config.php");
require_once("zoidweb4.php");
require_once("bbsengine4.php");

class sitenewsarchive
{
  function main()
  {
    startsession();
    
    $tmpl = getsmarty();
    
    setcurrentpage("sitenewsarchive");
    
    $page = getpage("site news archive");
    $data = [];
    $data["pagetemplate"] = "sitenewsarchive.tmpl";
    displaypage($page, $data);
    
    endsession();
    return;
    
  }
};

$a = new sitenewsarchive();
$b = $a->main();
if ($b === false)
{
  displayerrorpage($b->getMessage());
  exit;
}
?>
