<?php

require_once("config.php");
require_once("zoidweb2.php");
require_once("bbsengine3.php");

$form = getquickform("checkboxtest");
$el = $form->addElement("checkbox", "checkboxtest", "test checkbox");
$el->addRule("required", "the checkbox must be checked");
$form->addElement("submit", "submit", "submit");

if ($form->isSubmitted() && $form->validate())
{
  print "validated";
  exit;
}
print $form;
//$renderer = getquickformrenderer();
//print "<pre>".var_export($form->render($renderer), True)."</pre>";

//$form->render($renderer);
//print $renderer->toArray();

?>
