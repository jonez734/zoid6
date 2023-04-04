<?php

require_once("config.php");
require_once("engine.php");
require_once("session.php");
require_once("database.php");

//require_once("libvulcan.php");
//require_once("librepo.php");

print "<h1>greetings, world</h1>";

class index
{
  var $dbh = null;
  
  function getneedsapproval($limit=null)
  {
    $links = [];
    if (flag("ADMIN") === False)
    {
      return $links;
    }
    $sql = "select id from vulcan.link where approved='f' and eros='f'";
    $res = $this->dbh->getAll($sql);
    if (PEAR::isError($res))
    {
      logentry("getneedsapproval.100: " . $res->toString());
      return;
    }
    foreach ($res as $rec)
    {
      $id = $rec["id"];
      $link = getlinkbyid($id);
      if (accesslink("view", $link) === False)
      {
//        logentry("getneedsapproval.110: permission denied for link id#".var_export($id, True));
        continue;
      }
      $links[] = $link;
    }
    return $links;
  }

  function getsitenews($limit=3)
  {
    $sql = "select p.id from sophia.post as p, sophia.map_post_sig as m where p.parentid is null and m.postid = p.id and m.siglabelpath ~ 'top.sitenews' order by p.dateposted desc";
    if ($limit > 0)
    {
      $sql .=" limit {$limit}";
    }
    $res = $this->dbh->getAll($sql);
    if (PEAR::isError($res))
    {
      logentry("index.209: " . $res->toString());
      return null;
    }
    $sitenews = array();
    foreach ($res as $rec)
    {
      $id = $rec["id"];
      $post = getpost($id);
      if (PEAR::isError($post))
      {
        logentry("index.208: " . $post->toString());
        continue;
      }
      if ($post === null)
      {
        logentry("index.207: getpost(".var_export($id, True).") returned null");
        continue;
      }
      $access = accesspost("view", array("post" => $post));
//      logentry("index.210: access=".var_export($access, True));
      if ($access === True)
      {
        $sitenews[] = $post;
      }
    }
    return $sitenews;
  }

  function getlatestreleases($limit=5)
  {
/*
    $sql = "select * from repo.latestrelease where releasecount > 0 and hidden=false order by datereleased desc limit :limit";
    $dat = ["limit" => 10];

    $stmt = $this->pdo->prepare($sql);
    $stmt->execute($dat);
    $res = $stmt->fetchAll();
*/
//    logentry("this->dbh=".var_export($this->dbh, true));

    $sql = "select * from repo.latestrelease where releasecount > 0 and hidden=false order by datereleased desc limit {$limit}";
    $res = $this->dbh->getAll($sql);
    if (PEAR::isError($res))
    {
      logentry("index.210: " . $res->toString());
      return null;
    }
    if ($res === null)
    {
      logentry("index.200: query for latestreleases returned null");
      return null;
    }

/*
    if (PEAR::isError($res))
    {
      logentry("index.210: " . $res->toString());
      return null;
    }
    if ($res === null)
    {
      logentry("index.200: query for latestreleases returned null");
      return null;
    }
*/    
    $currentmemberid = getcurrentmemberid();
    
    $projects = [];
    foreach ($res as $rec)
    {
      $projectname = $rec["projectname"];

      $project = [];

      $project["title"] = $rec["projecttitle"];
      $project["description"] = $rec["projectdescription"];
      $project["name"] = $projectname;
      $project["hidden"] = toboolean($rec["hidden"]);
      $project["releasecount"] = $rec["releasecount"];

//      logentry("index.400: project=".var_export($project, True));
      
      if (accessproject("detail", array("project" => $project)) === False)
      {
        continue;
      }

      $latestrelease = [];
      $latestrelease["name"] = $rec["releasename"];
      $latestrelease["title"] = $rec["releasetitle"];
      $latestrelease["datereleasedepoch"] = $rec["datereleasedepoch"];

      $data = [];
      $data["project"] = $project;
      
      $actions = buildprojectactions($data);
      
      $project["actions"] = $actions;

      $project["latestrelease"] = $latestrelease;
      
      $projects[] = $project;
    }

    return $projects;
  }
  
  function getlatestlinks()
  {
    $sql = "select id from vulcan.link where (link.dateposted >= (now() - '30 days'::interval) or (link.dateapproved >= (now() - '30 days'::interval))) and link.eros='f' and link.broken='f' and link.approved='t' order by link.dateposted desc limit 10";
    $dat = [];
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute($dat);
    $res = $stmt->fetchAll();
    logentry("---> getlatestlinks.100: res=".var_export($res, true));
    if ($res == null)
    {
      return [];
    }
    
    $links = [];
    foreach ($res as $rec)
    {
      logentry("--> rec.id=".var_export($rec["id"], true));
      $link = getlinkbyid($rec["id"]);
      if (accesslink("view", $link) === true)
      {
        logentry("--> adding to list");
        $links[] = $link;
      }
    }
    return $links;
  }

  function main()
  {
    startsession();

    setcurrentsite("www");
    setcurrentpage("index");
    setcurrentaction("index");
    setreturnto(SITEURL);
    
/*
    try {
      $this->pdo = databaseconnect(SYSTEMDSN); // new \PDO(SYSTEMDSN4, $user, $pass, $options);
    } catch (\PDOException $e) {
      throw new \PDOException($e->getMessage(), (int)$e->getCode());
    }
*/
    $this->dbh = databaseconnect(SYSTEMDSN);
/*
    $projects = $this->getlatestreleases(5);
    if (PEAR::isError($projects))
    {
      logentry("index.110: ".$projects->toString());
      $projects = null;
    }
*/
    $latestreleases = $this->getlatestreleases();
//    $latestlinks = $this->getlatestlinks();

/*
    $sitenews = $this->getsitenews();
    if (PEAR::isError($sitenews))
    {
      logentry("index.121: " . $sitenews->toString());
      $sitenews = null;
    }

    $needsapproval = $this->getneedsapproval();
    if (PEAR::isError($needsapproval))
    {
      logentry("index.124: " . $needsapproval->toString());
      $needsapproval = null;
    }
*/    
    $kw = [];
    $kw["latestlinks"] = $latestlinks;
    $kw["needsapproval"] = []; // $needsapproval;
    $kw["sitenews"] = []; //$sitenews;
    $kw["latestreleases"] = $latestreleases; //$latestreleases;
    $kw["pagetemplate"] = "index.tmpl";
    $kw["choices"] = []; // buildchoices();
    displaypage($kw);

    return;
  }
};

$a = new index();
$a->main();
?>
