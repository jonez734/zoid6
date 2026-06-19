<?php

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
        "/srv/www/bbsengine6/php/",
        "/srv/www/smarty/",
        "/srv/www/markdown/",
    ];

    $allPaths = array_merge($paths, $defaults);
    $pathString = implode(PATH_SEPARATOR, $allPaths);

    return set_include_path($pathString) !== false;
}

}

?>
