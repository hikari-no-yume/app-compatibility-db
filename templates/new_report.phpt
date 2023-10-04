<?php declare(strict_types=1);

namespace hikari_no_yume\touchHLE\app_compatibility_db;

$session = getSession();
if ($session === NULL) {
    redirect('/signin');
    exit;
}

// Every report has to be connected to a version, and every version has to be
// connected to an app. Each of these objects has to be created separately in
// the database, and each is moderated separately. If there were separate forms
// for each, submitting a report for a new app would require far too many steps!
// So the form supports three modes:
// - /reports/new               => New app, new version, new report
// - /reports/new?app=123       => Existing app, new version, new report
// - /reports/new?version=123   => Existing app, existing version, new report

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "TODO<br><pre>";
    var_dump($_POST);
    echo "</pre>";
    exit;
}

$appId = $_GET['app'] ?? NULL;
$versionId = $_GET['version'] ?? NULL;

if ($versionId !== NULL) {
    $versionId = (int)$versionId;
    $versionInfo = getVersion($versionId);
    if ($versionInfo === NULL) {
        show404();
        exit;
    }
    $appId = $versionInfo['app_id'];
} else {
    $versionInfo = NULL;
}

if ($appId !== NULL) {
    $appId = (int)$appId;
    $appInfo = getApp($appId);
    if ($appInfo === NULL) {
        show404();
        exit;
    }
} else {
    $appInfo = NULL;
}

$breadcrumbs = [];
if ($appInfo !== NULL) {
    $breadcrumbs[] = 'Apps';
    $breadcrumbs[] = $appInfo['name'];
}
if ($versionInfo !== NULL) {
    $breadcrumbs[] = 'Versions';
    $breadcrumbs[] = $versionInfo['name'];
}
$breadcrumbs[] = 'Reports';
$breadcrumbs[] = 'Submit new';

require 'base.phpt';

?>

<h2>Submit a new report</h2>

<p>Thank you for choosing to contribute. Before submitting your contribution, please note that:</p>

<ul>
<li>Your contribution will be <strong>publicly attributed to your GitHub username.</strong>
<li>Your contribution may be rejected by a moderator, especially if it does not comply with the guidelines below.
<li>By submitting a contribution, you license it under the terms of <a href="<?=htmlspecialchars(SITE_CONTENT_LICENSE_URL)?>"><?=htmlspecialchars(SITE_CONTENT_LICENSE_NAME)?></a>.
</ul>

<form action=/reports/new method=post>
<fieldset>
<legend>New compatibility report</legend>

<fieldset>
<legend>App</legend>

<?php if ($appInfo !== NULL): ?>
<label>
<input type=radio disabled checked>This report is for an existing app:
<select disabled><option selected><?=htmlspecialchars($appInfo['name'])?></option></select>
<?php /* Hidden field because disabled fields aren't included in requests. */ ?>
<input type=hidden name=app value="<?=htmlspecialchars((string)$appId)?>">
</label>
<label>
<input type=radio disabled>This report is for a new app.
</label>
<?php else: ?>
<label>
<input type=radio disabled>This report is for an existing app.
</label>
<label>
<input type=radio disabled checked>This report is for a new app:
</label>
<fieldset>
<legend>New app</legend>
Before submitting a report for a new app, <strong>please check the <a href=/apps>list of existing apps</a>.</strong><br><br>
<?php printAppForm(); ?>
</fieldset>
<?php endif; ?>

</fieldset>

<fieldset>
<legend>Version</legend>

<?php if ($versionInfo !== NULL): ?>
<label>
<input type=radio disabled checked>This report is for an existing version:
<select disabled><option selected><?=htmlspecialchars($versionInfo['name'])?></option></select>
<?php /* Hidden field because disabled fields aren't included in requests. */ ?>
<input type=hidden name=version value="<?=htmlspecialchars((string)$versionId)?>">
</label>
<label>
<input type=radio disabled>This report is for a new version.
</label>
<?php else: ?>
<label>
<input type=radio disabled>This report is for an existing version.
</label>
<label>
<input type=radio disabled checked>This report is for a new version:
</label>
<fieldset>
<legend>New version</legend>
<?php if ($appInfo !== NULL): ?>
Before submitting a report for a new version, <strong>please check the <a href="/apps/<?=htmlspecialchars((string)$appId)?>">list of existing versions</a>.</strong><br><br>
<?php endif; ?>
<?php printVersionForm(); ?>
</fieldset>
<?php endif; ?>

</fieldset>

<fieldset>
<legend>Report</legend>
<?php printReportForm(); ?>
</fieldset>

<input type=submit value="Submit report">

</fieldset>
</form>