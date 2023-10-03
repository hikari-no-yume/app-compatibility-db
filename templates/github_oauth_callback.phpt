<?php declare(strict_types=1);

namespace hikari_no_yume\touchHLE\app_compatibility_db;

// This is the page that GitHub redirects to after signing in.

$oauthAccessToken = getOAuthAccessToken($_GET['code']);
$userInfo = getGitHubUserInfo($oauthAccessToken);

// The 'github:' prefix allows compatibility with old sessions and database
// records if more authentication providers are added in future.
//
// WARNING: If you want to collect more pieces of user information than this,
//          or change the purposes that this data is used for, you must update
//          templates/signin.phpt and your privacy policy.
$username = 'github:' . $userInfo->login;
$userId = 'github:' . $userInfo->id;

// TODO: Update username in database (if already present).

setSession([
    'username' => $username,
    'user_id' => $userId,
]);

redirect('/');
