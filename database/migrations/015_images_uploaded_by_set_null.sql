-- Allow deleting a user without being blocked by images they uploaded.
-- Mirrors the ON DELETE SET NULL already used on audit_log.user_id.
ALTER TABLE images DROP CONSTRAINT IF EXISTS images_uploaded_by_fkey;
ALTER TABLE images
    ADD CONSTRAINT images_uploaded_by_fkey
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL;
