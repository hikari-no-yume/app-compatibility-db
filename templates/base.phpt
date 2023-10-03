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

<?php if ($session !== NULL): ?>
<div id=signed-in>
Signed in as: @<?=htmlspecialchars(explode(':', $session['username'])[1])?>
<form method=post action=/signout>
<input type=submit value="Sign out">
</form>
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
