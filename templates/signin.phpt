<?php declare(strict_types=1);

namespace hikari_no_yume\touchHLE\app_compatibility_db;

// Already logged in
if (getSession() !== NULL) {
    redirect('/');
    return;
}

$breadcrumbs = ['Sign in'];

$githubSignInUrl = GITHUB_OAUTH_AUTHORIZE_URL . '?client_id=' . rawurlencode(GITHUB_CLIENT_ID);

// Prevent there being two “Sign in” buttons on the same page, which could be
// confusing.
$doNotShowSignInStatus = TRUE;

require 'header.phpt';

?>

<h2>Sign in</h2>

<p><?=htmlspecialchars(SITE_NAME)?> requires you to sign in with your GitHub account in order to contribute. Signing in is used to attribute your contributions to you:</p>

<ul>
<li>Your contributions are <strong>publicly attributed to your GitHub username</strong>
<li>Moderation actions may be informed by your contribution history and identity
</ul>

<p>When you sign in with GitHub, only the following information will be collected by <?=htmlspecialchars(SITE_NAME)?>:</p>

<ul>
<li>Your GitHub username
<li>Your GitHub account's unique ID number (so you can still be recognised if you change your username)
</ul>

<p>If you have changed your GitHub username, signing in will update the publicly displayed username on your past contributions.</p>

<p>A <strong>cookie will be set</strong> by <?=htmlspecialchars(SITE_NAME)?> when you sign in. This cookie is only used for the attribution described above.</p>

<p>More information can be found in the <a href="<?=htmlspecialchars(SITE_PRIVACY_POLICY)?>">privacy policy</a>. Note that signing in with GitHub also involves the processing of your data by GitHub, which has a separate privacy policy.</p>

<p>Click here to sign in:</p>

<form method=get action="https://github.com/login/oauth/authorize">
    <input type=hidden name=client_id value="<?=htmlspecialchars(GITHUB_CLIENT_ID)?>">
    <input type=submit value="Sign in with GitHub">
</form>

<?php

require 'footer.phpt';
