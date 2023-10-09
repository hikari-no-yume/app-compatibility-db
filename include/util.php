<?php declare(strict_types=1);

namespace hikari_no_yume\touchHLE\app_compatibility_db;

// Various functions useful across the site

function initDb(): void {
    global $db;
    $db = new \PDO('sqlite:' . SITE_DB_PATH);
    $db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
}

function query(string $query, array $args = []): array {
    global $db;
    $stmt = $db->prepare($query);
    $stmt->execute($args);
    return $stmt->fetchAll(\PDO::FETCH_ASSOC);
}

function beginTransaction(): void {
    global $db;
    $db->beginTransaction();
}
function commitTransaction(): void {
    global $db;
    $db->commit();
}
function rollbackTransaction(): void {
    global $db;
    $db->rollBack();
}

function redirect(string $url): void {
    header('HTTP/1.1 303 See Other');
    header('Location: ' . $url);
    exit;
}

function show404(): void {
    header("HTTP/1.1 404 Not Found");
    require '../templates/404.phpt';
    exit;
}

function exit400(): void {
    header('HTTP/1.1 400 Bad Request');
    exit;
}

// Get the current session data, if any. This is intended to not set a cookie
// if one does not already exist. The idea is that cookies are only set when
// necessary, so the user's privacy is respected by letting them actively choose
// whether to take actions that set a cookie, obviating the need for cookie
// banners or pop-ups.
function getSession(): ?array {
    if (!isset($_COOKIE['PHPSESSID'])) {
        return NULL;
    }
    session_start([
        'name' => 'PHPSESSID',
        'use_only_cookies' => TRUE,
        'read_and_close' => TRUE, // prevent lock contention
        'use_strict_mode' => TRUE, // don't accept unrecognised session IDs
    ]);
    if ($_SESSION === []) {
        return NULL; // session must have been stale
    }
    return $_SESSION;
}

// Set new session data. This will set a cookie.
// Be sure you've informed the user that the action they're taking will set a
// cookie.
function setSession(array $sessionData) {
    session_start([
        'name' => 'PHPSESSID',
        'use_only_cookies' => TRUE,
        'use_strict_mode' => TRUE, // don't recreate sessions with old IDs
    ]);
    $_SESSION = $sessionData;
    session_commit();
}

// Destroy session and unset session cookie. Same output buffering caveat as
// setSession() applies.
function destroySession(): void {
    if (!isset($_COOKIE['PHPSESSID'])) {
        return;
    }
    session_start([
        'name' => 'PHPSESSID',
        'use_only_cookies' => TRUE,
        'use_strict_mode' => TRUE, // don't accept unrecognised session IDs
    ]);
    session_destroy();
    // Expiry date in the past triggers deletion.
    setcookie('PHPSESSID', '', time() - 3600);
}

// Check whether the logged-in user is a moderator. This property is *not*
// stored in the session data, but rather derived from looking up the user ID
// (which is in the session) in the configured list of moderators, so that
// moderator access can be revoked without wiping sessions. $session is the
// array returned by getSession().
function signedInUserIsModerator(?array $session): bool {
    if ($session === NULL) {
        return FALSE;
    }
    return (MODERATOR_EXTERNAL_USER_IDS[$session['external_user_id']] ?? FALSE) === TRUE;
}

// Convert extra fields defined in config.php to field lists intended for
// printTable(), printRecord() or printRecordForm(). Filters by 'at_end'.
function convertExtraFieldInfo(array /*<array>*/ $extraFields, bool $atEnd): array {
    $fields = [];
    foreach ($extraFields as $fieldKey => $fieldInfo) {
        if ($atEnd !== (bool)($fieldInfo['at_end'] ?? FALSE)) {
            continue;
        }
        $fields[$fieldKey] = [
            "name" => $fieldInfo["name"],
            "extra" => TRUE,
            "required" => $fieldInfo["required"] ?? FALSE,
            "options" => $fieldInfo["options"] ?? NULL,
        ];
    }
    return $fields;
}

// Validate extra field input (e.g. from $_POST) against extra fields lists.
// The input should _not_ have been converted with convertExtraFieldInfo().
function validateExtraFields(array /*<array>*/ $extraFields, array $extraInput): bool {
    foreach ($extraInput as $fieldKey => $fieldValue) {
        $fieldInfo = $extraFields[$fieldKey] ?? NULL;
        if ($fieldInfo === NULL) {
            return FALSE;
        }
        if (!is_string($fieldValue)) {
            return FALSE;
        }
        if (($fieldInfo['required'] ?? FALSE) === TRUE && $fieldValue === "") {
            return FALSE;
        }
        if (isset($fieldInfo['options']) && !isset($fieldInfo['options'][$fieldValue])) {
            return FALSE;
        }
    }
    return TRUE;
}

