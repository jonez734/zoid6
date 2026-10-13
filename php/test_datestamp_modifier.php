<?php
/**
 * test_datestamp_modifier.php — Verifies the zoid6 `|datestamp`
 * Smarty modifier supports the full Python strftime vocabulary,
 * plus the %O opt-in (per-user timezone offset).
 *
 * This test is parallel to bbsengine6/php/test_datestamp_modifier.php
 * and verifies the same vocabulary in zoid6's modifier. Both
 * modifiers implement the same token-aware walker (see
 * bbsengine6/handbook/specs/datestamp.md).
 *
 * Usage:
 *   php test_datestamp_modifier.php
 */

define("DATEFORMAT", "%Y-%m-%d %I:%M%P %Z (%a)");

// The zoid6 modifier delegates to \bbsengine6\util\datestamp (the
// canonical PHP date/time formatter in bbsengine6/php/util.php),
// so load util.php to make the function available. The bbsengine6
// repo must be at the path used by bbsengine6's test
// (REPO_ROOT . "/bbsengine6/php/util.php"); zoid6's test assumes
// the bbsengine6 checkout is a sibling.
require_once(__DIR__ . "/../../bbsengine6/php/util.php");
$source = file_get_contents("/home/opencode/data/work/zoid6/smarty/modifier.datestamp.php");
$source = preg_replace('/require_once\("config\.php"\);/', '// stubbed', $source);
eval("?>" . $source);

echo "=== |datestamp modifier Tests (zoid6) ===\n\n";

$passed = 0;
$failed = 0;

function test_pass(string $name, string $detail = ''): void {
    global $passed;
    $passed++;
    $suffix = $detail ? ": $detail" : '';
    echo "  ✓ PASS: $name$suffix\n";
}

function test_fail(string $name, string $detail = ''): never {
    global $failed;
    $failed++;
    $suffix = $detail ? " — $detail" : '';
    echo "  ✗ FAIL: $name$suffix\n";
    exit(1);
}

function check(string $name, string $expected, string $actual): void {
    if ($expected === $actual) {
        test_pass($name, "[$actual]");
    } else {
        test_fail($name, "expected [$expected] got [$actual]");
    }
}

$ts = strtotime('2026-10-13 14:30:00 UTC');
$tsDate = date('Y-m-d H:i:s', $ts);
echo "Test timestamp: $tsDate (server-local)\n\n";

echo "--- Per-token parity (strftime vocabulary) ---\n\n";

check('%Y', '2026', smarty_modifier_datestamp($ts, '%Y'));
check('%y', '26',   smarty_modifier_datestamp($ts, '%y'));
check('%m', '10',   smarty_modifier_datestamp($ts, '%m'));
check('%b', 'Oct',  smarty_modifier_datestamp($ts, '%b'));
check('%B', 'October', smarty_modifier_datestamp($ts, '%B'));
check('%d', '13',   smarty_modifier_datestamp($ts, '%d'));
check('%j', '286',  smarty_modifier_datestamp($ts, '%j'));
check('%H', '14',   smarty_modifier_datestamp($ts, '%H'));
check('%I', '02',   smarty_modifier_datestamp($ts, '%I'));
check('%l', '2',    smarty_modifier_datestamp($ts, '%l'));
check('%M', '30',   smarty_modifier_datestamp($ts, '%M'));
check('%S', '00',   smarty_modifier_datestamp($ts, '%S'));
check('%p', 'PM',   smarty_modifier_datestamp($ts, '%p'));
check('%P', 'pm',   smarty_modifier_datestamp($ts, '%P'));
check('%Z', date('T', $ts), smarty_modifier_datestamp($ts, '%Z'));
check('%z', date('O', $ts), smarty_modifier_datestamp($ts, '%z'));
check('%:z', date('P', $ts), smarty_modifier_datestamp($ts, '%:z'));
check('%a', 'Tue',  smarty_modifier_datestamp($ts, '%a'));
check('%A', 'Tuesday', smarty_modifier_datestamp($ts, '%A'));
check('%%', '%',    smarty_modifier_datestamp($ts, '%%'));

