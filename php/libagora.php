<?php

require_once("InputDate.php");
require_once("InputUrl.php");

/**
 * return the count of amznitems to which the current user has access
 *
 * @since 20200412
 * @return integer
 */
function getamznitemcount()
{
    $dbh = dbconnect(SYSTEMDSN);
    $sql = "select asin from agora.amznitem";
    $res = $dbh->getAll($sql);
    if (PEAR::isError($res))
    {
        logentry("agora.index.getamznitemcount.100: " . $res->toString());
        return $res;
    }

    $count = 0;
    foreach ($res as $rec)
    {
        if (accessamznitem("view", $rec) === true)
        {
            $count++;
        }
    }
    return $count;
}

function buildamznitemrecord($values)
{
    $amznitem = [];
    $amznitem["asin"] = $values["asin"];
    $amznitem["imdbid"] = $values["imdbid"];
    $amznitem["checks"] = $values["checks"];
    $amznitem["affiliatelink"] = $values["affiliatelink"];
    $amznitem["title"] = $values["title"];
    
    logentry("buildamznitemrecord.100: " . var_export($amznitem, true));

    return $amznitem;
}

function buildamznitemfieldset($form)
{
    $fieldset = $form->addFieldset("amznitem")->setLabel("Amazon Item");

    $asin = $fieldset->addText("asin", "size=10")->setLabel("ASIN");
    // $asin->addRule("required", "'ASIN' is a required field");

    $imdbid = $fieldset->addText("imdbid", "size=10")->setLabel("IMDBId");

    $title = $fieldset->addText("title", "size=60")->setLabel("Title");
    $title->addRule("required", "'Title' is a required field");

    $affiliatelink = $fieldset->addElement(new HTML_Quickform2_Element_InputUrl("affiliatelink", "size=60"))->setLabel("affiliate link");
    $affiliatelink->addRule("required", "'affiliate link' is a required field");

    $checks = $fieldset->addText("checks", "size=60")->setLabel("Checks");
    $checks->addRule("required", "'checks' is a required field");

    $sigs = $fieldset->addText("sigs", "size=60")->setLabel("SIGs");
    $sigs->addRule("required", "'SIGs' is a required field");
    $sigs->addRule("callback", "all SIGs must exist", "validatesigs");

    return $form;
}

function buildamznitemcommentrecord($values)
{
    $amznitemcomment = [];
    $amznitemcomment["comment"] = $values["comment"];

    $datewatched = $values["datewatched"];
    logentry("buildamznitemcommentrecord.100: datewatched=".var_export($datewatched, true));
    if (is_array($datewatched) === true)
    {
        $amznitemcomment["datewatched"] = sprintf("%s-%s-%s", $datewatched["Y"], $datewatched["F"], $datewatched["d"]);
    }
    elseif ($datewatched === "")
    {
        $amznitemcomment["datewatched"] = null;
    }
    else
    {
        $amznitemcomment["datewatched"] = $datewatched;
    }
    $amznitemcomment["watchmethod"] = $values["watchmethod"];
    $amznitemcomment["asin"] = $values["asin"];
    $amznitemcomment["imdbid"] = $values["imdbid"];

    return $amznitemcomment;
}

function buildamznitemcommentfieldset($form)
{
    $fieldset = $form->addFieldset("amznitemcomment")->setLabel("Amazon Item Comment");
    $fieldset->addHidden("asin");
    $fieldset->addHidden("parentid");
    $watchmethod = $fieldset->addText("watchmethod")->setLabel("Watch Method");
    $datewatched = $fieldset->addElement(new HTML_QuickForm2_Element_InputDate("datewatched"))->setLabel("Date Watched");
    $comment = $fieldset->addTextarea("comment", ["rows" => 10, "cols" => 50, "data-editormode" => "tinymce"])->setLabel("Comment");
    $comment->addRule("required", "'comment' is a required field");
//    $datewatched = $fieldset->addElement("date", "datewatched", null, ["format" => "d-F-Y"])->setLabel("Date Watched");
    return $form;
}

