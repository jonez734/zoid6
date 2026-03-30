<?php

namespace {
  require_once("config.php");
  require_once("engine.php");
  require_once("database.php");
  require_once("libmember.php");
}

namespace vulcan\lib {
  /**
   * @since 20240829
   */
  function access($op, $data=null, $memberid=null)
  {
  //  return true;
  //  logentry("entering accesslink()");
    if ($memberid === null)
    {
      $memberid = \bbsengine6\member\lib\getcurrentid();
    }

    $sysopflag = \bbsengine6\member\lib\checkflag("SYSOP", $memberid);
    $adminflag = \bbsengine6\member\lib\checkflag("ADMIN", $memberid);
    $erosflag = \bbsengine6\member\lib\checkflag("EROS", $memberid);
    $magicflag = \bbsengine6\member\lib\checkflag("MAGIC", $memberid);

    \bbsengine6\util\logentry("accesslink.300: op=".var_export($op, true)." erosflag=".var_export($erosflag, true)." adminflag=".var_export($adminflag, True)." magicflag=".var_export($magicflag, True));

    $eros = isset($data["eros"]) ? $data["eros"] : null;

    $approved = isset($data["approved"]) ? $data["approved"] : null;

    $magic = isset($data["magic"]) ? $data["magic"] : null;
    
    $siglabelpath = isset($data["sigpath"]) ? $data["sigpath"] : null;
    $sub = substr($siglabelpath, 0, strlen("top.eros"));

    $currentpage = \bbsengine6\getcurrentpage();
    $currentaction = \bbsengine6\getcurrentaction();
    
    $res = true;
    switch ($op)
    {
      case "approve":
      {
        if ($adminflag === true)
        {
          $res = true;
          break;
        }
        $res = false;
        break;
      }

      case "view":
      {
        $res = true;

        if ($eros === true && $erosflag === false)
        {
            $res = false;
        }
        
  /*
        if ($approved === false && $adminflag === false)
        {
          $res = false;
        }
  */      
        if ($magic === true && $magicflag === false)
        {
          $res = false;
        }
        break;
//        return $res;
      }

      case "add":
      {
        if ($adminflag === true)
        {
          $res = true;
          break;
        }
        
  //      logentry("accesslink.21: siglabelpath=".var_export($siglabelpath, True)." sub=".var_export($sub, True)." flag=".var_export($erosflag, True));
        if ($sub === "top.eros" && $erosflag === true)
        {
          $res = true;
          break;
        }

       $res = false;
       break;
      }

      case "edit":
      {
        if (\bbsengine6\member\lib\checkflag("ADMIN", $memberid) === true)
        {
          $res = true;
          break;
        }

/*
        if ($sub === "top.eros" && $erosflag === true)
        {
          $res = true;
          break;
        }
*/        
        if ($data["postedby"] === $memberid)
        {
         $res = true;
         break;
        }

        $res = false;
        break;
      }
      
      case "broken":
      case "flag":
      {
        if (\bbsengine6\member\lib\checkflag("AUTHENTICATED", $memberid) === true)
        {
          $res = true;
          break;
        }
        $res = false;
        break;
      }
    }

    if ($op === "add" || $op === "approve")
    {
  //   logentry("accesslink.100: op=".var_export($op, True)." memberid=".var_export($memberid, True)." erosflag=".var_export($erosflag, True). " res=".var_export($res, True));
    }
    return $res;
  }

  function getlinksigs($url)
  {
    \bbsengine6\util\logentry("libvulcan.getlinksigs.100: url=".var_export($url, true));
    $sql = "select s.* from vulcan.map_link_sig as m, vulcan.link as l, engine.sig as s where m.path = s.path and l.url = m.url and m.path=s.path and m.url=:url";
    //select * from vulcan.map_link_sig as m, engine.sig as s where m.linkid=?";
    $pdo = \bbsengine6\database\connect(\config\SYSTEMDSN);
    $dat = ["url" => $url];
    $stmt = $pdo->prepare($sql);
    $stmt->execute($dat);
    $res = $stmt->fetchAll();
    
    foreach ($res as $rec)
    {
      \bbsengine6\util\logentry("libvulcan.getlinksigs.120: rec=".var_export($rec, true));
    }
    
    return $res;
  }

