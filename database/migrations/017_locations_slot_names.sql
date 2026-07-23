-- Optional per-location override of the photo slot names/count. NULL means
-- "use the brand's default slots". A JSON array of 1-4 names, e.g.
-- '["Fachada", "Showroom"]', reduces/renames the slots for that location only.
ALTER TABLE locations ADD COLUMN IF NOT EXISTS slot_names JSONB NULL;
