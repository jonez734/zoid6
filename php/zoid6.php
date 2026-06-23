<?php

namespace {
  require_once(__DIR__ . "/bootstrap.php");
  \zoid6\bootstrap();
}

namespace config {

/**
 * @since 20200423
 * 7
 */
define("GOOGLEANALYTICSACCOUNT", "UA-23705021-1");

define("IMAGESURL", "//zoidtechnologies.com/static/");

define("SYSTEMDSN", "pgsql:host=127.0.0.1;port=5432;dbname=zoid6");
define("SESSIONNAME", "zoid6");

//define("DATEFORMAT", "c"); // "%Y-%m-%d %H:%M"
define("DATEFORMAT", "%Y-%b-%d %l:%M%p %Z (%a)"); // @see https://php.net/strftime

define("CAPTCHAFONTPATH", "");

define("SESSIONCOOKIEEXPIRE", 60*60*12);
define("SESSIONCOOKIEDOMAIN", "zoidtechnologies.com");
define("SESSIONCOOKIEPATH", "/");

// @since 20181111
define("ENGINEVHOSTDIR", "/srv/www/vhosts/zoidtechnologies.com/engine/");
define("ENGINEDOCUMENTROOT", \ENGINEVHOSTDIR . "html/");
define("ENGINESKINDIR", \ENGINEDOCUMENTROOT . "skin/");
define("ENGINEFONTSDIR", \ENGINESKINDIR. "fonts/");
define("ENGINEURL", "/engine/");
define("LOGINURL", "/login");
define("LOGOUTURL", "/logout");
define("JOINURL", "/join");

define("CAPTCHAFONTFILE", "cour.ttf");

define("SHAREDURL", "/shared/");
if (!defined("SHAREDSKINURL")) define("SHAREDSKINURL", \SHAREDURL."skin/");
// @since 20220103
if (!defined("SHAREDARTURL")) define("SHAREDARTURL", \SHAREDSKINURL."art/");

if (!defined("ENGINESKINURL")) define("ENGINESKINURL", \ENGINEURL . "skin/");

define("NONCESALTLENGTH", 10);

// @since 20150120
//define("APIURL", "//api.zoidtechnologies.com/");

// @since 20140803
define("STATICJAVASCRIPTURL", \ENGINEURL. "js/");

// define("CRYPTSALT", '$2a$07$ChorErjIv9oteff');

// @since 20141203
// removed 2018aug15
// @since 20251223
define("FONTAWESOMEURL", "https://kit.fontawesome.com/684c5b77b8.js");

date_default_timezone_set("America/New_York");

define("SITETYPE", "webpage");

define("config\TOPBARUPDATEINTERVAL", 15000);

} /* config namespace */

