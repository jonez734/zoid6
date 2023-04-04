<?php

require_once("config.php");
require_once("zoidweb2.php");
require_once("bbsengine4.php");

$tmpl = getsmarty();
$tmpl->assign("foo", "Uma vez que possui ficha de cliente nas lojas b|Code, o seu email foi já adicionado à nossa loja online. Para obter a sua password clique no link seguinte: <a href='http://be-code.com/password.php'>Recuperar password</a>");
$tmpl->display("foo.tmpl");
exit;

?>
