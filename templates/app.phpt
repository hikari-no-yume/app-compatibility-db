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
<br>
<form action=/reports/new method=get>
<input type=hidden name=app value="<?=htmlspecialchars((string)$appId)?>">
<input type=submit value="Submit report for a new version">
</form>

<h3>Reports</h3>

<?php listReportsForApp($appId); ?>