function getamznitemcomments($parentid)
{
    $dbh = dbconnect(SYSTEMDSN);
    if (PEAR::isError($dbh))
    {
      logentry("libagora.getamznitemcomments.100: " . $dbh->toString());
      return $dbh;
    }

    $sql = "select * from agora.amznitemcomment where parentid=".$dbh->quote($parentid, "integer");

    $comments = [];

    $res = $dbh->getAll($sql);
    if (PEAR::isError($res))
    {
        logentry("agora.index.100: " . var_export($res->toString(), true));
        return $res;
    }
    if (count($res) > 0)
    {
        foreach ($res as $rec)
        {
            $amznitemcomment = decodejson($rec["attributes"]);
            if (accessamznitemcomment("view", $amznitemcomment) === true)
            {
              $id = $rec["id"];
              $actions = buildamznitemcommentactions($id, $amznitemcomment);
              $rec["actions"] = $actions;
              $comments[] = $rec;
            }
        }
    }
    return $comments;
}

/**
 * given an operation and an amznitem dict, returns a boolean to indicate if the operation is allowed.
 *
 * @param $op string
 * @param $amznitem dictionary
 * @param $memberid integer 
 * @since 20190811
 * @since 20200412 moved to libagora from zoidweb4
 * @return boolean
 */
