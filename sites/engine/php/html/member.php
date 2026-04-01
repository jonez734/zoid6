<?php

/**
 * contains member class which handles some account-related functions
 *
 * @package bbsengine2
 */

/**
 * import application config file 
 */
require_once("config.php");
require_once("database.php");
require_once("engine.php");

/**
 * contains member management functions (add, update, delete, view, summary)
 *
 * @package bbsengine3
 */
class member
{
  var $dbh = null;

  function gettotalposts($memberid)
  {
    $sql = "select count(id) from sophia.post where postedbyid=?";
    $dat = array($memberid);
//    $dbh = \bbsengine6\database\connect(\config\SYSTEMDSN);
    $stmt = $this->dbh->prepare($sql);
    $stmt->execute($dat);
    $res = $stmt->fetchColumn();
    return $res;
  }

  function gettotallinks($memberid)
  {
    $sql = "select count(id) from vulcan.link where postedby=?";
    $dat = array($memberid);
    $stmt = $this->dbh->prepare($sql);
    $stmt->execute($dat);
    if ($stmt->rowCount() == 0)
    {
      return 0;
    }
    $res = $stmt->fetchOne();
    return $res;
    
  }

  function getcount()
  {
    $sql = "select count(*) from engine.member";
    $stmt = $this->dbh->prepare($sql);
    $stmt->execute();
    $res = $stmt->fetchOne();
    return $res;
  }

  function getmemberform()
  {
    $currentmemberid = getcurrentmemberid();
    
    $form = getquickform("bbsengine4-member");
    if (PEAR::isError($form))
    {
      logentry("member.100: " . $form->toString());
      return $form;
    }

    return $form;
  }

  function buildmemberactions($id)
  {
    $actions = array();
    if (accessmember("detail", array("id" => $id)) === true && getcurrentaction() !== "detail")
    {
      $actions[] = array("href" => "/member-detail-{$id}", "title" => "detail");
    }

    if (accessmember("edit", array("id" => $id)) === true && getcurrentaction() !== "edit")
    {
      $actions[] = array("href" => "/member-edit-{$id}", "title" => "edit");
    }
    return $actions;
    
  }

  function detail()
  {
    $id = isset($_REQUEST["id"]) ? intval($_REQUEST["id"]) : null;
    
//    logentry("member.detail.100: cookie=".var_export($_COOKIE, true));
    
    if (accessmember("detail", array("id" => $id)) === false)
    {
      displaypermissiondenied();
      return;
    }

    $member = getmember($id);
    if (PEAR::isError($member))
    {
      displayerrorpage("Database error (code: member.view.1)");
      logentry("member.view.1: " . $member->toString());
      return;
    }

    if ($member === null)
    {
      displayerrorpage("Database error (code: member.view.2)");
      logentry("member.view.2: getmember returned null");
      return;
    }

    setcurrentaction("detail");
    
    $allflags = $this->buildallflags($id);
    $flags = array();
    foreach ($allflags as $flag)
    {
      if ($flag["value"] === true)
      {
        $flags[] = $flag["name"];
      }
    }

    $totalposts = gettotalposts();
    $totallinks = gettotallinks();
    $memberposts= getmemberposts($id);
    if (PEAR::isError($memberposts))
    {
      logentry("member.100: " . $memberposts->toString());
      $memberposts = null;
    }
    $memberlinks= getmemberlinks($id);
    if ($memberposts > 0)
    {
      $postspercent = 100.0 - (floatval($totalposts) / floatval($memberposts));
    }
    else
    {
      $postspercent = 0.00;
    }
    if ($memberlinks > 0)
    {
      $linkspercent = 100.0 - (floatval($totallinks) / floatval($memberlinks));
    }
    else
    {
      $linkspercent = 0.00;
    }
    $member["id"] = $id;
    $member["flags"] = $flags; // implode(", ", $flags);
    $member["actions"] = $this->buildmemberactions($id);
    $member["totalposts"] = $totalposts;
    $member["totallinks"] = $totallinks;
    $member["totalmemberposts"] = $memberposts;
    $member["totalmemberlinks"] = $memberlinks;
    $member["postspercent"] = $postspercent;
    $member["linkspercent"] = $linkspercent;


    $tooltip = isset($_REQUEST["tooltip"]) ? true : false;
    
    logentry("member.500: tooltip=".var_export($tooltip, true)." currentmemberid=".var_export(getcurrentmemberid(), true));
    if ($tooltip === true)
    {
      header("content-type: application/json; charset=utf-8");
//      header("content-type: text/plain; charset=utf-8");
      $tmpl = getsmarty();
      $tmpl->assign("member", $member);
      $fragment = $tmpl->fetch("member-detail.tmpl");
      $data = [];
      $data["fragment"] = $fragment;
      $encode = encodejson($data);
      $callback = isset($_REQUEST["callback"]) ? $_REQUEST["callback"] : null;
      if ($callback === null)
      {
        print "{$encode}";
      }
      else
      {
        print "{$callback} ({$encode});";
      }
      return;
    }
 
 //   $membername = $member["name"];
    
//    $page = getpage("account detail - {$membername}");
    $data = [];
    $data["member"] = $member;
    $data["pagetemplate"] = "page-member-detail.tmpl";
    displaypage(null, $data);
    return;
  }

