<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\LoginToken;
use App\Models\User;

class AuthService
{
    private const PERMISSIONS = [
        'upload'             => ['admin', 'editor'],
        'download'           => ['admin', 'editor', 'viewer'],
        'delete_any'         => ['admin', 'editor'],
        'delete_own'         => ['admin', 'editor'],
        'manage_users'       => ['admin'],
        'manage_brands'      => ['admin'],
        'view_images'        => ['admin', 'editor', 'viewer'],
        'convert'            => ['admin', 'editor'],
        'download_original'  => ['admin', 'editor'],
        'restore_images'     => ['admin'],
        'hard_delete_images' => ['admin'],
        'view_admin'         => ['admin'],
        'view_audit'         => ['admin', 'editor'],
    ];

    private const TOKEN_TTL_MINUTES       = 15;
    private const MAX_REQUESTS_PER_WINDOW = 3;
    private const REQUEST_WINDOW_MINUTES  = 15;

    private User $userModel;
    private LoginToken $tokenModel;

    public function __construct()
    {
        $this->userModel  = new User();
        $this->tokenModel = new LoginToken();
    }

    /**
     * Requests a login link for the given email, if it belongs to an active,
     * unlocked user. Always returns silently to the caller — whether the
     * email actually matched an account is never revealed.
     */
    public function requestLoginLink(string $email, bool $remember, string $ip): void
    {
        $user = $this->userModel->findByEmail($email);
        if (!$user || !$user['active'] || $this->userModel->isLocked($user)) {
            return;
        }

        if ($this->tokenModel->countRecentForUser((int) $user['id'], self::REQUEST_WINDOW_MINUTES) >= self::MAX_REQUESTS_PER_WINDOW) {
            return;
        }

        $token     = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        $this->tokenModel->createForUser((int) $user['id'], $tokenHash, self::TOKEN_TTL_MINUTES, $remember, $ip);

        $link = rtrim(env('APP_URL', ''), '/') . '/login/verificar?token=' . $token;

        $appName = env('APP_NAME', 'Repositório Digital');
        $subject = 'O seu link de acesso — ' . $appName;

        (new MailerService())->send(
            $user['email'],
            $subject,
            $this->buildLoginLinkEmailHtml($user['name'], $link),
            $this->buildLoginLinkEmailText($user['name'], $link)
        );
    }

    private function buildLoginLinkEmailHtml(string $name, string $link): string
    {
        $logoUrl = rtrim(env('APP_URL', ''), '/') . '/assets/img/caetano-logo-email.png';

        return <<<HTML
<!DOCTYPE html>
<html lang="pt">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head>
<body style="margin:0; padding:0; background:#EEF6FA; font-family:Arial, Helvetica, sans-serif;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#EEF6FA; padding:32px 16px;">
<tr><td align="center">
<table role="presentation" width="480" cellpadding="0" cellspacing="0" style="max-width:480px; background:#ffffff; border-radius:12px; overflow:hidden;">
<tr><td align="center" style="padding:32px 32px 8px;">
<img src="{$this->esc($logoUrl)}" width="160" alt="Caetano" style="display:block; max-width:160px; height:auto;">
</td></tr>
<tr><td style="padding:16px 32px 8px; color:#1e293b; font-size:15px; line-height:1.6;">
<p style="margin:0 0 16px;">Olá {$this->esc($name)},</p>
<p style="margin:0 0 16px;">Recebemos um pedido de acesso ao Repositório Digital. Clique no botão abaixo para entrar (válido por 15 minutos, uso único):</p>
</td></tr>
<tr><td align="center" style="padding:8px 32px 24px;">
<table role="presentation" cellpadding="0" cellspacing="0">
<tr><td align="center" bgcolor="#002E5D" style="border-radius:8px;">
<a href="{$this->esc($link)}" target="_blank" style="display:inline-block; padding:14px 32px; font-size:15px; font-weight:bold; color:#ffffff; text-decoration:none; border-radius:8px;">Aceder à Plataforma</a>
</td></tr>
</table>
</td></tr>
<tr><td style="padding:0 32px 32px; color:#64748b; font-size:13px; line-height:1.6;">
<p style="margin:0;">Se não pediu este acesso, pode ignorar este email.</p>
</td></tr>
</table>
<table role="presentation" width="480" cellpadding="0" cellspacing="0" style="max-width:480px;">
<tr><td align="center" style="padding:20px 16px 0; color:#94a3b8; font-size:12px;">
&copy; {$this->currentYear()} Caetano Automotive Portugal S.A.
</td></tr>
</table>
</td></tr>
</table>
</body>
</html>
HTML;
    }

    private function buildLoginLinkEmailText(string $name, string $link): string
    {
        return "Olá {$name},\n\n"
            . "Recebemos um pedido de acesso ao Repositório Digital. Use o link abaixo para entrar (válido por " . self::TOKEN_TTL_MINUTES . " minutos, uso único):\n\n"
            . "{$link}\n\n"
            . "Se não pediu este acesso, pode ignorar este email.";
    }

    private function esc(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function currentYear(): string
    {
        return date('Y');
    }

    /**
     * Verifies a login token and, if valid, logs the user in.
     */
    public function verifyLoginToken(string $token): ?array
    {
        $tokenHash = hash('sha256', $token);
        $row = $this->tokenModel->findValidByHash($tokenHash);
        if (!$row) {
            return null;
        }

        $user = $this->userModel->find((int) $row['user_id']);
        if (!$user || !$user['active']) {
            return null;
        }

        $this->tokenModel->markUsed((int) $row['id']);
        $this->userModel->resetLoginAttempts((int) $user['id']);

        // Regenerate session ID to prevent fixation
        session_regenerate_id(true);

        $_SESSION['user'] = [
            'id'         => (int) $user['id'],
            'name'       => $user['name'],
            'email'      => $user['email'],
            'role'       => $user['role'],
            'photo_path' => $user['photo_path'] ?? null,
        ];

        if (!empty($row['remember'])) {
            $rememberToken = bin2hex(random_bytes(32));
            $this->userModel->setRememberToken((int) $user['id'], $rememberToken);
            $days = (int) env('REMEMBER_ME_DAYS', 30);
            setcookie(
                'remember_token',
                $rememberToken,
                time() + ($days * 86400),
                '/',
                '',
                true,  // secure
                true   // httpOnly
            );
        }

        return $user;
    }

    public function logout(): void
    {
        $user = $this->user();
        if ($user) {
            $this->userModel->clearRememberToken($user['id']);
        }

        // Destroy session
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        session_destroy();

        // Clear remember cookie
        setcookie('remember_token', '', time() - 3600, '/', '', true, true);
    }

    public function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public function check(): bool
    {
        return !empty($_SESSION['user']);
    }

    public function can(string $action): bool
    {
        $user = $this->user();
        if (!$user) {
            return false;
        }
        $allowedRoles = self::PERMISSIONS[$action] ?? [];
        return in_array($user['role'], $allowedRoles, true);
    }

    public function isAdmin(): bool
    {
        $user = $this->user();
        return $user !== null && $user['role'] === 'admin';
    }

    public function generateCsrf(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public function verifyCsrf(string $token): bool
    {
        $session = $_SESSION['csrf_token'] ?? '';
        return !empty($session) && hash_equals($session, $token);
    }

    /**
     * Check if current user is locked (for display purposes)
     */
    public function isLocked(): bool
    {
        $user = $this->user();
        if (!$user) {
            return false;
        }
        $dbUser = $this->userModel->find($user['id']);
        return $dbUser ? $this->userModel->isLocked($dbUser) : false;
    }
}
