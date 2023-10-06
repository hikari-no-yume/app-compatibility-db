<?php declare(strict_types=1);

namespace hikari_no_yume\touchHLE\app_compatibility_db;

$session ??= getSession();

// Never show unapproved content publicly.
if (($showUnapproved ?? FALSE) == TRUE && $session === NULL) {
    show404();
}

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

<?php if (($showUnapproved ?? FALSE) === TRUE): ?>
<div class=unapproved>
⚠️  Some content on this page (shown with a grey background) is not yet approved by a moderator.
</div>
<?php endif; ?>

<?php if (empty($doNotShowSignInStatus)): ?>
<div id=sign-in-status-box>
<?php if ($session !== NULL): ?>
Signed in as: <?=formatExternalUsername($session['external_username'])?>
<?php if (signedInUserIsModerator($session)): ?>
<!-- By the power of Grayskull... -->
<div style="text-align:right">🗡 <b><i>You have the power!</i></b></div>
<?=formatButtonForm([
    'action' => '/',
    'method' => 'get',
    'param_name' => 'show_unapproved',
    'param_value' => '1',
    'label' => 'Show unapproved reports',
])?>
<?php endif; ?>
<?=formatButtonForm([
    'action' => '/signout',
    'method' => 'post',
    'label' => 'Sign out',
])?>
<?php else: ?>
<?=formatButtonForm([
    'action' => '/signin',
    'method' => 'get',
    'label' => 'Sign in',
])?>
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
