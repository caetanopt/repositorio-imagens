-- Login is now passwordless (email magic link) — password_hash is no longer set for new users.
ALTER TABLE users ALTER COLUMN password_hash DROP NOT NULL;
