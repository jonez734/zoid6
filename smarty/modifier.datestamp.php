<?php

//
// $Id: modifier.datestamp.php 1487 2011-04-12 21:00:40Z jam $
//
// @since 2026-10-15 — Body extracted to
// bbsengine6/php/smarty_modifier_datestamp.php so the
// modifier is defined in exactly one place. This file
// is now a 2-line stub: bootstrap + require the canonical
// body. The function body lives in
// bbsengine6/php/smarty_modifier_datestamp.php; the
// bbsengine6 sibling
// (bbsengine6/smarty/modifier.datestamp.php) uses a
// parallel require path. See
// bbsengine6/handbook/specs/datestamp.md.
//
// Layout assumption: bbsengine6 is installed as a sibling
// of zoid6 (e.g., /srv/www/bbsengine6 next to
// /srv/www/zoid6, or both as sibling checkouts in a dev
// tree). The __DIR__-based relative path requires this
// layout. If a future deploy puts bbsengine6 elsewhere,
// this path must be updated to match.

\bbsengine6\util\smarty_plugin_bootstrap();
require_once("config.php");
require_once(__DIR__ . "/../../bbsengine6/php/smarty_modifier_datestamp.php");
