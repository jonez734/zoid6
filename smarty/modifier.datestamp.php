<?php

//
// $Id: modifier.datestamp.php 1487 2011-04-12 21:00:40Z jam $
//
// @since 2026-10-13 — This file and bbsengine6/smarty/modifier.datestamp.php
// are intentionally close cousins, not byte-identical. Both delegate
// to \bbsengine6\util\datestamp() (the canonical PHP date/time
// formatter in bbsengine6/php/util.php) — there is one walker
// implementation, three call sites (the helper itself plus the two
// Smarty modifiers). What differs:
//
//   1. bbsengine6's modifier carries a VHOSTDOCROOT bootstrap block
//      that primes include_path for bbsengine6config.php when the
//      plugin loads outside a fully-bootstrapped vhost. This file
//      (zoid6) does NOT need that block: zoid6-using vhosts load
//      zoid6config.php first (via require_once('zoid6config.php')
//      in config.php), which transitively sets up the same
//      include_path via bbsengine6config.php. Adding a mirror block
//      here would be dead code; removing it from bbsengine6 would
//      drop a defensive guardrail. Symmetry in behavior is preserved
//      by the shared helper — asymmetry in file structure is by
//      design.
//
//   2. Cosmetic: legacy CVS $Id tag, @package name, Input: doc
//      phrasing, and @author line. These are pre-2026 differences
//      inherited from the original smarty2/smarty3 plugins; they
//      have no runtime effect.
//
// @since 2026-10-13 — Thin wrapper around \bbsengine6\util\datestamp().
// Previously embedded a 100+ line token-aware walker here; the walker
// now lives in bbsengine6/php/util.php (the canonical PHP date/time
// formatter, mirroring bbsengine6.util.datestamp in Python). See
// bbsengine6/handbook/specs/datestamp.md for the full spec.

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
 * Input:    integer to evaluate<br />
 * Example:  {$var|datestamp}<br />
 * @author   zoidtechnologies.com
 * @version 2.1
 * @param string         $timestamp Unix epoch (integer or numeric string).
 *                                 Non-numeric input renders as empty string.
 * @param string|null    $format    Optional strftime format string.
 *                                 See \bbsengine6\util\datestamp() for the
 *                                 resolution chain (format arg → DATEFORMAT
 *                                 → default).
 * @return string
 */
function smarty_modifier_datestamp($string, $format = null)
{
    if (!is_numeric($string)) {
        return '';
    }
    return \bbsengine6\util\datestamp((int)$string, $format);
}