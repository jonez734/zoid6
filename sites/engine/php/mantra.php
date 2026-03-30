<?php

require_once("config.php");
require_once("zoid6.php");
require_once("engine.php");
require_once("session.php");
require_once("util.php");

require_once("Pager.php");

class mantra 
{
  var $dbh = null;
  
  function filterfunction($row)
  {
    $id = $row["id"];
    if (accessmantra($id) === true)
    {
      return true;
    }
    return false;
  }
  function buildactions($row)
  {
    $actions = buildmantraactions($row);
  }
  function getcount()
  {
    $sql = "select count(id) from engine.mantra";
    return $this->dbh->getOne($sql);
  }
  
  function summary()
  {
    if (accessmantra("summary") === false)
    {
      displaypermissiondenied();
      return;
    }

    setcurrentsite("engine");
//    setcurrentaction("summary");
    setreturnto(getcurrenturi());

    $perPage = 25;
        
    $params = [];
    $params["mode"] = "Sliding";
    $params["perPage"] = $perPage;
    $params["delta"] = 3;
    $params["totalItems"] = $this->getcount();
    $params["curPageSpanPre"] = "[ ";
    $params["curPageSpanPost"] = " ]";
    $params["fileName"] = "mantra-summary-%d";
    $params["append"] = false;
    $params["path"] = "/";
    
    $pageinfo = buildpageinfo("select mantra.* from engine.mantra order by dateposted desc", $params);
    $currentpageid = isset($_REQUEST["currentPageID"]) ? intval($_REQUEST["currentPageID"]) : 1;
    if ($currentpageid != 1)
    {
      setcurrentpage("mantrasummary-{$currentpageid}");
    }
    else
    {
      setcurrentpage("mantrasummary");
    }
    $res = $pageinfo["data"];
    $data = [];
    foreach ($res as $rec)
    {
      $id = $rec["id"];
      $mantra = getmantra($id);
      $data[] = $mantra;
    }
    $pageinfo["data"] = $data;

/*
    $pager = Pager::factory($params);
        
    $pageinfo = array();
    $pageinfo["totalItems"] = $params["totalItems"];
    $pageinfo["range"] = $pager->range;
    $pageinfo["numPages"] = $pager->numPages();

    $sql = "select mantra.* from engine.mantra order by dateposted desc";
    list($pageinfo["from"], $pageinfo["to"]) = $pager->getOffsetByPageId();
    $qry = $this->dbh->limitQuery($sql, null, $params["perPage"], $pageinfo["from"]-1);
    if (PEAR::isError($qry))
    {
      logentry("mantra.100: " . $qry->toString());
      displayerrorpage("Database Error (code: mantra.100)");
      return;
    }

    $pageinfo["data"] = [];
    while ($row = $qry->fetchRow())
    {
      $row["actions"] = buildmantraactions(["mantraid" => $row["id"]]);
      $pageinfo["data"][] = $row;
    }
    $qry->free();
    $pageinfo["links"] = $pager->getLinks();
*/
//    $page = getpage("mantra summary");
    $data = [];
    $data["pagetemplate"] = "mantra-summary.tmpl";
    $data["pageinfo"] = $pageinfo;
/*
    $page->addStyleSheet(SKINURL."css/mantra.css");
    $page->addStylesheet(STATICSKINURL."css/pagerinfo.css");
    $page->addBodyContent(fetchpageheader());
    $page->addBodyContent(fetchtopbar());
    $page->addBodyContent(fetchsidebar());
*/
//    $tmpl = getsmarty();
//    $tmpl->assign("pagerinfo", $pagerinfo);
//    $page->addBodyContent($tmpl->fetch("mantra-summary.tmpl"));

//    $page->addBodyContent(fetchpagefooter());
    $res = \bbsengine6\displaypage(null, $data);
    if (PEAR::isError($res))
    {
      \bbsengine6\util\logentry("mantra.200: " .$res->toString());
    }
//    $page->display();
    return $res;
  }

