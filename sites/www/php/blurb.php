<?php

require_once("/srv/www/bbsengine6/php/bootstrap.php");

require_once("config.php");

require_once("engine.php");
require_once("session.php");
require_once("database.php");

require_once("zoid6.php");

require_once("/srv/www/bbsengine6/php/blurb.php");

class blurbpage
{
  function main()
  {
    \bbsengine6\session\start();

    \bbsengine6\setcurrentsite(\config\SITENAME);
    \bbsengine6\setcurrentpage("blurb");
    \bbsengine6\setreturnto(SITEURL);

    $mode = $_GET["mode"] ?? "list";
    $id = isset($_GET["id"]) ? (int)$_GET["id"] : null;
    $page = isset($_GET["page"]) ? (int)$_GET["page"] : 1;
    $limit = 20;
    $offset = ($page - 1) * $limit;

    if ($mode === "view" && $id !== null)
    {
      $this->view($id);
    }
    else
    {
      $this->list($offset, $limit, $page);
    }

    return;
  }

  function list(int $offset, int $limit, int $page)
  {
    \bbsengine6\setcurrentaction("blurb-list");

    $blurbs = \bbsengine6\blurb\getlist($offset, $limit);
    $total = \bbsengine6\blurb\getcount();
    $totalpages = ceil($total / $limit);

    $data = [];
    $data["blurbs"] = $blurbs;
    $data["page"] = $page;
    $data["totalpages"] = $totalpages;
    $data["total"] = $total;
    $data["limit"] = $limit;

    \bbsengine6\displaypage($data, "blurb.tmpl");

    return;
  }

  function view(int $id)
  {
    \bbsengine6\setcurrentaction("blurb-view");

    $blurb = \bbsengine6\blurb\getbyid($id);

    if ($blurb === null)
    {
      \bbsengine6\displaypage([], "permissiondenied.tmpl");
      return;
    }

    $breadcrumbs = \bbsengine6\blurb\buildbreadcrumblist($id);

    $data = [];
    $data["blurb"] = $blurb;
    $data["breadcrumbs"] = $breadcrumbs;
    \bbsengine6\util\logentry("inside sites/www/php/blurb.php")

    \bbsengine6\displaypage($data, "blurb.tmpl");

    return;
  }
};

$a = new blurbpage();
$a->main();

?>
