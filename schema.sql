CREATE TABLE IF NOT EXISTS apps (
    app_id      INTEGER PRIMARY KEY AUTOINCREMENT,
    created     DATETIME NOT NULL,
    created_by  INTEGER NOT NULL,
    approved    DATETIME,
    approved_by INTEGER,
    name        TEXT NOT NULL,
    extra       TEXT NOT NULL,      -- JSON
    FOREIGN KEY(created_by) REFERENCES users(user_id),
    FOREIGN KEY(approved_by) REFERENCES users(user_id)
);

CREATE TABLE IF NOT EXISTS versions (
    version_id  INTEGER PRIMARY KEY AUTOINCREMENT,
    app_id      INTEGER NOT NULL,
    created     DATETIME NOT NULL,
    created_by  INTEGER NOT NULL,
    approved    DATETIME,
    approved_by INTEGER,
    name        TEXT NOT NULL,
    extra       TEXT NOT NULL,      -- JSON
    FOREIGN KEY(app_id) REFERENCES apps(app_id),
    FOREIGN KEY(created_by) REFERENCES users(user_id),
    FOREIGN KEY(approved_by) REFERENCES users(user_id)
);

CREATE TABLE IF NOT EXISTS reports (
    report_id   INTEGER PRIMARY KEY AUTOINCREMENT,
    version_id  INTEGER NOT NULL,
    created     DATETIME NOT NULL,
    created_by  INTEGER NOT NULL,
    approved    DATETIME,
    approved_by INTEGER,
    rating      INTEGER NOT NULL,   -- from 1 to 5
    extra       TEXT NOT NULL,      -- JSON
    FOREIGN KEY(version_id) REFERENCES versions(version_id),
    FOREIGN KEY(created_by) REFERENCES users(user_id),
    FOREIGN KEY(approved_by) REFERENCES users(user_id)
);

CREATE TABLE IF NOT EXISTS users (
    user_id             INTEGER PRIMARY KEY AUTOINCREMENT,
    external_user_id    STRING UNIQUE NOT NULL, -- "service_name:xxxxxx"
    external_username   STRING NOT NULL         -- "service_name:xxxxxx"
);
