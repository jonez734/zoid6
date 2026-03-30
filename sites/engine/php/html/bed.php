<?php

require_once("PEAR.php");

require_once("config.php");
require_once("zoid6.php");
require_once("engine.php");
require_once("session.php");

function loginlogout()
{
  $tmpl = getsmarty();
  $data = [];
  $data["fragment"] = $tmpl->fetch("topbar-loginlogout.tmpl");
  return $data;
}

function notifycount()
{
  $currentmember = getcurrentmember();
  if (PEAR::isError($currentmember))
  {
    logentry("topbar.notifycount.110: " . $currentmember->toString());
    return;
  }
  $tmpl = getsmarty();
  $data = [];
  $data["fragment"] = $tmpl->fetch("topbar-notifycount.tmpl");
  return $data;
}

function topbarjoin()
{
  $tmpl = getsmarty();
  $data = [];
  $data["fragment"] = $tmpl->fetch("topbar-join.tmpl");
  return $data;
}

function greetings()
{
  $tmpl = getsmarty();
  $data = [];
  $data["fragment"] = $tmpl->fetch("topbar-greetings.tmpl");
  return $data;
}

function creditcount()
{
  $tmpl = getsmarty();
  $data = [];
  $data["fragment"] = $tmpl->fetch("topbar-credits.tmpl");
  return $data;
}

function topbarupdateinterval()
{
//  logentry("topbarupdateinterval.100: called");
  $data = ["topbarupdateinterval" => 30000];
  return $data;
}

function currentmemberid()
{
  $currentmemberid = \bbsengine6\member\lib\getcurrentid();
  $data = ["currentmemberid" => $currentmemberid];
  return $data;
}

function getvar($var)
{
    $res = null;

//    logentry("bed.getvar.100: var=".var_export($var, true));

    switch ($var)
    {
        case "topbar.loginlogout":
        {
          // logentry("engine.topbar.100: calling loginlogout()");
          $res = loginlogout();
          break;
        }
        case "topbar.notifycount":
        {
          $res = notifycount();
          break;
        }
        case "topbar.creditcount":
        {
          $res = creditcount();
          break;
        }
        case "topbar.join":
        {
          $res = topbarjoin();
          break;
        }
        case "topbar.greetings":
        {
          $res = greetings();
          break;
        }
        case "engine.topbarupdateinterval":
        {
          $res = topbarupdateinterval();
//          logentry("bed.340: topbarinterval called, res=".var_export($res, true));
          break;
        }
        case "engine.currentmemberid":
        {
//          logentry("bed.318: currentmemberid");
          $res = currentmemberid();
          break;
        }
        default:
        {
          logentry("bed.320: ".var_export($var, true)." not defined");
          $data = [];
          $data["fragment"] = "<b>error</b>";
          $res = $data;
          break;
        }
    }
    if (PEAR::isError($res))
    {
      logentry("bed.300: " . $res->toString());
      return null;
    }
    return $res;
}

\bbsengine6\session\start();

// $mode is ignored at the moment
$var = isset($_REQUEST["var"]) ? $_REQUEST["var"] : null;
$res = getvar($var);
//logentry("bed.120: var=".var_export($var, true)." res=".var_export($res, true));
if (PEAR::isError($res))
{
    logentry("engine.bed.100: var=".var_export($var, true)." ". $res->toString());
    $res = ["fragment" => "<b>error</b>"];
//    print encodejson($res);
//    exit(1);
}

$encoded = \bbsengine6\encodejson($res);
if (isset($_REQUEST["callback"]))
{
  header('Content-type: text/javascript'); // text/plain
//              logentry("bed.php.100: callback exists");
  $output = $_REQUEST["callback"]."({$encoded})";
  print $output;
//              logentry("bed.php.120: output=".var_export($output, true));
}
else
{
  header("Content-type: application/json");
  logentry("bed.php.110: using json (no callback)");
  print $encoded;
}
?>
