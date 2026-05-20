<?php

declare(strict_types=1);

namespace App\Models;

class Brand extends Model
{
    protected string $table = 'brands';

    public function findBySlug(string $slug): ?array
    {
        return $this->findBy('slug', $slug);
    }

    public function slugExists(string $slug, ?int $excludeId = null): bool
    {
        $sql    = 'SELECT COUNT(*) AS cnt FROM "brands" WHERE "slug" = ?';
        $params = [$slug];
        if ($excludeId !== null) {
            $sql    .= ' AND "id" != ?';
            $params[] = $excludeId;
        }
        $row = $this->db()->query($sql, $params)->fetch();
        return (int) ($row['cnt'] ?? 0) > 0;
    }
}
