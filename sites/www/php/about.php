<?php

require_once("/srv/www/bbsengine6/php/bootstrap.php");

require_once("config.php");

require_once("session.php");
require_once("page.php");

\bbsengine6\session\start();

$a = new \bbsengine6\Page();
$b = $a->main();

\bbsengine\session\end();

?>
