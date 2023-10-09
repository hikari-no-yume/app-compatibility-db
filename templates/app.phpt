<?php declare(strict_types=1);

namespace hikari_no_yume\touchHLE\app_compatibility_db;

// Viewing a single unapproved app is not exclusive to moderators; it lets a
// user see their report after submitting it.
$showUnapproved = (($_GET['show_unapproved'] ?? '0') === '1');

$session = getSession();

$appInfo = getApp($appId);

if ($appInfo == NULL || (!$showUnapproved && $appInfo['approved'] === NULL)) {
    show404();
}

$breadcrumbs = ['Apps', $appInfo['name']];

require 'header.phpt';

?>

<h2>App</h2>

<?php printApp($appInfo, signedInUserIsModerator($session)); ?>

<h3>Versions</h3>

<?php listVersionsForApp($appId, $showUnapproved, signedInUserIsModerator($session)); ?>
<br>
<?php printButtonForm([
    'action' => '/reports/new',
    'method' => 'get',
    'param_name' => 'app',
    'param_value' => (string)$appId,
    'label' => 'Submit report for a new version',
]); ?>

<h3>Reports</h3>

<?php listReportsForApp($appId, $showUnapproved, signedInUserIsModerator($session)); ?>

<h2>Legend</h2>
<?php printRatingsLegend(); ?>

<?php

require 'footer.phpt';
