<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class Image extends Model
{
    protected string $table  = 'images';
    protected bool $softDeletes = true;

    public function findWithRelations(int $id): ?array
    {
        $stmt = $this->db()->query(
            "SELECT i.*,
                    b.name AS brand_name, b.slug AS brand_slug,
                    l.name AS location_name, l.slug AS location_slug,
                    u.name AS uploader_name, u.email AS uploader_email
             FROM images i
             INNER JOIN brands    b ON b.id = i.brand_id
             INNER JOIN locations l ON l.id = i.location_id
             INNER JOIN users     u ON u.id = i.uploaded_by
             WHERE i.id = ?",
            [$id]
        );
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function searchGallery(array $filters = [], int $page = 1, int $perPage = 24): array
    {
        [$where, $params] = $this->buildGalleryWhere($filters);

        $order = match ($filters['sort'] ?? 'newest') {
            'oldest'   => 'i.created_at ASC',
            'name_asc' => 'i.original_filename ASC',
            'name_desc'=> 'i.original_filename DESC',
            'size_asc' => 'i.optimized_filesize ASC',
            'size_desc'=> 'i.optimized_filesize DESC',
            default    => 'i.created_at DESC',
        };

        $offset = ($page - 1) * $perPage;

        $sql = "SELECT i.*,
                       b.name AS brand_name, b.slug AS brand_slug,
                       l.name AS location_name, l.slug AS location_slug,
                       u.name AS uploader_name
                FROM images i
                INNER JOIN brands    b ON b.id = i.brand_id
                INNER JOIN locations l ON l.id = i.location_id
                INNER JOIN users     u ON u.id = i.uploaded_by";

        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= " ORDER BY {$order} LIMIT {$perPage} OFFSET {$offset}";

        $stmt = $this->db()->query($sql, $params);
        return $stmt->fetchAll();
    }

    public function countGallery(array $filters = []): int
    {
        [$where, $params] = $this->buildGalleryWhere($filters);

        $sql = "SELECT COUNT(*) as cnt
                FROM images i
                INNER JOIN brands    b ON b.id = i.brand_id
                INNER JOIN locations l ON l.id = i.location_id
                INNER JOIN users     u ON u.id = i.uploaded_by";

        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $stmt = $this->db()->query($sql, $params);
        $row  = $stmt->fetch();
        return (int) ($row['cnt'] ?? 0);
    }

    private function buildGalleryWhere(array $filters): array
    {
        $where  = [];
        $params = [];

        // Soft deletes
        $showDeleted = !empty($filters['show_deleted']);
        if (!$showDeleted) {
            $where[] = "i.deleted_at IS NULL";
        }

        // Brand filter — can be array or single value
        if (!empty($filters['brand_id'])) {
            $brandIds = (array) $filters['brand_id'];
            $brandIds = array_filter(array_map('intval', $brandIds));
            if (!empty($brandIds)) {
                $placeholders = implode(',', array_fill(0, count($brandIds), '?'));
                $where[]  = "i.brand_id IN ({$placeholders})";
                $params   = array_merge($params, $brandIds);
            }
        }

        // Location filter
        if (!empty($filters['location_id'])) {
            $locIds = (array) $filters['location_id'];
            $locIds = array_filter(array_map('intval', $locIds));
            if (!empty($locIds)) {
                $placeholders = implode(',', array_fill(0, count($locIds), '?'));
                $where[]  = "i.location_id IN ({$placeholders})";
                $params   = array_merge($params, $locIds);
            }
        }

        // Full-text search on filename
        if (!empty($filters['search'])) {
            $where[]  = "(i.original_filename LIKE ? OR b.name LIKE ? OR l.name LIKE ?)";
            $term     = '%' . $filters['search'] . '%';
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
        }

        return [$where, $params];
    }

    public function findDeleted(): array
    {
        $stmt = $this->db()->query(
            "SELECT i.*,
                    b.name AS brand_name,
                    l.name AS location_name,
                    u.name AS uploader_name
             FROM images i
             INNER JOIN brands    b ON b.id = i.brand_id
             INNER JOIN locations l ON l.id = i.location_id
             INNER JOIN users     u ON u.id = i.uploaded_by
             WHERE i.deleted_at IS NOT NULL
             ORDER BY i.deleted_at DESC"
        );
        return $stmt->fetchAll();
    }

    public function findByIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }
        $ids  = array_filter(array_map('intval', $ids));
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->db()->query(
            "SELECT i.*,
                    b.name AS brand_name, b.slug AS brand_slug,
                    l.name AS location_name
             FROM images i
             INNER JOIN brands    b ON b.id = i.brand_id
             INNER JOIN locations l ON l.id = i.location_id
             WHERE i.id IN ({$placeholders}) AND i.deleted_at IS NULL",
            $ids
        );
        return $stmt->fetchAll();
    }

    public function countByLocation(int $brandId, int $locationId): int
    {
        $row = $this->db()->query(
            'SELECT COUNT(*) AS cnt FROM "images" WHERE "brand_id" = ? AND "location_id" = ? AND "deleted_at" IS NULL',
            [$brandId, $locationId]
        )->fetch();
        return (int) ($row['cnt'] ?? 0);
    }

    public function findByLocation(int $brandId, int $locationId): array
    {
        return $this->db()->query(
            'SELECT i.*, u.name AS uploader_name
             FROM "images" i
             INNER JOIN "users" u ON u.id = i.uploaded_by
             WHERE i.brand_id = ? AND i.location_id = ? AND i.deleted_at IS NULL
             ORDER BY i.created_at ASC',
            [$brandId, $locationId]
        )->fetchAll();
    }

    public function countByBrand(int $brandId): int
    {
        $row = $this->db()->query(
            'SELECT COUNT(*) AS cnt FROM "images" WHERE "brand_id" = ? AND "deleted_at" IS NULL',
            [$brandId]
        )->fetch();
        return (int) ($row['cnt'] ?? 0);
    }
}
