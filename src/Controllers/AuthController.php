<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Models\AuditLog;

class AuthController extends Controller
{
    public function showLogin(Request $request, array $params = []): void
    {
        // Already logged in → redirect to gallery
        if ($this->auth->check()) {
            $this->redirect('/');
        }

        $this->render('auth/login', [
            'error'      => $this->getFlash('error'),
            'success'    => $this->getFlash('success'),
            'csrf_token' => $this->csrfToken(),
        ]);
    }

    /**
     * Requests a login link for the given email. Always shows the same
     * generic message, whether or not the email matches an active account —
     * this avoids leaking which emails are registered.
     */
    public function requestLink(Request $request, array $params = []): void
    {
        $this->requireCsrf();

        $email    = trim($request->post('email', ''));
        $remember = (bool) $request->post('remember_me', false);

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            if ($this->wantsJson()) {
                $this->json(['success' => false, 'error' => 'Introduza um email válido.'], 422);
            }
            $this->setFlash('error', 'Introduza um email válido.');
            $this->setOld(['email' => $email]);
            $this->redirect('/login');
        }

        $this->auth->requestLoginLink($email, $remember, $request->ip());

        $auditLog = new AuditLog();
        $auditLog->log(null, 'login_link_requested', 'auth', null, [
            'email' => $email,
            'ip'    => $request->ip(),
        ]);

        if ($this->wantsJson()) {
            $this->json(['success' => true, 'email' => $email]);
        }

        $this->setFlash('success', 'Se o email pertencer a uma conta activa, foi enviado um link de acesso. Verifique a sua caixa de entrada.');
        $this->redirect('/login');
    }

    public function verify(Request $request, array $params = []): void
    {
        $token = trim((string) $request->get('token', ''));

        if (empty($token)) {
            $this->setFlash('error', 'Link de acesso inválido.');
            $this->redirect('/login');
        }

        $user = $this->auth->verifyLoginToken($token);

        if (!$user) {
            $auditLog = new AuditLog();
            $auditLog->log(null, 'login_link_invalid', 'auth', null, [
                'ip' => $request->ip(),
            ]);

            $this->setFlash('error', 'O link de acesso é inválido ou já expirou. Peça um novo link.');
            $this->redirect('/login');
        }

        $auditLog = new AuditLog();
        $auditLog->log((int) $user['id'], 'login', 'auth', (int) $user['id'], [
            'ip' => $request->ip(),
        ]);

        $this->redirect('/');
    }

    public function doLogout(Request $request, array $params = []): void
    {
        $user = $this->auth->user();

        if ($user) {
            $auditLog = new AuditLog();
            $auditLog->log($user['id'], 'logout', 'auth', $user['id'], []);
        }

        $this->auth->logout();
        $this->redirect('/login');
    }
}
