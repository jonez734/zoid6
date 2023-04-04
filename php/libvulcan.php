<?php

require_once("config.php");
require_once("engine.php");
require_once("database.php");

/**
 * @since 20111205
 */
function accesslink($op, $data=null, $memberid=null)
{
//  return true;
//  logentry("entering accesslink()");
  if ($memberid === null)
  {
    $memberid = getcurrentmemberid();
  }

  $adminflag = checkmemberflag("ADMIN", $memberid);
  $erosflag = checkmemberflag("EROS", $memberid);
  $magicflag = checkmemberflag("MAGIC", $memberid);

  logentry("accesslink.300: op=".var_export($op, true)." erosflag=".var_export($erosflag, true)." adminflag=".var_export($adminflag, True)." erosflag=".var_export($erosflag, True));

  $eros = isset($data["eros"]) ? $data["eros"] : null;

  $approved = isset($data["approved"]) ? $data["approved"] : null;

  $magic = isset($data["magic"]) ? $data["magic"] : null;
  
  $siglabelpath = isset($data["sigpath"]) ? $data["sigpath"] : null;
  $sub = substr($siglabelpath, 0, strlen("top.eros"));

  $currentpage = getcurrentpage();
  $currentaction = getcurrentaction();
  
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
      
      return $res;
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
      if (checkmemberflag("ADMIN", $memberid) === true)
      {
        $res = true;
        break;
      }

      if ($sub === "top.eros" && $erosflag === true)
      {
        $res = true;
        break;
      }
      
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
      if (checkmemberflag("AUTHENTICATED", $memberid) === true)
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

function getlinksigs($id)
{
  logentry("libvulcan.getlinksigs.100: id=".var_export($id, true));
  $sql = "select s.* from vulcan.map_link_sig as m, vulcan.link as l, engine.sig as s where m.siglabelpath = s.path and l.id = m.linkid and m.siglabelpath=s.path and m.linkid=?";
  //select * from vulcan.map_link_sig as m, engine.sig as s where m.linkid=?";
  $pdo = databaseconnect(SYSTEMDSN4);
  $dat = [$id];
  $stmt = $pdo->prepare($sql);
  $stmt->execute($dat);
  return $stmt->fetch();

  foreach ($res as $rec)
  {
    logentry("libvulcan.getlinksigs.120: rec=".var_export($rec, true));
  }
  
}

function getlinkbyid($id)
{
  $pdo = databaseconnect(SYSTEMDSN4);
  $currentmemberid = getcurrentmemberid();

  logentry("getlinkbyid.80: id=".var_export($id, true));
  $sql = "select * from vulcan.link where id=?";
  $dat = [$id];
  $stmt = $pdo->prepare($sql);
  $stmt->execute($dat);
  $res = $stmt->fetch();
  logentry("--> libvulcan.getlinkbyid.100: res=".var_export($res, true));
  if ($res === null)
  {
    logentry("libvulcan.getlinkbyid.120: select returned null");
    return [];
  }
  
  $link = [];
  $link["url"] = $res["url"];
  $link["title"] = $res["title"];
  $link["sigs"] = getlinksigs($id); // postgres_to_php_array($res["sigs"]);
  $link["postedby"] = $res["postedby"];
  $link["datepostedepoch"] = $res["datepostedepoch"];
//  $link["breadcrumbs"] = [];
//  $link["breadcrumbs"] = buildbreadcrumblist($link);
//  logentry("link.breadcrumbs=".var_export($link["breadcrumbs"], true));
  $link["eros"] = toboolean($link["eros"], "vulcan.link.eros");
  $link["magic"] = toboolean($link["magic"], "vulcan.link.magic");
  $link["approved"] = toboolean($link["approved"], "vulcan.link.approved");
  $link["broken"] = toboolean($link["broken"], "vulcan.link.broken");
  $actions = buildlinkactions($currentmemberid, ["linkid" => $id, "link" => $link]);
  $link["actions"] = $actions;
  logentry("---!> getlinkbyid.140: link=".var_export($link, true));

  return $link;

/*
  $sigmap = getsigmap("vulcan.map_link_sig", "linkid", $linkid);
  if (PEAR::isError($sigmap))
  {
    logentry("getlinkbyid.30: " . $sigmap->toString());
    return $sigmap;
  }
  $link["sigs"] = $sigmap;
  return $link;
}
*/
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
  
  $pdo = databaseconnect(SYSTEMDSN4);
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
  if (getcurrentaction() !== "detail")
  {
    $actions[] = array("href" => VULCANURL . "detail-{$linkid}", "title" => "detail", "class" => "fa fa-angle-double-down fa-fw");
  }

  if (checkmemberflag("ADMIN"))
  {
    $actions[] = array("href" => VULCANURL . "edit-{$linkid}", "title" => "edit", "class" => "fa fa-edit fa-fw");
    if ($approved === False)
    {
      $actions[] = array("href" => VULCANURL . "approve-{$linkid}", "title" => "approve", "class" =>"fa fa-thumbs-up fa-fw");
    }
  }

  if (checkmemberflag("AUTHENTICATED"))
  {
    if ($broken === False)
    {
      $actions[] = array("href" => VULCANURL . "broken-{$linkid}", "title" => "report broken link", "class" => "fa fa-unlink fa-fw");
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
?>
