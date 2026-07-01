-- The original locations table (003_create_locations.sql) made "slug" globally
-- unique, before brand_id existed. Once locations became scoped to a brand
-- (006_alter_locations_brand.sql), that global constraint was never fixed to
-- match — so two different brands still cannot both have a location named
-- e.g. "Aveiro" or "Porto", even though the app logic (Location::slugExistsForBrand)
-- already assumes uniqueness is scoped per brand. This caused unhandled
-- unique-violation errors (500) when creating/importing locations that reuse
-- a common name across brands.

ALTER TABLE locations DROP CONSTRAINT IF EXISTS locations_slug_key;

ALTER TABLE locations
    ADD CONSTRAINT locations_brand_id_slug_key UNIQUE (brand_id, slug);