function accessamznitem($op, $amznitem, $memberid=null)
{
    if ($memberid === null)
    {
        $memberid = getcurrentmemberid();
    }
    switch ($op)
    {
        case "edit":
        {
          if (flag("ADMIN", $memberid) === true)
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
 * builds an array of dictionaries with available actions
 *
 * @param $amznitem dictionary
 * @param $memberid integer
 * @return array
 * @since 20190811
 * @since 20200412 moved to libagora from zoidweb4
 */
function buildamznitemactions($amznitem, $memberid=null)
{
    $asin = $amznitem["asin"];
    $nodeid = $amznitem["id"];

    $actions = [];
    if (accessamznitem("edit", $amznitem, $memberid) === true)
    {
      $actions[] = ["href" => AGORAURL . "amznitem-edit-{$nodeid}", "title" => "edit amznitem", "class" => "fa fa-edit fa-fw"];
    }
    if (accessamznitemcomment("add", $amznitem, $memberid) === true)
    {
      $actions[] = ["href" => AGORAURL . "amznitemcomment-add-{$nodeid}", "title" => "add comment", "class" => "fa fa-comment fa-fw"];
    }
    return $actions;
}

/**
 * builds an array of dictionaries with available actions for the given amznitemcomment
 *
 * @param $amznitemcomment dictionary
 * @param $memberid integer
 * @return array
 * @since 20190811
 * @since 20200412 moved to libagora from zoidweb4
 */
function buildamznitemcommentactions($nodeid, $amznitemcomment)
{
//  logentry("buildamznitemcommentactions.200: nodeid=".var_export($nodeid, true));
  
  $actions = [];
  if (accessamznitemcomment("edit", $amznitemcomment) === true)
  {
    $actions[] = ["href" => AGORAURL."amznitemcomment-edit-{$nodeid}", "title" => "edit comment", "class" => "fa fa-edit fa-fw"];
  }
  if (accessamznitemcomment("reply", $amznitemcomment) === true)
  {
    $actions[] = ["href" => AGORAURL."amznitemcomment-add-{$nodeid}", "title" => "reply to comment", "class" => "fa fa-reply fa-fw"];
  }
  if (accessamznitemcomment("upvote", $amznitemcomment) === true)
  {
    $actions[] = ["href" => AGORAURL."amznitemcomment-upvote-{$nodeid}", "title" => "upvote comment", "class" => "far fa-thumbs-up"];
  }
/*
  if (accessamznitemcomment("downvote", $amznitemcomment) === true)
  {
    $actions[] = ["href" => AGORAURL."amznitemcomment-downvote-{$nodeid}", "title" => "downvote comment", "class" => "far fa-thumbs-down"];
  }
*/
  return $actions;
}

/**
 * given an operation and an amznitemcomment dict, returns a boolean to indicate if the operation is allowed.
 *
 * @param $op string
 * @param $amznitemcomment dictionary
 * @param $memberid integer 
 * @since 20190811
 * @since 20200412 moved to libagora from zoidweb4
 * @return boolean
 */
function accessamznitemcomment($op, $amznitemcomment, $memberid=null)
{
    if ($memberid === null)
    {
        $memberid = getcurrentmemberid();
    }
    switch ($op)
    {
        case "edit":
        {
          if (flag("ADMIN", $memberid) === true)
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
          break;
        }
        case "add":
        {
          if (flag("AUTHENTICATED"))
          {
            $res = true;
            break;
          }
          $res = false;
          break;

        }
        case "reply":
        {
          if (flag("AUTHENTICATED"))
          {
            $res = true;
            break;
          }
          $res = false;
          break;
        }
        case "upvote":
        {
          if (flag("AUTHENTICATED"))
          {
            $res = true;
            break;
          }
          $res = false;
          break;
        }
        case "downvote":
        {
          if (flag("AUTHENTICATED"))
          {
            $res = true;
            break;
          }
          $res = false;
          break;
        }
        default:
        {
          $res = false;
          logentry("accessamznitemcomment.100: unknown op ".var_export($op, true));
          break;
        }
    }
    return $res;
}

/*
function buildamznitem($rec)
{
  $amznitem = $rec;
//  $amznitem["attributes"] = decodejson($amznitem["attributes"]);
//  $amznitem["sigs"] = postgres_to_php_array($amznitem["sigs"]);

  $breadcrumbs = [];

  $amznitem["comments"] = [];

  foreach ($rec["sigs"] as $sig)
  {
    $breadcrumb = buildbreadcrumbs($sig);
    $breadcrumbs[] = $breadcrumb;
  }
  $amznitem["breadcrumbs"] = $breadcrumbs;

  $amznitem["actions"] = buildamznitemactions($amznitem);
//  logentry("buildamznitem.100: attributes=".var_export($amznitem["attributes"], true));
  
  return $amznitem;
}
*/

/**
 * function to get an amznitem based on the node id instead of the asin
 *
 * @since 20200419
 * @param integer $nodeid
 * @return amznitem
 */
function getamznitembynodeid($nodeid)
{
  $dbh = dbconnect(SYSTEMDSN);
  $sql = "select asin from agora.amznitem where id=?";
  $dat = [$nodeid];
  $asin = $dbh->getOne($sql, ["text"], $dat, ["integer"]);
  if (PEAR::isError($asin))
  {
    logentry("getamznitembynodeid.100: " . $res->toString());
    return $res;
  }
  return getamznitem($asin);
}

/**
 * returns a record from agora.amznitem for the given asin. automagically decodes amznitem.attributes, assigns amznitem.sigs to a php array, and build breadcrumbs.
 * 
 * @since 20200419
 * @return dict
 */
function getamznitem($asin)
{
    $dbh = dbconnect(SYSTEMDSN);
    if (PEAR::isError($dbh))
    {
        logentry("getamznitem.100: " . var_export($dbh, true));
        return $dbh;
    }

    $sql = "select * from agora.amznitem where asin=?";
    $dat = [$asin];
    $amznitem = $dbh->getRow($sql, null, $dat, ["text"]);
    if (PEAR::isError($amznitem))
    {
        logentry("getamznitem.120: " . var_export($amznitem, true));
        return $amznitem;
    }
    if ($amznitem === null)
    {
      logentry("getamznitem.130: query for asin ".var_export($asin, true)." returned null");
      return $amznitem;
    }

    $amznitem["actions"] = buildamznitemactions($amznitem);
    $amznitem["comments"] = [];

    $sigslist = postgres_to_php_array($amznitem["sigs"]);
//    logentry("getamznitem.140: sigslist=".var_export($sigslist, true));
    $breadcrumbs = [];
    foreach ($sigslist as $sig)
    {
      $breadcrumbs[] = buildbreadcrumbs($sig);
    }
    $amznitem["breadcrumbs"] = $breadcrumbs;
    $amznitem["sigs"] = $sigslist;
    $amznitem["attributes"] = decodejson($amznitem["attributes"]);
    return $amznitem;
}

?>
