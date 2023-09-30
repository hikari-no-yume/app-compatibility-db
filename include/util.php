<?php declare(strict_types=1);

namespace hikari_no_yume\touchHLE\app_compatibility_db;

// Various functions useful across the site

function init(): void {
    ini_set('display_errors', '0');

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

function show404(): void {
    header("HTTP/1.1 404 Not Found");
    require '../templates/404.phpt';
    exit;
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

