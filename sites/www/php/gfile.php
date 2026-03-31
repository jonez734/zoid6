<?php

//
// $Id: gfile.php 2138 2012-02-14 22:20:39Z jam $
//

//
// Copyright (c) 2004-2007 Zoid Technologies, LLC. All Rights Reserved.
//

/**
 * class to process gfile table
 *
 * @since 1.0.0
 * @copyright Copyright (c) 2004-2010 Zoid Technologies. All Rights Reserved.
 * @package bbsengine
 */

require_once("config.php");
require_once("zoidweb4.php");
require_once("bbsengine4.php");

require_once("Pager/Pager.php");

class gfile
{
    var $dbh = False;
    
    function &getform($submitmode, $id=0)
    {
        $form = getquickform("bbsengine-gfile");
        if (PEAR::isError($form))
        {
          logentry("gfile.getform.0: " . $form->toString());
          return $form;
        }
        
        $form->addElement("header", "header", "gfile");

        $form->addElement("hidden", "id", $id);

        $form->addElement("hidden", "mode", $submitmode);
        
        $form->addElement("text", "title", "title");
        $e = &$form->getElement("title");
        $e->setSize(80);
        $e->setMaxLength(255);
        $form->addRule("title", "title is a required field", "required");
        
        $form->addElement("textarea", "body", "body");
        $e = &$form->getElement("body");
        $e->setCols(80);
        $e->setRows(20);
        $form->addRule("body", "body is a required field", "required");

        $form->addElement("text", "keywords", "tags");
        $e = &$form->getElement("keywords");
        $e->setSize(80);
        $e->setMaxLength(255);
        
        $dbh = &dbconnect(SYSTEMDSN);
        if (PEAR::isError($dbh))
        {
            logentry("www: gfile: getform: " . $dbh->toString());
        }
        else
        {
            $sigarray = getsigarray($dbh);
            $form->addElement("select", "sigid", "sig", $sigarray);
            $e = &$form->getElement("sigid");
            $e->setSize(1);
            $e->setMultiple(False);
            $form->addRule("sigid", "sig is a required field", "required");
        }
        
        $options = array();
        $options["PUBLIC"] = "public access";
        $options["AUTHENTICATED"] = "any authenticated user";
        $options = array_merge($options, getsystemflaglist());

        $form->addElement("select", "viewpermission", "view permission", $options);
        
        $form->addElement("textarea", "summary", "summary");
        $e = &$form->getElement("summary");
        $e->setCols(80);
        $e->setRows(20);
        
        $buttons = array();
        $buttons[] = &HTML_QuickForm::createElement("submit", null, $submitmode);
        $buttons[] = &HTML_QuickForm::createElement("reset",  null, "reset");
        $form->addGroup($buttons);
        
        return $form;
    }
    
    function get($id)
    {
        $sql = <<<SQL
select www.gfile.*, 
 coalesce(s.name, 'top') as signame, 
 coalesce(s.title, 'top') as sigtitle,
 m1.name as postedbyname,
extract(epoch from www.gfile.dateposted) as datepostedepoch,
extract(epoch from www.gfile.lastmodified) as lastmodifiedepoch
from www.gfile
  left outer join sig as s on (s.id = www.gfile.sigid)
  left outer join member as m1 on (m1.id = www.gfile.authorid)
where www.gfile.id=?
limit 1;
SQL;
        $res = $this->dbh->getRow($sql, null, array($id));
        if (PEAR::isError($res))
        {
            logentry("www: gfile: get: select returned error " . $res->toString());
            return False;
        }
        if ($res == null)
        {
            logentry("www: gfile: get: select returned null");
            return False;
        }
        
        if (count($res) == 0)
        {
            logentry("www: gfile: get: unable to retrieve gfile with id '{$id}'");
            return False;
        }

        $currentmemberid = getcurrentmemberid();

        $gfile = $res;

        $actions = array();
        $actions["view"] = accessgfile("view", $gfile, $currentmemberid);
        $actions["edit"] = accessgfile("edit", $gfile, $currentmemberid);
        $actions["delete"] = accessgfile("delete", $gfile, $currentmemberid);
        $gfile["action"] = $actions;
        return $gfile;
    }

