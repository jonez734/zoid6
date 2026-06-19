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
      displayerrorpage("page not found", 404);
      return;
    }
    $ext = isset($_REQUEST["ext"]) ? $_REQUEST["ext"] : null;

    // confirm name is alphanumeric 
    // prepend "/handbook/" to all names
    setreturnto(getcurrenturi());
    setcurrentsite(LOGENTRYPREFIX);

    $filename = basename($name);
    logentry("page.130: filename=".var_export($filename, True));

    $res = $this->bodyfilename($name);
    if ($res === null)
    {
      displayerrorpage("page not found", 404);
      return;
    }

    $tmpl = getsmarty();

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
      $body = $tmpl->fetch("{$name}.tmpl");
    }
    else
    {
      if (is_readable($bodyfilename) === False)
      {
        displayerrorpage("page not readable", 403);
        return;
      }
      $tmpl->assign("body", file_get_contents($bodyfilename));
      $body = $tmpl->fetch("file.tmpl");
    }

    setcurrentpage($name);
    setcurrentaction("view");

    $pagedata = [];
    $pagedata["body"] = $body;
    $pagedata["sidebardata"] = buildsidebarmenu();
    $options = [];
//    $options["pagedata"] = $pagedata;
    $options["body"] = $body;

    $page = getpage($name, $options);
    if (is_readable(SKINURL."css/{$name}"))
    {
      $page->addStyleSheet(SKINURL."css/{$name}");
    }
    displaypage(null, $options);
    return;
  }
};

$a = new page();
$b = $a->main();
if ($b === false)
{
  displayerrorpage($b->getMessage());
  exit;
}
