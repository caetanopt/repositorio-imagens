-- Passwordless login: single-use, short-lived tokens sent by email.
CREATE TABLE IF NOT EXISTS login_tokens (
    id         SERIAL PRIMARY KEY,
    user_id    INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    token_hash VARCHAR(64) NOT NULL UNIQUE,
    remember   BOOLEAN NOT NULL DEFAULT FALSE,
    expires_at TIMESTAMP NOT NULL,
    used_at    TIMESTAMP NULL,
    ip         VARCHAR(64) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_login_tokens_user ON login_tokens (user_id);
CREATE INDEX IF NOT EXISTS idx_login_tokens_expires ON login_tokens (expires_at);
