<?php declare(strict_types=1);

namespace hikari_no_yume\touchHLE\app_compatibility_db;

// Various functions useful across the site

function init(): void {
    ini_set('display_errors', '0');
    ini_set('session.auto_start', '0'); // See getSession() remarks

    require_once '../config.php';

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

// Set new session data. This will set a cookie, and therefore needs to be
// called before outputting anything if output buffering isn't in use.
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

// Convert extra fields defined in config.php to column/field lists intended for
// printTable(), printRecord() or printRecordForm(). Filters by 'at_end'.
function convertExtraFieldInfo(array /*<array>*/ $extraFields, bool $atEnd): array {
    $columns = [];
    foreach ($extraFields as $fieldKey => $fieldInfo) {
        if ($atEnd !== (bool)($fieldInfo['at_end'] ?? FALSE)) {
            continue;
        }
        $columns[$fieldKey] = [
            "name" => $fieldInfo["name"],
            "extra" => TRUE,
            "required" => $fieldInfo["required"] ?? FALSE,
        ];
    }
    return $columns;
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
    }
    return TRUE;
}

function formatExternalUsername(string $externalUsername) {
    $username = explode(':', $externalUsername)[1];
    $userUrl = 'https://github.com/' . $username;
    return '<a href="' . htmlspecialchars($userUrl) . '">@' . htmlspecialchars($username) . '</a>';
}

function formatButtonForm(array $buttonInfo): string {
    $form = '<form action="' . htmlspecialchars($buttonInfo['action']) . '" method="' . htmlspecialchars($buttonInfo['method']) . '">';
    if (isset($buttonInfo['param_name']) && isset($buttonInfo['param_value'])) {
        $form .= '<input type=hidden name="' . htmlspecialchars($buttonInfo['param_name']) . '" value="' . htmlspecialchars($buttonInfo['param_value']) . '">';
    }
    $form .= '<input type=submit value="' . htmlspecialchars($buttonInfo['label']) . '">';
    $form .= '</form>';
    return $form;
}

// Helper function for printTable() and printRecord()
function printCell(array $row, \stdClass $rowExtra, string $columnKey, array /*<array>*/ $columnInfo): void {
    $cell = (($columnInfo['extra'] ?? FALSE) === TRUE)
          ? ($rowExtra->{$columnKey} ?? NULL)
          : ($row[$columnKey] ?? NULL);
    $cellContent = \htmlspecialchars((string)$cell);

    $openingTag = '<td>';
    if (($columnInfo['datetime'] ?? FALSE) === TRUE) {
        // SQLite uses the 'YYYY-MM-DD HH:MM:SS' format in UTC, but
        // HTML <time>'s datetime attribute wants RFC 3339 format.
        // Note that it also must be compatible with the JavaScript
        // Date() constructor (see htdocs/script.js).
        [$date, $time] = explode(' ', $cell);
        $rfc3339DateTime = $date . 'T' . $time . 'Z';
        $cellContent = '<time datetime="' . $rfc3339DateTime . '">' . $cellContent . '</time>';
    } else if (($columnInfo['rating'] ?? FALSE) === TRUE) {
        $cellContent = htmlspecialchars(RATINGS[$cell]['symbol'] ?? '');
    } else if (($columnInfo['unapproved_if_nonzero'] ?? FALSE) === TRUE) {
        if ($cellContent != 0) {
            $openingTag = '<td class=unapproved>';
        }
    } else if (isset($columnInfo['link'])) {
        [$linkUrlPrefix, $linkIdColumn] = $columnInfo['link'];
        $linkUrlSuffix = $columnInfo['link'][2] ?? '';
        $cellContent = '<a href="' . htmlspecialchars($linkUrlPrefix . $row[$linkIdColumn] . $linkUrlSuffix) . '">' . $cellContent . '</a>';
    } else if (isset($columnInfo['external_username'])) {
        $cellContent = formatExternalUsername($cell);
    } else if (isset($columnInfo['button'])) {
        $buttonInfo = $columnInfo['button'];
        $buttonInfo['param_value'] = (string)$row[$buttonInfo['param_column']];
        $cellContent = formatButtonForm($buttonInfo);
    }

    echo $openingTag, $cellContent, '</td>';
}

// Helper function for printRecordForm()
function printFormCell(string $fieldKey, array /*<array>*/ $fieldInfo, string $fieldName): void {
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
    } else {
        echo '<input type=text ', $common, '>';
    }
    echo '</td>';
}

// Print an HTML table with several rows of data.
// - $columns provides the keys, ordering, names and formatting for columns.
// - $rows provides the data for each row as an associative array, with the keys
//   being a subset of the keys in $columns.
// The key 'extra' in a row is always treated as a JSON object.
// The key 'unapproved' is also special. If it is truthy, the row is tagged with
// the 'unapproved' CSS class.
function printTable(array /*<array>*/ $columns, array /*<array>*/ $rows): void {
    echo '<table>';

    echo '<thead>';
    echo '<tr>';
    foreach ($columns as $column) {
        echo '<th>', \htmlspecialchars($column['name']), '</th>';
    }
    echo '</tr>';
    echo '</thead>';

    echo '<tbody>';
    foreach ($rows as $row) {
        $rowExtra = json_decode($row['extra'] ?? '{}');
        if ((bool)($row['unapproved'] ?? FALSE)) {
            echo '<tr class=unapproved>';
        } else {
            echo '<tr>';
        }
        foreach ($columns as $columnKey => $columnInfo) {
            printCell($row, $rowExtra, $columnKey, $columnInfo);
        }
        echo '</tr>';
    }
    echo '</tbody>';

    echo '</table>';
}

// Print an HTML table for a single record, displayed vertically.
// The parameters work similarly to printTable():
// - $fields has the same format as $columns
// - $record has the same format as $rows[0]
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
