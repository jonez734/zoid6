<?php

namespace config {

/*
define("config\SYSTEMDSN", "pgsql:host=127.0.0.1;port=5432;dbname=rgs");
define("config\SESSIONNAME", "rgs");
*/
/**
 * http://pear.php.net/manual/en/package.database.db.intro-dsn.php
 */
//define("SYSTEMDSN", "pgsql://apache@127.0.0.1:5433/zoid6");
// define("OLDSYSTEMDSN", "pgsql://apache@127.0.0.1/zoidweb3");
// define("SYSTEMDSN", "pgsql://apache@localhost/zoidweb2");

/*
define("DATEFORMAT", "%Y-%b-%d %l:%M%p %Z (%a)"); // @see https://php.net/strftime

define("CAPTCHAFONTPATH", "");
if (defined("lib\SESSIONCOOKIEDOMAIN") === False)
{
  define('config\SESSIONCOOKIEEXPIRE', 60*60*12);
  define('config\SESSIONCOOKIEDOMAIN', ".theaiconsensus.com");
  define('config\SESSIONCOOKIEPATH', "/");
}

// @since 20181111
define("ENGINEVHOSTDIR", "/srv/www/vhosts/engine.theaiconsensus.com/");
define("ENGINEDOCUMENTROOT", ENGINEVHOSTDIR . "html/");
define("ENGINESKINDIR", ENGINEDOCUMENTROOT . "skin/");
define("ENGINEFONTSDIR", ENGINESKINDIR. "fonts/");
define("CAPTCHAFONTFILE", "cour.ttf");

// @since 20150727, changed to https @since 20180502
//define("ENGINEURL", "/");

// @since 20140803
define("STATICJAVASCRIPTURL", ENGINEURL. "js/");

define("LOGOUTURL", ENGINEURL."logout");
define("LOGINURL", ENGINEURL."login");

// define("CRYPTSALT", '$2a$07$ChorErjIv9oteff');

// @since 20141203
// removed 2018aug15
//define("FONTAWESOMECSSURL", "//maxcdn.bootstrapcdn.com/font-awesome/4.6.3/css/font-awesome.min.css");
//define("FONTAWESOMECSSURL", "//static.zoidtechnologies.com/font-awesome-4.2.0/css/font-awesome.min.css");

\date_default_timezone_set("America/New_York");

define("SITETYPE", "webpage");
*/
} /* namespace config */