namespace zoid6 {

function buildchoices($choices=[])
{
    $choices[] = ["name" => "teos", "title" => "teos", "url" => "/teos/", "desc" => ""];
    $choices[] = ["name" => "achilles", "title" => "a study of sodium glutamates", "url" => "/achilles/", "desc" => ""];
    $choices[] = ["name" => "asimov", "title" => "asimov: robotics, ai, and augmented reality", "url" => "/asimov/", "desc" => ""];
    // $choices[] = ["name" => "socrates", "title" => "threaded forum", "url" => "https://socrates.zoidtechnologies.com/", "desc" => ""];
    $choices[] = ["name" => "www", "title" => "www site", "url" => "/", "desc" => "www site"];
//    $choices[] = ["name" => "repo", "title" => "download site", "url" => "https://repo.zoidtechnologies.com", "desc" => "repo site"];
    $choices[] = ["name" => "empyre", "title" => "the game empyre", "url" => "/empyre/", "desc" => ""];
    return \bbsengine6\buildchoices($choices);
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
    new HTML_QuickForm2_Element_Captcha_Image(
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
  if (!\bbsengine6\database\validateTableName($table))
  {
    logentry("buildbreadcrumblist.050: invalid table name: " . var_export($table, true));
    return [];
  }

  $id = $link[$key];

  $dbh = \bbsengine6\database\connect(\SYSTEMDSN);
  if (($dbh === false))
  {
    logentry("buildbreadcrumblist.10: " . $dbh->toString());
    return $dbh;
  }
  $sql = "select unnest(sigs) as siglabelpath from {$table} where id=?";
  $dat = array($id);
  $res = $dbh->getAll($sql, null, $dat, array("integer"));
  if (($res === false))
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
 * @since 20150925
 */
function getlinksigmap($linkid)
{
/*
  $dbh = \bbsengine6\database\connect(SYSTEMDSN);
  $sql = "select siglabelpath from vulcan.map_link_sig where linkid=?";
  $dat = array($linkid);
  $res = $dbh->getAll($sql, null, $dat, array("integer"));
  if (($res === false))
  {
    logentry("getlinksigmap.102: " . $res->toString());
    return $res;
  }
  if ($res === null)
  {
    logentry("getlinksigmap.100: getlinksigmap(".var_export($linkid, True).") returned null");
  }
  $sigs = array();
  foreach ($res as $rec)
  {
    $sigs[] = $rec["siglabelpath"];
  }
  logentry("getlinksigmap.104: sigs=".var_export($sigs, True));
  return $sigs;
*/
  $res = getsigmap("vulcan.map_link_sig", "linkid", $linkid);
  if (($res === false))
  {
    logentry("getlinksigmap.100: " . $res->toString());
  }
  logentry("getlinksigmap.102: res=".var_export($res, True));
  return $res;

}

/**
 * return a list of dictionaries with keys 'title' and 'uri' for each part of $sigpath (ltree)
 *
 * @since 20120106
 * moved to bbsengine3.php
 */
/* function buildbreadcrumbs($sigpath) */

/**
 * @since 20111205
 */
function accesslink($op, $data=null, $memberid=null)
{
  if ($memberid === null)
  {
    $memberid = getcurrentmemberid();
  }

  $adminflag = flag("ADMIN", $memberid);
  $erosflag = flag("EROS", $memberid);
  $magicflag = flag("MAGIC", $memberid);

//  logentry("accesslink.300: adminflag=".var_export($adminflag, True)." erosflag=".var_export($erosflag, True));

  $eros = isset($data["eros"]) ? $data["eros"] : null;

  $approved = isset($data["approved"]) ? $data["approved"] : null;

  $magic = isset($data["magic"]) ? $data["magic"] : null;

  $siglabelpath = isset($data["sigpath"]) ? $data["sigpath"] : null;
  $sub = substr($siglabelpath, 0, strlen("top.eros"));

  $currentpage = getcurrentpage();
  $currentaction = getcurrentaction();

  $res = True;
  switch ($op)
  {
    case "approve":
    {
      if ($adminflag === True)
      {
        $res = True;
        break;
      }
      $res = False;
      break;
    }

    case "view":
    {
      if ($eros === true && $erosflag === false)
      {
          $res = False;
          break;
      }

      if ($approved === false && $adminflag === false)
      {
        $res = False;
        break;
      }

      if ($magic === True && $magicflag === False)
      {
        $res = False;
        break;
      }

/*
      if ($eros === true && $currentsite === "vulcan" && $currentpage === "summary")
      {
        logentry("skipping eros link site=vulcan, currentaction=summary");
        $res = False;
        break;
      }
*/
      $res = True;
      break;
    }

    case "add":
    {
      if ($adminflag === True)
      {
        $res = True;
        break;
      }

//      logentry("accesslink.21: siglabelpath=".var_export($siglabelpath, True)." sub=".var_export($sub, True)." flag=".var_export($erosflag, True));
      if ($sub === "top.eros" && $erosflag === True)
      {
        $res = True;
        break;
      }

     $res = False;
     break;
    }

    case "edit":
    {
      if (flag("ADMIN", $memberid) === True)
      {
        $res = True;
        break;
      }

      if ($sub === "top.eros" && $erosflag === True)
      {
        $res = True;
        break;
      }

      if ($data["postedby"] === $memberid)
      {
       $res = True;
       break;
      }

      $res = False;
      break;
    }

    case "broken":
    case "flag":
    {
      if (flag("AUTHENTICATED", $memberid) === True)
      {
        $res = True;
        break;
      }
      $res = False;
      break;
    }
  }

  if ($op === "add" || $op === "approve")
  {
//   logentry("accesslink.100: op=".var_export($op, True)." memberid=".var_export($memberid, True)." erosflag=".var_export($erosflag, True). " res=".var_export($res, True));
  }
  return $res;
}

/**
 * copied from zoidweb1
 *
 * @since 20131014
 */
function sortsidebaritems($a, $b)
{
  $foo = isset($a["title"]) ? $a["title"] : null;
  $bar = isset($b["title"]) ? $b["title"] : null;

  if ($foo < $bar) return -1;
  if ($foo == $bar) return 0;
  if ($foo > $bar) return 1;
}

/**
 * @since 20131028
 * @param string op operation like 'browse', 'add', 'edit', etc
 * @return bool
 */
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
//      logentry("zoidweb4.accesssig.100: iserospath=".var_export($iserospath, true)." erosflag=".var_export($erosflag, true)." labelpath=".var_export($path, true));
      if ($iserospath === True && $erosflag === False)
      {
        $res = False;
        break;
      }
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
      // anyone with "eros" flag can add a link
      if ($iserospath === True && $erosflag === True)
      {
       $res = True;
       break;
      }
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

function getpage($title, $options=null)
{
  $_options = array();
  $_options["staticskinurl"] = SHAREDSKINURL;
//  $_options["sitecss"] = SITECSS;

  if (is_array($options))
  {
    $options = array_merge($_options, $options);
  }
  else
  {
    $options = $_options;
  }

  $disablepageheader = False;
  if (is_array($options) && array_key_exists("disablepageheader", $options))
  {
   $disablepageheader = $options["disablepageheader"];
  }
  $tmpl = getsmarty();

  $page = _getpage($title, $options);
  if ($disablepageheader === False)
  {
//    $page->addScript("https://ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.js", ["execute" => "immediate"]);
//    $page->addStyleSheet("//ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/themes/start/jquery-ui.css");
// @since 20161021
// made small change in www/index.js, now migrate 1.4.1 is not needed
//    $page->addScript("//code.jquery.com/jquery-migrate-1.4.1.js");
    $page->addScript("https://code.jquery.com/jquery-migrate-3.0.0.js");
//    $page->addStyleSheet(SHAREDSKINURL."css/sidebar.css");
//    $page->addStyleSheet(SHAREDSKINURL."css/zoidweb3.css");
//    $page->addStyleSheet(SHAREDSKINURL."css/actions.css");
//    $page->addStyleSheet(SHAREDSKINURL."css/breadcrumbs.css");

    /**
     * @since 2016nov06
     */
    $page->addStyleSheet(SHAREDSKINURL."css/bbsengine3badge.css");

    $page->addStyleSheet(SHAREDSKINURL."css/panel.css");

   /**
    * @since 20160601
    */
   $page->addStylesheet(SHAREDSKINURL."css/pagetransition.css");

   $page->setMetaData("google-site-verification", "-crBDeAJOq6aN0255NpBZKq9KiCFhIa7GVozMsjdb0Y");
   $page->setMetaData("viewport", "width=device-width, initial-scale=1");

   $page->addScriptDeclaration($tmpl->fetch("googleanalytics.tmpl"));

   $page->addScript(STATICJAVASCRIPTURL."bbsengine4.js", ["execute" => "defer"]);

//   $page->addScript(STATICJAVASCRIPTURL."tooltip.js", ["execute" => "defer"]);

   $page->addScript(STATICJAVASCRIPTURL."topbar.js", ["execute" => "defer"]);
   $page->addStyleSheet(SHAREDSKINURL."css/topbar.css");

   $page->addScript(STATICJAVASCRIPTURL."clock.js", ["execute" => "defer"]);
   $page->addStylesheet(SHAREDSKINURL."css/clock.css");

   $page->addStyleSheet(SHAREDSKINURL."css/actions.css");

   // commented out 2016oct24, trying to fix the problem with get-notify-count
   //   $page->addScript(ENGINEURL."js/jquery.jsonp.js", ["execute" => "defer"]);
   //   $page->addScript(STATICJAVASCRIPTURL."page.js", ["execute" => "defer"]);

   //   $page->addStyleSheet(SHAREDSKINURL."css/stickyfooter.css");

   if (defined("FONTAWESOMECSSURL") === True)
   {
     $page->addStyleSheet(FONTAWESOMECSSURL);
   }
   $page->addStyleSheet(SHAREDSKINURL."css/noscript.css");
   $page->addBodyContent($tmpl->fetch("noscript.tmpl"));

   if (getcurrentmemberid() > 0)
   {
     $page->addScript(ENGINEURL."js/notify.js", ["execute" => "async"]);
     $page->addStyleSheet(SHAREDSKINURL."css/notify.css");
   }

   if (getsitevar("smoothstate") === True)
   {
     // @FIX local copy because I had to make a small patch so that forms would work properly (confirmed?)
     $page->addScript(\ENGINEURL."js/jquery.smoothState.js");
     $page->addScript(\ENGINEURL."js/initsmoothstate.js");
   }
   $page->addStyleSheet(\SHAREDSKINURL."css/animate.css");
   $page->addStyleSheet(\SHAREDSKINURL."css/page.css");
    // @since 20170420
    $page->addStyleSheet("//fonts.googleapis.com/css?family=Montserrat|Open+Sans");
}

  return $page;
}

/**
 * @since 20120425
 */
function accessproject($op, $data=null, $memberid=null)
{
  if ($memberid === null)
  {
    $memberid = getcurrentmemberid();
  }

  $project = isset($data["project"]) ? $data["project"] : null;

//  logentry("accessproject.98: project.hidden=".var_export($project["hidden"], True));

  $hidden = isset($project["hidden"]) && $project["hidden"] === True ? True : False;
//  logentry("accessproject.99: hidden=".var_export($hidden, True));

  $adminflag = flag("ADMIN", $memberid);

//  logentry("accessproject.100: name=".var_export($project["name"], True)." hidden=".var_export($hidden, True)." adminflag=".var_export($adminflag, True)." op=".var_export($op, True));

  switch ($op)
  {
    case "detail":
    {
      if ($hidden === False)
      {
        $res = True;
        break;
      }
      if ($adminflag === True)
      {
        $res = True;
        break;
      }
      if ($hidden === True && $adminflag === True)
      {
        $res = True;
        break;
      }
      $res = False;
      break;
    }
    case "edit":
    case "delete":
    case "hide":
    case "unhide":
    {
      if ($adminflag === True)
      {
        $res = True;
        break;
      }
      $res = False;
      break;
    }
    case "showhidden":
    {
      if ($hidden === True && $adminflag === True)
      {
        $res = True;
        break;
      }
      $res = False;
      break;
    }
    default:
    {
      $res = PEAR::raiseError("unknown operation requested");
      break;
    }
  }
//  logentry("accessproject.110: res=".var_export($res, True));
  return $res;
}

/**
 * @since 20140810
 */
function buildpathfromuri($uri)
{
  logentry("buildpathfromuri.100: !! renamed to buildlabelpath");
  return buildlabelpath($uri);
}

function buildlinkactions($memberid, $data)
{
  $linkid = isset($data["linkid"]) ? intval($data["linkid"]) : null;
  $link = isset($data["link"]) ? $data["link"] : null;
  $approved = $link["approved"];
  $broken = $link["broken"];
  $eros = $link["eros"];

//  logentry("buildlinkactions.200: broken=".var_export($broken, True));

  $actions = array();
  if (getcurrentaction() !== "detail")
  {
    $actions[] = array("href" => VULCANURL . "detail-{$linkid}", "title" => "detail", "class" => "fa fa-angle-double-down fa-fw");
  }

  if (flag("ADMIN"))
  {
    $actions[] = array("href" => VULCANURL . "edit-{$linkid}", "title" => "edit", "class" => "fa fa-edit fa-fw");
    if ($approved === False)
    {
      $actions[] = array("href" => VULCANURL . "approve-{$linkid}", "title" => "approve", "class" =>"fa fa-thumbs-up fa-fw");
    }
  }

  if (flag("AUTHENTICATED"))
  {
    if ($broken === False)
    {
      $actions[] = array("href" => VULCANURL . "broken-{$linkid}", "title" => "report broken link", "class" => "fa fa-unlink fa-fw");
    }
  }

  return $actions;
}

/**
 * local version of getsmarty() that sets up plugin dirs, template dirs, etc
 *
 * @since 20140710
 */
function getsmarty($options=null)
{
  $options = [];
  $options["pluginsdir"] = [SMARTYPLUGINSDIR, "/srv/www/zoid6/smarty/"];
  $options["templatedir"] = SMARTYTEMPLATESDIR;
  $options["compiledir"] = SMARTYCOMPILEDTEMPLATESDIR;
  $options["compileid"] = LOGENTRYPREFIX;
  return _getsmarty($options);
}


/**
 * @since 20140726
 */
/**
 * @since 20140807
 */
function fetchpagefooter($options=null)
{
  $tmpl = getsmarty();
  $mantra = getrandommantra();
  if (($mantra === false))
  {
    logentry("fetchpagefooter.100: " . $mantra->toString());
    $mantra = null;
  }

  $tmpl->assign("mantra", $mantra);
  return $tmpl->fetch("pagefooter.tmpl");
}

/**
 * @since 20140911
 */
function getprojectid($projectname)
{
 $dbh = \bbsengine6\database\connect(\SYSTEMDSN);
 if (($dbh === false))
 {
  logentry("getprojectid.100: " . $dbh->toString());
  return $dbh;
 }
 $sql = "select id from repo.project where name=?";
 $dat = array($projectname);
 $res = $dbh->getOne($sql, array("integer"), $dat, array("text"));
 if (($res === false))
 {
  logentry("getprojectid.102: ".$res->toString());
  return $res;
 }
 return $res;
}

/**
 * @since 20140911
 */
function accesspost($op, $data=null, $memberid=null)
{
  $siguri = isset($data["siguri"]) ? $data["siguri"] : null;
  $post = isset($data["post"]) ? $data["post"] : null;
  $id = isset($data["id"]) ? intval($data["id"]) : null;

  if ($memberid === null)
  {
    $memberid = getcurrentmemberid();
  }

  $admin = flag("ADMIN", $memberid);
  $postedbyid = $post["postedbyid"];

//  logentry("accesspost.199: post.frozen=".var_export($post["frozen"], True));

  $frozen = toboolean($post["frozen"], "zoid6.accesspost.frozen");
  $draft = toboolean($post["draft"], "zoid6.accesspost.draft");
  $junk = toboolean($post["junk"], "zoid6.accesspost.junk");

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
     if (($sig === false))
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
 * @since 20150409
 */
function buildprojectactions($data)
{
 $project = $data["project"];
 $projectname = $project["name"];

 $actions = array();
 if (accessproject("detail", $data) === True && getcurrentaction() !== "detail")
 {
  $actions[] = array("name" => "detail", "title" => "project detail", "href" => "//projects.zoidtechnologies.com/{$projectname}", "class" => "fa fa-angle-double-down fa-fw");
 }

 if (accessproject("edit", $data) === True)
 {
  $actions[] = array("name" => "edit", "title" => "edit", "href" => REPOURL . "project-edit/{$projectname}", "class" => "fa fa-edit fa-fw");
 }

 if (accessproject("hide", $data) === True)
 {
  if ($project["hidden"] === False)
  {
   $actions[] = array("name" => "hide", "title" => "hide", "href" => REPOURL . "project-hide/{$projectname}", "class" => "fa fa-eye-slash fa-fw");
  }
  else
  {
   $actions[] = array("name" => "unhide", "title" => "unhide", "href" => REPOURL . "project-unhide/{$projectname}", "class" => "fa fa-eye fa-fw");
  }
 }

 if (accessproject("delete", $data) === True)
 {
  $actions[] = array("name" => "delete", "title" => "delete", "href" => REPOURL . "project-delete/{$projectname}", "class" => "fa fa-trash fa-fw");
 }

/*
 if (accessproject("download", $project) === True)
 {
  $actions[] = array("name" => "download", "title" => "download", "href" => "/download/{$projectname}", "class" => "fa fa-download fw");
 }
*/
 return $actions;

}

/**
 * @since 20141229
 */
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

  if (($record === false))
  {
    logentry("buildrecord.100: " . $record->toString());
    $record = null;
  }

  return $record;
}

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

/**
 * @since 20170502
 */
function buildnotifydata($notifytype, $data)
{
  switch ($notifytype)
  {
    case "link":
    {
      $linkid = isset($data["linkid"]) ? $data["linkid"] : null;
      $link = getlinkbyid($linkid);
      if (($link === false))
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

/*
 * function which accepts an html_quickform2 instance and calls the callback if the form is validated.
 * @since 20150629
 */
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
      if (($res === false))
      {
        logentry("processform.101: " . $res->toString());
        return $res;
      }

      logentry("processform.500: exit");
      return $res;
    }
    return False;
}


/**
 * @since 20150701
 * @param postid integer
 * @return dictionary or PEAR::error
 */
function getpost($id, $maxdepth=0, $currentdepth=0)
{
  $memberid = getcurrentmemberid();

//  logentry("getpost.99: id=".var_export($id, True)." depth=".var_export($depth, True));
//  $sql = "select * from sophia.post where id=?";
  $sql = "select * from sophia.post where id=?";
  $dat = [$id];
  $dbh = \bbsengine6\database\connect(\SYSTEMDSN);
  $post = $dbh->getRow($sql, null, $dat, array("integer"));
  if (($post === false))
  {
    logentry("getpost.100: " . $post->toString());
    return $post;
  }
  if ($post === null)
  {
    logentry("getpost.104: id=".var_export($id, True)." returned null");
    return $post;
  }
  $post["frozen"] = toboolean($post["frozen"], "zoid6.getpost.frozen");
  $post["draft"] = toboolean($post["draft"], "zoid6.getpost.draft");
  $post["junk"] = toboolean($post["junk"], "zoid6.getpost.junk");
  $post["postedbyid"] = intval($post["postedbyid"]);

  $sigmap = getsigmap("sophia.map_post_sig", "postid", $id);
  if (($sigmap === false))
  {
    logentry("getpost.105: " . $sigmap->toString());
    $sigmap = null;
  }
//  logentry("getpost.106: sigmap=".var_export($sigmap, True));
//  $sigs = implodesigmap($sigmap);
//  logentry("getpost.110: siglist=".var_export($siglist, True));
  $post["sigs"] = $sigmap;
  $post["actions"] = buildpostactions($post);

  $post["replies"] = [];

//  logentry("getpost.108: postid=".var_export($id, True)." currentdepth=".var_export($currentdepth, True)." maxdepth=".var_export($maxdepth, True));

  if ($maxdepth > 0 && $currentdepth <= $maxdepth)
  {
//    logentry("getpost.110: gathering replies for post id #{$id}...");
    $sql = "select id from sophia.getreplies(?)";
    $dat = [$id];
    $res = $dbh->getAll($sql, ["integer"], $dat, ["integer"]);
    foreach ($res as $rec)
    {
      $replyid = $rec["id"];
//      logentry("getpost.111: replyid=".var_export($replyid, True));
//      logentry("getpost.112: calling getpost(".var_export($replyid, True).")");
      $reply = getpost($replyid, $maxdepth, $currentdepth+1);
      //    logentry("getpost.120: reply=".var_export($reply, True));
      if (accesspost("view", $reply, $memberid) === True)
      {
//        logentry("getpost.121: accesspost('view',...,".var_export($memberid, True).") returned true");
        $post["replies"][] = $reply;
      }
    }
  }
  return $post;
}

/**
 * @since 20150702
 */
function buildpostactions($post)
{
  $frozen = toboolean($post["frozen"], "buildpostactions.frozen"); // isset($post["frozen"]) && $post["frozen"] === True ? True : False;
  $junk = toboolean($post["junk"], "buildpostactions.junk");
  $draft = toboolean($post["draft"], "buildpostactions.draft"); // isset($post["draft"]) && $post["draft"] === True ? True : False;
  $id = isset($post["id"]) ? intval($post["id"]) : null;
//  logentry("buildpostactions.100: frozen=".var_export($frozen, True)." draft=".var_export($draft, True)." id=".var_export($id, True));

  $actions = array();

  $data = [];
  $data["post"] = $post;
  if (accesspost("reply", $data))
  {
    $actions[] = array("href" => SOPHIAURL."post-reply-{$id}", "title" => "reply", "desc" => "reply to post with id #{$id}", "class" => "fa fa-reply fa-fw");
  }
  if (accesspost("delete", $data))
  {
    $actions[] = array("href" => SOPHIAURL."post-delete-{$id}", "title" => "delete", "desc" => "delete post with id #{$id}", "class" => "fa fa-trash fa-fw");
  }
  if (accesspost("edit", $data))
  {
    $actions[] = array("href" => SOPHIAURL."post-edit-{$id}", "title" => "edit", "desc" => "edit post with id #{$id}", "class" => "fa fa-edit fa-fw");
  }
  if (accesspost("freeze", $data))
  {
    $actions[] = ["href" => SOPHIAURL."post-freeze-{$id}", "title" => "freeze", "desc" => "freeze post with id #{$id}", "class" => "fa fa-lock fa-fw"];
  }
  if (accesspost("thaw", $data))
  {
    $actions[] = array("href" => SOPHIAURL."post-thaw-{$id}", "title" => "thaw", "desc" => "thaw post with id #{$id}", "class" => "fa fa-unlock fa-fw");
  }
  if (accesspost("markasdraft", $data))
  {
    $actions[] = array("href" => SOPHIAURL."post-markasdraft-{$id}", "title" => "mark as draft", "desc" => "mark post with id #{$id} as draft");
  }
  if (accesspost("markaspublished", $data))
  {
    $actions[] = array("href" => SOPHIAURL."post-markaspublished-{$id}", "title" => "mark as published", "desc" => "mark post with id #{$id} as published");
  }
  if (accesspost("detail", $data))
  {
    $actions[] = ["href" => SOPHIAURL."post-detail-{$id}", "title" => "detail", "desc" => "show post detail and replies for id #{$id}", "class" => "fa fa-fw fa-angle-double-down"];
  }
  if (accesspost("markasjunk", $data))
  {
    $actions[] = ["href" => SOPHIAURL."post-markasjunk-{$id}", "title" => "junk", "desc" => "mark this post as junk", "class" => "fa fa-trash-alt fa-fw"];
  }

  return $actions;
}

/**
 * @since 20150927
 * @return string
 * @param siglabelpaths array of siglabelpaths
 */
function implodesiglabelpaths($siglabelpaths)
{
  return implode(", ", $siglabelpaths);
}

/**
 * @since 20151029
 * @param integer postid
 * @return list of sigs in a particular post
 */
function getsigpostmap($postid)
{
  $res = getsigmap("sophia.map_sig_post", "postid", $postid);
  if (($res === false))
  {
    logentry("getsigpostmap.100: " . $res->toString());
    return $res;
  }
  return $res;
}

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
function getsiglinkmap($linkid)
{
  $res = getsigmap("vulcan.map_link_sig", "linkid", $linkid);
  if (($res === false))
  {
    logentry("getsiglinkmap.100: " . $res->toString());
  }
  return $res;
}

/**
 * @since 20150404
 * moved to bbsengine3.php
 * function buildpagerinfo($sql, $params)
*/

/**
 * @since 20150722
 */
function gettotalposts()
{
  $dbh = \bbsengine6\database\connect(\SYSTEMDSN);
  if (($dbh === false))
  {
    logentry("gettotalpostcount.100: " . $dbh->toString());
    return $dbh;
  }
  $sql = "select count(id) from sophia.post";
  $res = $dbh->getOne($sql);
  return $res;
}

/**
 * @since 20150722
 */
function gettotallinks()
{
  $dbh = \bbsengine6\database\connect(\SYSTEMDSN);
  if (($dbh === false))
  {
    logentry("gettotallinkcount.100: " . $dbh->toString());
    return $dbh;
  }
  $sql = "select count(id) from vulcan.link";
  $res = $dbh->getOne($sql);
  return $res;
}

/**
 * @since 20150722
 */
function getmemberposts($memberid)
{
  $dbh = \bbsengine6\database\connect(\SYSTEMDSN);
  if (($dbh === false))
  {
    logentry("getmemberpostcount.100: " . $dbh->toString());
    return $dbh;
  }
  $sql = "select count(id) from sophia.post where postedbyid=?";
  $dat = array($memberid);
  $res = $dbh->getOne($sql, array("integer"), $dat, array("integer"));
  return $res;
}

/**
 * @since 20150722
 */
function getmemberlinks($memberid)
{
  $dbh = \bbsengine6\database\connect(\SYSTEMDSN);
  if (($dbh === false))
  {
    logentry("getmemberlinkcount.100: " . $dbh->toString());
    return $dbh;
  }
  $sql = "select count(id) from vulcan.link where postedby=?";
  $dat = array($memberid);
  $res = $dbh->getOne($sql, array("integer"), $dat, array("integer"));
  return $res;
}

function getpostreplycount($postid)
{
  static $count = 0;

  $dbh = \bbsengine6\database\connect(\SYSTEMDSN);
  if (($dbh === false))
  {
    logentry("getpostreplycount.100: ".$dbh->toString());
    return $dbh;
  }
  $sql = "select count(id) from sophia.post where parentid=?";
  $dat = [$postid];
  $res = $dbh->getOne($sql, ["integer"], $dat, ["integer"]);
  if (($res === false))
  {
    logentry("getpostreplycount.110: " . $res->toString());

  }
  $count += $res["count"];
  return $count;
}

/**
 * function to build up a generic page (like "about") and call
 * displaypage().  this function elimintates a lot of duplicate code.  it is
 * not in the engine because of the buildsidebarmenu() call
 *
 * @since 20160204
 * @param string $pagename
 * @return result of displaypage() engine call
 */
function displaygenericpage($pagename)
{
/*    $tmpl = getsmarty();
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
    if (($res === false))
    {
      logentry("displaygenericpage.100: ". $res->toString());
    }
*/
  $data = [];
  $data["pagetemplate"] = "{$pagename}.tmpl";
  $data["pagename"] = $pagename;
  $page = getpage($pagename);
  return displaypage($page, $data);
}

function getreplycount($postid)
{
  $dbh = \bbsengine6\database\connect(\SYSTEMDSN);
  $sql = "with recursive t as (select id from sophia.post where parentid=? union all select post.id from sophia.post join t on (sophia.post.parentid=t.id)) select count(*) from t";
  $dat = [$postid];
  $res = $dbh->getOne($sql, null, $dat, null);
  if (($res === false))
  {
    logentry("getreplycount.100: " . $res->toString());
  }
  return $res;
}

/**
 * @since 20140512
 * moved 20190523 to bbsengine4.php
 */
/*
function getrandommantra()
{
  $sql = "select id from engine.mantra order by random() limit 1";
  $dbh = \bbsengine6\database\connect(SYSTEMDSN);
  if (($dbh === false))
  {
    logentry("getrandommantra.100: " . $dbh->toString());
    return $dbh;
  }

  $mantraid = $dbh->getOne($sql);
  if (($mantraid === false))
  {
    logentry("getrandommantra.110: " . $mantraid->toString());
    return $mantraid;
  }

  $mantra = getmantra($mantraid);
  $mantra["id"] = $mantraid;
  return $mantra;
}
*/
/**
 * @since 20171125
*/
function displaypage($page, $data=[])
{
//    logentry("zoidweb4.displaypage.100: data=".var_export($data, true));

//  logentry("zoidweb3.displaypage called");

  // options contains 'metadata' key
  // for each key in the list of fields, use metadata if avail, otherwise use define
/*
  $metadata = isset($options["metadata"]) ? $options["metadata"] : [];
  $tags = [
    "SITEURL" => "og:url",
    "SITETITLE" => "og:title",
    "SITEKEYWORDS" => "keywords",
    "SITETITLE" => "og:title",
    "SITEDESCRIPTION" => "og:description",
    "SITETYPE" => "og:type",
    "FACEBOOKAPPID" => "fb:app_id",
    "SITEGENERATOR" => "bbsengine4"
  ];
  $foo = [];
  foreach ($tags as $k => $v)
  {
    if (isset($metadata[$v]))
    {
      $foo[$v] = $metadata[$v];
    }
    else if (defined($k) === True)
    {
      $foo[$v] = constant($k);
    }
    else
    {
      $foo[$v] = "NEEDINFO-{$k}";
    }
  }
//  $mergedmeta = array_merge($defaultmeta, $metadata);
//  logentry("displaypage.zoidweb.100: foo=".var_export($foo, True));

  foreach ($foo as $key => $content)
  {
    if (strpos($key, "og:") === 0 || strpos($key, "fb:") === 0)
    {
      $data = "<meta property=\"{$key}\" content=\"{$content}\">";
//      $page->addRawHeaderData($data);
    }
    else
    {
//      $data = "<meta name=\"{$key}\" content=\"{$content}\">";
//      $page->setMetaData($key, $content);
    }
//    $page->addRawHeaderData($data);
  }

//  $pagedata = isset($options["pagedata"]) ? $options["pagedata"] : null;
//  $template = isset($pagedata["template"]) ? $pagedata["template"] : "intentional404.tmpl";
*/
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

function vulcangetneedsapproval()
{
  $dbh = \bbsengine6\database\connect(\SYSTEMDSN);
  $sql = "select * from vulcan.link where approved='f' and eros='f'";
  $dat = [];
  $res = $dbh->getAll($sql, null, $dat, null);
  if (($res === false))
  {
      logentry("vulcanneedsapproval.100: " . $res->toString());
      return PEAR::raiseError("database error (code: vulcanneedsapproval.100)");
  }
  if ($res === null)
  {
      logentry("vulcanneedsapproval.110: select returned null");
  }
  $needsapproval = [];
  foreach ($res as $rec)
  {
    $id = $rec["id"];
    $link = getlinkbyid($id);
    if (($link === false))
    {
      logentry("vulcanneedsapproval.120: " . $res->toString());
      continue;
    }
    if (accesslink("view", $link) === True)
    {
      $needsapproval[] = $link;
    }
  }
  return $needsapproval;
}

function vulcangetrecentlyadded()
{
  $dbh = \bbsengine6\database\connect(\SYSTEMDSN);
  if (($dbh === false))
  {
    logentry("vulcangetrecentlyadded.100: " . $dbh->toString());
    return PEAR::raiseError("Database Error (code: vulcangetrecentlyadded.100)");
  }

  $sql = <<<eos
select link.id from vulcan.link
where approved='t' and eros='f'
and ((now() - link.dateapproved <= '30 days'::interval) or (now() - link.lastmodified <= '30 days'::interval))
order by broken asc, dateapproved desc, lastmodified desc, title asc
limit 10
eos;

  $res = $dbh->getAll($sql);
  if (($res === false))
  {
    logentry("vulcangetrecentlyadded.110: " . $res->toString());
    return PEAR::raiseError("Database Error (code: vulcangetrecentlyadded.110)");
  }

  $currentmemberid = getcurrentmemberid();

  $recentlyadded = [];
  foreach ($res as $rec)
  {
    $id = $rec["id"];
    $link = getlinkbyid($id);
    if (($link === false))
    {
      logentry("vulcangetrecentlyadded.120: " . $link->toString());
      continue;
    }

    if (accesslink("view", $link) === True)
    {
      $recentlyadded[] = $link;
    }
  }
  return $recentlyadded;
}

/**
 * copied from zoidweb2
 *
 * @since 20180223
 */
function sortsidebarmenu($a, $b)
{
  $foo = isset($a["title"]) ? $a["title"] : null;
  $bar = isset($b["title"]) ? $b["title"] : null;

  if ($foo < $bar) return -1;
  if ($foo == $bar) return 0;
  if ($foo > $bar) return 1;
}

/**
 * copied from zoidweb2
 *
 * @since 20131014
 */
function buildsidebarmenu($menu=[])
{
//  $currentpage = getcurrentpage();
//  $menu = array();

  if (flag("ADMIN"))
  {
      $menu[] = array("name" => "addflag", "title" => "add system flag", "url" => ENGINEURL."flag-add", "desc" => "add system flag to the database");
      $menu[] = array("name" => "addmantra", "title" => "add mantra", "url" => ENGINEURL."mantra-add", "desc" => "add mantra");
      $menu[] = array("name" => "addsitenews", "title" => "add site news", "url" => \TEOSURL."sitenews/add-post", "desc" => "add a post to the 'site news' sig to be displayed on the www site");
//      $menu[] = array("name" => "addlink", "title" => "add link", "url" => VULCANURL . "add");
//      $menu[] = array("name" => "addfeed", "title" => "add feed", "url" => DEMETERURL . "add");
  }

  $menu[] = array("name" => "about", "title" => "about", "url" => "/about", "desc" => "about this site");
  $menu[] = array("name" => "teos", "title" => "teos", "url" => \TEOSURL, "desc" => "catalog view");
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

/**
 * function which returns the sidebar. if the param is null, builds standard menu options, else passes direct to _fetchnavbar
 *
 * @since 20150309
 */
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



/**
 * function which converts a postgres array (comma separated text) to a php array
 * https://prosuncsedu.wordpress.com/2009/10/13/postgres-array-to-php-array-or-vice-versa/
 *
 * @since 20190822
 * @since 20200418 removed in favor of json
 * @param $text postgres array in text format
 * @return array
 */
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

}

?>
