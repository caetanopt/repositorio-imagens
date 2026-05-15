<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

abstract class Model
{
    protected string $table;
    protected string $primaryKey = 'id';
    protected bool $softDeletes  = false;

    protected function db(): Database
    {
        return Database::getInstance();
    }

    public function find(int $id): ?array
    {
        $sql = "SELECT * FROM `{$this->table}` WHERE `{$this->primaryKey}` = ?";
        if ($this->softDeletes) {
            $sql .= " AND deleted_at IS NULL";
        }
        $stmt = $this->db()->query($sql, [$id]);
        $row  = $stmt->fetch();
        return $row ?: null;
    }

    public function findAll(
        array $conditions = [],
        string $order     = '',
        ?int $limit       = null,
        int $offset       = 0
    ): array {
        [$where, $params] = $this->buildWhere($conditions);

        if ($this->softDeletes && !isset($conditions['deleted_at'])) {
            $where[] = "`{$this->table}`.`deleted_at` IS NULL";
        }

        $sql = "SELECT * FROM `{$this->table}`";
        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        if (!empty($order)) {
            $sql .= " ORDER BY {$order}";
        }
        if ($limit !== null) {
            $sql .= " LIMIT {$limit} OFFSET {$offset}";
        }

        $stmt = $this->db()->query($sql, $params);
        return $stmt->fetchAll();
    }

    public function findBy(string $field, mixed $value): ?array
    {
        $sql = "SELECT * FROM `{$this->table}` WHERE `{$field}` = ?";
        if ($this->softDeletes) {
            $sql .= " AND deleted_at IS NULL";
        }
        $sql .= ' LIMIT 1';
        $stmt = $this->db()->query($sql, [$value]);
        $row  = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $columns = implode('`, `', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $sql = "INSERT INTO `{$this->table}` (`{$columns}`) VALUES ({$placeholders})";
        $this->db()->query($sql, array_values($data));
        return $this->db()->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        if (empty($data)) {
            return false;
        }
        $sets = implode(' = ?, ', array_map(fn($k) => "`{$k}`", array_keys($data))) . ' = ?';
        $sql  = "UPDATE `{$this->table}` SET {$sets} WHERE `{$this->primaryKey}` = ?";
        $params = array_merge(array_values($data), [$id]);
        $stmt = $this->db()->query($sql, $params);
        return $stmt->rowCount() >= 0;
    }

    public function softDelete(int $id): bool
    {
        if (!$this->softDeletes) {
            return $this->hardDelete($id);
        }
        $sql  = "UPDATE `{$this->table}` SET `deleted_at` = NOW() WHERE `{$this->primaryKey}` = ?";
        $stmt = $this->db()->query($sql, [$id]);
        return $stmt->rowCount() > 0;
    }

    public function hardDelete(int $id): bool
    {
        $sql  = "DELETE FROM `{$this->table}` WHERE `{$this->primaryKey}` = ?";
        $stmt = $this->db()->query($sql, [$id]);
        return $stmt->rowCount() > 0;
    }

    public function restore(int $id): bool
    {
        if (!$this->softDeletes) {
            return false;
        }
        $sql  = "UPDATE `{$this->table}` SET `deleted_at` = NULL WHERE `{$this->primaryKey}` = ?";
        $stmt = $this->db()->query($sql, [$id]);
        return $stmt->rowCount() > 0;
    }

    public function count(array $conditions = []): int
    {
        [$where, $params] = $this->buildWhere($conditions);

        if ($this->softDeletes && !isset($conditions['deleted_at'])) {
            $where[] = "`{$this->table}`.`deleted_at` IS NULL";
        }

        $sql = "SELECT COUNT(*) as cnt FROM `{$this->table}`";
        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $stmt = $this->db()->query($sql, $params);
        $row  = $stmt->fetch();
        return (int) ($row['cnt'] ?? 0);
    }

    private function buildWhere(array $conditions): array
    {
        $where  = [];
        $params = [];
        foreach ($conditions as $field => $value) {
            if ($value === null) {
                $where[] = "`{$field}` IS NULL";
            } else {
                $where[] = "`{$field}` = ?";
                $params[] = $value;
            }
        }
        return [$where, $params];
    }
}
