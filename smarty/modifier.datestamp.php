<?php

// 
// $Id: modifier.datestamp.php 1487 2011-04-12 21:00:40Z jam $
//

require_once("config.php");

/**
 * Smarty plugin to take a timstamp expressed in UNIX epoch and return a string that formats it in a consistent way.
 * @package bbsengine2
 */


/**
 * Smarty datestamp modifier plugin
 *
 * Type:     modifier<br />
 * Name:     datestamp<br />
 * Date:     Aug 24, 2006
 * Purpose:  convert UNIX-epoch timestamp into a human readable string.
 * Input:    integer to evaluate
 * Example:  {$var|datestamp}
 * @author   Jeff MacDonald <jam [at] zoid technologies dot com>
 * @version 1.0
 * @param string
 * @return string
 */
function smarty_modifier_datestamp($string)
{
    return strftime(DATEFORMAT, $string);
}

?>
