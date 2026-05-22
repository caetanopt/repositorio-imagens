-- Renomear Mercedes → Mercedes-Benz
UPDATE brands SET name = 'Mercedes-Benz', slug = 'mercedes-benz'
WHERE slug = 'mercedes' OR name ILIKE 'mercedes';

-- Eliminar Seat (apaga primeiro as imagens e localizações associadas, ou faz soft-delete)
-- ATENÇÃO: elimina em cascata se as FK tiverem ON DELETE CASCADE;
-- caso contrário eliminar manualmente primeiro as imagens e localizações.
DELETE FROM brands WHERE slug = 'seat' OR name ILIKE 'seat';

-- Acrescentar novas marcas
INSERT INTO brands (name, slug) VALUES
    ('Farizon',       'farizon'),
    ('Geely',         'geely'),
    ('Hyundai',       'hyundai'),
    ('MINI',          'mini'),
    ('Opel',          'opel'),
    ('Peugeot',       'peugeot'),
    ('Renault',       'renault'),
    ('Škoda',         'skoda'),
    ('Volkswagen',    'volkswagen'),
    ('VOYAH',         'voyah'),
    ('XPENG',         'xpeng'),
    ('Zeekr',         'zeekr'),
    ('Caetano Parts', 'caetano-parts')
ON CONFLICT (slug) DO NOTHING;
