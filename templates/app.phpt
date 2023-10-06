<?php declare(strict_types=1);

namespace hikari_no_yume\touchHLE\app_compatibility_db;

// Viewing a single unapproved app is not exclusive to moderators; it lets a
// user see their report after submitting it.
$showUnapproved = (($_GET['show_unapproved'] ?? '0') === '1');

$appInfo = getApp($appId);

if ($appInfo == NULL || (!$showUnapproved && $appInfo['approved'] === NULL)) {
    show404();
}

$breadcrumbs = ['Apps', $appInfo['name']];

require 'base.phpt';

?>

<h2>App</h2>

<?php printApp($appInfo); ?>

<h3>Versions</h3>

<?php listVersionsForApp($appId, $showUnapproved); ?>
<br>
<?=formatButtonForm([
    'action' => '/reports/new',
    'method' => 'get',
    'param_name' => 'app',
    'param_value' => (string)$appId,
    'label' => 'Submit report for a new version',
])?>

<h3>Reports</h3>

<?php listReportsForApp($appId, $showUnapproved); ?>

<h2>Legend</h2>
<?php printRatingsLegend(); ?>
