<?php
/*
 * this module performs a "logout" of the currently logged in member.
 *
 * @copyright (C) 2007-2024 {@link http://zoidtechnologies.com/ zoidtechnologies.com} All Rights Reserved.
 */
require_once("config.php");
require_once("zoidweb4.php");
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
    session_regenerate_id(true);

    return;
  }
}

$l = new logout();
$l->main();

?>
