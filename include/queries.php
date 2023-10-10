<?php declare(strict_types=1);

namespace hikari_no_yume\touchHLE\app_compatibility_db;

// Functions for querying and displaying particular types of data.
// Many of these will only be used on a single page of the site.

function listApps(bool $showUnapproved): void {
    if ($showUnapproved) {
        $extraColumns = '
            ,
            unapproved_version_counts.count AS unapproved_version_count,
            unapproved_report_counts.count AS unapproved_report_count
        ';
        $extraJoins = '
            LEFT JOIN
                (
                    SELECT
                        COUNT(*) AS count,
                        app_id
                    FROM
                        versions
                    WHERE
                        approved IS NULL
                    GROUP BY
                        app_id
                )
                AS
                    unapproved_version_counts
                ON
                    apps.app_id = unapproved_version_counts.app_id
            LEFT JOIN
                (
                    SELECT
                        COUNT(*) AS count,
                        versions.app_id AS app_id
                    FROM
                        reports
                    LEFT JOIN
                            versions
                        ON
                            versions.version_id = reports.version_id
                    WHERE
                        reports.approved IS NULL
                    GROUP BY
                        versions.app_id
                )
                AS
                    unapproved_report_counts
                ON
                    apps.app_id = unapproved_report_counts.app_id
        ';
    } else {
        $extraColumns = '';
        $extraJoins = '';
    }

    $rows = query('
        SELECT
            apps.app_id AS app_id,
            name,
            COALESCE(version_summaries.last_updated, apps.created) AS last_updated,
            (approved IS NULL) AS unapproved,
            version_summaries.best_rating AS best_rating,
            extra
            ' . $extraColumns . '
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
                        WHERE
                            (:show_unapproved OR approved IS NOT NULL)
                        GROUP BY
                            version_id
                    )
                    AS
                        report_summaries
                    ON
                        versions.version_id = report_summaries.version_id
                WHERE
                    (:show_unapproved OR approved IS NOT NULL)
                GROUP BY
                    app_id
            )
            AS
                version_summaries
            ON
                apps.app_id = version_summaries.app_id
        ' . $extraJoins . '
        WHERE
            :show_unapproved OR approved IS NOT NULL
        ORDER BY
            name ASC
        ;
    ', [':show_unapproved' => $showUnapproved]);

    $columns = [
        'name' => [
            'name' => 'App name',
            'link' => ['/apps/', 'app_id', ($showUnapproved ? '?show_unapproved=1' : '')],
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
    if ($showUnapproved) {
        $columns += [
            'unapproved_version_count' => [
                'name' => 'Unapproved versions',
                'unapproved_if_nonzero' => TRUE,
            ],
            'unapproved_report_count' => [
                'name' => 'Unapproved reports',
                'unapproved_if_nonzero' => TRUE,
            ],
        ];
    }

    printTable($columns, $rows);
}

// Returns NULL if the app isn't found.
function getApp(int $id): ?array {
    $rows = query('
        SELECT
            *,
            users.external_username AS created_by_username,
            (approved IS NULL) AS unapproved,
            approved,
            (SELECT external_username FROM users WHERE user_id = apps.approved_by) AS approved_by
        FROM
            apps
        LEFT JOIN
                users
            ON
                users.user_id = apps.created_by
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

// Helper for printApp()/listVersionsForApp()/listReportsForApp()
function moderationActionButtons(string $urlPrefix, string $idColumn, string $label): array {
    return [
        [
            'action_prefix' => $urlPrefix,
            'action_column' => $idColumn,
            'action_suffix' => '/approve',
            'method' => 'post',
            'label' => '✅ Approve',
            'onsubmit' => 'return confirm("Are you sure you want to ✅ approve this ' . $label . '?")',
            'depends_on_column' => 'unapproved',
        ],
        [
            'action_prefix' => $urlPrefix,
            'action_column' => $idColumn,
            'action_suffix' => '/delete',
            'method' => 'post',
            'label' => '🚮 Delete',
            'onsubmit' => 'return confirm("Are you sure you want to 🚮 DELETE this ' . $label . '? This action CANNOT BE UNDONE.")',
        ]
    ];
}

// Input comes from getApp().
function printApp(array $appInfo, bool $moderatorView): void {
    $fields = [
        'name' => [
            'name' => 'App name',
            'link' => ['/apps/', 'app_id'],
        ]
    ];
    $fields += convertExtraFieldInfo(APP_EXTRA_FIELDS, FALSE);
    $fields += [
        'created' => [
            'name' => 'First reported',
            'datetime' => TRUE,
        ],
        'created_by_username' => [
            'name' => 'First reported by',
            'external_username' => TRUE,
        ],
    ];
    if ($moderatorView) {
        $fields += [
            'approved' => [
                'name' => 'Approved',
                'datetime' => TRUE,
            ],
            'approved_by' => [
                'name' => 'Approved by',
                'external_username' => TRUE,
            ],
        ];
    }
    $fields += convertExtraFieldInfo(APP_EXTRA_FIELDS, TRUE);
    if ($moderatorView) {
        $fields += [
            '_buttons' => [
                'name' => '',
                'buttons' => moderationActionButtons('/apps/', 'app_id', 'app'),
            ],
        ];
    }

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
// The new app has unapproved state, awaiting moderation.
function createApp(array $app): ?int {
    $createdBy = $app['created_by'] ?? NULL;
    if (!is_int($createdBy)) {
        return NULL;
    }

    $name = $app['name'] ?? NULL;
    if (!is_string($name) || $name === "" || !validateInputLength($name)) {
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
                created_by,
                name,
                extra
            )
        VALUES
            (
                datetime(),
                :created_by,
                :name,
                :extra
            )
        RETURNING
            app_id
        ;
    ', [
        ':created_by' => $createdBy,
        ':name' => $name,
        ':extra' => $extra,
    ]);
    return $rows[0]['app_id'];
}

// It is recommended to call this as part of a transaction.
function approveApp(int $appId, int $approvedByUserId): void {
    query('
        UPDATE
            apps
        SET
            approved = datetime(),
            approved_by = :approved_by_user_id
        WHERE
            app_id = :app_id AND
            approved IS NULL
        ;
    ', [
        ':app_id' => $appId,
        ':approved_by_user_id' => $approvedByUserId,
    ]);
}

// This must be called as part of a transaction!
// It deletes not only the app, but also its connected versions and reports!
// There is no audit log or undo!
function deleteApp(int $appId): void {
    query('
        DELETE FROM
            report_screenshots
        WHERE
            EXISTS (
                SELECT
                    1
                FROM
                    reports
                LEFT JOIN
                        versions
                    ON
                        reports.version_id = versions.version_id
                WHERE
                    reports.report_id = report_screenshots.report_id AND
                    versions.app_id = :app_id
            )
        ;
    ', [':app_id' => $appId]);
    query('
        DELETE FROM
            reports
        WHERE
            EXISTS (
                SELECT
                    1
                FROM
                    versions
                WHERE
                    version_id = reports.version_id AND
                    app_id = :app_id
            )
        ;
    ', [':app_id' => $appId]);
    query('
        DELETE FROM
            versions
        WHERE
            app_id = :app_id
        ;
    ', [':app_id' => $appId]);
    query('
        DELETE FROM
            apps
        WHERE
            app_id = :app_id
        ;
    ', [':app_id' => $appId]);
    cleanUpUsers();
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

function listVersionsForApp(int $appId, bool $showUnapproved, bool $moderatorView): void {
    if ($moderatorView) {
        $extraColumns = '
            ,
            approved,
            (SELECT external_username FROM users WHERE user_id = versions.approved_by) AS approved_by
        ';
    } else {
        $extraColumns = '';
    }

    $rows = query('
        SELECT
            versions.version_id AS version_id,
            name,
            report_summaries.rating AS best_rating,
            COALESCE(report_summaries.last_updated, versions.created) AS last_updated,
            users.external_username AS created_by_username,
            (approved IS NULL) AS unapproved,
            extra
            ' . $extraColumns . '
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
                WHERE
                    :show_unapproved OR approved IS NOT NULL
                GROUP BY
                    version_id
            )
            AS
                report_summaries
            ON
                versions.version_id = report_summaries.version_id
        LEFT JOIN
                users
            ON
                users.user_id = versions.created_by
        WHERE
            app_id = :app_id AND
            (:show_unapproved OR approved IS NOT NULL)
        ORDER BY
            name ASC, rating DESC
        ;
    ', [
        ':app_id' => $appId,
        ':show_unapproved' => $showUnapproved,
    ]);

    $columns = [
        'name' => [
            'name' => 'Version number',
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
        'created_by_username' => [
            'name' => 'First reported by',
            'external_username' => TRUE,
        ],
    ];
    if ($moderatorView) {
        $columns += [
            'approved' => [
                'name' => 'Approved',
                'datetime' => TRUE,
            ],
            'approved_by' => [
                'name' => 'Approved by',
                'external_username' => TRUE,
            ],
        ];
    }
    $columns += convertExtraFieldInfo(VERSION_EXTRA_FIELDS, TRUE);

    $buttons = [
        [
            'action' => '/reports/new',
            'method' => 'get',
            'label' => 'Submit report for this version',
            'param_name' => 'version',
            'param_column' => 'version_id',
        ]
    ];
    if ($moderatorView) {
        foreach (moderationActionButtons('/versions/', 'version_id', 'version') as $button) {
            $buttons[] = $button;
        }
    }

    $columns += [
        '_buttons' => [
            'name' => '',
            'buttons' => $buttons,
        ],
    ];

    printTable($columns, $rows);
}

function printVersionForm(): void {
    $fields = [
        'name' => [
            'name' => 'Version number',
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
// The new version has unapproved state, awaiting moderation.
function createVersion(array $version): ?int {
    $appId = $version['app_id'] ?? NULL;
    if (!is_int($appId)) {
        return NULL;
    }

    $createdBy = $version['created_by'] ?? NULL;
    if (!is_int($createdBy)) {
        return NULL;
    }

    $name = $version['name'] ?? NULL;
    if (!is_string($name) || $name === "" || !validateInputLength($name)) {
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
                created_by,
                name,
                extra
            )
        VALUES
            (
                :app_id,
                datetime(),
                :created_by,
                :name,
                :extra
            )
        RETURNING
            version_id
        ;
    ', [
        ':app_id' => $appId,
        ':created_by' => $createdBy,
        ':name' => $name,
        ':extra' => $extra,
    ]);
    return $rows[0]['version_id'];
}

// It is recommended to call this as part of a transaction.
function approveVersion(int $versionId, int $approvedByUserId): void {
    query('
        UPDATE
            versions
        SET
            approved = datetime(),
            approved_by = :approved_by_user_id
        WHERE
            version_id = :version_id AND
            approved IS NULL
        ;
    ', [
        ':version_id' => $versionId,
        ':approved_by_user_id' => $approvedByUserId,
    ]);
}

// This must be called as part of a transaction!
// It deletes not only the version, but also its connected reports!
// There is no audit log or undo!
function deleteVersion(int $versionId): void {
    query('
        DELETE FROM
            report_screenshots
        WHERE
            EXISTS (
                SELECT
                    1
                FROM
                    reports
                WHERE
                    reports.report_id = report_screenshots.report_id AND
                    version_id = :version_id
            )
        ;
    ', [':version_id' => $versionId]);
    query('
        DELETE FROM
            reports
        WHERE
            version_id = :version_id
        ;
    ', [':version_id' => $versionId]);
    query('
        DELETE FROM
            versions
        WHERE
            version_id = :version_id
        ;
    ', [':version_id' => $versionId]);
    cleanUpUsers();
}

// Returns NULL if the report isn't found.
function getReport(int $id): ?array {
    $rows = query('
        SELECT
            *,
            versions.app_id
        FROM
            reports
        LEFT JOIN
                versions
            ON
                versions.version_id = reports.version_id
        WHERE
            report_id = :report_id
        ;
    ', [':report_id' => $id]);

    if ($rows === []) {
        return NULL;
    } else {
        return $rows[0];
    }
}

// Returns NULL if the report isn't found, or has no screenshot.
// The result is a binary blob of JPEG data.
function getReportScreenshotImage(int $id): string {
    $rows = query('
        SELECT
            image
        FROM
            report_screenshots
        WHERE
            report_id = :report_id
        ;
    ', [':report_id' => $id]);

    if ($rows === []) {
        return NULL;
    } else {
        return $rows[0]['image'];
    }
}

function listReportsForApp(int $appId, bool $showUnapproved, bool $moderatorView): void {
    if ($moderatorView) {
        $extraColumns = '
            ,
            reports.approved AS approved,
            (SELECT external_username FROM users WHERE user_id = reports.approved_by) AS approved_by
        ';
    } else {
        $extraColumns = '';
    }

    $rows = query('
        SELECT
            reports.report_id AS report_id,
            versions.name AS version_name,
            reports.rating AS rating,
            reports.created AS created,
            users.external_username AS created_by_username,
            (reports.approved IS NULL) AS unapproved,
            reports.extra AS extra,
            EXISTS (SELECT 1 FROM report_screenshots WHERE report_screenshots.report_id = reports.report_id) AS has_screenshot
            ' . $extraColumns . '
        FROM
            reports
        LEFT JOIN
                versions
            ON
                reports.version_id = versions.version_id
        LEFT JOIN
                users
            ON
                users.user_id = reports.created_by
        WHERE
            app_id = :app_id AND
            (:show_unapproved OR reports.approved IS NOT NULL)
        ORDER BY
            reports.created DESC
        ;
    ', [
        ':app_id' => $appId,
        ':show_unapproved' => $showUnapproved,
    ]);

    $columns = [
        'version_name' => [
            'name' => 'Version number',
        ],
    ];
    $columns += convertExtraFieldInfo(REPORT_EXTRA_FIELDS, FALSE);
    $columns += [
        'rating' => [
            'name' => 'Rating',
            'rating' => TRUE,
        ],
        'created' => [
            'name' => 'Reported',
            'datetime' => TRUE,
        ],
        'created_by_username' => [
            'name' => 'Reported by',
            'external_username' => TRUE,
        ],
    ];
    if ($moderatorView) {
        $columns += [
            'approved' => [
                'name' => 'Approved',
                'datetime' => TRUE,
            ],
            'approved_by' => [
                'name' => 'Approved by',
                'external_username' => TRUE,
            ],
        ];
    }

    $columns += convertExtraFieldInfo(REPORT_EXTRA_FIELDS, TRUE);
    $columns += [
        'has_screenshot' => [
            'name' => 'Screenshot',
            'link' => ['#report-screenshot-', 'report_id'],
            'link_label' => 'View',
            'link_if' => 'has_screenshot',
        ],
    ];

    if ($moderatorView) {
        $columns += [
            '_buttons' => [
                'name' => '',
                'buttons' => moderationActionButtons('/reports/', 'report_id', 'report'),
            ],
        ];
    }

    printTable($columns, $rows, ['report-', 'report_id']);
}

function listReportScreenshotsForApp(int $appId, bool $showUnapproved): void {
    $rows = query('
        SELECT
            report_id
        FROM
            reports
        LEFT JOIN
                versions
            ON
                reports.version_id = versions.version_id
        WHERE
            app_id = :app_id AND
            (:show_unapproved OR reports.approved IS NOT NULL) AND
            EXISTS (SELECT 1 FROM report_screenshots WHERE report_screenshots.report_id = reports.report_id)
        ORDER BY
            reports.created DESC
        ;
    ', [
        ':app_id' => $appId,
        ':show_unapproved' => $showUnapproved,
    ]);

    foreach ($rows as $row) {
        $reportId = (string)$row['report_id'];
        echo '<figure id="', htmlspecialchars('report-screenshot-' . $reportId), '">';
        echo '<img src="', htmlspecialchars('/reports/' . $reportId . '/screenshot'), '" alt="Screenshot">';
        echo '<figcaption><a href="#report-', htmlspecialchars($reportId), '">Go to report</a></figcaption>';
        echo '</figure>';
    }
}

function printReportForm(): void {
    $fields = convertExtraFieldInfo(REPORT_EXTRA_FIELDS, FALSE);
    $fields += [
        'rating' => [
            'name' => 'Rating',
            'rating' => TRUE,
            'required' => TRUE,
        ],
    ];
    $fields += convertExtraFieldInfo(REPORT_EXTRA_FIELDS, TRUE);

    if (REPORT_SCREENSHOTS_ALLOWED) {
        $fields += [
            'screenshot' => [
                'name' => 'Screenshot',
                'image_upload' => TRUE,
            ],
        ];
    }

    printRecordForm($fields, 'report');
}

// It is recommended to call this as part of a transaction.
// The result is a the ID of the new report, or NULL if the input is invalid
// in some way.
// The new report has unapproved state, awaiting moderation.
function createReport(array $report): ?int {
    $createdBy = $report['created_by'] ?? NULL;
    if (!is_int($createdBy)) {
        return NULL;
    }

    $rating = $report['rating'] ?? NULL;
    if (!is_int($rating) || $rating < 1 || $rating > 5) {
        return NULL;
    }

    $versionId = $report['version_id'] ?? NULL;
    if (!is_int($versionId)) {
        return NULL;
    }

    $screenshot = $report['screenshot'] ?? NULL;
    if ($screenshot === '') {
        $screenshot = NULL;
    // This should be a base64 data URI for a JPEG image, which will have been
    // compressed on the client by the code in script.js, which uses 80% JPEG
    // quality and limits the size to at most 640 × 640 pixels. Cursory testing
    // suggests the result is usually around 50KB and, rarely, as high as 96KB.
    // I'm not sure what the actual maximum is, but 50% more than the largest
    // size I've seen is probably a reasonable limit. The (8/6) is to compensate
    // for base64 encoding.
    } else if (!is_string($screenshot) ||
        strlen($screenshot) > (150 * 1000 * (8/6)) ||
        !str_starts_with($screenshot, "data:image/jpeg;base64,")) {
        return NULL;
    } else {
        $screenshot = substr($screenshot, strlen("data:image/jpeg;base64,"));
        $screenshot = base64_decode($screenshot, /* strict: */ TRUE);
        if ($screenshot === FALSE) {
            return NULL;
        }
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
                created_by,
                rating,
                extra
            )
        VALUES
            (
                :version_id,
                datetime(),
                :created_by,
                :rating,
                :extra
            )
        RETURNING
            report_id
        ;
    ', [
        ':version_id' => $versionId,
        ':created_by' => $createdBy,
        ':rating' => $rating,
        ':extra' => $extra,
    ]);
    $reportId = $rows[0]['report_id'];

    if ($screenshot !== NULL) {
        $rows = query('
            INSERT INTO
                report_screenshots(
                    report_id,
                    image
                )
            VALUES
                (
                    :report_id,
                    :image
                )
            ;
        ', [
            ':report_id' => $reportId,
            ':image' => $screenshot,
        ]);
    }

    return $reportId;
}

// It is recommended to call this as part of a transaction.
function approveReport(int $reportId, int $approvedByUserId): void {
    query('
        UPDATE
            reports
        SET
            approved = datetime(),
            approved_by = :approved_by_user_id
        WHERE
            report_id = :report_id AND
            approved IS NULL
        ;
    ', [
        ':report_id' => $reportId,
        ':approved_by_user_id' => $approvedByUserId,
    ]);
}

// This must be called as part of a transaction!
// There is no audit log or undo!
function deleteReport(int $reportId): void {
    query('
        DELETE FROM
            report_screenshots
        WHERE
            report_id = :report_id
        ;
    ', [':report_id' => $reportId]);
    query('
        DELETE FROM
            reports
        WHERE
            report_id = :report_id
        ;
    ', [':report_id' => $reportId]);
    cleanUpUsers();
}

// Register the (external user ID, external username) pair in the database, if
// it doesn't already exist, and return the internal user ID. Note that the
// external user ID and username must be prefixed with the service they're from.
//
// This should only be called as part of a transaction that adds some other
// record referencing the user ID, so that users' identities are not tracked
// unless they have chosen to do submit something, at which point they are
// warned of the tracking. (See templates/new_report.phpt.)
function createOrGetUserId(string $externalUserId, string $externalUsername): int {
    $rows = query('
        SELECT
            user_id
        FROM
            users
        WHERE
            external_user_id = :external_user_id
        ;
    ', [':external_user_id' => $externalUserId]);

    if ($rows !== []) {
        return $rows[0]['user_id'];
    }

    $rows = query('
        INSERT INTO
            users(
                external_user_id,
                external_username
            )
        VALUES
            (
                :external_user_id,
                :external_username
            )
        RETURNING
            user_id
        ;
    ', [
        ':external_user_id' => $externalUserId,
        ':external_username' => $externalUsername
    ]);
    return $rows[0]['user_id'];
}

// If there is an external username associated with this external user ID in the
// database, update the username. Otherwise, do nothing. Note that the external
// user ID and username must be prefixed with the service they're from.
//
// This should be done when the user logs in, and they need to be informed of
// this consequence before logging in (see templates/new_report.phpt).
// This begins and ends a transaction!
function updateUsernameForUser(string $externalUserId, string $externalUsername): void {
    beginTransaction();
    query('
        UPDATE
            users
        SET
            external_username = :external_username
        WHERE
            external_user_id = :external_user_id AND
            external_username <> :external_username
        ;
    ', [
        ':external_user_id' => $externalUserId,
        ':external_username' => $externalUsername
    ]);
    commitTransaction();
}

// This must be called as part of a transaction!
// Helper function for deleteApp(), deleteVersion() and deleteReport():
// Remove users from the database if they're no longer referenced by anything.
// This is in line with the principles of createOrGetUserId().
function cleanUpUsers(): void {
    query('
        DELETE FROM
            users
        WHERE
                (NOT EXISTS(
                    SELECT
                        1
                    FROM
                        apps
                    WHERE
                        created_by = users.user_id OR
                        approved_by = users.user_id
                ))
            AND
                (NOT EXISTS(
                    SELECT
                        1
                    FROM
                        versions
                    WHERE
                        created_by = users.user_id OR
                        approved_by = users.user_id
                ))
            AND
                (NOT EXISTS(
                    SELECT
                        1
                    FROM
                        reports
                    WHERE
                        created_by = users.user_id OR
                        approved_by = users.user_id
                ))
        ;
    ');
}
