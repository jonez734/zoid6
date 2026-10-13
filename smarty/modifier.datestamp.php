<?php

//
// $Id: modifier.datestamp.php 1487 2011-04-12 21:00:40Z jam $
//
// @since 2026-10-13 — Full rewrite to match bbsengine6's |datestamp
// modifier. Previously a 3-line `date(\config\DATEFORMAT, $string)`
// shim with no strftime translation; the format string was passed
// straight to date() which produced broken output for any strftime
// format. Now implements the same token-aware walker as
// bbsengine6/smarty/modifier.datestamp.php, plus the %O per-user
// timezone opt-in (reads $_SESSION['localtimezoneoffset']).
// See bbsengine6/handbook/specs/datestamp.md for the full spec.

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
 * Date:     Aug 24, 2006<br />
 * Purpose:  convert UNIX-epoch timestamp into a human readable string.<br />
 * Input:    string to evaluate<br />
 * Example:  {$var|datestamp}<br />
 * Example:  {$var|datestamp:"%Y-%m-%d"}<br />
 * @author   zoidtechnologies.com
 * @version 2.0
 * @param string   $timestamp  Unix epoch (integer or numeric string)
 * @param string|null $format  Optional strftime format string.
 *                              If null, reads \config\DATEFORMAT.
 * @return string
 */
function smarty_modifier_datestamp($string, $format = null)
{
    if (!is_numeric($string)) {
        return '';
    }
    $timestamp = (int)$string;

    if ($format === null || $format === '') {
        if (defined('\config\DATEFORMAT')) {
            $format = \config\DATEFORMAT;
        } elseif (defined('DATEFORMAT')) {
            $format = DATEFORMAT;
        } else {
            $format = '%Y-%m-%d %H:%M';
        }
    }

    $offsetMinutes = isset($_SESSION['localtimezoneoffset'])
        ? (int)$_SESSION['localtimezoneoffset']
        : 0;
    $localTs = $timestamp + ($offsetMinutes * 60);

    $userOffsetValue = '';
    if ($offsetMinutes !== 0) {
        $sign = $offsetMinutes >= 0 ? '+' : '-';
        $abs = abs($offsetMinutes);
        $oh = str_pad((string)intdiv($abs, 60), 2, '0', STR_PAD_LEFT);
        $om = str_pad((string)($abs % 60), 2, '0', STR_PAD_LEFT);
        $userOffsetValue = sprintf('%s%02d:%02d', $sign, $oh, $om);
    }

    $strftimeToDate = [
        '%Y' => 'Y', '%y' => 'y',
        '%m' => 'm', '%b' => 'M', '%B' => 'F',
        '%d' => 'd',
        '%H' => 'H', '%I' => 'h', '%l' => 'g',
        '%M' => 'i', '%S' => 's',
        '%p' => 'A', '%P' => 'a',
        '%Z' => 'T',
        '%z' => 'O',
        '%:z' => 'P',
        '%a' => 'D', '%A' => 'l',
        '%%' => '%',
    ];

    $inlineTokens = ['%%', '%j', '%O'];
    $allTokens = array_merge($inlineTokens, array_keys($strftimeToDate));
    usort($allTokens, function ($a, $b) { return strlen($b) - strlen($a); });

    $result = '';
    $len = strlen($format);
    for ($i = 0; $i < $len; $i++) {
        $ch = $format[$i];
        if ($ch === '%' && $i + 1 < $len) {
            $matched = null;
            foreach ($allTokens as $tok) {
                if (substr($format, $i, strlen($tok)) === $tok) {
                    $matched = $tok;
                    break;
                }
            }
            if ($matched !== null) {
                $i += strlen($matched) - 1;
                if ($matched === '%%') {
                    $result .= '%';
                } elseif ($matched === '%j') {
                    $result .= (string)((int)date('z', $localTs) + 1);
                } elseif ($matched === '%O') {
                    $result .= $userOffsetValue;
                } else {
                    $result .= date($strftimeToDate[$matched], $localTs);
                }
                continue;
            }
            if ($i + 1 < $len && ctype_alpha($format[$i + 1])) {
                $result .= '%' . $format[$i + 1];
                $i++;
            } else {
                $result .= '%';
            }
            continue;
        }
        $result .= $ch;
    }

    return $result;
}