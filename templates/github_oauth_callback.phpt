<?php declare(strict_types=1);

namespace hikari_no_yume\touchHLE\app_compatibility_db;

// This is the page that GitHub redirects to after signing in.

$oauthAccessToken = getOAuthAccessToken($_GET['code']);
$userInfo = getGitHubUserInfo($oauthAccessToken);

// WARNING: If you want to collect more pieces of user information than this,
//          or change the purposes that this data is used for, you must update
//          templates/signin.phpt and your privacy policy.
$githubUsername = $userInfo->login;
$githubUserId = (string)$userInfo->id;

// TODO: Set up a session.
// TODO: Update username in database (if already present).

?>
<h1>Greetings, @<?=htmlspecialchars($githubUsername)?>!</h1>
<p>Your GitHub user ID is <?=htmlspecialchars($githubUserId)?>.</p>