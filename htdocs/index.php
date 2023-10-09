<?php declare(strict_types=1);

namespace hikari_no_yume\touchHLE\app_compatibility_db;

// Set this first thing, in case an error happens while loading the includes.
ini_set('display_errors', '0');

// See getSession() remarks in util.php.
ini_set('session.auto_start', '0');

// Everything is more annoying without output buffering.
ob_start();

require_once '../config.php';

require_once '../include/util.php';
require_once '../include/queries.php';
require_once '../include/oauth.php';

initDb();

$path = $_SERVER['PATH_INFO'] ?? '';
if (!str_ends_with($path, '/')) {
    $path .= '/';
}

if ($path === '/') {
    require '../templates/home.phpt';
} else if ($path === '/apps/' || $path === '/versions/'|| $path === '/reports/') {
    // These might be their own sections at some point.
    redirect('/');
} else if (preg_match('#^/apps/(\d+)/$#', $path, $matches) === 1) {
    $appId = (int)$matches[1];
    require '../templates/app.phpt';
} else if (preg_match('#^/apps/(\d+)/approve/$#', $path, $matches) === 1) {
    $appId = (int)$matches[1];
    $objectKind = 'app';
    $moderationAction = 'approve';
    require '../templates/moderation_action.phpt';
} else if (preg_match('#^/apps/(\d+)/delete/$#', $path, $matches) === 1) {
    $appId = (int)$matches[1];
    $objectKind = 'app';
    $moderationAction = 'delete';
    require '../templates/moderation_action.phpt';
} else if (preg_match('#^/versions/(\d+)/approve/$#', $path, $matches) === 1) {
    $versionId = (int)$matches[1];
    $objectKind = 'version';
    $moderationAction = 'approve';
    require '../templates/moderation_action.phpt';
} else if (preg_match('#^/versions/(\d+)/delete/$#', $path, $matches) === 1) {
    $versionId = (int)$matches[1];
    $objectKind = 'version';
    $moderationAction = 'delete';
    require '../templates/moderation_action.phpt';
} else if (preg_match('#^/reports/(\d+)/approve/$#', $path, $matches) === 1) {
    $reportId = (int)$matches[1];
    $objectKind = 'report';
    $moderationAction = 'approve';
    require '../templates/moderation_action.phpt';
} else if (preg_match('#^/reports/(\d+)/delete/$#', $path, $matches) === 1) {
    $reportId = (int)$matches[1];
    $objectKind = 'report';
    $moderationAction = 'delete';
    require '../templates/moderation_action.phpt';
} else if ($path === '/reports/new/') {
    require '../templates/new_report.phpt';
} else if ($path === '/signin/') {
    require '../templates/signin.phpt';
} else if ($path === '/signin/github-oauth-callback/') {
    require '../templates/github_oauth_callback.phpt';
} else if ($path === '/signout/') {
    require '../templates/signout.phpt';
} else {
    show404();
}