  function getlinkbyurl($url)
  {
    $pdo = \bbsengine6\database\connect(\config\SYSTEMDSN);
    $currentmemberid = \bbsengine6\member\lib\getcurrentid();

    \bbsengine6\util\logentry("getlinkbyurl.80: id=".var_export($url, true));
    $sql = "select * from vulcan.link where url=:url";
    $dat = ["url" => $url];
    $stmt = $pdo->prepare($sql);
    $stmt->execute($dat);
    $res = $stmt->fetch();
    \bbsengine6\util\logentry("--> libvulcan.getlinkbyurl.100: res=".var_export($res, true));
    if ($res === null)
    {
      \bbsengine6\util\logentry("libvulcan.getlinkbyid.120: select returned null");
      return [];
    }
    
    $link = [];
    $link["url"] = $res["url"];
    $link["title"] = $res["title"];
    $link["sigs"] = getlinksigs($url); // postgres_to_php_array($res["sigs"]);
    $link["postedby"] = $res["postedby"];
    $link["datepostedepoch"] = $res["datepostedepoch"];
  //  $link["breadcrumbs"] = [];
  //  $link["breadcrumbs"] = buildbreadcrumblist($link);
  //  logentry("link.breadcrumbs=".var_export($link["breadcrumbs"], true));
    $flags = [];
    $flags["eros"] = \bbsengine6\toboolean($link["eros"], "vulcan.link.eros");
    $flags["magic"] = \bbsengine6\toboolean($link["magic"], "vulcan.link.magic");
    $flags["approved"] = \bbsengine6\toboolean($link["approved"], "vulcan.link.approved");
    $flags["broken"] = \bbsengine6\toboolean($link["broken"], "vulcan.link.broken");
    $link["flags"] = $flags;

    $actions = buildlinkactions($currentmemberid, ["linkid" => $res["id"], "link" => $link]);
    $link["actions"] = $actions;
    if ($link["broken"] === true)
    {
      $link["icon"] = "fa-chain-broken";
    }
    else
    {
      $link["icon"] = "fa-link";
    }
    \bbsengine6\util\logentry("---!> getlinkbyurl.140: link=".var_export($link, true));

    return $link;
  }

  /**
   * @since 20150925
   * @since 20221117
   * @param integer id id of "item"
   * @param text maptable name of map table (including schema)
   * @param text idfield name of id field (f.e. 'linkid', or 'itemid')
   * @return array list of hrefs for each label along the path of the sig
   *
   */
  function buildbreadcrumblist($link, $table="vulcan.link", $key="id")
  {
    $id = $link[$key];
    
    $pdo = \bbsengine6\database\connect(\config\SYSTEMDSN);
    $sql = "select unnest(sigs) as siglabelpath from {$table} where id=?";
    $dat = [$id];
    $stmt = $pdo->prepare($sql);
    $stmt->execute($dat);
    $res = $stmt->fetch();

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
   * @since 20221117
   */
  function buildlinkactions($memberid, $data)
  {
    $linkid = isset($data["linkid"]) ? intval($data["linkid"]) : null;
    $link = isset($data["link"]) ? $data["link"] : null;
    $approved = $link["approved"];
    $broken = $link["broken"];
    $eros = $link["eros"];

  //  logentry("buildlinkactions.200: broken=".var_export($broken, True));
    
    $actions = [];
    if (\bbsengine6\getcurrentaction() !== "detail")
    {
      $actions[] = array("href" => \config\VULCANURL . "detail-{$linkid}", "title" => "detail", "class" => "fa fa-angle-double-down fa-fw");
    }

    if (\bbsengine6\member\lib\checkflag("ADMIN"))
    {
      $actions[] = array("href" => \config\VULCANURL . "edit-{$linkid}", "title" => "edit", "class" => "fa fa-edit fa-fw");
      if ($approved === False)
      {
        $actions[] = array("href" => \config\VULCANURL . "approve-{$linkid}", "title" => "approve", "class" =>"fa fa-thumbs-up fa-fw");
      }
    }

    if (\bbsengine6\member\lib\checkflag("AUTHENTICATED"))
    {
      if ($broken === False)
      {
        $actions[] = array("href" => \config\VULCANURL . "broken-{$linkid}", "title" => "report broken link", "class" => "fa fa-unlink fa-fw");
      }
    }

    return $actions;
  }

/**
 * @since 20150925
 * @since 20221124
 * @param dict link link being processed
 * @param text maptable name of map table (including schema)
 * @param text idfield name of id field (f.e. 'linkid', or 'itemid')
 * @return array list of hrefs for each label along the path of the sig
 *
 */
/*
function buildbreadcrumblist($linkid)
{
  $pdo = databaseconnect(SYSTEMDSN4);
  $sql = "select unnest(sigs) as siglabelpath from vulcan.link where id=?";
  $dat = [$linkid];
  $stmt = $pdo->prepare($sql);
  $stmt->execute($dat);
  $res = $stmt->fetchAll();
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
*/

} /* namespace vulcan\lib */
?>
