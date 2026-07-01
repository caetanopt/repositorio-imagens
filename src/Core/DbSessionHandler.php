<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Stores PHP sessions in the database instead of the local filesystem.
 * Required on serverless platforms (Vercel) where each request can be
 * served by a different instance with its own ephemeral disk — file-based
 * sessions written by one instance are invisible to another, which (with
 * session.use_strict_mode enabled) causes the session to silently reset
 * and breaks things like CSRF validation right after the page loads.
 */
class DbSessionHandler implements \SessionHandlerInterface
{
    private int $lifetime;

    public function __construct(int $lifetime)
    {
        $this->lifetime = $lifetime;
    }

    public function open(string $path, string $name): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function read(string $id): string|false
    {
        $db  = Database::getInstance();
        $sql = $db->getDriver() === 'mysql'
            ? 'SELECT data FROM sessions WHERE id = ? AND last_activity > DATE_SUB(NOW(), INTERVAL ? SECOND)'
            : "SELECT data FROM sessions WHERE id = ? AND last_activity > NOW() - (? || ' seconds')::interval";

        $row = $db->query($sql, [$id, $this->lifetime])->fetch();

        return $row ? $row['data'] : '';
    }

    public function write(string $id, string $data): bool
    {
        $db  = Database::getInstance();
        $sql = $db->getDriver() === 'mysql'
            ? 'INSERT INTO sessions (id, data, last_activity) VALUES (?, ?, NOW())
               ON DUPLICATE KEY UPDATE data = VALUES(data), last_activity = NOW()'
            : 'INSERT INTO sessions (id, data, last_activity) VALUES (?, ?, NOW())
               ON CONFLICT (id) DO UPDATE SET data = EXCLUDED.data, last_activity = NOW()';

        $db->query($sql, [$id, $data]);

        return true;
    }

    public function destroy(string $id): bool
    {
        Database::getInstance()->query('DELETE FROM sessions WHERE id = ?', [$id]);
        return true;
    }

    public function gc(int $max_lifetime): int|false
    {
        $db  = Database::getInstance();
        $sql = $db->getDriver() === 'mysql'
            ? 'DELETE FROM sessions WHERE last_activity < DATE_SUB(NOW(), INTERVAL ? SECOND)'
            : "DELETE FROM sessions WHERE last_activity < NOW() - (? || ' seconds')::interval";

        $stmt = $db->query($sql, [$max_lifetime]);

        return $stmt->rowCount();
    }
}
