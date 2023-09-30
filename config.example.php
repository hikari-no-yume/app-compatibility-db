<?php

// Human-readable name of the site, as plain text
const SITE_NAME = 'App compatibility database';

// Path to the SQLite 3 database, relative to the htdocs directory
const SITE_DB_PATH = '../app_db.sqlite3';

// Additional fields are stored in the JSON blob columns in the DB.
// Currently these can only be plain-text, single-line fields.
//
// The format for a list of fields is:
//
//      [
//          'key1' => [
//              'name' => 'Human-readable name 1',
//          ],
//          'key2' => [
//              'name' => 'Human-readable name 2',
//              'at_end' => TRUE,
//          ],
//          ...
//      ]
//
// Notes:
//
// - There can be any number of fields.
// - The order of fields in this list is the order they'll show up in tables
//   and forms up.
// - 'key1', 'key2' etc are the keys used for the fields in the JSON blob, and
//   are also used as HTML form field names. The keys can be any string, but
//   it is probably a good idea to only use simple ASCII identifiers.
// - If no data for a field is found in the database, it will show up as blank.
// - If a field is removed from the list, it won't show up on the site, but its
//   data is still in the database.
// - If 'at_end' is TRUE, the field appears after all the built-in fields.
//   Otherwise, it appears between the name and the rating or creation date.

// Additional fields for apps
const APP_EXTRA_FIELDS = [
    'release_year' => [
        'name' => 'Release year',
    ],
];

// Additional fields for versions
const VERSION_EXTRA_FIELDS = [];

// Additional fields for reports
const REPORT_EXTRA_FIELDS = [
    'operating_system' => [
        'name' => 'Operating system',
    ],
    'remarks' => [
        'name' => 'Remarks',
        'at_end' => TRUE,
    ],
];
