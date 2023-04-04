<?php
/*
 * this module performs a "logout" of the currently logged in member.
 *
 * @copyright (C) 2007 {@link http://zoidtechnologies.com/ Zoid Technologies} All Rights Reserved.
 */
require_once("config.php");
require_once("zoidweb2.php");
require_once("bbsengine4.php");

class logout
{
  function main()
  {
    startsession();
    
    clearpageprotocol();

    $id = $_SESSION["currentmemberid"];

    displayredirectpage("OK -- logged out");
    logentry("logout: OK for id #{$id}");
    clearcurrentmemberid();
    removesessioncookie();

    return;
  }
}

$l = new logout();
$l->main();

?>
