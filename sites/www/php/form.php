<?php

require_once("config.php");
require_once("zoidweb2.php");
require_once("bbsengine4.php");

class form 
{
  function main()
  {
    $form = new HTML_QuickForm2("testform", "post", null, False);

    $element = $form->addText("foo")->setLabel("Your Name")->addRule("required", "Please enter your name");

    $form->addElement("submit", "blah", array("value" => "Send!"));
    
    if ($form->validate() && $form->isSubmitted())
    {
      print "form validated";
      return;
    }
    
    $renderer = HTML_QuickForm2_Renderer::factory("array")->setOption(array("group_errors" => True));
    $form->render($renderer);
    print "toArray()=".var_export($renderer->toArray(), True);
//    print "value of renderer->group_errors: ".var_export($renderer->getOption("group_errors"), True);
    
    $tmpl = getsmarty();
    $tmpl->assign("form", $renderer->toArray());
    $tmpl->display("form.tmpl");
    return;
  }
};

$a = new form();
$b = $a->main();
exit;
