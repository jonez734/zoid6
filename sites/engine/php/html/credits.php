<?php

require_once("config.php");
require_once("zoidweb3.php");
require_once("bbsengine4.php");

class credits
{
  var $dbh = null;

  function getcreditcount()
  {
    $data = array();
    
    $currentmember = getcurrentmember();
    if (PEAR::isError($currentmember))
    {
      logentry("credits.getcount.110: " . $res->toString());
      $res = -1;
    }
    $tmpl = getsmarty();

    $data["fragment"] = $tmpl->fetch("topbar-credits.tmpl");

    header("Content-Type: application/json; charset=utf-8");

    $encoded = encodejson($data);
    
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

  function main()
  {
    startsession();
    
    $this->dbh = dbconnect(SYSTEMDSN);
    if (PEAR::isError($this->dbh))
    {
      displayerrorpage("Database Error (code: credits.10)");
      logentry("credits.10: " . $this->dbh->toString());
      return;
    }

    $res = null;
    
    $mode = isset($_REQUEST["mode"]) ? $_REQUEST["mode"] : null;
    switch ($mode)
    {
      case "count":
      {
        $res = $this->getcreditcount();
        break;
      }
      default:
      {
        $res = PEAR::raiseError("invalid mode");
        break;
      }
    }
    $this->dbh->disconnect();
    return $res;
  }
};

$a = new credits();
$b = $a->main();
if (PEAR::isError($b))
{
  displayerrorpage($b->getMessage());
  logentry("credits.100: " . $b->toString());
  exit;
}

?>