    function getcount()
    {
		$sql = "select count(*) from www.gfile";
		$res = $this->dbh->getOne($sql);
		if (PEAR::isError($res))
		{
		    logentry("gfile: getcount: " . $res->toString());
		    return False;
		}

		return $res;
    }

    function view()
    {
        $id = isset($_GET["id"]) ? intval($_GET["id"]) : null;
        if ($id === null)
        {
            displayerrormessage("gfile: view: must specify id");
            return False;
        }

        $gfile = $this->get($id);
        if (PEAR::isError($gfile))
        {
            logentry("gfile.10: " . $gfile->toString());
            return PEAR::raiseError("input error (code: gfile.10)");
        }

        if (accessgfile("view", $gfile, getcurrentmemberid()) === False)
        {
            displaypermissiondenied();
            return;
        }

        setcurrentpage("wwwgfile");
        
        $title = $gfile["title"];

        $page = getpage("gfile - {$title}");
        $page->addBodyContent(fetchpageheader());

        $tmpl = getsmarty();
        $tmpl->assign("id", $id);
        $tmpl->assign("gfile", $gfile);

        
//        $page->addBodyContent(fetchsidebar());
        $page->addBodyContent($tmpl->fetch("gfile.tmpl"));
        $page->addBodyContent(fetchpagefooter());
        $page->display();
        return True;
    }

    function summary()
    {
        $perpage = prefget("itemsperpage", getcurrentmemberid());
        if (is_null($perpage)) $perpage = 10;
        $params = array("mode" => "Sliding", "perPage" => $perpage, "delta" => 2, "totalItems" => $this->getcount());
        
        $pager = Pager::factory($params);
        
        $page = array();
        $page["totalItems"] = $params["totalItems"];
        $page["links"] = $pager->links;

        $sql = "select *, extract(epoch from dateposted) as datepostedepoch, extract(epoch from lastmodified) as lastmodifiedepoch from www.gfile order by dateposted desc";
        list($page["from"], $page["to"]) = $pager->getOffsetByPageId();
        $res = $this->dbh->limitQuery($sql, $page["from"]-1, $params["perPage"]);
        if (PEAR::isError($res))
        {
          logentry("zoid: gfile: " . $res->toString());
          return False;
        }

        $admin = flag("ADMIN");
        $action = array();
        $action["edit"] = $admin;
        $action["delete"] = $admin;
        $action["view"] = True;

        $page["data"] = array();
        while ($res->fetchInto($row, DB_FETCHMODE_ASSOC))
        {
          $row["dateposted"] = datestamp($row["datepostedepoch"]);
          $row["postedbyname"] = getmembername($row["authorid"]);
          $row["lastmodifiedbyname"] = getmembername($row["lastmodifiedby"]);
          $row["signame"] = getsigname($row["sigid"]);
          $row["action"] = $action;

          $page["data"][] = $row;
        }

        displayheader("gfiles - summary");

        $options = array();
        if (flag("ADMIN"))
        {
            $options[] = array("title" => "add gfile", "url" => "/gfile/add");
        }
        displaysidebar($options);

        $s = &getsmarty();
        $s->assign_by_ref("page", $page);
        $s->display("gfile-summary.tmpl");
		displayfooter();
		
		return True;
    }
    
