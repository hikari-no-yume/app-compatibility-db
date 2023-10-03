<?php declare(strict_types=1);

namespace hikari_no_yume\touchHLE\app_compatibility_db;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    header('Allow: POST');
    exit;
}

destroySession();

redirect('/');