echo "\n--- Combined format ---\n\n";

$combined = '%Y-%m-%d %H:%M:%S %Z (%a)';
$expected = '2026-10-13 14:30:00 ' . date('T', $ts) . ' (Tue)';
check('combined', $expected, smarty_modifier_datestamp($ts, $combined));

$combinedWithJ = 'day %j of %Y';
check('combined with %j', 'day 286 of 2026', smarty_modifier_datestamp($ts, $combinedWithJ));

check('literal % via %%', '100% off', smarty_modifier_datestamp($ts, '100%% off'));

echo "\n--- Default format (reads DATEFORMAT) ---\n\n";

$defaultResult = smarty_modifier_datestamp($ts);
$expectedDefault = '2026-10-13 02:30pm ' . date('T', $ts) . ' (Tue)';
check('reads DATEFORMAT', $expectedDefault, $defaultResult);

echo "\n--- %O opt-in (per-user TZ) ---\n\n";

check('%O no session', '', smarty_modifier_datestamp($ts, '%O'));
check('%O no session with context', '2026-10-13 ', smarty_modifier_datestamp($ts, '%Y-%m-%d %O'));

session_start();
$_SESSION['localtimezoneoffset'] = -240;
$ts_edt = smarty_modifier_datestamp($ts, '%Y-%m-%d %H:%M %Z %O');
check('%O EDT', '2026-10-13 10:30 ' . date('T', $ts) . ' -04:00', $ts_edt);

$_SESSION['localtimezoneoffset'] = 330;
$ts_ist = smarty_modifier_datestamp($ts, '%Y-%m-%d %H:%M %Z %O');
check('%O IST', '2026-10-13 20:00 ' . date('T', $ts) . ' +05:30', $ts_ist);

$_SESSION['localtimezoneoffset'] = -330;
check('%O -330', '-05:30', smarty_modifier_datestamp($ts, '%O'));

unset($_SESSION['localtimezoneoffset']);

echo "\n--- Edge cases ---\n\n";

check('non-numeric input', '', smarty_modifier_datestamp('not a number'));
check('null input', '', smarty_modifier_datestamp(null));
check('empty format falls back to DATEFORMAT', $expectedDefault, smarty_modifier_datestamp($ts, ''));
check('null format falls back to DATEFORMAT', $expectedDefault, smarty_modifier_datestamp($ts, null));

echo "\n--- Token-aware walker (bare letters preserved) ---\n\n";

check('bare letters preserved: "day"', 'day 286', smarty_modifier_datestamp($ts, 'day %j'));
check('bare letters with literal hyphen', 'day-of-year: 286', smarty_modifier_datestamp($ts, 'day-of-year: %j'));
check('multi-character token %:z', '+00:00', smarty_modifier_datestamp($ts, '%:z'));
check('unknown %X passes through', '%X', smarty_modifier_datestamp($ts, '%X'));

echo "\n--- %j vs %d distinction ---\n\n";

$tsDayOfYear = smarty_modifier_datestamp($ts, '%j');
$tsDayOfMonth = smarty_modifier_datestamp($ts, '%d');
if ($tsDayOfYear === $tsDayOfMonth) {
    test_fail('j vs d should differ on day 13 of year', "%j=$tsDayOfYear, %d=$tsDayOfMonth");
}
test_pass('j vs d values differ when expected', "%j=$tsDayOfYear (day-of-year) %d=$tsDayOfMonth (day-of-month)");

echo "\n=== Test Results ===\n";
echo "Passed: $passed\n";
echo "Failed: $failed\n";

if ($failed > 0) {
    exit(1);
}

echo "\n✓ All |datestamp modifier tests passed!\n";
exit(0);