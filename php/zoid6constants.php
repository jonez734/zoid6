<?php

/**
 * zoid6constants.php
 *
 * Application-level / third-party content-asset constants for zoid6 sites.
 *
 * Charter: constants in this file are *content pointers* (CDN URLs,
 * third-party tracking IDs, sibling-site URLs), NOT zoid6 infrastructure.
 * Each one is guarded with `if (!defined(...))` so a vhost's own
 * `define()` in its config-prod.php wins; if no override, the default
 * here is used.
 *
 * Load: required once by zoid6config.php at the top of that file, so
 * every consumer that loads zoid6config.php (every zoid6 vhost, plus
 * transitively every bbsengine6 vhost that loads bbsengine6config.php)
 * picks these up automatically. No consumer needs to require this
 * file directly.
 *
 * @since 2026-09-27 — FONTAWESOMEURL and GOOGLEANALYTICSACCOUNT moved
 * here from zoid6.php. The engine router bypasses legacy entry points
 * (index.php, link.php, teos.php) which used to require zoid6.php
 * directly, so constants in zoid6.php were silently undefined on
 * engine-routed pages. Moving them here (and loading via
 * zoid6config.php, which is loaded transitively by the engine) makes
 * them available everywhere without each entry point remembering to
 * add its own require. Fixes the FA Kit icons-not-rendering bug on
 * /rec/, /handbook/<v>/, etc.
 */

namespace zoid6\constants
{
    // FontAwesome Kit URL — used by zoid6/shared/skin/tmpl/page.tmpl
    // via {$smarty.const.FONTAWESOMEURL} in the <head> block.
    if (!defined("FONTAWESOMEURL"))
        define("FONTAWESOMEURL", "https://kit.fontawesome.com/684c5b77b8.js");

    // Google Analytics tracking ID — used by zoid6/shared/skin/tmpl/
    // googleanalytics.tmpl via {$smarty.const.GOOGLEANALYTICSACCOUNT}.
    // Note: several vhosts override this in their config-prod.php
    // (murdermotel, asimov, achilles, atlas, empyre, moneyday, etc.);
    // those overrides load first, the guard below ensures they win.
    if (!defined("GOOGLEANALYTICSACCOUNT"))
        define("GOOGLEANALYTICSACCOUNT", "UA-23705021-1");

    // Achilles site URL — used by zoid6/sites/www/php/lib.php and
    // shared/skin/tmpl menu rendering. (Was already here, kept in
    // place during this refactor.)
    if (!defined("ACHILLESURL"))
        define("ACHILLESURL", "https://achilles.zoidtechnologies.com/");
};

?>
