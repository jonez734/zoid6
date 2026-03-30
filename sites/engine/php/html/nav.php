<?php

require_once("config.php");
require_once("zoidweb3.php");
require_once("bbsengine4.php");

class nav
{
  function main()
  {
    startsession();
    
    $res = null;
      
    $mode = isset($_REQUEST["mode"]) ? $_REQUEST["mode"] : "content";
    // logentry("topbar.120: mode=".var_export($mode, True));
    switch ($mode)
    {
      case "choices":
      {
        $res = $this->choices();
        break;
      }
      
      // @FIX: add 'default' case which handles the error
      default:
      {
        $res["fragment"] = "unknown mode {$mode}";
        break;
      }
    }
    $encoded = encodejson($res);
    if (isset($_REQUEST["callback"]))
    {
      print $_REQUEST["callback"]."({$encoded})";
    }
    else
    {
      print $encoded;
    }
    return;
  }
};

$a = new nav();
$b = $a->main();
if (PEAR::isError($b))
{
    logentry("nav.999: " . $b->toString());
    displayerrorpage($b->getMessage());
    exit;
}
