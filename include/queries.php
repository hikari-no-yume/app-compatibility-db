<?php declare(strict_types=1);

namespace hikari_no_yume\touchHLE\app_compatibility_db;

// Functions for querying and displaying particular types of data.
// Many of these will only be used on a single page of the site.

function listApps(): void {
    $rows = query('
        SELECT
            apps.app_id AS app_id,
            name,
            COALESCE(version_summaries.last_updated, apps.created) AS last_updated,
            version_summaries.best_rating AS best_rating,
            extra
        FROM
            apps
        LEFT JOIN
            (
                SELECT
                    MAX(report_summaries.rating) AS best_rating,
                    MAX(report_summaries.last_updated, versions.created) AS last_updated,
                    app_id
                FROM
                    versions
                LEFT JOIN
                (
                    SELECT
                        MAX(rating) AS rating,
                        MAX(created) AS last_updated,
                        version_id
                    FROM
                        reports
                    GROUP BY
                        version_id
                )
                AS
                    report_summaries
                ON
                    versions.version_id = report_summaries.version_id
                GROUP BY
                    app_id
            )
            AS
                version_summaries
            ON
                apps.app_id = version_summaries.app_id
        ;
    ');

    $columns = [
        'name' => [
            'name' => 'App name',
            'link' => ['/apps/', 'app_id'],
        ],
    ];
    $columns += convertExtraFieldInfo(APP_EXTRA_FIELDS, FALSE);
    $columns += [
        'best_rating' => [
            'name' => 'Best rating',
            'rating' => TRUE,
        ],
        'last_updated' => [
            'name' => 'Last updated',
            'datetime' => TRUE,
        ],
    ];
    $columns += convertExtraFieldInfo(APP_EXTRA_FIELDS, TRUE);

    printTable($columns, $rows);
}

// Returns NULL if the app isn't found.
function getApp(int $id): ?array {
    $rows = query('
        SELECT
            *
        FROM
            apps
        WHERE
            app_id = :app_id
        ;
    ', [':app_id' => $id]);

    if ($rows === []) {
        return NULL;
    } else {
        return $rows[0];
    }
}

// Input comes from getApp().
function printApp(array $appInfo): void {
    $fields = [
        'name' => [
            'name' => 'App name',
            'link' => ['/apps/', 'app_id'],
        ]
    ];
    $fields += convertExtraFieldInfo(APP_EXTRA_FIELDS, FALSE);
    $fields += [
        'created' => [
            'name' => 'Created',
            'datetime' => TRUE,
        ],
    ];
    $fields += convertExtraFieldInfo(APP_EXTRA_FIELDS, TRUE);

    printRecord($fields, $appInfo);
}

function printAppForm(): void {
    $fields = [
        'name' => [
            'name' => 'App name',
            'required' => TRUE,
        ]
    ];
    $fields += convertExtraFieldInfo(APP_EXTRA_FIELDS, FALSE);
    $fields += convertExtraFieldInfo(APP_EXTRA_FIELDS, TRUE);

    printRecordForm($fields, 'app');
}

// It is recommended to call this as part of a transaction.
// The result is a the ID of the new app, or NULL if the input is invalid
// in some way.
function createApp(array $app): ?int {
    $name = $app['name'] ?? NULL;
    if (!is_string($name) || $name === "") {
        return NULL;
    }

    $extra = $app['extra'] ?? [];
    if (!is_array($extra)) {
        return NULL;
    }
    if (!validateExtraFields(APP_EXTRA_FIELDS, $extra)) {
        return NULL;
    }
    $extra = json_encode($extra);

    $rows = query('
        INSERT INTO
            apps(
                created,
                name,
                extra
            )
        VALUES
            (
                datetime(),
                :name,
                :extra
            )
        RETURNING
            app_id
        ;
    ', [
        ':name' => $name,
        ':extra' => $extra,
    ]);
    return $rows[0]['app_id'];
}

// Returns NULL if the version isn't found.
function getVersion(int $id): ?array {
    $rows = query('
        SELECT
            *
        FROM
            versions
        WHERE
            version_id = :version_id
        ;
    ', [':version_id' => $id]);

    if ($rows === []) {
        return NULL;
    } else {
        return $rows[0];
    }
}

function listVersionsForApp(int $appId): void {
    $rows = query('
        SELECT
            versions.version_id AS version_id,
            name,
            report_summaries.rating AS best_rating,
            COALESCE(report_summaries.last_updated, versions.created) AS last_updated,
            extra
        FROM
            versions
        LEFT JOIN
            (
                SELECT
                    MAX(rating) AS rating,
                    MAX(created) AS last_updated,
                    version_id
                FROM
                    reports
                GROUP BY
                    version_id
            )
            AS
                report_summaries
            ON
                versions.version_id = report_summaries.version_id
        WHERE
            app_id = :app_id
        ;
    ', [':app_id' => $appId]);

    $columns = [
        'name' => [
            'name' => 'Version name',
        ],
    ];
    $columns += convertExtraFieldInfo(VERSION_EXTRA_FIELDS, FALSE);
    $columns += [
        'best_rating' => [
            'name' => 'Best rating',
            'rating' => TRUE,
        ],
        'last_updated' => [
            'name' => 'Last updated',
            'datetime' => TRUE,
        ],
    ];
    $columns += convertExtraFieldInfo(VERSION_EXTRA_FIELDS, TRUE);
    $columns += [
        '_new_report' => [
            'name' => '',
            'button' => [
                'action' => '/reports/new',
                'method' => 'get',
                'label' => 'Submit report for this version',
                'param_name' => 'version',
                'param_column' => 'version_id',
            ],
        ],
    ];

    printTable($columns, $rows);
}

function printVersionForm(): void {
    $fields = [
        'name' => [
            'name' => 'Version name',
            'required' => TRUE,
        ]
    ];
    $fields += convertExtraFieldInfo(VERSION_EXTRA_FIELDS, FALSE);
    $fields += convertExtraFieldInfo(VERSION_EXTRA_FIELDS, TRUE);

    printRecordForm($fields, 'version');
}

// It is recommended to call this as part of a transaction.
// The result is a the ID of the new version, or NULL if the input is invalid
// in some way.
function createVersion(array $version): ?int {
    $appId = $version['app_id'] ?? NULL;
    if (!is_int($appId)) {
        return NULL;
    }

    $name = $version['name'] ?? NULL;
    if (!is_string($name) || $name === "") {
        return NULL;
    }

    $extra = $version['extra'] ?? [];
    if (!is_array($extra)) {
        return NULL;
    }
    if (!validateExtraFields(VERSION_EXTRA_FIELDS, $extra)) {
        return NULL;
    }
    $extra = json_encode($extra);

    $rows = query('
        INSERT INTO
            versions(
                app_id,
                created,
                name,
                extra
            )
        VALUES
            (
                :app_id,
                datetime(),
                :name,
                :extra
            )
        RETURNING
            version_id
        ;
    ', [
        ':app_id' => $appId,
        ':name' => $name,
        ':extra' => $extra,
    ]);
    return $rows[0]['version_id'];
}

function listReportsForApp(int $appId): void {
    $rows = query('
        SELECT
            versions.name AS version_name,
            reports.rating AS rating,
            reports.created AS created,
            reports.extra AS extra
        FROM
            reports
        LEFT JOIN
                versions
            ON
                reports.version_id = versions.version_id
        WHERE
            app_id = :app_id
        ORDER BY
            reports.created DESC
        ;
    ', [':app_id' => $appId]);

    $columns = [
        'version_name' => [
            'name' => 'Version name',
        ],
    ];
    $columns += convertExtraFieldInfo(REPORT_EXTRA_FIELDS, FALSE);
    $columns += [
        'rating' => [
            'name' => 'Rating',
            'rating' => TRUE,
        ],
        'created' => [
            'name' => 'Created',
            'datetime' => TRUE,
        ],
    ];
    $columns += convertExtraFieldInfo(REPORT_EXTRA_FIELDS, TRUE);

    printTable($columns, $rows);
}

function printReportForm(): void {
    $fields = convertExtraFieldInfo(REPORT_EXTRA_FIELDS, FALSE);
    $fields += [
        'rating' => [
            'name' => 'Rating',
            'rating' => TRUE,
        ],
    ];
    $fields += convertExtraFieldInfo(REPORT_EXTRA_FIELDS, TRUE);

    printRecordForm($fields, 'report');
}

// It is recommended to call this as part of a transaction.
// The result is a the ID of the new report, or NULL if the input is invalid
// in some way.
function createReport(array $report): ?int {
    $rating = $report['rating'] ?? NULL;
    if (!is_int($rating) || $rating < 1 || $rating > 5) {
        return NULL;
    }

    $versionId = $report['version_id'] ?? NULL;
    if (!is_int($versionId)) {
        return NULL;
    }

    $extra = $report['extra'] ?? [];
    if (!is_array($extra)) {
        return NULL;
    }
    if (!validateExtraFields(REPORT_EXTRA_FIELDS, $extra)) {
        return NULL;
    }
    $extra = json_encode($extra);

    $rows = query('
        INSERT INTO
            reports(
                version_id,
                created,
                rating,
                extra
            )
        VALUES
            (
                :version_id,
                datetime(),
                :rating,
                :extra
            )
        RETURNING
            report_id
        ;
    ', [
        ':version_id' => $versionId,
        ':rating' => $rating,
        ':extra' => $extra,
    ]);
    return $rows[0]['report_id'];
}
