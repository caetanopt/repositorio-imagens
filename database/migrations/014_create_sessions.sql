-- PHP sessions backed by the database instead of the local filesystem.
-- Vercel's serverless functions have ephemeral, per-instance disk — a
-- session written to /tmp on one instance is invisible to a later request
-- served by a different instance, which (combined with
-- session.use_strict_mode) silently starts a brand new empty session and
-- breaks CSRF validation on the very next request (e.g. right after
-- loading the login page). Storing sessions in Postgres makes them
-- consistent across all instances.

CREATE TABLE IF NOT EXISTS sessions (
    id            VARCHAR(128) PRIMARY KEY,
    data          TEXT         NOT NULL DEFAULT '',
    last_activity TIMESTAMP    NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_sessions_last_activity ON sessions (last_activity);
