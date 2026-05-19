<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;

class EmailController extends Controller
{
    public function index(Request $request, array $params = []): void
    {
        $htmlFile = __DIR__ . '/../../public/email.html';
        if (!file_exists($htmlFile)) {
            http_response_code(404);
            die('Email marketing interface not found.');
        }
        header('Content-Type: text/html; charset=UTF-8');
        readfile($htmlFile);
        exit;
    }

    public function campaign(Request $request, array $params = []): void
    {
        // Serve the same SPA — the JS router handles the campaign view
        $this->index($request, $params);
    }
}
