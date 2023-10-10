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

<div id=breadcrumbs>
<?php if (PARENT_SITE_NAME !== NULL): ?>
<a href="<?=htmlspecialchars(PARENT_SITE_URL)?>"><?=htmlspecialchars(PARENT_SITE_NAME)?></a> &gt;
<?php endif; ?>
<h1><a href=/><?=htmlspecialchars(SITE_NAME)?></a></h1>
<?php

if (!empty($breadcrumbs)) {
    echo ' &gt; ', htmlspecialchars(implode(' > ', $breadcrumbs));
}

?>
</div>

<?php if (($showUnapproved ?? FALSE) === TRUE): ?>
<div class=unapproved>
⚠️  Some content on this page (shown with a grey background) is not yet approved by a moderator. This content is not visible publicly.
</div>
<?php endif; ?>

<?php if (empty($doNotShowSignInStatus)): ?>
<div id=sign-in-status-box>
<?php if ($session !== NULL): ?>
Signed in as: <?php printExternalUsername($session['external_username']); ?>
<?php if (signedInUserIsModerator($session)): ?>
<!-- By the power of Grayskull... -->
<div style="text-align:right">🗡 <b><i>You have the power!</i></b></div>
<?php printButtonForm([
    'action' => '/',
    'method' => 'get',
    'param_name' => 'show_unapproved',
    'param_value' => '1',
    'label' => 'Show unapproved reports',
]); ?>
<?php endif; ?>
<?php printButtonForm([
    'action' => '/signout',
    'method' => 'post',
    'label' => 'Sign out',
]); ?>
<?php else: ?>
<?php printButtonForm([
    'action' => '/signin',
    'method' => 'get',
    'label' => 'Sign in',
]); ?>
<?php endif; ?>
</div>
<?php endif; ?>
