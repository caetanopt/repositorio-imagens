-- Índices recomendados para performance
-- Executar no Supabase Dashboard > SQL Editor

-- Tabela images: queries por marca, localização e soft delete
CREATE INDEX IF NOT EXISTS idx_images_brand_location_deleted
    ON images (brand_id, location_id, deleted_at);

CREATE INDEX IF NOT EXISTS idx_images_brand_deleted
    ON images (brand_id, deleted_at);

CREATE INDEX IF NOT EXISTS idx_images_location_deleted
    ON images (location_id, deleted_at);

CREATE INDEX IF NOT EXISTS idx_images_uploaded_by
    ON images (uploaded_by);

-- Tabela users: login por email
CREATE UNIQUE INDEX IF NOT EXISTS idx_users_email
    ON users (email);

CREATE INDEX IF NOT EXISTS idx_users_remember_token
    ON users (remember_token) WHERE remember_token IS NOT NULL;

-- Tabela locations: lookup por marca e slug
CREATE INDEX IF NOT EXISTS idx_locations_brand_slug
    ON locations (brand_id, slug);

-- Tabela brands: lookup por slug
CREATE UNIQUE INDEX IF NOT EXISTS idx_brands_slug
    ON brands (slug);

-- Tabela audit_logs: consultas de auditoria por utilizador e data
CREATE INDEX IF NOT EXISTS idx_audit_logs_user_created
    ON audit_logs (user_id, created_at DESC);

CREATE INDEX IF NOT EXISTS idx_audit_logs_entity
    ON audit_logs (entity_type, entity_id);
