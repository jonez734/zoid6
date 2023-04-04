<?php

require_once("config.php");
require_once("zoidweb2.php");
require_once("bbsengine3.php");

require_once("HTML/QuickForm2.php");
require_once("HTML/QuickForm2/Renderer.php");
require_once("HTML/QuickForm2/Renderer/Array.php");

startsession();

$memberid = 403;

$dbh = dbconnect(SYSTEMDSN);

$sql = "select upper(flag.name) as name, coalesce(mmf.value, flag.defaultvalue) as value, flag.description from engine.flag left outer join engine.map_member_flag as mmf on flag.name = mmf.name and mmf.memberid=?";
$dat = array($memberid);
$res = $dbh->getAll($sql, null, $dat, array("integer"));
if (PEAR::isError($res))
{
  logentry("_buildflagarray.10: " . $res->toString());
  exit;
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

$form = new HTML_QuickForm2("checkboxes");

$flags = array();
foreach ($allflags as $flag)
{
  $name = $flag["name"];
  $value = $flag["value"];
  $flags[$name] = $value;
}

$defaults = array();
$defaults["flags"] = $flags;

$form->addDataSource(new HTML_QuickForm2_DataSource_Array($defaults));

$fieldset = $form->addElement("fieldset", "flagfieldset")->setLabel("Flags");
$group = $fieldset->addElement("group", "flags")->setSeparator("<br />");

foreach($allflags as $flag)
{
  $name = $flag["name"];
  $element = $group->addElement("checkbox", $name)->setLabel($name." - ".$flag["description"]);
}

$form->addElement("submit", "test", array("value" => "testing"));

if ($form->isSubmitted())
{
  $values = $form->getValue();
  print "<pre>".var_export($values, True)."</pre><br />";
  $formflags = $values["flags"];
  foreach ($allflags as $flag)
  {
    $name = $flag["name"];
    $value = isset($formflags[$name]) ? True : False;
    print "name=".var_export($name, True)." value=".var_export($value, True)."<br />";//." flag=".var_export($flag, True)."<br />";
    $res = setflag($name, $value, $memberid);
    if (PEAR::isError($res))
    {
      logentry("checkboxes.100: " . $res->toString());
      break;
    }
  }
  exit;
}

$renderer = HTML_QuickForm2_Renderer::factory("array");
$form->render($renderer);

$page = getpage("checkboxes");
$page->addStyleSheet(SKINURL . "css/form.css");
$page->addBodyContent(fetchpageheader("checkboxes"));

$page->addBodyContent(fetchtopbar());
$page->addBodyContent(fetchsidebar(buildsidebarmenu()));
  
$tmpl = getsmarty();
$tmpl->assign("form", $renderer->toArray());

$page->addBodyContent($tmpl->fetch("form.tmpl"));
$page->addBodyContent(fetchpagefooter());
    
$page->display();

//print "<pre>".var_export($form->render($renderer), True)."</pre>";

?>
