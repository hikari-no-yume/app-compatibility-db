<?php declare(strict_types=1);

namespace hikari_no_yume\touchHLE\app_compatibility_db;

require_once '../include/util.php';
require_once '../include/queries.php';

init();

$path = $_SERVER['PATH_INFO'] ?? '';
if (!str_ends_with($path, '/')) {
    $path .= '/';
}

if ($path === '/') {
    require '../templates/home.phpt';
} else if ($path === '/apps/') {
    // This might be its own section at some point.
    header("HTTP/1.1 303 See Other");
    header("Location: /");
} else if (preg_match('#^/apps/(\d+)/$#', $path, $matches) !== FALSE) {
    $appId = (int)$matches[1];
    require '../templates/app.phpt';
} else {
    show404();
}