function printExternalUsername(string $externalUsername): void {
    $username = explode(':', $externalUsername)[1];
    $userUrl = 'https://github.com/' . $username;
    echo '<a href="', htmlspecialchars($userUrl), '">@', htmlspecialchars($username), '</a>';
}

function printButtonForm(array $buttonInfo): void {
    echo '<form action="', htmlspecialchars($buttonInfo['action']), '"';
    echo ' method="', htmlspecialchars($buttonInfo['method']), '"';
    if (isset($buttonInfo['onsubmit'])) {
        echo ' onsubmit="', htmlspecialchars($buttonInfo['onsubmit']), '"';
    }
    echo '>';
    if (isset($buttonInfo['param_name']) && isset($buttonInfo['param_value'])) {
        echo '<input type=hidden name="', htmlspecialchars($buttonInfo['param_name']), '" value="', htmlspecialchars($buttonInfo['param_value']), '">';
    }
    echo '<input type=submit value="', htmlspecialchars($buttonInfo['label']), '">';
    echo '</form>';
}

// Helper function for printTable() and printRecord()
function printCell(array $record, \stdClass $recordExtra, string $fieldKey, array $fieldInfo): void {
    $cell = (($fieldInfo['extra'] ?? FALSE) === TRUE)
          ? ($recordExtra->{$fieldKey} ?? NULL)
          : ($record[$fieldKey] ?? NULL);

    if (($fieldInfo['unapproved_if_nonzero'] ?? FALSE) === TRUE && $cell != 0) {
        echo '<td class=unapproved>';
    } else {
        echo '<td>';
    }

    if (($fieldInfo['datetime'] ?? FALSE) === TRUE) {
        if ($cell !== NULL) {
            // SQLite uses the 'YYYY-MM-DD HH:MM:SS' format in UTC, but
            // HTML <time>'s datetime attribute wants RFC 3339 format.
            // Note that it also must be compatible with the JavaScript
            // Date() constructor (see htdocs/script.js).
            [$date, $time] = explode(' ', $cell);
            $rfc3339DateTime = $date . 'T' . $time . 'Z';
            echo '<time datetime="', $rfc3339DateTime, '">', htmlspecialchars($cell), '</time>';
        }
    } else if (($fieldInfo['rating'] ?? FALSE) === TRUE) {
        echo htmlspecialchars(RATINGS[$cell]['symbol'] ?? '');
    } else if (isset($fieldInfo['options'])) {
        echo htmlspecialchars($fieldInfo['options'][$cell] ?? '');
    } else if (isset($fieldInfo['link'])) {
        [$linkUrlPrefix, $linkIdField] = $fieldInfo['link'];
        $linkUrlSuffix = $fieldInfo['link'][2] ?? '';
        echo '<a href="', htmlspecialchars($linkUrlPrefix . $record[$linkIdField] . $linkUrlSuffix), '">', htmlspecialchars($cell), '</a>';
    } else if (isset($fieldInfo['external_username'])) {
        if ($cell !== NULL) {
            printExternalUsername($cell);
        }
    } else if (isset($fieldInfo['buttons'])) {
        foreach ($fieldInfo['buttons'] as $buttonInfo) {
            if (isset($buttonInfo['depends_on_column']) && $record[$buttonInfo['depends_on_column']] == 0) {
                continue;
            }
            if (isset($buttonInfo['param_column'])) {
                $buttonInfo['param_value'] = (string)$record[$buttonInfo['param_column']];
            }
            if (isset($buttonInfo['action_column'])) {
                $buttonInfo['action'] = $buttonInfo['action_prefix'] . (string)$record[$buttonInfo['action_column']] . ($buttonInfo['action_suffix'] ?? '');
            }
            printButtonForm($buttonInfo);
        }
    } else {
        echo htmlspecialchars((string)$cell);
    }

    echo '</td>';
}

