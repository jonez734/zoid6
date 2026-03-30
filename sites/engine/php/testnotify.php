<?php

require_once("config.php");
require_once("zoidweb2.php");
require_once("bbsengine3.php");

function main()
{
    startsession();
    $type = "link-added";
    $memberid = getcurrentmemberid();

    $linkid = 42;
    $data = ["linkid" => $linkid];

    if ($memberid === null)
    {
        logentry("testnotify.48: currentmemberid is null");
        return PEAR::raiseError("not logged in");
    }
    $res = sendnotify($type, $memberid, $data);
    if (PEAR::isError($res))
    {
        logentry("testnofity.50: " . $res->toString());
        return $res;
    }
    $page = getpage("testing notify");
    $options["pagedata"]["body"] = "<h1>hello, world</h1><p>OK. notify of type {$type} sent to memberid {$memberid}</p>";
    displaypage($page, $options);
    return;
}

$a = main();
if (PEAR::isError($a))
{
    logentry("testnotify.100: " . $a->toString());
    displayerrorpage($a->getMessage());
    exit;
}
?>
