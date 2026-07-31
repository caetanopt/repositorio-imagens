-- Allows uploading a brand logo via the admin UI (Supabase Storage), instead
-- of only relying on a static file committed under public/assets/img/brands/.
ALTER TABLE brands ADD COLUMN IF NOT EXISTS logo_path VARCHAR(255) NULL;