  function buildallflags($memberid)
  {
    $sql = "select upper(flag.name) as name, coalesce(mmf.value, flag.defaultvalue) as value, flag.description from engine.flag left outer join engine.map_member_flag as mmf on flag.name = mmf.name and mmf.memberid=?";
    $dat = array($memberid);
    $res = $this->dbh->getAll($sql, null, $dat, array("integer"));
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
      
      $flag["value"] = ($rec["value"] === "t") ? true : false;
      $allflags[] = $flag;
    }
    
    return $allflags;
  }

  function addmemberflagfieldset($form, $memberid)
  {
    $fieldset = $form->addElement("fieldset", "flagfieldset")->setLabel("Flags");
    $group = $fieldset->addElement("group", "flags")->setSeparator(["<br />"]);

    $allflags = $this->buildallflags($memberid);
    foreach($allflags as $flag)
    {
      $name = $flag["name"];
      $element = $group->addCheckbox($name)->setLabel($name." - ".$flag["description"]); // addElement("checkbox", $name)->setLabel($name." - ".$flag["description"]);
    }
    return;
  }

  function edit()
  {
    $memberid = isset($_REQUEST["id"]) ? intval($_REQUEST["id"]) : null;
    $currentmemberid = getcurrentmemberid();
    
    logentry("member.210: memberid=".var_export($memberid, true));

    if (accessmember("edit", array("id" => $memberid)) === false)
    {
      displaypermissiondenied();
      return;
    }

    $member = getmember($memberid);
    if (PEAR::isError($member))
    {
      displayerrorpage("Database Error (code: member.200)");
      logentry("member.200: " . $member->toString());
      return;
    }
    
    if ($member === null)
    {
      displayerrorpage("input error (code: member.202)");
      logentry("member.202: getmember(".var_export($memberid, true).") returned null.");
      return;
    }

    setcurrentaction("edit");

    $form = $this->getmemberform();
    $res = buildmemberfieldset($form);
    $res = buildchangepasswordfieldset($form, array("memberid" => $memberid));
    if (accessmember("editflags", array("id" => $memberid)) === true)
    {
      $this->addmemberflagfieldset($form, $memberid);
      logentry("memberedit.100: -- calling buildallflags");
      $allflags = $this->buildallflags($memberid);
      if (PEAR::isError($allflags))
      {
        logentry("memberedit.110: " . $allflags->toString());
        displayerrorpage("Database Error (memberedit.110)");
        return;
      }
      $flags = array();
      foreach ($allflags as $flag)
      {
        $name = $flag["name"];
        $value = $flag["value"];
        $flags[$name] = $value;
      }
      $data = array();
      $data["flags"] = $flags;
      $form->addDataSource(new HTML_QuickForm2_DataSource_Array($data));
    }

    $form->addDataSource(new HTML_QuickForm2_DataSource_Array($member));

    $constants = array();
    $constants["id"] = $memberid;
    $constants["mode"] = "edit";
    $form->addDataSource(new HTML_QuickForm2_DataSource_Array($constants));

    $form->addElement("submit", "submitmember", array("value" => "update"));
    
    $res = handleform($form, array($this, "update"), "edit member");
    if ($res === true)
    {
      logentry("editmember.102: handleform(...) returned true");
      return true;
    }

    $renderer = getquickformrenderer();
    $form->render($renderer);

    $res = displayform($renderer, "edit member");
    if (PEAR::isError($res))
    {
      logentry("editmember.101: " . $res->toString());
    }
    return $res;
 
/*
    $issubmitted = $form->isSubmitted();
    $validate = $form->validate();
    
    if ($issubmitted === true)
    {
      $value = $form->getValue();
      $pageprotocol = isset($value["pageprotocol"]) ? $value["pageprotocol"] : standard;
      setpageprotocol($pageprotocol);
    }
    else
    {
      clearpageprotocol();
    }

    if ($validate === true)
    {
      $form->toggleFrozen(true);
      $form->addRecursiveFilter("trim");
      $form->addRecursiveFilter("strip_tags");
      $values = $form->getValue();
      $res = $this->update($values);
      if (PEAR::isError($res))
      {
        logentry("editmember.10: " . $res->toString());
        return $res;
      }
      return;
    }

    $renderer = getquickformrenderer(); 
    $form->render($renderer);
  
    $tmpl = getsmarty();
    $tmpl->assign("form", $renderer->toArray());

    $bodycontent = array();
    $bodycontent[] = fetchpageheader("Edit Member");
    $bodycontent[] = fetchtopbar();
    $bodycontent[] = fetchsidebar();
    $bodycontent[] = $tmpl->fetch("form.tmpl");
    $bodycontent[] = fetchpagefooter();

    $pageprotocol = getpageprotocol();
    
    if ($pageprotocol === "standard")
    {
      $page = getpage("Edit Member");
      $page->addStyleSheet(ENGINESKINURL . "css/form.css");
      $page->addScript(STATICJAVASCRIPTURL."form.js");
      $page->addBodyContent($bodycontent);
      $page->display();
    }
    else if ($pageprotocol === "enhanced")
    {
      $page = array();
      $page["body"] = implode($bodycontent);
      
      print encodejson(array("page" => $page));
    }
*/
    return;
  }
  
  function update($values)
  {
    $memberid = isset($values["id"]) ? intval($values["id"]) : null;

    $currentmemberid = getcurrentmemberid();
    
    logentry("update.100: memberid=".var_export($memberid, true));
    if (accessmember("edit", array("id" => $memberid)) === false)
    {
      displaypermissiondenied();
      return;
    }

    $member = buildmemberrecord($values);
    
    $res = $this->dbh->beginTransaction();
    if (PEAR::isError($res))
    {
      displayerrorpage("Database Error. (code: updatemember.100)");
      logentry("updatemember.100: " . $res->toString());
      $this->dbh->rollback();
      return;
    }

//    logentry("update member record...");
    if (accessmember("edit", array("id" => $memberid)) === true)
    {
      $res = $this->dbh->autoExecute("engine.__member", $member, MDB2_AUTOQUERY_UPDATE, "id=" . $this->dbh->quote($memberid, "integer"));
      if (PEAR::isError($res))
      {
        logentry("updatemember.110: " . $res->toString());
        displayerrorpage("Database Error (code: updatemember.110)");
        return;
      }
    }
    
//    logentry("update member flags...");
    if (accessmember("editflags", array("id" => $memberid)) === true)
    {
      $allflags = $this->buildallflags($memberid);
      if (PEAR::isError($allflags))
      {
        logentry("updatemember.120: " . $allflags->toString());
        return;
      }
      
      $flags = array();
      foreach ($allflags as $flag)
      {
        $name = $flag["name"];
        $value = isset($values["flags"][$name]) ? true : false;
//        logentry("calling setflag...");
        $res = setflag($name, $value, $memberid);
        if (PEAR::isError($res))
        {
          logentry("updatemember.122: " . $res->toString());
          $this->dbh->rollback();
          break;
        }
      }
    }

    $plaintext = isset($values["newPassword"]) ? $values["newPassword"] : null;
    $plaintext = trim($plaintext);
    if ($plaintext != "")
    {
      $hashedpassword = hashpassword($plaintext);
      setpassword($memberid, $hashedpassword);
    }
    $res = $this->dbh->commit();
    displayredirectpage("OK -- Account Details Updated");
    return true;
  }
  
  function delete()
  {
    if (!flag("ADMIN"))
    {
      displaypermissiondenied();
      logentry("member.delete: permission denied.");
      return;
    }
    
    $memberid = isset($_REQUEST["id"]) ? intval($_REQUEST["id"]) : null;
    $res = $this->dbh->autoExecute("member", null, MDB2_AUTOQUERY_DELETE, "id=" . $this->dbh->quote($memberid, "integer"));
    if (PEAR::isError($res))
    {
      displayerrorpage("Database error during delete operation");
      logentry("member.delete: " . $res->toString());
      return;
    }
    displayredirectpage("OK -- account deleted");
    return;
  }

  function summary()
  {
    if (accessmember("summary") === false)
    {
      displaypermissiondenied();
      return;
    }

    setcurrentpage("summary");
    
    $perPage = 10;
        
    $params = array();
    $params["mode"] = "Sliding";
    $params["perPage"] = $perPage;
    $params["delta"] = 3;
    $params["totalItems"] = $this->getcount();
    $params["curPageSpanPre"] = "[ ";
    $params["curPageSpanPost"] = " ]";
        
    $pager = Pager::factory($params);
        
    $pageinfo = array();
    $pageinfo["totalItems"] = $params["totalItems"];
    $pageinfo["range"] = $pager->range;
    $pageinfo["numPages"] = $pager->numPages();

    $sql = "select member.* from engine.member order by fullname asc";
    list($pageinfo["from"], $pageinfo["to"]) = $pager->getOffsetByPageId();
    $qry = $this->dbh->limitQuery($sql, null, $params["perPage"], $pageinfo["from"]-1);
    if (PEAR::isError($qry))
    {
      logentry("member.summary.0: " . $qry->toString());
      displayerrorpage("Database Error (code: member.summary.0)");
      return;
    }

    $pageinfo["data"] = array();
    while ($row = $qry->fetchRow())
    {
      $pageinfo["data"][] = $row;
    }
    $qry->free();
    $pageinfo["links"] = $pager->getLinks();

    $page = getpage("Website Member List");
    $page->addStyleSheet("/skin/css/member.css");
    $page->addStyleSheet("/skin/css/pagelinks.css");
    $page->addStyleSheet("/skin/css/actions.css");
    $page->addBodyContent(fetchheader());
    $tmpl = getsmarty();
    $tmpl->assign("pageinfo", $pageinfo);
    $page->addBodyContent($tmpl->fetch("member-summary.tmpl"));
//    displayheader("Account List", "member.css");

//    $s = &getsmarty();
//    $s->assignByRef("pageinfo", $pageinfo);
//    $s->display("member-summary.tmpl");
    $page->addBodyContent(fetchpagefooter());
    $page->display();
//    displayfooter();
    return;
  }
  
