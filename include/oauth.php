<?php declare(strict_types=1);

namespace hikari_no_yume\touchHLE\app_compatibility_db;

// Functions and constants for interacting with GitHub's OAuth API. No idea
// how much this generalises to other OAuth implementations.

const GITHUB_OAUTH_AUTHORIZE_URL = "https://github.com/login/oauth/authorize";
const GITHUB_OAUTH_ACCESS_TOKEN_URL = "https://github.com/login/oauth/access_token";
const GITHUB_USER_INFO_URL = "https://api.github.com/user";

function getOAuthAccessToken(string $sessionCode): string {
    $url = GITHUB_OAUTH_ACCESS_TOKEN_URL;
    $content = http_build_query([
        'client_id' => GITHUB_CLIENT_ID,
        'client_secret' => GITHUB_CLIENT_SECRET,
        'code' => $sessionCode,
    ]);

    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => [
                'User-Agent: ' . USER_AGENT,
                'Content-Type: application/x-www-form-urlencoded',
                'Accept: application/json',
            ],
            'content' => $content,
        ]
    ]);

    return json_decode(file_get_contents($url, FALSE, $context))->access_token;
}

function getGitHubUserInfo(string $oauthAccessToken): \stdClass {
    $url = GITHUB_USER_INFO_URL;

    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => [
                'User-Agent: ' . USER_AGENT,
                'Accept: application/vnd.github+json',
                'Authorization: token ' . $oauthAccessToken,
                'X-GitHub-Api-Version: 2022-11-28',
            ],
        ]
    ]);

    return json_decode(file_get_contents($url, FALSE, $context));
}
