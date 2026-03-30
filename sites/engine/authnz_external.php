#!/usr/bin/env php

require_once("config.php");
require_once("zoidweb2.php");
require_once("bbsengine3.php");

$user = trim(fgets(STDIN));
$pass = trim(fgets(STDIN));

if ($user === $pass)
{
    exit 0;
}

exit 1;
