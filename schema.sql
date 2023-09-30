CREATE TABLE IF NOT EXISTS apps (
    app_id      INTEGER PRIMARY KEY AUTOINCREMENT,
    created     DATETIME NOT NULL,
    name        TEXT NOT NULL,
    extra       TEXT NOT NULL       -- JSON
);

CREATE TABLE IF NOT EXISTS versions (
    version_id  INTEGER PRIMARY KEY AUTOINCREMENT,
    app_id      INTEGER NOT NULL,
    created     DATETIME NOT NULL,
    name        TEXT NOT NULL,
    extra       TEXT NOT NULL,      -- JSON
    FOREIGN KEY(app_id) REFERENCES apps(app_id)
);

CREATE TABLE IF NOT EXISTS reports (
    report_id   INTEGER PRIMARY KEY AUTOINCREMENT,
    version_id  INTEGER NOT NULL,
    created     DATETIME NOT NULL,
    rating      INTEGER NOT NULL,   -- from 1 to 5
    extra       TEXT NOT NULL,      -- JSON
    FOREIGN KEY(version_id) REFERENCES versions(version_id)
);
