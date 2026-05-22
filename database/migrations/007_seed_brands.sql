-- Adicionar marcas BYD, Nissan e Audi
-- Executar no Supabase SQL Editor

INSERT INTO brands (name, slug)
VALUES
    ('BYD',    'byd'),
    ('Nissan', 'nissan'),
    ('Audi',   'audi')
ON CONFLICT (slug) DO NOTHING;
