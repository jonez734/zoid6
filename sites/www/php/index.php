<?php

namespace {
    /*
     * @since 20190817
     * @since 20221104
     */
    $includepath = get_include_path().":/srv/www/zoid6/php/:/srv/www/bbsengine6/php/:/srv/www/smarty/:/srv/www/markdown/";
    if (set_include_path($includepath) === false)
    {
        print("include path fail");
    }

  require_once("engine.php");
  require_once("session.php");
  require_once("database.php");
  require_once("util.php");
//  require_once("zoid6.php");
  require_once("lib.php");

  require_once("config.php");

  //require_once("libvulcan.php");
  //require_once("librepo.php");

  //print "<h1>greetings, world</h1>";

  class index
  {
    var $pdo = null;
    
    function getlatestreleases($limit=5)
    {
      $sql = "select * from repo.latestrelease where releasecount > 0 and hidden=false order by datereleased desc limit :limit";
      $dat = ["limit" => 10];

      $stmt = $this->pdo->prepare($sql);
      $stmt->execute($dat);
      $res = $stmt->fetchAll();
      if ($res === null)
      {
        logentry("index.200: query for latestreleases returned null");
        return null;
      }

      $currentmemberid = \bbsengine6\member\lib\getcurrentmemberid();
      
      $projects = [];
      foreach ($res as $rec)
      {
        $projectname = $rec["projectname"];

        $project = [];

        $project["title"] = $rec["projecttitle"];
        $project["description"] = $rec["projectdescription"];
        $project["name"] = $projectname;
        $project["private"] = toboolean($rec["private"]);
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
      \bbsengine6\session\start();

      \bbsengine6\setcurrentsite(\config\SITENAME);
      \bbsengine6\setcurrentpage("index");
      \bbsengine6\setcurrentaction("index");
      \bbsengine6\setreturnto(SITEURL);
      
      \bbsengine6\util\logentry(var_export(SMARTYTEMPLATESDIR, true));
  /*
      try {
        $this->pdo = databaseconnect(SYSTEMDSN); // new \PDO(SYSTEMDSN4, $user, $pass, $options);
      } catch (\PDOException $e) {
        throw new \PDOException($e->getMessage(), (int)$e->getCode());
      }
  */
      $this->dbh = \bbsengine6\database\connect(SYSTEMDSN);
  /*
      $projects = $this->getlatestreleases(5);
      if (PEAR::isError($projects))
      {
        logentry("index.110: ".$projects->toString());
        $projects = null;
      }
  */
  //    $latestreleases = $this->getlatestreleases();
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
      $choices = [];
      // Add site-specific choices here if SYSOP or admin features are needed
      // Example: if (\bbsengine6\member\lib\checkflag("SYSOP")) {
      //   $choices[] = ["name" => "add link", "title" => "...", "url" => "...", "desc" => "..."];
      // }
      $data = [];
      $data["latestlinks"] = [];
      $data["needsapproval"] = [];
      $data["sitenews"] = [];
      $data["latestreleases"] = [];
      $data["choices"] = \zoid6\buildchoices($choices);
      \bbsengine6\displaypage($data, "index.tmpl");

      return;
    }
  };

  $a = new index();
  $a->main();
}
?>