// Helper function for printRecordForm()
function printFormCell(string $fieldKey, array $fieldInfo, string $fieldName): void {
    echo '<td>';
    $common = 'name="' . htmlspecialchars($fieldName) . '"';
    $common .= ' id="' . htmlspecialchars($fieldName) . '"';
    if (($fieldInfo['required'] ?? FALSE) === TRUE) {
        $common .= ' required';
    }
    if (($fieldInfo['rating'] ?? FALSE) === TRUE) {
        echo '<select ', $common, '>';
        echo '<option value="" selected>(please select)</option>';
        for ($i = 1; $i <= 5; $i++) {
            echo '<option value=', $i, '> ', $i, ' - ', htmlspecialchars(RATINGS[$i]['symbol']), ' - ', htmlspecialchars(RATINGS[$i]['description']), '</option>';
        }
        echo '</select>';
    } else if (isset($fieldInfo['options'])) {
        echo '<select ', $common, '>';
        echo '<option value="" selected>(please select)</option>';
        foreach ($fieldInfo['options'] as $optionKey => $optionName) {
            echo '<option value="', htmlspecialchars((string)$optionKey), '">', htmlspecialchars($optionName), '</option>';
        }
        echo '</select>';
    } else {
        echo '<input type=text ', $common, '>';
    }
    echo '</td>';
}

// Print an HTML table with several rows of data. Each row is one record, and
// each column corresponds to a field.
// - $fields provides the keys, ordering, names and formatting for the fields.
// - $records provides the data for each record as an associative array, with
//   the keys being a subset of the keys in $fields.
// The key 'extra' in a record is always treated as a JSON object.
// The key 'unapproved' is also special. If it is truthy, the row for the record
// is tagged with the 'unapproved' CSS class.
function printTable(array /*<array>*/ $fields, array /*<array>*/ $records): void {
    echo '<table>';

    echo '<thead>';
    echo '<tr>';
    foreach ($fields as $field) {
        echo '<th>', \htmlspecialchars($field['name']), '</th>';
    }
    echo '</tr>';
    echo '</thead>';

    echo '<tbody>';
    foreach ($records as $record) {
        $recordExtra = json_decode($record['extra'] ?? '{}');
        if ((bool)($record['unapproved'] ?? FALSE)) {
            echo '<tr class=unapproved>';
        } else {
            echo '<tr>';
        }
        foreach ($fields as $fieldKey => $fieldInfo) {
            printCell($record, $recordExtra, $fieldKey, $fieldInfo);
        }
        echo '</tr>';
    }
    echo '</tbody>';

    echo '</table>';
}

// Print an HTML table for a single record, displayed vertically.
// The parameters work similarly to printTable(). $record has the same format
// as $records[0].
function printRecord(array /*<array>*/ $fields, array $record): void {
    if ((bool)($record['unapproved'] ?? FALSE)) {
        echo '<table class=unapproved>';
    } else {
        echo '<table>';
    }

    echo '<tbody>';
    foreach ($fields as $fieldKey => $fieldInfo) {
        $recordExtra = json_decode($record['extra'] ?? '{}');
        echo '<tr>';
        echo '<th>', \htmlspecialchars($fieldInfo['name']), '</th>';
        printCell($record, $recordExtra, $fieldKey, $fieldInfo);
        echo '</tr>';
    }
    echo '</tbody>';

    echo '</table>';
}

// Print part of an HTML form (without the <form> tag) for a single record,
// displayed vertically. The $fields parameter works like printRecord()'s.
// The form inputs' name attributes will have the format
// '$recordName[field_key_here]', which PHP will parse into an associative
// array when submitted, so it's possible to have several records in a single
// form.
function printRecordForm(array /*<array>*/ $fields, string $recordName): void {
    echo '<table>';

    echo '<tbody>';
    foreach ($fields as $fieldKey => $fieldInfo) {
        $fieldName = (($fieldInfo['extra'] ?? FALSE) === TRUE)
                   ? $recordName . '[extra][' . $fieldKey . ']'
                   : $recordName . '[' . $fieldKey . ']';
        echo '<tr>';
        echo '<th>';
        echo '<label for="', htmlspecialchars($fieldName), '">', htmlspecialchars($fieldInfo['name']), '</label>';
        if (($fieldInfo['required'] ?? FALSE) == TRUE) {
            echo '<span class=required>*</span>';
        }
        echo '</th>';
        printFormCell($fieldKey, $fieldInfo, $fieldName);
        echo '</tr>';
    }
    echo '</tbody>';

    echo '</table>';
}

function printRatingsLegend(): void {
    $columns = [
        'rating' => [
            'name' => 'Rating',
            'rating' => TRUE,
        ],
        'description' => [
            'name' => 'Description',
        ],
    ];
    $rows = [];
    for ($i = 1; $i <= 5; $i++) {
        $rows[] = [
            'rating' => $i,
            'description' => RATINGS[$i]['description'],
        ];
    }
    printTable($columns, $rows);
}
