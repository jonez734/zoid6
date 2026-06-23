<?php

namespace {
    require_once("/srv/www/bbsengine6/php/bootstrap.php");
}

namespace zoid6 {

/**
 * Set PHP include_path for zoid6.
 *
 * @param array $paths Optional additional paths to prepend to defaults.
 * @return bool True on success, false on failure.
 * @since 20250619
 */
function bootstrap(array $paths = []): bool
{
    $defaults = [
        "/srv/www/zoid6/php/",
    ];

    $current = array_filter(
        explode(PATH_SEPARATOR, get_include_path()),
        'strlen'
    );

    $current = array_map(
        static fn($p) => rtrim($p, DIRECTORY_SEPARATOR),
        $current
    );

    $newPaths = array_merge($paths, $defaults);

    foreach ($newPaths as $path) {
        $normalized = rtrim($path, DIRECTORY_SEPARATOR);
        if (!in_array($normalized, $current, true)) {
            $current[] = $normalized;
        }
    }

    $pathString = implode(PATH_SEPARATOR, $current);
    \bbsengine6\util\logentry("zoid6/php/bootstrap.php: pathString=".var_export($pathString, true));
    return set_include_path($pathString) !== false;
}

}

?>
