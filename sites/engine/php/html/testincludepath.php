<?php
$includepath = get_include_path();
print var_export($includepath, true)."<br />";
$zoidweb3 = str_replace("zoidweb3", "zoidweb4", $includepath);
print var_export($zoidweb3, true)."<br />";

?>