    function add()
    {
         if (!flag("ADMIN"))
         {
             permissiondenied();
             return True;
         }

         $sigid = $_REQUEST["sigid"];
 //        $sigid = preparestring($sigid);
         $sigid = intval($sigid);

         $data = array();
         $data["sigid"] = $sigid;

         $form = $this->getform("add");
         $form->setDefaults($data);
         if ($form->validate())
         {
             // CSRF validation for form submission
             if (!\bbsengine6\util\csrfCheckRequest())
             {
                 $remoteAddr = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown';
                 $userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'unknown';
                 \bbsengine6\util\logentry("CSRF validation failed for gfile.add: ip={$remoteAddr}, user_agent={$userAgent}");
                 displayerrorpage("Invalid security token. Please try again (code: gfile.add.csrf)");
                 return False;
             }
             
             $form->freeze();
             return $form->process(array(&$this, "insert"), False);
         }

        $renderer = new HTML_QuickForm_Renderer_Array(True);
        $form->accept($renderer);

        displayheader("gfile - add");
        $options = array();
        $options[] = array("title" => "gfile summary", "url" => "/gfile/summary");
        displaysidebar($options);

        
        $s = &getsmarty();
        $s->assign("form", $renderer->toArray());
        $s->display("form.tmpl");

        displayfooter();
        return True;
    }

    function insert($values)
    {
        if (!flag("ADMIN"))
        {
            permissiondenied();
            return True;
        }
        
        $currentmemberid = getcurrentmemberid();
        
        $sigid = ($values["sigid"] == 0) ? null : $sigid;
        
        $gfile = array();
        $gfile["title"] = $values["title"];
        $gfile["summary"] = $values["summary"];
        $gfile["body"] = $values["body"];
        $gfile["authorid"] = $currentmemberid;
        $gfile["dateposted"] = "now()";
        $gfile["lastmodified"] = "now()";
        $gfile["lastmodifiedby"] = $currentmemberid;
        $gfile["sigid"] = $sigid;
        $gfile["keywords"] = $values["keywords"];
        $gfile["viewpermission"] = $values["viewpermission"];
        
        $res = $this->dbh->autoExecute("www.gfile", $gfile, DB_AUTOQUERY_INSERT);
        if (PEAR::isError($res))
        {
            displayerrormessage("www: gfile: insert: execute returned error");
            logentry("www: gfile: insert: " . $res->toString());
            return False;
        }

        redirectpage("OK", "/sig/view/{$sigid}");
        return True;
    }
    
    function edit()
    {
         if (!flag("ADMIN"))
         {
             permissiondenied();
             return True;
         }

         $id = $_REQUEST["id"];
 //        $id = preparestring($id);
         $id = intval($id);
         if (empty($id))
         {
             displayerrormessage("gfile: edit: must specify id");
             return False;
         }
         
         $gfile = $this->get($id);
         if ($gfile == False)
         {
             displayerrormessage("gfile: edit: gfile does not exist");
             return False;
         }

         $form = $this->getform("edit", $id);
         $form->setDefaults($gfile, "unhtmlentities");

         if ($form->validate())
         {
             // CSRF validation for form submission
             if (!\bbsengine6\util\csrfCheckRequest())
             {
                 $remoteAddr = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown';
                 $userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'unknown';
                 \bbsengine6\util\logentry("CSRF validation failed for gfile.edit: ip={$remoteAddr}, user_agent={$userAgent}");
                 displayerrorpage("Invalid security token. Please try again (code: gfile.edit.csrf)");
                 return False;
             }
             
             $form->freeze();
             $form->process(array(&$this, "update"), False);
             return True;
         }

        $renderer = new HTML_QuickForm_Renderer_Array(True);
        $form->accept($renderer);

        displayheader("gfile - edit");

        $options = array();
        $options[] = array("title" => "gfile summary", "url" => "/gfile/summary");
        displaysidebar($options);
        
        $s = &getsmarty();
        $s->assign("form", $renderer->toArray());
        $s->display("form.tmpl");

        displayfooter();

		return True;
    }

