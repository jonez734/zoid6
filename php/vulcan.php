<?php

namespace \zoid6\vulcan
{
    define("URL", "https://vulcan.zoidtechnologies.com/");

    function access($op, $link)
    {
        return True;
    }

    function actions($memberid, $data)
    {
      $linkid = isset($data["linkid"]) ? intval($data["linkid"]) : null;
      $link = isset($data["link"]) ? $data["link"] : null;
      $approved = $link["approved"];
      $broken = $link["broken"];

    //  logentry("buildlinkactions.200: broken=".var_export($broken, True));
      
      $actions = [];
      if (\bbsengine6\getcurrentaction() !== "detail")
      {
        $actions[] =["href" => URL . "detail-{$linkid}", "title" => "detail", "class" => "fa fa-angle-double-down fa-fw"];
      }

      if (\bbsengine6\member\flag("ADMIN"))
      {
        $actions[] = ["href" => URL . "edit-{$linkid}", "title" => "edit", "class" => "fa fa-edit fa-fw"];
        if ($approved === False)
        {
          $actions[] = ["href" => URL . "approve-{$linkid}", "title" => "approve", "class" =>"fa fa-thumbs-up fa-fw"];
        }
      }

      if (flag("AUTHENTICATED"))
      {
        if ($broken === False)
        {
          $actions[] = ["href" => URL . "broken-{$linkid}", "title" => "report broken link", "class" => "fa fa-unlink fa-fw"];
        }
      }

      return $actions;
    }

    /**
     * moved from vulcan.php
     * @since 20111205
     */
    function getbyid($linkid)
    {
      $currentmemberid = \bbsengine6\member\getcurrentid();
      
      $dbh = \bbsengine6\database\connect(SYSTEMDSN);
/*
      if (PEAR::isError($dbh))
      {
        logentry("getlinkbyid.10: " .$dbh->toString());
        return $dbh;
      }
*/
      $sql = "select * from vulcan.link where id=:linkid";
      $dat = ["linkid" => $linkid];
      $stmt = $dbh->prepare($sql);
      $stmt->execute($dat);
      if ($stmt->rowCount() == 0)
      {
          return null;
      }
      $res = $stmt->fetch();

      $link = $res;
      $link["magic"] = \bbsengine6\toboolean($link["magic"], "vulcan.link.magic");
      $link["approved"] = \bbsengine6\toboolean($link["approved"], "vulcan.link.approved");
      $link["broken"] = \bbsengine6\toboolean($link["broken"], "vulcan.link.broken");
      $link["breadcrumbs"] = \bbsengine6\blurb\breadcrumbs($link, "vulcan.link.breadcrumbs");
      
    //  logentry("getlinkbyid.130: magic=".var_export($link["magic"], True));

      $actions = \zoid6\vulcan\actions($currentmemberid, array("linkid" => $linkid, "link" => $link));
      $link["actions"] = $actions;

    /*
      $sigmap = getsigmap("vulcan.map_link_sig", "linkid", $linkid);
      if (PEAR::isError($sigmap))
      {
        logentry("getlinkbyid.30: " . $sigmap->toString());
        return $sigmap;
      }
      $link["sigs"] = $sigmap;
    */  
      return $link;
    }

};
?>
