<?php

/**
 * this is a module to handle member registrations
 */

require_once("config.php");
require_once("engine.php");
require_once("session.php");
require_once("database.php");
require_once("member.php");

class join
{
    var $pdo = null;
    
    function insert($values)
    {
      $member = [];
      $member["email"] = $values["email"];
      $member["name"] = $values["name"];
      $member["moniker"] = $values["moniker"];
      if (\bbsengine6\flag("ADMIN"))
      {
        $member["credits"] = $values["credits"];
      }
      $member["datecreated"] = "now()";
      $member["createdbyid"] = getcurrentmemberid();
      $member["dateupdated"] = "now()";
      $member["updatedbyid"] = getcurrentmemberid();

      $res = $this->pdo->beginTransaction();
      if ($res === false)
      {
        logentry("join.15: " . $res->toString());
        return PEAR::raiseError("Unable to start transaction (code: join.15)");
      }
      $res = $this->pdo->autoExecute("engine.__member", $member, MDB2_AUTOQUERY_INSERT);
      if ($res === false)
      {
          logentry("join.20: " . $res->toString());
          $this->pdo->rollback();
          return PEAR::raiseError("Database Error (code: join.20)");
      }

      $memberid = $this->pdo->lastInsertID();
      $res = \bbsengine6\setpassword($memberid, $values["password"]);
      if ($res === false)
      {
          logentry("join.22: " . $res->toString());
          return $res;
      }

      $res = $this->pdo->commit();

      $data = [];
      $data["pagetemplate"] = "thankyouforjoining.tmpl";
      displaypage($data);
      return True;
    }
    
    function main()
    {
        \bbsengine6\session\start();
        
/*
        if (\bbsengine6\flag("AUTHENTICATED") === False)
        {
            \bbsengine6\displaypermissiondenied();
            return;
        }
*/
        $this->pdo = \bbsengine6\database\connect(\config\SYSTEMDSN);
        
        \bbsengine6\setcurrentsite("rgs");
        \bbsengine6\setcurrentaction("join");

        \bbsengine6\logentry("join.100: site=".var_export(\bbsengine6\getcurrentsite(), True)." action=".var_export(\bbsengine6\getcurrentaction(), true));

        $form = \bbsengine6\getquickform(\config\LOGENTRYPREFIX."-join");
        \bbsengine6\member\buildfieldset($form, ["uniqueusername" => true]);
        // buldprofilefieldset($form);
        \bbsengine6\buildnewpasswordfieldset($form);
        \bbsengine6\buildcaptchafieldset($form);

        $form->addElement("submit", "submit", array("value" => "join"));
        
        $const = [];
        $const["memberid"] = isset($_REQUEST["memberid"]) ? intval($_REQUEST["memberid"]) : \bbsengine6\member\getcurrentid();
        
        $form->addDataSource(new HTML_QuickForm2_DataSource_Array($const));
  
        $defaults = [];
        $form->addDataSource(new HTML_QuickForm2_DataSource_Array($defaults));
        
        $res = \bbsengine6\handleform($form, [$this, "insert"], "new user");
        if ($res === false)
        {
            logentry("join.100: " . $res->toString());
            return PEAR::raiseError("displayform error (code: join.100)");
        }
        if ($res === True)
        {
            logentry("join.130: handleform(...) returned True");
            return True;
        }
        $renderer = \bbsengine6\getquickformrenderer();
        $form->render($renderer);
        $res = \bbsengine6\displayform($renderer, "knock, knock neo...");
        if ($res === false)
        {
          logentry("join.302: " . $res->toString());
          return PEAR::raiseError("error displaying form (code: join.302)");
        }
//        $this->pdo->disconnect();
        return $res;
    }
};

$j = new join();
$r = $j->main();
if ($r === false)
{
    logentry("join.100: " . $r->toString());
    displayerrormessage($r->getMessage());
    exit;
}
?>
