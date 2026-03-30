<?php

require_once("config.php");
require_once("engine.php");
require_once("session.php");
require_once("lib.php");

/**
 * this module accepts a username and password and validates it against the postgresql database.
 *
 * @copyright (c) 2007-2019 {@link http://zoidtechnologies.com/ zoidtechnologies.com} all rights reserved
 * @package bbsengine6
 */
class login
{
  /**
   * @since 20150420
   */
  function checklogin($args)
  {
          logentry("checklogin.50: args=".var_export($args, True));

          $email = $args["email"];
          $password = $args["password"];
          $id = getmemberidfromemail($email);
          if (PEAR::isError($id))
          {
                  logentry("checklogin.60: ".$id->toString());
                  return False;
          }
          if (checkpassword($password, $id) === True)
          {
                  logentry("checklogin.65: checkpassword passed");
                  return True;
          }
          logentry("checklogin.66: checkpassword failed");
          return False;
  }
  /**
   * wrapper which validates credentials (username/password) passed via quickform.
   *
   * @return none
   */
  function validate($values)
  {
    $username = isset($values["name"]) ? $values["name"] : null;
    $password = isset($values["passwd"]) ? $values["passwd"] : null;

    return $this->_validate($username, $password);
  }

  function _validate($username, $password)
  {
//    logentry("login.100: username=".var_export($username, true)." password=".var_export($password, true));
    $dbh = dbconnect(SYSTEMDSN);
    if (PEAR::isError($dbh))
    {
      displayerrorpage("database error (code: login.102)");
      logentry("login.102: " . $dbh->toString());
      return False;
    }

    $memberid = getmemberid($username);
    if (PEAR::isError($memberid))
    {
      logentry("login.104: " . $memberid->toString());
      return PEAR::raiseError("Invalid Login (code: login.104)");
    }

    if (checkpassword($password, $memberid) === False)
    {
      displayerrorpage("invalid login or password (code: login.106)");
      logentry("login.106: wrong password. user input: username=".var_export($username, True).", password=".var_export($password, True));
      return False;
    }

    // @since 20141223
    $member = getmember($memberid);
    if (PEAR::isError($member))
    {
      logentry("login.200: " . $member->toString());
      return $member;
    }

    setlastlogin($member["lastlogin"]);
    setlastloginfrom($member["lastloginfrom"]);
    
    $member = [];
    $member["lastlogin"] = "now()";
    $member["lastloginfrom"] = $_SERVER["REMOTE_ADDR"];
    $res = $dbh->autoExecute("engine.__member", $member, MDB2_AUTOQUERY_UPDATE, "id=" . $dbh->quote($memberid, "integer"));
    if (PEAR::isError($res))
    {
      \bbsengine6\displayerrorpage("database error (login.108)");
      \bbsengine6\logentry("login.108: " . $res->toString());
      $dbh->rollback();
      return False;
    }

    \bbsengine6\member\setcurrentid($memberid);

    \session_regenerate_id(true);

    \setcookie(session_name(),\session_id(),time()+\config\SESSIONCOOKIEEXPIRE, \config\SESSIONCOOKIEPATH, \config\SESSIONCOOKIEDOMAIN);
                
//    session_set_cookie_params($lifetime); <-- called in startsession() in bbsengine3.php

    \bbsengine6\displayredirectpage("OK -- logged in");

    $member = getmember($memberid);
    
    $name = isset($member["name"]) ? $member["name"] : null;
    
    \bbsengine6\logentry("login.20: success for ".var_export($name, True)." (#{$memberid})");
    // $dbh->disconnect();
    return True;
  }
  
  function main()
  {
    \bbsengine6\session\start();
    
    \bbsengine6\setcurrentsite("engine");
    \bbsengine6\setcurrentaction("login");

    $form = \bbsengine6\getquickform("bbsengine6-login", "post", array("action" => "/login"));
    $this->buildloginfieldset($form);
    \bbsengine6\buildcaptchafieldset($form);

    $group = $form->addGroup("buttons");
    $group->setSeparator("&nbsp;");
    
    $group->addElement("submit", "submit", ["value" => "red pill (accept)"]);
    $group->addElement("submit", "cancel", ["value" => "blue pill (decline)"]);

    $const = [];
    $form->addDataSource(new HTML_QuickForm2_DataSource_Array($const));

    $res = \bbsengine6\handleform($form, [$this, "validate"], "follow the white rabbit...");
    if (PEAR::isError($res))
    {
      \bbsengine6\logentry("login.300: " . $res->toString());
      return PEAR::raiseError("login form handling error (code: login.300)");
    }
    if ($res === True)
    {
      logentry("login.310: handleform(...) returned True");
      return $res;
    }
    $renderer = \bbsengine6\getquickformrenderer();
    $form->render($renderer);
    $options = [];
//    $options["stylesheets"] = [STATICSKINURL."css/login.css"];
    $res = \bbsengine6\displayform($renderer, "knock, knock, neo...", $options);
    if (PEAR::isError($res))
    {
      \bbsengine6\logentry("login.320: " . $res->toString());
      return PEAR::raiseError("error displaying form (code: login.320)");
    }
    return $res;
  }

  function buildloginfieldset($form)
  {
    $fieldset = $form->addElement("fieldset");
    $fieldset->setLabel("Authenticate");
    $name = $fieldset->addElement("text", "name");
    $name->setLabel("Handle");
    $name->addRule("required", "'Handle' is a required field");

    $password = $fieldset->addElement("password", "passwd");
    $password->setLabel("Password");
    $password->addRule("required", "'Password' is a required field");
    
    $fieldset->addRule("callback", "'Handle' or 'Password' incorrect.", [$this, "checklogin"]); // array("callback" => "checklogin"));
    
    return;
  }

}

$a = new login();
$b = $a->main();
if (PEAR::isError($b))
{
  \bbsengine6\logentry("login.400: " . $b->toString());
}

?>