  function detail()
  {
    $id = isset($_REQUEST["id"]) ? intval($_REQUEST["id"]) : null;

    $bare = isset($_REQUEST["bare"]) ? true : false;
//    logentry("mantra.98: bare=".var_export($bare, true));

    \bbsengine6\setcurrentpage("mantra");
    \bbsengine6\setcurrentaction("detail");

    if (\bbsengine6\member\lib\checkflag("AUTHENTICATED") == false)
    {
      \bbsengine6\displaypermissiondenied();
      return;
    }

    $mantra = \bbsengine6\getmantra($id);
    if ($mantra === null)
    {
      \bbsengine6\util\logentry("mantra.100: getmantra(".var_export($id, true).") returned null");
      return \PEAR::raiseError("mantra not found (code: mantra.100)");
    }

    if (PEAR::isError($mantra))
    {
      \bbsengine6\util\logentry("mantra.110: " . $mantra->toString());
      return \PEAR::raiseError("mantra database error (code: mantra.110)");
    }

    $tmpl = \bbsengine6\getsmarty();
//    $tmpl->assign("mantra", $mantra);

/*
    if ($bare === true)
    {
      header("content-type: application/json; charset=utf-8");
      $data = $tmpl->fetch("mantra-detail.tmpl");
      $encode = encodejson($data);
      $callback = isset($_REQUEST["callback"]) ? $_REQUEST["callback"] : null;
      print "{$callback} ({$encode});";
      return;

    }
*/
    \bbsengine6\setreturnto(\bbsengine6\getcurrenturi()); // "/mantra-detail-{$id}");
//    clearpageprotocol();

//    $page = getpage("Mantra #{$id} detail");
/*
    $page->addBodyContent(fetchpageheader("Mantra #{$id} detail"));
    $page->addBodyContent(fetchtopbar());
    $page->addBodyContent(fetchsidebar());
    $page->addBodyContent($tmpl->fetch("mantra-detail.tmpl"));
    $page->addBodyContent(fetchpagefooter());
*/
//    $options["pagedata"]["sidebardata"] = buildsidebarmenu();
//    $options["pagedata"]["body"] = $tmpl->fetch("mantra-detail.tmpl");
    
    $data = [];
    $data["pagetemplate"] = "mantra-detail.tmpl";
    $data["mantra"] = $mantra;

    $res = bbsengine6\displaypage(null, $data);
    if (\PEAR::isError($res))
    {
      \bbsengine6\util\logentry("mantra.700: " . $res->toString());
      return PEAR::raiseError("unable to display mantra detail page (code: mantra.700)");
    }
    return;
  }

/*
  function insert($values)
  {
    $currentmemberid = getcurrentmemberid();
    if (accessmantra("add") === false)
    {
      displaypermissiondenied();
      return;
    }
    $mantra = buildmantrarecord($values);
    $mantra["postedbyid"] = $currentmemberid;
    $mantra["dateposted"] = "now()";
    $mantra["lastmodifiedbyid"] = $currentmemberid;
    $mantra["lastmodified"] = "now()";

    $this->dbh->beginTransaction();

    $res = $this->dbh->autoExecute("engine.__mantra", $mantra, MDB2_AUTOQUERY_INSERT);
    if (PEAR::isError($res))
    {
      logentry("mantra.200: " . $res->toString());
      return $res;
    }
    $this->dbh->commit();

    displayredirectpage("OK -- mantra added");

    return true;
  }
*/
/*
  function add()
  {
    $mantra = array();
    if (accessmantra("add", $mantra) === false)
    {
      logentry("permission denied (code: mantra.add.10)");
      displaypermissiondenied();
      return;
    }

    setcurrentaction("add");
    setcurrentpage("mantra-add");

    $uri = isset($_REQUEST["uri"]) ? $_REQUEST["uri"] : null; // dirname(getcurrenturi());
    logentry("mantra.300: uri=".var_export($uri, true));
    
    $form = getquickform("mantra-add");
    buildmantrafieldset($form);
    buildcaptchafieldset($form);
    buildbuttonfieldset($form, "add mantra");

    $defaults = array();
    $form->addDataSource(new HTML_QuickForm2_DataSource_Array($defaults));

    $const = array();
    $const["mode"] = "add";
    $const["memberid"] = getcurrentmemberid();
    $const["mantraid"] = $id;
    $form->addDataSource(new HTML_QuickForm2_DataSource_Array($const));

    $res = handleform($form, array($this, "insert"));
    if (PEAR::isError($res))
    {
      logentry("mantra.500: " . $res->toString());
      return PEAR::raiseError("could not handle mantra form (code: mantra.500)");
    }
    if ($res === true)
    {
      logentry("mantra.502: handleform(....) returned true");
      return true;
    }

    $renderer = getquickformrenderer();
    $form->render($renderer);
  
    $res = displayform($renderer, "add mantra");
    if (PEAR::isError($res))
    {
      logentry("mantra.504: " . $res->toString());
      return PEAR::raiseError("unable to display form (code: mantra.504)");
    }
    return $res;
  }
*/
/*
  function update($values)
  {
    $id = $values["id"];
    $mantra = buildmantrarecord($values);

    $mantra["lastmodified"] = "now()";
    $mantra["lastmodifiedbyid"] = getcurrentmemberid();
    $res = $this->dbh->autoExecute("engine.__mantra", $mantra, MDB2_AUTOQUERY_UPDATE, "id=".$this->dbh->quote($id, "integer"));
    if (PEAR::isError($res))
    {
      logentry("mantra.400: ". $res->toString());
      return PEAR::raiseError("Database Error (code: mantra.400)");
    }
    displayredirectpage("OK -- mantra updated");

    return true;
  }
*/
/*
  function edit()
  {
    $id = isset($_REQUEST["id"]) ? intval($_REQUEST["id"]) : null;

    $mantra = getmantra($id);
    if (PEAR::isError($mantra))
    {
      logentry("mantra.500: " . $mantra->toString());
      return PEAR::raiseError("Database Error (code: mantra.500)");
    }
    
    if ($mantra === null)
    {
      logentry("mantra.510: mantra id ".var_export($id, true)." returned null.");
      return PEAR::raiseError("Input Error (code: mantra.510)");
    }

    if (accessmantra("edit", $mantra) === false)
    {
      displaypermissiondenied();
      return;
    }

    setcurrentaction("edit");
    
    $form = getquickform("mantra-edit");
    buildmantrafieldset($form);
    buildbuttonfieldset($form, "edit mantra");

    $defaults = $mantra;

    $form->addDataSource(new HTML_QuickForm2_DataSource_Array($defaults));

    $const = array();
    $const["mode"] = "edit";
    $const["memberid"] = getcurrentmemberid();
    $const["id"] = $id;
    
    $form->addDataSource(new HTML_QuickForm2_DataSource_Array($const));

    $res = handleform($form, array($this, "update"));
    if (PEAR::isError($res))
    {
      logentry("mantra.400: " . $res->toString());
      return PEAR::raiseError("unable to handle form (code: mantra.400)");
    }
    if ($res === true)
    {
      return true;
    }
  
    $renderer = getquickformrenderer();
    $form->render($renderer);
    $rendered = $renderer->toArray();
    $res =  displayform($renderer, "edit mantra");
    if (PEAR::isError($res))
    {
      logentry("mantra.402: " . $res->toString());
      return PEAR::raiseError("unable to display form (code: mantra.402)");
    }

    return true;
  }
*/
  function main()
  {
    \bbsengine6\session\start();
    
    $bare = isset($_REQUEST["bare"]) ? true : false;
    
    if ($bare === false)
    {
      \bbsengine6\setcurrentsite("www");
      \bbsengine6\setcurrentpage("mantra");
    }
    
    $this->dbh = \bbsengine6\database\connect(\config\SYSTEMDSN);
    
    $mode = isset($_REQUEST["mode"]) ? $_REQUEST["mode"] : null;
    
    switch ($mode)
    {
      case "detail":
      {
        $r = $this->detail();
        break;
      }
/*
      case "edit":
      {
        $r = $this->edit();
        break;
      }
      case "add":
      {
        $r = $this->add();
        break;
      }
*/
      case "summary":
      {
        $r = $this->summary();
        break;
      }
      default:
      {
        $r = PEAR::raiseError("invalid mode (code: mantra.600)");
        break;
      }
    }
//    $this->dbh->disconnect();
    return $r;
  }
};

$a = new mantra();
$b = $a->main();
if (PEAR::isError($b))
{
  \bbsengine6\util\logentry("mantra.999: " . $b->toString());
  \bbsengine6\displayerrorpage($b->getMessage());
  exit;
}