/*
  function add()
  {
    if (accessmember("add", array()) === false)
    {
      displaypermissiondenied();
      return;
    }

    $memberid = null;

    setcurrentaction("add");

    $form = $this->getmemberform();
    $res = buildmemberfieldset($form);
    $res = buildnewpasswordfieldset($form);
    if (accessmember("editflags", array("id" => $memberid)) === true)
    {
      $this->addmemberflagfieldset($form, $memberid);
    }

    logentry("memberadd.100: -- calling buildallflags");
    $allflags = $this->buildallflags($memberid);
    if (PEAR::isError($allflags))
    {
      logentry("memberadd.110: " . $allflags->toString());
      displayerrorpage("Database Error (memberadd.110)");
      return;
    }
    $flags = array();
    foreach ($allflags as $flag)
    {
      $name = $flag["name"];
      $value = $flag["value"];
      $flags[$name] = $value;
    }

    $data = array();
    $data["flags"] = $flags;
    $form->addDataSource(new HTML_QuickForm2_DataSource_Array($data));

//    $form->addDataSource(new HTML_QuickForm2_DataSource_Array($member));

    $constants = array();
    $constants["id"] = $memberid;
    $constants["mode"] = "add";
    $form->addDataSource(new HTML_QuickForm2_DataSource_Array($constants));

    $form->addElement("submit", "submitmember", array("value" => "update"));

    $issubmitted = $form->isSubmitted();
    $validate = $form->validate();
    
    if ($issubmitted === true)
    {
      $value = $form->getValue();
      $pageprotocol = isset($value["pageprotocol"]) ? $value["pageprotocol"] : standard;
      setpageprotocol($pageprotocol);
    }
    else
    {
      $pageprotocol = clearpageprotocol();
    }

    if ($validate === true)
    {
      $form->toggleFrozen(true);
      $form->addRecursiveFilter("trim");
      $form->addRecursiveFilter("strip_tags");
      $values = $form->getValue();
      $res = $this->insert($values);
      if (PEAR::isError($res))
      {
        logentry("addmember.10: " . $res->toString());
        return $res;
      }
      return;
    }

    $renderer = getquickformrenderer(); 
    $form->render($renderer);
  
    $tmpl = getsmarty();
    $tmpl->assign("form", $renderer->toArray());

    $bodycontent = array();
    $bodycontent[] = fetchpageheader("Add Member");
    $bodycontent[] = fetchtopbar();
    $bodycontent[] = fetchsidebar();
    $bodycontent[] = $tmpl->fetch("form.tmpl");
    $bodycontent[] = fetchpagefooter();

    $pageprotocol = getpageprotocol();
    logentry("member.510: pageprotocol=".var_export($pageprotocol, true));
    
    if ($pageprotocol === "standard")
    {
      $page = getpage("Add Member");
      $page->addStyleSheet(ENGINESKINURL . "css/form.css");
      $page->addScript(STATICJAVASCRIPTURL."form.js");
      foreach ($bodycontent as $fragment)
      {
        $page->addBodyContent($fragment);
      }

      $page->display();
    }
    else if ($pageprotocol === "enhanced")
    {
      $page = array();
      $page["body"] = implode($bodycontent);
      
      print encodejson(array("page" => $page));
    }
    return;
  }
  
  function insert($values)
  {
    var_export($values);
    return;
  }
*/  
  function main()
  {
    $this->dbh = \bbsengine6\database\connect(\config\SYSTEMDSN);
    if (PEAR::isError($this->dbh))
    {
      logentry("member: " . $this->dbh->toString());
      displayerrorpage("Database Connect Error");
      return;
    }
    
    startsession();

    setcurrentsite("www");
    setcurrentpage("member");

//    setreturnto(SITEURL);

    $mode = isset($_REQUEST["mode"]) ? $_REQUEST["mode"] : null;
    switch ($mode)
    {
/*
      case "add":
      {
        $res = $this->add();
        break;
      }
      case "edit":
      {
        $res = $this->edit();
        break;
      }
*/
      case "detail":
      {
        $res = $this->detail();
        break;
      }
/*
      case "delete":
      {
        $res = $this->delete();
        break;
      }
*/
/*
      case "summary":
      {
        $res = $this->summary();
        break;
      }
*/
      default:
        displayerrorpage("unknown mode");
        break;
    }
    
    $this->dbh->disconnect();
    return;
  }
}

$m = new member();
$m->main();

?>
