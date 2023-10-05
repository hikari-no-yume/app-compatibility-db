<?php declare(strict_types=1);

namespace hikari_no_yume\touchHLE\app_compatibility_db;

$showUnapproved = (($_GET['show_unapproved'] ?? '0') === '1');

require 'base.phpt';

?>

<h2>Apps</h2>
<?php listApps($showUnapproved); ?>
<br>
<form action=/reports/new method=get>
<input type=submit value="Submit report for a new app">
</form>

<h2>Legend</h2>
<?php printRatingsLegend(); ?>
