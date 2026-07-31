<?php

declare(strict_types=1);

namespace App\Models;

class LoginToken extends Model
{
    protected string $table = 'login_tokens';

    public function createForUser(int $userId, string $tokenHash, int $ttlMinutes, bool $remember, string $ip): int
    {
        if ($this->isPgsql()) {
            $sql = sprintf(
                "INSERT INTO \"login_tokens\" (\"user_id\", \"token_hash\", \"remember\", \"expires_at\", \"ip\")
                 VALUES (?, ?, ?, NOW() + INTERVAL '%d minutes', ?) RETURNING id",
                $ttlMinutes
            );
            $row = $this->db()->query($sql, [$userId, $tokenHash, $remember ? 1 : 0, $ip])->fetch();
            return (int) ($row['id'] ?? 0);
        }

        $sql = 'INSERT INTO "login_tokens" ("user_id", "token_hash", "remember", "expires_at", "ip")
                VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL ? MINUTE), ?)';
        $this->db()->query($sql, [$userId, $tokenHash, $remember ? 1 : 0, $ttlMinutes, $ip]);
        return $this->db()->lastInsertId();
    }

    public function findValidByHash(string $tokenHash): ?array
    {
        $row = $this->db()->query(
            'SELECT * FROM "login_tokens"
             WHERE "token_hash" = ? AND "used_at" IS NULL AND "expires_at" > NOW()
             LIMIT 1',
            [$tokenHash]
        )->fetch();
        return $row ?: null;
    }

    public function markUsed(int $id): void
    {
        $this->db()->query(
            'UPDATE "login_tokens" SET "used_at" = NOW() WHERE "id" = ?',
            [$id]
        );
    }

    /**
     * Number of tokens requested for a user in the last N minutes — used to
     * throttle repeated link requests without revealing account existence.
     */
    public function countRecentForUser(int $userId, int $minutes): int
    {
        if ($this->isPgsql()) {
            $sql = sprintf(
                "SELECT COUNT(*) AS cnt FROM \"login_tokens\"
                 WHERE \"user_id\" = ? AND \"created_at\" > NOW() - INTERVAL '%d minutes'",
                $minutes
            );
            $row = $this->db()->query($sql, [$userId])->fetch();
            return (int) ($row['cnt'] ?? 0);
        }

        $row = $this->db()->query(
            'SELECT COUNT(*) AS cnt FROM "login_tokens"
             WHERE "user_id" = ? AND "created_at" > DATE_SUB(NOW(), INTERVAL ? MINUTE)',
            [$userId, $minutes]
        )->fetch();
        return (int) ($row['cnt'] ?? 0);
    }
}
