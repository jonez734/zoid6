<?php

require_once("config.php");
require_once("zoidweb2.php");
require_once("bbsengine4.php");

  // from http://www.danielkassner.com/2010/05/22/get-date-by-position-ie-third-wednesday-of-january
  function _meetingdate($position, $weekday, $month, $year)
  {
    $position = strtolower($position);
    $weekday = strtolower($weekday);
    $month = intval($month);
    $year = intval($year);
    if ($month > 12)
    {
      $month = 1;
      $year += 1;
    }
    
    $d = new DateTime("{$year}-{$month}-01");
    $dow = $d->format("w");
    
    $days = array("sunday" => 0, "monday" => 1, "tuesday" => 2, "wednesday" => 3, "thursday" => 4, "friday" => 5, "saturday" => 6);
    
    $offset = $days[$weekday] - $dow;
    if ($offset < 0) 
    {
      $offset += 7;
    }
    
    switch ($position)
    {
      case "first":
      case "1st":
      {
        $offset += 0;
        break;
      }
      case "2nd":
      case "second":
      {
        $offset += 7;
        break;
      }
      case "3rd":
      case "third":
      {
        $offset += 14;
        break;
      }
      case "4th":
      case "fourth":
      {
        $offset += 21;
        break;
      }
    }
    $d->modify("+{$offset} days");
    return $d;
  }

  startsession();
  
  setcurrentpage("maux");
  
  $today = new DateTime();
  
  $tmpl = getsmarty();
  
  $year = date("Y");
  $month = date("m");
  // @since 20120910
  $vets = _meetingdate("3rd", "monday", $month, $year);
  $vets->setTime(18,00);
  if ($today > $vets)
  {
    $vets = _meetingdate("3rd", "monday", $month+1, $year);
    $vets->setTime(18,00);
  }
  $tmpl->assign("vets", $vets->getTimestamp());

  $maux = _meetingdate("3rd", "tuesday", $month, $year);
  $maux->setTime(18,30);
  if ($today > $maux)
  {
    $maux = _meetingdate("3rd", "tuesday", $month+1, $year);
    $maux->setTime(18,30);
  }
  $tmpl->assign("maux", $maux->getTimestamp());
    
  $laux = _meetingdate("2nd", "tuesday", $month, $year);
  $laux->setTime(18,00);
  if ($today > $laux)
  {
    //      logentry("main.10: ladies' meeting for this month already happened");
    $laux = _meetingdate("2nd", "tuesday", $month+1, $year);
    $laux->setTime(18,00);
  }
  $tmpl->assign("laux", $laux->getTimestamp());

/*
  $ridersmeeting = _meetingdate("2nd", "saturday", $month, $year);
  $ridersmeeting->setTime(14,00);
  if ($today > $ridersmeeting)
  {
    $ridersmeeting = _meetingdate("2nd", "saturday", $month+1, $year);
    $ridersmeeting->setTime(14,00);
  }
  $tmpl->assign("ridersmeeting", $ridersmeeting->getTimestamp());
*/  
  $page = getpage("VFW #10058 Men's Auxiliary Web Page");
  $page->addStyleSheet("/skin/css/ribbon.css");
  $page->addBodyContent(fetchpageheader("VFW #10058 Unofficial Web Page"));
  $page->addBodyContent($tmpl->fetch("vfw.tmpl"));
  $page->addBodyContent(fetchpagefooter());
  $page->display();
  exit;
?>
