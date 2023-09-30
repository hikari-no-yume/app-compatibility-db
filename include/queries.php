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
            'stars' => TRUE,
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

function listVersionsForApp(int $appId): void {
    $rows = query('
        SELECT
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
            'stars' => TRUE,
        ],
        'last_updated' => [
            'name' => 'Last updated',
            'datetime' => TRUE,
        ],
    ];
    $columns += convertExtraFieldInfo(VERSION_EXTRA_FIELDS, TRUE);

    printTable($columns, $rows);
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
            'stars' => TRUE,
        ],
        'created' => [
            'name' => 'Created',
            'datetime' => TRUE,
        ],
    ];
    $columns += convertExtraFieldInfo(REPORT_EXTRA_FIELDS, TRUE);

    printTable($columns, $rows);
}