    function update($values)
    {
        if (!flag("ADMIN"))
        {
            permissiondenied();
            return False;
        }

        $currentmemberid = getcurrentmemberid();

        $id = intval($values["id"]);
        
        $sigid = intval($values["sigid"]);
        $sigid = ($sigid == 0) ? null : $sigid;
        
        $gfile = array();
        $gfile["title"] = $values["title"];
        $gfile["summary"] = $values["summary"];
        $gfile["body"] = $values["body"];
        $gfile["lastmodified"] = "now()";
        $gfile["lastmodifiedby"] = $currentmemberid;
        $gfile["keywords"] = $values["keywords"];
        $gfile["sigid"] = $sigid;
        $gfile["viewpermission"] = $values["viewpermission"];
        
        $res = $this->dbh->autoExecute("www.gfile", $gfile, DB_AUTOQUERY_UPDATE, "id={$id}");
        if (PEAR::isError($res))
        {
            displayerrormessage("www: gfile: update: execute returned error");
            logentry("www: gfile: update: " . $res->toString());
            return False;
        }
        
        redirectpage("OK", "/gfile/view/{$id}");
        return True;
    }

    function delete()
    {
         if (!flag("ADMIN"))
         {
             permissiondenied();
             return False;
         }
         
 //        $id = preparestring($_GET["id"]);
         $id = isset($_GET["id"]) ? $_GET["id"] : null;
         if (strlen($id) == 0)
         {
             displayerrormessage("gfile: delete: must specify id of document to delete.");
             return False;
         }
         
         $sql = "select title from gfile where id=?";
         $res = $this->dbh->getOne($sql, array($id));
         if ($res == null)
         {
             displayerrormessage("www: gfile: delete: execute returned null");
             return False;
         }
         
         if (PEAR::isError($res))
         {
             displayerrormessage("www: gfile: delete: execute returned error");
             logentry("www: gfile: delete: " . $res->toString());
             return False;
         }

         if (count($res) == 0)
         {
             displayerrormessage("gfile: delete: unable to retrieve document with id '{$id}' from database.");
             return False;
         }

         $confirm = isset($_GET["confirm"]) ? $_GET["confirm"] : null;
         if (empty($confirm))
         {
             deleteconfirmation("gfile - delete - confirmation required", "are you sure you wish to delete the gfile entitled '{$res}'?", "/gfile.php?mode=delete&confirm=1&id={$id}", "/gfile.php?mode=summary");
             return True;
         }
         
         // CSRF validation for delete confirmation
         if ($_SERVER['REQUEST_METHOD'] === 'POST')
         {
             if (!\bbsengine6\util\csrfCheckRequest())
             {
                 $remoteAddr = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown';
                 $userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'unknown';
                 \bbsengine6\util\logentry("CSRF validation failed for gfile.delete: ip={$remoteAddr}, user_agent={$userAgent}");
                 displayerrorpage("Invalid security token. Please try again (code: gfile.delete.csrf)");
                 return False;
             }
         }
         
         $sql = "delete from www.gfile where id=?";
         $res = $this->dbh->query($sql, array($id));
         if ($res == null)
         {
             displayerrormessage("www: gfile: delete: execute returned null");
             return False;
         }
         
         if (PEAR::isError($res))
         {
             displayerrormessage("www: gfile: delete: execute returned error");
             logentry("www: gfile: delete: " . $res->toString());
             return False;
         }
         
         redirectpage("OK", "/gfile/summary");
         return True;
    }
        
    function main()
    {
        startsession();
        setcurrentpage("gfile");
        setcurrentsite("www");
        setcurrentaction("index");
//        checksession();
//        session_start();
        
        $mode = isset($_REQUEST["mode"]) ? $_REQUEST["mode"] : "";
//        $mode = preparestring($mode);
        if (empty($mode)) $mode = "summary";
        
        $this->dbh = &dbconnect(SYSTEMDSN);
        if (PEAR::isError($this->dbh))
        {
            displayerrormessage("gfile: error connecting to database");
            logentry("www: gfile: main: " . $this->dbh->toString());
            return False;
        }

        switch($mode)
        {
            case "view":
                $this->view();
                break;
            case "add":
                $this->add();
                break;
            case "edit":
                $this->edit();
                break;
            case "delete":
                $this->delete();
                break;
            case "summary":
                $this->summary();
                break;
            default:
                displayerrormessage("unknown mode '{$mode}'");
                break;
        }
        
        $this->dbh->disconnect();
        endsession();
    }
}

$g = new gfile();
$g->main();
