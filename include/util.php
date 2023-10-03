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

// Convert extra fields defined in config.php to column/field lists intended for
// printTable() or printRecord(). Filters by 'at_end'.
function convertExtraFieldInfo(array /*<array>*/ $extraFields, bool $atEnd): array {
    $columns = [];
    foreach ($extraFields as $fieldKey => $fieldInfo) {
        if ($atEnd !== (bool)($fieldInfo['at_end'] ?? FALSE)) {
            continue;
        }
        $columns[$fieldKey] = [
            "name" => $fieldInfo["name"],
            "extra" => TRUE,
        ];
    }
    return $columns;
}

// Helper function for printTable() and printDetail()
function printCell(array $row, \stdClass $rowExtra, string $columnKey, array /*<array>*/ $columnInfo): void {
    $cell = (($columnInfo['extra'] ?? FALSE) === TRUE)
          ? ($rowExtra->{$columnKey} ?? NULL)
          : ($row[$columnKey] ?? NULL);
    $cellContent = \htmlspecialchars((string)$cell);

    if (($columnInfo['datetime'] ?? FALSE) === TRUE) {
        // SQLite uses the 'YYYY-MM-DD HH:MM:SS' format in UTC, but
        // HTML <time>'s datetime attribute wants RFC 3339 format.
        // Note that it also must be compatible with the JavaScript
        // Date() constructor (see htdocs/script.js).
        [$date, $time] = explode(' ', $cell);
        $rfc3339DateTime = $date . 'T' . $time . 'Z';
        $cellContent = '<time datetime="' . $rfc3339DateTime . '">' . $cellContent . '</time>';
    } else if (($columnInfo['stars'] ?? FALSE) === TRUE) {
        $cellContent = str_repeat("⭐️", (int)$cell);
    } else if (isset($columnInfo['link'])) {
        [$linkUrlPrefix, $linkIdColumn] = $columnInfo['link'];
        $cellContent = '<a href="' . htmlspecialchars($linkUrlPrefix . $row[$linkIdColumn]) . '">' . $cellContent . '</a>';
    }

    echo '<td>', $cellContent, '</td>';
}

// Print an HTML table with several rows of data.
// - $columns provides the keys, ordering, names and formatting for columns.
// - $rows provides the data for each row as an associative array, with the keys
//   being a subset of the keys in $columns.
// The key 'extra' in a row is always treated as a JSON object.
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
        echo '<tr>';
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
    echo '<table>';

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

