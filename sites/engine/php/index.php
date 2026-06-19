<?php

require_once("config.php");
require_once("engine.php");
require_once("session.php");

\bbsengine6\session\start(); // startsession();

$data = [];
$data["pagetemplate"] = "index.tmpl";

$res = \bbsengine6\displaypage($data);
/*
if ($res === false)
{
    print "hello, world";
    logentry("engine.index.100: " . $res->toString());
    exit;
}
*/
?>
