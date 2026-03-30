<?php

require_once("config.php");
require_once("zoidweb2.php");
require_once("bbsengine3.php");

class site 
{
/*
    function togglesitedebug()
    {
        $sitedebug = isset($_SESSION["sitedebug"]) ? $_SESSION["sitedebug"] : False;
        if ($sitedebug === True)
        {
            $_SESSION["sitedebug"] = False;
        }
        else
        {
            $_SESSION["sitedebug"] = True;
        }
        logentry("togglesitedebug.100: sitedebug=".var_export($_SESSION["sitedebug"], True));
        displayredirectpage("sitedebug set to ".var_export($_SESSION["sitedebug"], True));
        return;
    }
*/

    /*
     * @since 20161022
     */
    function togglesitevar()
    {
        $var = isset($_REQUEST["var"]) ? $_REQUEST["var"] : NULL;
        logentry("togglesitevar.140: var=".var_export($var, True));
        if ($var === null)
        {
            logentry("togglesitevar.150: var is null");
            return PEAR::raiseError("togglesitevar passed invalid varname");
        }
        $value = getsitevar($var);
        if (PEAR::isError($value))
        {
            logentry("togglesitevar.100: " . $value->toString());
            return $value;
        }
/*
        $value = toboolean($value);
        if (is_bool($value) === False)
        {
            logentry("togglesitevar.120: value is type ".var_export(gettype($value), True)." not a boolean");
            return PEAR::raiseError("invalid sitevar name (code: togglesitevar.120)");
        }
*/
        logentry("togglesitevar.125: name=".var_export($var, True)." original value=".var_export($value, True));
        $value = ($value == True) ? False: True;
        logentry("togglesitevar.130: name=".var_export($var, True)." value after toggle=".var_export($value, True));
        $res = setsitevar($var, $value);
        if (PEAR::isError($res))
        {
            logentry("togglesitevar.130: error setting sitevar ".$res->toString());
            return $res;
        }
        displayredirectpage("OK -- ".var_export($var, True)." toggled to ".var_export($value, True));
        return;
    }

    function main()
    {
        startsession();
        
        $mode = isset($_REQUEST["mode"]) ? $_REQUEST["mode"] : null;
        switch ($mode)
        {
            case "toggle":
            {
                $res = $this->togglesitevar();
                break;
            }
            default:
            {
                $res = PEAR::raiseError("invalid mode (code: engine.site.100)");
                break;
            }
        }
        return $res;
    }
};

$a = new site();
$b = $a->main();
if (PEAR::isError($b))
{
    displayerrorpage($b->getMessage());
    exit;
}
?>
