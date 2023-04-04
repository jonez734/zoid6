<?php

/**
 * @since 20120425
 * @since 20221116
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
  
  $adminflag = checkmemberflag("ADMIN", $memberid);
  
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
 * @since 20150409
 * @since 20221116
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

?>
