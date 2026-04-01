<?php

require_once("config.php");
require_once("zoidweb2.php");
require_once("bbsengine4.php");


displayredirectpage("message testing", null, 0);
exit;

require_once("Calendar/Month/Weekdays.php");
require_once("Calendar/Day.php");
//require_once("Calendar/Decorator.php");
require_once("Calendar/Decorator/Textual.php");
require_once("Calendar/Util/Textual.php");     

$year =  2017;
$month = 4;

$nth = 2; // 2nd
$dow = 3; // wed, 0=sunday

$tmpday = 1+7*$nth;
$date = new DateTime("{$year}-{$month}-{$tmpday}");
logentry("test.110: date=".var_export($date, True));
$tmpday = 8-$dow;
$d = new DateTime("{$year}-{$month}-{$tmpday}");
$weekday = $d->format("w");
logentry("test.120: weekday=".var_export($weekday, True));
$result = $date->sub(new DateInterval("P{$weekday}D"));
logentry("test.140: result=".var_export($result, True));
print "OK";

exit;


function buildordinaldate($buf) // $ordinal, $dayname, $monthname, $year)
{
//  $buf = "{$ordinal} {$dayname} {$monthname} {$year}";
  $epoch = strtotime($buf);
  $day = new Calendar_Day(2017, 4, 9);
  $day->setTimestamp($epoch);
  return $day;
  
}
$buf = "second wednesday january 2018";
$moneyday = buildordinaldate($buf);

$ts = mktime(0, 0, 0, $moneyday->thisMonth()+1, $moneyday->thisDay(), $moneyday->thisYear());
$day = new Calendar_Day($moneyday->thisYear(), $moneyday->thisMonth(), $moneyday->thisDay());
$day->setTimestamp($ts);
$buf = "second wednesday ".Calendar_Util_Textual::thisMonthName($day)." ".$day->thisYear();
$nextmoneyday = buildordinaldate($buf);

$ts = mktime(0, 0, 0, $moneyday->thisMonth()-1, $moneyday->thisDay(), $moneyday->thisYear());
$day = new Calendar_Day($moneyday->thisYear(), $moneyday->thisMonth(), $moneyday->thisDay());
$day->setTimestamp($ts);
$buf = "second wednesday ".Calendar_Util_Textual::thisMonthName($day)." ".$day->thisYear();
$prevmoneyday = buildordinaldate($buf);
print "prevmoneyday=".var_export($prevmoneyday, True)."<br />";
print "moneyday=".var_export($moneyday, True)."<br />";
print "nextmoneyday=".var_export($nextmoneyday, True)."<br />";

exit;

/*
function buildallflags($memberid)
{
  $dbh = dbconnect(SYSTEMDSN);
  $sql = "select upper(flag.name) as name, coalesce(mmf.value, flag.defaultvalue) as value, flag.description from engine.flag left outer join engine.map_member_flag as mmf on flag.name = mmf.name and mmf.memberid=?";
  $dat = array($memberid);
  $res = $dbh->getAll($sql, null, $dat, array("integer"));
  if (PEAR::isError($res))
  {
    logentry("_buildflagarray.10: " . $res->toString());
    return;
  }

  $allflags = array();
  foreach ($res as $rec)
  {
    $flag = array();
    $flag["name"] = $rec["name"];
    $flag["description"] = $rec["description"];
    
    $flag["value"] = ($rec["value"] === "t") ? True : False;
    $allflags[] = $flag;
  }
  
  return $allflags;
}

function update($values)
{
  print "values=".var_export($values, True);
  return;
}

function main()
{
  $form = getquickform("test-html5-elements");
  $fieldset = $form->addElement("fieldset", "flagfieldset")->setLabel("Flags");
  $group = $fieldset->addElement("group", "flags")->setSeparator("<br />");

  $memberid = isset($_REQUEST["memberid"]) ? intval($_REQUEST["memberid"]) : NULL;

  $allflags = buildallflags($memberid);
  foreach($allflags as $flag)
  {
    $name = $flag["name"];
    $element = $group->addElement("checkbox", $name)->setLabel($name." - ".$flag["description"]);
  }

  $elem = $form->addElement("text", "testtext", ["data-foo" => "bar"]);
  $elem->setLabel("test set label");
  $constants = array();
  $constants["id"] = $memberid;
  $constants["mode"] = "edit";
  $form->addDataSource(new HTML_QuickForm2_DataSource_Array($constants));

  $form->addElement("submit", "submitmember", array("value" => "update"));

  $res = handleform($form, "update", "test form");
  if ($res === True)
  {
    logentry("editmember.102: handleform(...) returned True");
    return True;
  }

  $renderer = getquickformrenderer();
  $form->render($renderer);

  $res = displayform($renderer, "edit member");
  if (PEAR::isError($res))
  {
    logentry("editmember.101: " . $res->toString());
  }
}
*/
function main()
{
  print "hello, world";
}
main();

/*
require_once("config.php");
require_once("zoidweb2.php");
require_once("bbsengine3.php");

startsession();
setcurrentsite("wwww");
setcurrentpage("test");
setcurrentaction("index");

$page = getpage("testing smoothstate transitions");
$page->addStylesheet("/skin/css/test.css");
$tmpl = getsmarty();
$options = [];
$options["pagedata"]["body"] = $tmpl->fetch("test.tmpl");
displaypage($page, $options);
exit;
*/
/*
$notify = getnotify(25);
$notify["data"] = buildnotifydata($notify);
$notify["actions"] = buildnotifyactions($notify);

// var_dump($notify["data"]);

$page = getpage("test!");

$page->addScript(STATICJAVASCRIPTURL."notify.js", ["execute" => "async"]);
$page->addStyleSheet(ENGINESKINURL."css/notify.css");

$tmpl = getsmarty();
$tmpl->assign("notify", $notify);
$options = [];
$options["pagedata"]["body"] = $tmpl->fetch($notify["template"]);
displaypage($page, $options);
exit;
*/

/*
$post = getpost(32, 0, 0);
print "<pre>".var_export($post, True)."</pre>";
*/
/*
function insert($values)
{
  var_export($values);
  return True;
}

setcurrentsite("www");
setcurrentpage("test");
setcurrentaction("view");

$page = getpage("test page");

$page->addBodyContent(fetchpageheader());
$page->addBodyContent(fetchtopbar());
$form = getquickform("test");
$res = buildaccountfieldset($form);
$res = buildmantrafieldset($form);
$res = handleform($form, "insert", "new user");
if (PEAR::isError($res))
{
  logentry("test.100: " . $res->toString());
  return PEAR::raiseError("displayform error (code: test.100)");
}

if ($res === True)
{
  logentry("test.130: handleform(...) returned True");
  return True;
}
$renderer = getquickformrenderer();
$form->render($renderer);
$res = displayform($renderer, "knock, knock neo...");
if (PEAR::isError($res))
{
  logentry("test.302: " . $res->toString());
  return PEAR::raiseError("error displaying form (code: test.302)");
}

// $page->addBodyContent(fetchpagefooter());
// displaypage($page);
*/
exit;
?>
