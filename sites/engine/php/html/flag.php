<?php

require_once("config.php");
require_once("zoidweb4.php");
require_once("bbsengine4.php");

class flag
{
/*
    function __edit($flag)
    {
        $form = getquickform("bbsengine4-flag");
        if (PEAR::isError($form))
        {
            logentry("engine.flag.100: " . $form->toString());
            return $form;
        }
        $fieldset = $form->addElement("fieldset", "flag");
        $fieldset->addElement("text", "name")->setLabel("name");
        $fieldset->addElement("text", "description")->setLabel("description");
        $fieldset->addElement("checkbox", "default")->setLabel("default true");

        $form->addDataSource(new HTML_QuickForm2_DataSource_Array($flag));
        return $form;
    }
*/
    function add()
    {
        if (flag("ADMIN") === False)
        {
            displaypermissiondenied();
            return;
        }
        
        setcurrentsite("engine");
        setcurrentpage("flag");
        setcurrentaction("add");
        setreturnto(getcurrenturi());

        displayerrorpage("under construction");
        return;

        $form = getquickform("engine-flag-add");

        buildflagfieldset($form);

        $defaults = [];
        $defaults["name"] = "";
        $defaults["description"] = "";
        $defaults["defaultvalue"] = False;
//        $form = $this->__edit($flag);

        $form->addElement("submit", "submitflag", ["value" => "add flag!"]);

        $form->addDataSource(new HTML_QuickForm2_DataSource_Array($defaults));
        
        $currentmemberid = getcurrentmemberid();
        $constants = array();
        $constants["id"] = $currentmemberid;
        $constants["mode"] = "add";
        $form->addDataSource(new HTML_QuickForm2_DataSource_Array($constants));

        $res = handleform($form, [$this, "insert"], "add flag");
        if (PEAR::isError($res))
        {
            logentry("engine.flag.108: " . $res->toString());
            return $res;
        }
        logentry("engine.flag.104: handleform(...) returned ".var_export($res, True));
        if ($res === True)
        {
//          logentry("engine.flag.102: handleform(...) returned True");
          return $res;
        }

        $renderer = getquickformrenderer();
        $form->render($renderer);

        $res = displayform($renderer, "add flag");
        if (PEAR::isError($res))
        {
          logentry("engine.flag.120: " . $res->toString());
          return $res;
        }
        return $res;
    }

    function insert($values)
    {
//        logentry("called bbsengine4.flag.insert");
      $currentmemberid = getcurrentmemberid();
      $flag = buildflagrecord($values);
      $res = $this->dbh->beginTransaction();
      if (PEAR::isError($res))
      {
        logentry("engine.flag.insert.100: unable to begin transaction");
        return PEAR::raiseError("unable to add link to database (code: engine.flag.insert.100)");
      }

      $res = $this->dbh->autoExecute("engine.flag", $flag, MDB2_AUTOQUERY_INSERT);
      if (PEAR::isError($res))
      {
        $this->dbh->rollback();
        logentry("engine.flag.insert.140: " . $res->toString());
        return PEAR::raiseError("database error (code: engine.flag.insert.140)");
      }

//      $id = $this->dbh->lastInsertID();
      
      $this->dbh->commit();

      displayredirectpage("OK -- flag added");
      logentry("engine.flag.insert.150: flag added");

      return True;
    }

    function main()
    {
        startsession();

        setcurrentsite("engine");
        $this->dbh = dbconnect(SYSTEMDSN);
        if (PEAR::isError($this->dbh))
        {
            logentry("engine.flag.100: " . $this->dbh->toString());
            return $dbh;
        }
        $mode = isset($_REQUEST["mode"]) ? $_REQUEST["mode"] : null;
        switch ($mode)
        {
            case "add":
            {
                $res = $this->add();
                break;
            }
/*
            case "edit":
            {
                $res = $this->edit();
                break;
            }
*/
            case "delete":
            {
                $res = $this->delete();
                break;
            }
            default:
            {
                return PEAR::raiseError("engine.flag.120: unknown mode '".var_export($mode, True)."'");
            }
        }
        $this->dbh->disconnect();
        return $res;
    }
}

$a = new flag();
$b = $a->main();
if (PEAR::isError($b))
{
    logentry("engine.flag.900: " . $b->getMessage());
    displayerrorpage($b->getMessage());
    exit;
}
?>
