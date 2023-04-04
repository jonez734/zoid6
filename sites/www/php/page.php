<?php

require_once("config.php");
require_once("zoidweb4.php");
require_once("bbsengine4.php");

class page
{
  function bodyfilename($name)
  {
    $tmpl = getsmarty();
    if ($tmpl->templateExists($name.".tmpl"))
    {
      return ["template", $name.".tmpl"];
    }

    if (file_exists(DOCUMENTROOT.$name.".md") && is_readable(DOCUMENTROOT.$name.".md"))
    {
      return ["markdown", DOCUMENTROOT.$name.".md"];
    }

    if (file_exists(DOCUMENTROOT.$name.".txt") && is_readable(DOCUMENTROOT.$name.".txt"))
    {
      return ["plain", DOCUMENTROOT.$name.".txt"];
    }
    return null;
  }

  function main()
  {
    startsession();

    $name = isset($_REQUEST["name"]) ? $_REQUEST["name"] : null;
    if ($name === null)
    {
      logentry("page.200: 'name' is null");
      displayerrorpage("page not found (null)", 404);
      return;
    }
    $ext = isset($_REQUEST["ext"]) ? $_REQUEST["ext"] : null;

    // confirm name is alphanumeric
    // prepend "/handbook/" to all names
    setreturnto(getcurrenturi());
    setcurrentsite(LOGENTRYPREFIX);

    $filename = basename($name);
    logentry("page.130: filename=".var_export($filename, true));

    $tmpl = getsmarty();
    logentry("tmpl.getTemplateDir()=".var_export($tmpl->getTemplateDir(), true));

    $res = $this->bodyfilename($name);
    if ($res === null)
    {
      displayerrorpage("page not found name=".var_export($name, true)." include_path=", 404);
      return;
    }


    list($mode, $bodyfilename) = $res;

    if ($ext === "md")
    {
      $mode = "markdown";
    }
    else if ($ext === "txt")
    {
      $mode = "plain";
    }
    else if ($ext === null)
    {
      $mode = "markdown"; // default
    }

    $tmpl->assign("mode", $mode);
    if ($mode === "template")
    {
      $templatename = "{$name}.tmpl";
      if ($tmpl->template_exists($templatefilename) === False)
      {
        displayerrorpage("template not readable", 404);
        return;
      }
//      $content = $tmpl->fetch("{$name}.tmpl");
    }

    logentry("zoidweb3.page.100: name=".var_export($name, true));
    setcurrentpage($name);
    setcurrentaction("view");

    $data = [];
    $data["pagetemplate"] = "{$name}.tmpl";
    $data["name"] = $name;
    
    $page = getpage($name);
/*
    if (is_readable(SKINURL."css/{$name}"))
    {
      $page->addStyleSheet(SKINURL."css/{$name}");
    }
*/
    displaypage($page, $data);
    return;
  }
};

$a = new page();
$b = $a->main();
if (PEAR::isError($b))
{
  displayerrorpage($b->getMessage());
  exit;
}