namespace lib {

function buildchoices($choices=[])
{
    //$choices[] = ["name" => "achilles", "title" => "achilles", "url" => "https://achilles.zoidtechnologies.com/", "desc" => ""];
    $choices[] = ["name" => "www", "title" => "www site", "url" => "https://www.theaiconsensus.com", "desc" => "www site"];
    $choices[] = ["name" => "teos", "title" => "teos", "url" => "https://www.theaiconsensus.com/teos/", "desc" => "catalog view"];
    $choices[] = ["name" => "socrates", "title" => "socrates", "url" => "https://www.theaiconsensus.com/socrates/", "desc" => "threaded discussion system"];
//    $choices[] = ["name" => "repo", "title" => "download site", "url" => "https://repo.zoidtechnologies.com", "desc" => "repo site"];
    $res = \bbsengine6\buildchoices($choices);
    \bbsengine6\util\logentry("rgs.lib.buildchoices.100: res=".var_export($res, true));
    return $res;
}

require_once("HTML/QuickForm2/Element/Captcha/Image.php");
//require_once("libagora.php");

function buildcaptchafieldset($form, $options=null)
{
  $_options = array(
//    "label" => "Are You Human?",

    // Captcha options
    "output" => "png",
    "width"  => 300,
    "height" => 100,

    // Path where to store images
    "imageDir" => DOCUMENTROOT. "captchas/",
    "imageDirUrl" => "/captchas/",
    "imageOptions" => [
      "font_path"        => ENGINEFONTSDIR, // /usr/share/fonts/truetype/fonts/",
      "font_file"        => CAPTCHAFONTFILE, // "cour.ttf",
      "text_color"       => "#000000",
      "background_color" => "#ffffff",
      "lines_color"      => "#000000",
    ],
    "captchaHtmlAttributes" => [ "class" => "captchaclass" ]
  );
  if (is_array($options))
  {
   $_options = array_merge($_options, $options);
  }
//  logentry("buildcaptchafieldset.100: " . var_export($_options, True));

  $fs = $form->addFieldset("captcha");
  $el = $fs->addElement(
    new \HTML_QuickForm2_Element_Captcha_Image(
      "captcha[image]",
      ["id" => "captcha_image"],
      $_options)
  );
  $fs->setLabel("Human Verification");
  return;
}

/**
 * @since 20150925
 * @param integer id id of "item"
 * @param text maptable name of map table (including schema)
 * @param text idfield name of id field (f.e. 'linkid', or 'itemid')
 * @return array list of hrefs for each label along the path of the sig
 *
 */
function buildbreadcrumblist($link, $table, $key="id")
{
  $id = $link[$key];
  
  $dbh = dbconnect(\config\SYSTEMDSN);
  if (PEAR::isError($dbh))
  {
    logentry("buildbreadcrumblist.10: " . $dbh->toString());
    return $dbh;
  }
  $sql = "select unnest(sigs) as siglabelpath from {$table} where id=?";
  $dat = array($id);
  $res = $dbh->getAll($sql, null, $dat, array("integer"));
  if (PEAR::isError($res))
  {
    logentry("buildbreadcrumblist.42: " . $res->toString());
    return $res;
  }
//  logentry("buildbreadcrumblist.50: res=".var_export($res, True));
  $breadcrumbs = [];
  foreach ($res as $rec)
  {
    $siglabelpath = $rec["siglabelpath"];
    $breadcrumbs[] = buildbreadcrumbs($siglabelpath);
  }
//  logentry("buildbreadcrumblist.100: breadcrumbs=".var_export($breadcrumbs, True));
  return $breadcrumbs;
}

/**
 * copied from zoidweb1
 * 
 * @since 20131014
 */
/*
function sortsidebaritems($a, $b)
{
  $foo = isset($a["title"]) ? $a["title"] : null;
  $bar = isset($b["title"]) ? $b["title"] : null;

  if ($foo < $bar) return -1;
  if ($foo == $bar) return 0;
  if ($foo > $bar) return 1;
}
*/
/*
function accesssig($op, $sig)
{
  $path = $sig["path"];

  $erosflag = flag("EROS");
  $adminflag = flag("ADMIN");

//  $path = isset($data["path"]) ? $data["path"] : "top";

//  logentry("accesssig.110: path=".var_export($path, true));
  $iserospath = strpos($path, "top.eros") === 0;
//  logentry("accesssig.100: path=".var_export($path, True)." iserospath=".var_export($iserospath, true));

  switch ($op)
  {
    case "view":
    case "browse":
    {
      $res = True;
      break;
    }

    case "add":
    case "edit":
    {
      $res = ($adminflag === True) ? True : False;
      break;
    }
    case "addlink":
    {
      // anyone with "admin" flag can add a link
      if ($adminflag === True)
      {
       $res = True;
       break;
      }
      // else denied
      $res = False;
      break;
    }
    case "addsig":
    case "addpost":
    {
     $res = ($adminflag === True) ? True : False;
     break;
    }
    default: 
    {
      $res = null;
      break;
    }
  }
//  logentry("zoidwebaccesssig.10: op=".var_export($op, true)." res=".var_export($res, True)); // ." uid=".var_export($uid, true)); // . " data=".var_export($data, True));
  return $res;
}
*/
/*
function buildpathfromuri($uri)
{
  logentry("buildpathfromuri.100: !! renamed to buildlabelpath");
  return buildlabelpath($uri);
}
*/
/** 
 * local version of getsmarty() that sets up plugin dirs, template dirs, etc
 *
 * @since 20140710
 */
/*
function getsmarty($options=null)
{
  $options = [];
  $options["pluginsdir"] = [SMARTYPLUGINSDIR, "/srv/www/zoid6/smarty/"];
  $options["templatedir"] = [SMARTYTEMPLATESDIR, "/srv/www/zoid6/skin/tmpl/"];
  $options["compiledir"] = SMARTYCOMPILEDTEMPLATESDIR;
  $options["compileid"] = LOGENTRYPREFIX;
  return _getsmarty($options);
}
*/

/** 
 * @since 20140726
 */

/*
function getlabelpathfromsigid($sigid) */

/**
 * @since 20140807
 */
function fetchpagefooter($options=null)
{
  $tmpl = getsmarty();
  $mantra = getrandommantra();
  if (PEAR::isError($mantra))
  {
    logentry("fetchpagefooter.100: " . $mantra->toString());
    $mantra = null;
  }

  $tmpl->assign("mantra", $mantra);
  return $tmpl->fetch("pagefooter.tmpl");
}

/**
 * escapes and json encodes given data parameter
 *
 * @since 20140730
 */
/* function encodejson($data) */

/**
 * decodes given json data into a dictionary (associative array)
 * 
 * @since 20140730
 */
/* function decodejson($data) */

/**
 * @since 20240812
 */
function accesspost($op, $data=null, $memberid=null)
{
  $siguri = isset($data["siguri"]) ? $data["siguri"] : null;
  $post = isset($data["post"]) ? $data["post"] : null;
  $id = isset($data["id"]) ? intval($data["id"]) : null;
  
  if ($memberid === null)
  {
    $memberid = \bbsengine6\member\getcurrentid();
  }

  $admin = flag("ADMIN", $memberid);
  $postedbyid = $post["postedbyid"];

//  logentry("accesspost.199: post.frozen=".var_export($post["frozen"], True));

  $frozen = \bbsengine6\toboolean($post["frozen"], "lib.accesspost.frozen");
  $draft = \bbsengine6\toboolean($post["draft"], "lib.accesspost.draft");
  $junk = \bbsengine6\toboolean($post["junk"], "lib.accesspost.junk");

//  logentry("accesspost.200: op=".var_export($op, True)." memberid=".var_export($memberid, True)." frozen=".var_export($frozen, True)." draft=".var_export($draft, True));
 
  switch ($op)
  {
    case "markasjunk":
    {
      if ($admin === true && $junk === false)
      {
        $res = True;
        break;
      }
      $res = False;
      break;
    }
    case "markasdraft":
    {
      if ($draft === False && ($admin === True || $postedbyid === $memberid))
      {
        $res = True;
        break;
      }
      $res = False;
      break;
    }
    case "markaspublished":
    {
      if ($draft === True && ($admin === True || $postedbyid === $memberid))
      {
        $res = True;
        break;
      }
      $res = False;
      break;
    }
    case "edit":
    {
      if ($admin === True || $postedbyid === $memberid)
      {
        $res = True;
        break;
      }
      $res = False;
      break;
   }

   case "add":
   {
     $siglabelpath = buildlabelpath($siguri);

     $sig = getsig($siglabelpath);
     if (PEAR::isError($sig))
     {
      logentry("accesspost.110: ".$sig->toString());
      $res = False;
      break;
     }

     if (accesssig("addpost", $sig) === True)
     {
      logentry("accesspost.120: accesssig returned true");
      $res = True;
      break;
     }
     logentry("accesspost.122: accesssig returned false");
     $res = False;
     break;
   }
  
   case "freeze":
   {
     if ($frozen === True)
     {
       $res = False;
       break;
     }
     if ($admin === True)
     {
       $res = True;
       break;
     }
     $res = False;
     break;
   }
   case "thaw":
   {
     if ($frozen === False)
     {
       $res = False;
       break;
     }
     if ($postedbyid === $memberid || $admin === True)
     {
       $res = True;
       break;
     }
     $res = False;
     break;
   }
   case "reply":
   {
     if ($frozen === True)
     {
       $res = False;
       break;
     }
     if (flag("AUTHENTICATED") === True)
     {
       $res = True;
       break;
    }
    $res = False;
    break;
   }
   case "detail":
   case "view":
   {
//     logentry("accesspost.149: draft=".var_export($draft, True)." adminflag=".var_export($adminflag, True)." postedbyid=".var_export($postedbyid, True)." memberid=".var_export($memberid, True));
     
     if ($draft === True)
     {
       if ($admin === True or $postedbyid === $memberid)
       {
         $res = True;
         break;
       }
       $res = False;
       break;
     }
     if ($junk === True && $admin === False)
     {
       $res = False;
       break;
     }
     
     $res = True;
     break;
   }
   case "delete":
   {
     if ($admin === True or ($postedbyid === $memberid and $postedbyid > 0))
     {
       $res = True;
       break;
     }
     $res = False;
     break;
   }
   default:
   {
     logentry("accesspost.150: unknown op ".var_export($op, True));
     $res = null;
     break;
   }
  }

//  logentry("accesspost.100: op=".var_export($op, True)." res=".var_export($res, True));
  return $res;
}

/**
 * @since 20141229
 */
/*
function buildaccountfieldset($form, $flags=array())
{
 $fieldset = $form->addElement("fieldset");
 $fieldset->setLabel("User Account");

 $username = $fieldset->addText("username", "size=30");
 $username->setLabel("Username");
 $username->addRule("required", "'Username' is a required field");
 $uniqueusername = isset($flags["uniqueusername"]) ? $flags["uniqueusername"] : True;
 if ($uniqueusername === True)
 {
  $username->addRule("callback", "Username is currently in use", "uniqueusernamecallback");
 }

 $email = $fieldset->addText("email", "size=30");
// $email = $fieldset->addElement("email", "email", "size=30");
 $email->setLabel("E-Mail Adddress");
 $email->addRule("required", "'Email' is a required field");
 $email->addRule("email", "'Email' must be in correct format");

  $realname = $fieldset->addText("realname");
  $realname->setLabel("Real Name");
 
 return; 
}
*/
/*
function buildnotifyrecord($key, $notify)
{
  $data = $notify["data"];

  $val = isset($data[$key]) ? $data[$key] : null;
  if ($val === null)
  {
    logentry("buildrecord.210: ".var_export($key, True)." is null.");
    return null;
  }
  
  logentry("buildrecord.212: key=".var_export($key, True)." data=".var_export($data, True)." val=".var_export($val, True));

  switch ($key)
  {
    case "linkid":
    {
      $record = getlinkbyid($val);
      break;
    }
    default:
    {
      logentry("buildrecord.900: unhandled type. key=".var_export($key, True));
      $record = null;
      break;
    }
  }

  if (PEAR::isError($record))
  {
    logentry("buildrecord.100: " . $record->toString());
    $record = null;
  }

  return $record;
}
*/
/*
function buildnotifytemplatename($type)
{
  switch ($type)
  {
    case "link-approved":
    {
      $res = "notify-link-approved.tmpl";
      break;
    }
    case "link-added":
    {
      $res = "notify-link-added.tmpl";
      break;
    }
    default:
    {
      $res = null;
      break;
    }
  }
  return $res;
}
*/
/**
 * @since 20170502
 */
/*
function buildnotifydata($notifytype, $data)
{
  switch ($notifytype)
  {
    case "link":
    {
      $linkid = isset($data["linkid"]) ? $data["linkid"] : null;
      $link = getlinkbyid($linkid);
      if (PEAR::isError($link))
      {
        logentry("buildnotifydata.100: " .$link->toString());
        return null;
      }
      return $link;
    }
    default:
    {
      logentry("buildnotifydata.110; unhandled notify type ".var_export($notifytype, True));
      return null;
    }
  }
}
*/
/*
 * function which accepts an html_quickform2 instance and calls the callback if the form is validated.
 * @since 20150629
 */
/*
function processform($form, $validatedcallback)
{
    logentry("processform.100: enter");
    
    $issubmitted = $form->isSubmitted();
    $validate = $form->validate();

    if ($issubmitted === True)
    {
      logentry("processform.200: issubmitted===true");
      $value = $form->getValue();
//      $pageprotocol = isset($value["pageprotocol"]) ? $value["pageprotocol"] : "standard";
//      setpageprotocol($pageprotocol);
    }
    else
    {
//      $pageprotocol = clearpageprotocol();
    }

    if ($issubmitted === True && $validate === True)
    {
      logentry("processform.210: form validated");
      
      if (is_callable($validatedcallback) === False)
      {
        logentry("processform.220: callback is not valid");
        return False;
      }

      $form->toggleFrozen(True);
// handled by getquickform() now
//      $form->addRecursiveFilter("trim");
//      $form->addRecursiveFilter("strip_tags");
      $values = $form->getValue();

      logentry("processform.480: values=".var_export($values, True));
      
      $res = call_user_func($validatedcallback, $values); // $this->update($values);
      if (PEAR::isError($res))
      {
        logentry("processform.101: " . $res->toString());
        return $res;
      }
  
      logentry("processform.500: exit");
      return $res;
    }
    return False;
}
*/
/**
 * @since 20150927
 * @return string
 * @param siglabelpaths array of siglabelpaths
 */
/*
function implodesiglabelpaths($siglabelpaths)
{
  return implode(", ", $siglabelpaths);
}
*/
/**
 * @since 20151029
 * @param integer postid
 * @return list of sigs in a particular post
 */
/*
function getsigpostmap($postid)
{
  $res = getsigmap("sophia.map_sig_post", "postid", $postid);
  if (PEAR::isError($res))
  {
    logentry("getsigpostmap.100: " . $res->toString());
    return $res;
  }
  return $res;
}
*/
/**
 * function to return a list of sigs given a mapping table name, a keyfield
 * name, and a keyfield value.  this function is normally called from
 * something like {@see getsigpostmap()}
 * @since 20151029
 * @param string name of table including schema
 * @param string keyfieldname
 * @param string keyfieldvalue
 * @return mixed list of sigs or PEAR::Error
 */
/*
/* function getsigmap($maptable, $keyfieldname, $keyfieldvalue) */
/**
 * function that returns a list of sigs given a linkid
 *
 * @since 20151029
 * @param integer linkid
 * @return mixed list of sigs or PEAR::Error
 */
/*
function getsiglinkmap($linkid)
{
  $res = getsigmap("vulcan.map_link_sig", "linkid", $linkid);
  if (PEAR::isError($res))
  {
    logentry("getsiglinkmap.100: " . $res->toString());
  }
  return $res;
}
*/
/**
 * @since 20150404
 * moved to bbsengine3.php
 * function buildpagerinfo($sql, $params) 
*/

/**
 * @since 20150722
 */
/*
function gettotalposts()
{
  $dbh = dbconnect(\config\SYSTEMDSN);
  if (PEAR::isError($dbh))
  {
    logentry("gettotalpostcount.100: " . $dbh->toString());
    return $dbh;
  }
  $sql = "select count(id) from sophia.post";
  $res = $dbh->getOne($sql);
  return $res;
}
*/
/**
 * @since 20150722
 */
/*
function getmemberposts($memberid)
{
  $dbh = dbconnect(\config\SYSTEMDSN);
  if (PEAR::isError($dbh))
  {
    logentry("getmemberpostcount.100: " . $dbh->toString());
    return $dbh;
  }
  $sql = "select count(id) from sophia.post where postedbyid=?";
  $dat = array($memberid);
  $res = $dbh->getOne($sql, array("integer"), $dat, array("integer"));
  return $res;
}
*/
/**
 * @since 20150722
 */
/*
function getmemberlinks($memberid)
{
  $dbh = dbconnect(\config\SYSTEMDSN);
  if (PEAR::isError($dbh))
  {
    logentry("getmemberlinkcount.100: " . $dbh->toString());
    return $dbh;
  }
  $sql = "select count(id) from vulcan.link where postedby=?";
  $dat = array($memberid);
  $res = $dbh->getOne($sql, array("integer"), $dat, array("integer"));
  return $res;
}
*/
/*
function getpostreplycount($postid)
{
  static $count = 0;
  
  $dbh = dbconnect(\config\SYSTEMDSN);
  if (PEAR::isError($dbh))
  {
    logentry("getpostreplycount.100: ".$dbh->toString());
    return $dbh;
  }
  $sql = "select count(id) from sophia.post where parentid=?";
  $dat = [$postid];
  $res = $dbh->getOne($sql, ["integer"], $dat, ["integer"]);
  if (PEAR::isError($res))
  {
    logentry("getpostreplycount.110: " . $res->toString());
    
  }
  $count += $res["count"];
  return $count;
}
*/
/**
 * function to build up a generic page (like "about") and call
 * displaypage().  this function elimintates a lot of duplicate code.  it is
 * not in the engine because of the buildsidebarmenu() call
 *
 * @since 20160204
 * @param string $pagename
 * @return result of displaypage() engine call
 */
/*
function displaygenericpage($pagename)
{
    $tmpl = getsmarty();
    $pagedata = [];
    $pagedata["body"] = $tmpl->fetch("{$pagename}.tmpl");
//    $pagedata["sidebardata"] = buildsidebarmenu();
    
    $options = [];
    $options["pagedata"] = $pagedata;
    
    $page = getpage($name, $options);
    if (is_readable(SKINURL."css/{$pagename}"))
    {
      $page->addStyleSheet(SKINURL."css/{$pagename}");
    }
    $res = displaypage($page, $options);
    if (PEAR::isError($res))
    {
      logentry("displaygenericpage.100: ". $res->toString());
    }

  $data = [];
  $data["pagetemplate"] = "{$pagename}.tmpl";
  $data["pagename"] = $pagename;
  $page = getpage($pagename);
  return displaypage($page, $data);
}
*/
/*
function getreplycount($postid)
{
  $dbh = dbconnect(\config\SYSTEMDSN);
  $sql = "with recursive t as (select id from sophia.post where parentid=? union all select post.id from sophia.post join t on (sophia.post.parentid=t.id)) select count(*) from t";
  $dat = [$postid];
  $res = $dbh->getOne($sql, null, $dat, null);
  if (PEAR::isError($res))
  {
    logentry("getreplycount.100: " . $res->toString());
  }
  return $res;
}
*/
/**
 * @since 20171125
*/
/*
function displaypage($page, $data=[])
{
//  logentry("zoidweb3.displaypage.100: data=".var_export($data, True));
  if ($page === null)
  {
    $page = getpage("zoid technologies");
  }
  $s = isset($data["sidebar"]) ? $data["sidebar"] : null;
  $nav = buildsidebarmenu($s);
  $data["nav"] = $nav;
  $pagetemplate = isset($data["pagetemplate"]) ? $data["pagetemplate"] : "page.tmpl";
  $data["pagetemplate"] = $pagetemplate;
  $data["pagefooter"]["mantra"] = getrandommantra();
//  logentry("zoidweb3.displaypage.300: data=".var_export($data, True));
  return _displaypage($page, $data);
}
*/
/**
 * copied from zoidweb2
 * 
 * @since 20180223
 */
/*
function sortsidebarmenu($a, $b)
{
  $foo = isset($a["title"]) ? $a["title"] : null;
  $bar = isset($b["title"]) ? $b["title"] : null;

  if ($foo < $bar) return -1;
  if ($foo == $bar) return 0;
  if ($foo > $bar) return 1;
}
*/
/**
 * copied from zoidweb2
 * 
 * @since 20131014
 */
/*
function buildsidebarmenu($menu=[])
{
//  $currentpage = getcurrentpage();
//  $menu = array();

  if (flag("ADMIN"))
  {
      $menu[] = array("name" => "addflag", "title" => "add system flag", "url" => ENGINEURL."flag-add", "desc" => "add system flag to the database");
      $menu[] = array("name" => "addmantra", "title" => "add mantra", "url" => ENGINEURL."mantra-add", "desc" => "add mantra");
      $menu[] = array("name" => "addsitenews", "title" => "add site news", "url" => TEOSURL."sitenews/add-post", "desc" => "add a post to the 'site news' sig to be displayed on the www site");
//      $menu[] = array("name" => "addlink", "title" => "add link", "url" => VULCANURL . "add");
//      $menu[] = array("name" => "addfeed", "title" => "add feed", "url" => DEMETERURL . "add");
  }

  $menu[] = array("name" => "about", "title" => "about", "url" => "/about", "desc" => "about this site");
  $menu[] = array("name" => "teos", "title" => "teos", "url" => TEOSURL, "desc" => "catalog view");
//  $menu[] = array("name" => "bbsengine", "title" => "BBSEngine", "url" => "http://bbsengine.org/", "desc" => "Simple But Elegant Web Application Framework");
  $menu[] = array("name" => "vulcan", "title" => "vulcan", "url" => VULCANURL, "desc" => "links database", "icon" => SKINURL . "art/new2.png");
  $menu[] = array("name" => "www", "title" => "www site", "url" => WWWURL, "desc" => "main www site");
  $menu[] = array("name" => "repo", "title" => "software repo", "url" => REPOURL, "desc" => "download software");
//  $menu[] = array("name" => "aolbonics", "title" => "Urban Dictionary", "url" => AOLBONICSURL, "desc" => "Urban Dictionary");
  $menu[] = array("name" => "projects", "title" => "projects", "url" => "http://projects.zoidtechnologies.com/", "desc" => "Projects Site");
  $menu[] = array("name" => "sophia", "title" => "sophia", "url" => SOPHIAURL, "desc" => "forum/blog");
  
  $menu[] = array("name" => "achilles", "title" => "achilles", "url" => ACHILLESURL, "desc" => "");
  $menu[] = array("name" => "psyche", "title" => "psyche", "url" => PSYCHEURL, "desc" => "");
  $menu[] = array("name" => "agora", "title" => "agora", "url" => AGORAURL, "desc" => "a Worthy marketplace", "class" => "fas fa-fw fa-store");
  $menu[] = array("name" => "jamhacks", "title" => "jamhacks", "url" => WWWURL."jamhacks", "desc" => "combination resume, biography, and portfolio");
  $menu[] = array("name" => "sitenewsarchive", "title" => "site news archive", "url" => WWWURL."sitenewsarchive/", "desc" => "site news older than a month or two");
  if (flag("AUTHENTICATED"))
  {
    $menu[] = array("name" => "casino", "title" => "casino", "url" => CASINOURL, "desc" => "casino for entertainment purposes only");
  }
  if (flag("ADMIN"))
  {
    $menu[] = ["name" => "amznitem-add", "title" => "add amazon item", "url" => AGORAURL."amznitem-add"];
  }
  $menu[] = ["name" => "mantrasummary", "title" => "mantra summary", "url" => ENGINEURL."mantra-summary", "desc" => "mantra summary pages"];
  uasort($menu, "sortsidebarmenu");
  return $menu;
}
*/
/**
 * function which returns the sidebar. if the param is null, builds standard menu options, else passes direct to _fetchnavbar
 * 
 * @since 20150309
 */
/*
function fetchsidebar($menu=null)
{
//  logentry("fetchsidebar.zoidweb.100: called. menu=".var_export($menu, True));
  $options = [];
//  $foo = buildsidebarmenu($menu);
//  logentry("fetchsidebar.zoidweb.110: foo=".var_export($foo, True));
  $options["sidebardata"] = buildsidebarmenu($menu);
  $options["template"] = "sidebar.tmpl";
  return _fetchnavbar($options);
}
*/
/**
 * function which converts a postgres array (comma separated text) to a php array
 * https://prosuncsedu.wordpress.com/2009/10/13/postgres-array-to-php-array-or-vice-versa/
 *
 * @since 20190822
 * @since 20200418 removed in favor of json
 * @param $text postgres array in text format
 * @return array
 */
/*
function postgres_to_php_array($text)
{
  $text = trim($text, "{}");
  $res = explode(",", $text);
  if (is_array($res) === false)
  {
    return [$res];
  }
  return $res;
}
*/
} /* end namespace lib */
?>
