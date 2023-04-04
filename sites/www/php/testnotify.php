<?php

require_once("config.php");
require_once("zoidweb2.php");
require_once("bbsengine4.php");

startsession();

$currentmemberid = getcurrentmemberid();
if ($currentmemberid === null)
{
  displaypermissiondenied();
  exit;
}
$data = array();
$data["linkid"] = 374;

$notify = array();
$notify["data"] = $data;

//$link = buildnotifyrecord("linkid", $notify);
//logentry("testnotify.100: link=".var_export($link, True));
//$data["link"] = $link;

sendnotify("link-added", $currentmemberid, $data);

print "sendnotify() called<br />";

?>
