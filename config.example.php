<?php

// Human-readable name of the site, as plain text
const SITE_NAME = 'Example Emulator app compatibility database';

// If this database belongs to a larger site (for example, the main website of
// your emulator), you can specify its name and URL here in plain text, and
// they'll be linked at the top of the page. If the name is set to NULL, they
// won't be.
const PARENT_SITE_NAME = NULL; // e.g. 'Example Emulator main site'
const PARENT_SITE_URL = NULL; // e.g. 'https://emulator.example.org'

// URL of the privacy policy of the site (may be external)
// Make sure this doesn't contradict templates/signin.phpt
const SITE_PRIVACY_POLICY = "privacy.html";

// Path to the SQLite 3 database, relative to the htdocs directory
const SITE_DB_PATH = '../app_db.sqlite3';

// Plain-text name and URL for the license that contributions are made under.
// Don't change this once contributions have been made! There is no tracking for
// license changes, so the wrong license will be displayed next to old
// contributions, which is a license violation in and of itself!
const SITE_CONTENT_LICENSE_NAME = 'CC BY 4.0 International';
const SITE_CONTENT_LICENSE_URL = 'https://creativecommons.org/licenses/by/4.0/';

// Compatibility ratings used in reports. These must be numbered 1 to 5,
// with larger numbers being better. For each rating, there should be a symbol
// (e.g. a star count) and a short description of what this rating means.
// This example rating system is heavily inspired by Dolphin's.
const RATINGS = [
    1 => [
        'symbol' => '⭐️',
        'description' => 'Completely broken: app crashes immediately without any user interaction.',
    ],
    2 => [
        'symbol' => '⭐️⭐️',
        'description' => 'Only (part of) the main menu, intro or similar is working.',
    ],
    3 => [
        'symbol' => '⭐️⭐️⭐️',
        'description' => 'Some of the main content of the app works, but with major issues.',
    ],
    4 => [
        'symbol' => '⭐️⭐️⭐️⭐️',
        'description' => 'The main content of the app works (e.g. entire game is playable) with only small issues.',
    ],
    5 => [
        'symbol' => '⭐️⭐️⭐️⭐️⭐️',
        'description' => 'Everything works. The app is fully usable.',
    ],
];

// Plain text shown when submitting a new app, report or version. There are also
// specific texts for each of those, so only put general stuff here.
const GENERAL_GUIDANCE = "Do not link to pirated content in your submission.";

// Additional fields are stored in the JSON blob columns in the DB.
//
// The format for a list of fields is:
//
//      [
//          'key1' => [
//              'name' => 'Human-readable name 1',
//              'required' => TRUE,
//          ],
//          'key2' => [
//              'name' => 'Human-readable name 2',
//              'options' => [
//                  'option1' => 'Human-readable option name 1',
//                  'option2' => 'Human-readable option name 2',
//              ],
//          ],
//          'key3' => [
//              'name' => 'Human-readable name 3',
//              'at_end' => TRUE,
//          ],
//          ...
//      ]
//
// Notes:
//
// - There can be any number of fields.
// - The order of fields in this list is the order they'll show up in tables
//   and forms.
// - 'key1', 'key2' etc are the keys used for the fields in the JSON blob, and
//   are also used as HTML form field names. The keys can be any string, but
//   it is probably a good idea to only use simple ASCII identifiers.
// - If no data for a field is found in the database, it will show up as blank.
// - If a field is removed from the list, it won't show up on the site, but its
//   data is still in the database.
// - 'name' is the only required key when describing a field.
// - If 'at_end' is TRUE, the field appears after all the built-in fields.
//   Otherwise, it appears between the name and the rating or creation date.
// - If 'required' is TRUE, the field is marked as required and the form can't
//   be submitted without entering something for it.
// - They are plain-text, single-line fields by default. If you specify
//   'options' they become multiple-choice. The keys of that field are the
//   values stored in the JSON and sent in the form, whereas the values are
//   human-readable names. Therefore, you can add new keys and change the values
//   but you probably don't want to rename or remove keys. Don't use the key '',
//   that's reserved for when a selection hasn't been made.

// Additional fields for apps
const APP_EXTRA_FIELDS = [
    'developer_publisher' => [
        'name' => 'Developer/Publisher',
        'required' => TRUE,
    ],
    'release_year' => [
        'name' => 'Release year',
    ],
    'bitness' => [
        'name' => 'Bitness',
        'options' => [
            '32' => '32-bit',
            '64' => '64-bit',
            '128' => '128-bit',
            'quantum' => 'qubit',
        ],
    ],
];

// Plain text shown when submitting a new app. This might be used to explain,
// for example, how the name field should be used.
const APP_GUIDANCE = "";

// Additional fields for versions
const VERSION_EXTRA_FIELDS = [];

// Plain text shown when submitting a new version.
const VERSION_GUIDANCE = "";

// Additional fields for reports
const REPORT_EXTRA_FIELDS = [
    'operating_system' => [
        'name' => 'Operating system',
        'required' => TRUE,
    ],
    'gpu' => [
        'name' => 'GPU',
        'required' => TRUE,
    ],
    'remarks' => [
        'name' => 'Remarks',
        'at_end' => TRUE,
    ],
];

// Whether to allow attaching a screenshot to a report.
// Currently there is no configurability for the screenshot processing: they
// always become JPEGs with a maximum size of 640 on either axis, 80% quality,
// 500KB upload size limit.
const REPORT_SCREENSHOTS_ALLOWED = TRUE;

// Plain text shown when submitting a new report.
const REPORT_GUIDANCE = "";

// External user IDs of moderators empowered to approve and delete reports. Note
// that there is not currently an audit log for removals!
// The only currently accepted format for an external user ID is "github:xxxxxx"
// where xxxxxx is the GitHub user ID (not the same as the username).
// You can find the user ID at https://api.github.com/users/<username here>.
const MODERATOR_EXTERNAL_USER_IDS = [
    // "github:xxxxxx" => TRUE,
];

// GitHub API keys for authentication.
// Register the app at https://github.com/settings/applications/new.
// The callback URL must be "https://<your domain here>/signin/github-oauth-callback".
// Be sure to use a different application for testing and for the real site,
// and never make these public.
const GITHUB_CLIENT_ID = "aaaaaaaaaaaaaaaaaaaa";
const GITHUB_CLIENT_SECRET = "aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa";

// User-Agent header used when authenticating with the GitHub API.
const USER_AGENT = "App compatibility database (https://<your domain here>)";
