<?php declare(strict_types=1);

namespace hikari_no_yume\touchHLE\app_compatibility_db;

$session = getSession();

?>
<!doctype html>
<meta charset=utf-8>
<title><?php

if (!empty($breadcrumbs)) {
    echo htmlspecialchars(implode(' - ', array_reverse($breadcrumbs))), ' - ';
}
echo htmlspecialchars(SITE_NAME);

?></title>

<link rel=stylesheet href=/style.css>
<script src=/script.js></script>

<h1><?=htmlspecialchars(SITE_NAME)?></h1>

<?php if (empty($doNotShowSignInStatus)): ?>
<div id=sign-in-status-box>
<?php if ($session !== NULL): ?>
Signed in as: <?=formatExternalUsername($session['external_username'])?>
<form method=post action=/signout>
<input type=submit value="Sign out">
</form>
<?php else: ?>
<form method=get action=/signin>
<input type=submit value="Sign in">
</form>
<?php endif; ?>
</div>
<?php endif; ?>

<div id=breadcrumbs>
<a href=/>Home</a>
<?php

if (!empty($breadcrumbs)) {
    echo ' &gt; ', htmlspecialchars(implode(' > ', $breadcrumbs));
}

?>
</div>
