<?php
declare(strict_types=1);
namespace App\Models;

class Location extends Model
{
    protected string $table = 'locations';

    public function findBySlug(string $slug): ?array
    {
        return $this->findBy('slug', $slug);
    }

    public function findByBrand(int $brandId): array
    {
        return $this->db()->query(
            'SELECT * FROM "locations" WHERE "brand_id" = ? ORDER BY "name" ASC',
            [$brandId]
        )->fetchAll();
    }

    public function slugExistsForBrand(string $slug, int $brandId, ?int $excludeId = null): bool
    {
        $sql    = 'SELECT COUNT(*) AS cnt FROM "locations" WHERE "slug" = ? AND "brand_id" = ?';
        $params = [$slug, $brandId];
        if ($excludeId !== null) {
            $sql    .= ' AND "id" != ?';
            $params[] = $excludeId;
        }
        $row = $this->db()->query($sql, $params)->fetch();
        return (int) ($row['cnt'] ?? 0) > 0;
    }
}
