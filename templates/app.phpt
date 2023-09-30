<?php declare(strict_types=1);

namespace hikari_no_yume\touchHLE\app_compatibility_db;

$appInfo = getApp($appId);

if ($appInfo == NULL) {
    show404();
}

$breadcrumbs = ['Apps', $appInfo['name']];

require 'base.phpt';

?>

<h2>App</h2>

<?php printApp($appInfo); ?>

<h3>Versions</h3>

<?php listVersionsForApp($appId); ?>

<h3>Reports</h3>

<?php listReportsForApp($appId); ?>
