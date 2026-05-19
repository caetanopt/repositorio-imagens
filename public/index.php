<?php

declare(strict_types=1);

// Autoload
require_once __DIR__ . '/../vendor/autoload.php';

// Temporary server debug — remove after diagnosis
if (($_SERVER['REQUEST_URI'] ?? '') === '/debug-server') {
    header('Content-Type: application/json');
    echo json_encode([
        'REQUEST_URI'  => $_SERVER['REQUEST_URI']  ?? null,
        'PATH_INFO'    => $_SERVER['PATH_INFO']    ?? null,
        'SCRIPT_NAME'  => $_SERVER['SCRIPT_NAME']  ?? null,
        'PHP_SELF'     => $_SERVER['PHP_SELF']     ?? null,
        'QUERY_STRING' => $_SERVER['QUERY_STRING'] ?? null,
        'HTTP_HOST'    => $_SERVER['HTTP_HOST']    ?? null,
    ]);
    exit;
}

// Load .env if present (not available on Vercel — env vars set via dashboard)
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

// Error reporting based on env
if (env('APP_DEBUG', false)) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(0);
}

// Session configuration
ini_set('session.cookie_httponly', '1');
ini_set('session.use_strict_mode', '1');
$lifetime = (int) env('SESSION_LIFETIME', 7200);
ini_set('session.gc_maxlifetime', (string) $lifetime);
session_set_cookie_params($lifetime);

session_start();

// Remember-me auto-login
if (empty($_SESSION['user']) && !empty($_COOKIE['remember_token'])) {
    $userModel = new \App\Models\User();
    $user = $userModel->findByRememberToken($_COOKIE['remember_token']);
    if ($user && $user['active']) {
        $_SESSION['user'] = [
            'id'    => $user['id'],
            'name'  => $user['name'],
            'email' => $user['email'],
            'role'  => $user['role'],
        ];
    }
}

// ─── REST API block ──────────────────────────────────────────────────────────
// Handle /api/* before the HTML router so it always returns JSON.
$requestUri    = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$requestMethod = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

if (str_starts_with($requestUri, '/api/')) {
    header('Content-Type: application/json; charset=UTF-8');
    header('X-Content-Type-Options: nosniff');

    // Helper: send JSON and exit
    $apiJson = static function (mixed $data, int $status = 200): never {
        http_response_code($status);
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    };

    // Auth check — session already started above
    if (empty($_SESSION['user'])) {
        $apiJson(['error' => 'Não autenticado.', 'code' => 401], 401);
    }

    // Parse JSON body once
    $rawBody  = file_get_contents('php://input');
    $jsonBody = (array) (json_decode($rawBody ?: '{}', true) ?? []);

    // DB instance
    $db = \App\Core\Database::getInstance();

    // Strip /api/ prefix and split path segments
    $apiPath      = substr($requestUri, 4); // e.g. /lists, /campaigns/5/send
    $pathSegments = array_values(array_filter(explode('/', $apiPath)));
    // $pathSegments[0] = resource, [1] = id (optional), [2] = action (optional)

    $resource   = $pathSegments[0] ?? '';
    $resourceId = isset($pathSegments[1]) && is_numeric($pathSegments[1]) ? (int) $pathSegments[1] : null;
    $action     = $pathSegments[2] ?? null;

    try {
        // ── GET /api/brands ──────────────────────────────────────────────────
        if ($resource === 'brands' && $requestMethod === 'GET') {
            $brandId  = $_GET['id'] ?? null;

            if ($brandId !== null) {
                $brand = $db->query('SELECT id, name, slug FROM brands WHERE id = :id', [':id' => (int) $brandId])->fetch();
                if (!$brand) {
                    $apiJson(['error' => 'Marca não encontrada.', 'code' => 404], 404);
                }
                if (!empty($_GET['permissions'])) {
                    $brand['permissions'] = ['view_images', 'upload_images', 'delete_own', 'email_marketing'];
                }
                $apiJson($brand);
            }

            $brands = $db->query('SELECT id, name, slug FROM brands ORDER BY name ASC')->fetchAll();
            $apiJson(['data' => $brands, 'total' => count($brands)]);
        }

        // ── /api/lists ───────────────────────────────────────────────────────
        if ($resource === 'lists') {
            if ($requestMethod === 'GET') {
                $brandId = (int) ($_GET['brand_id'] ?? 0);
                $limit   = min((int) ($_GET['limit'] ?? 50), 200);
                if (!$brandId) {
                    $apiJson(['error' => 'brand_id obrigatório.', 'code' => 400], 400);
                }
                $rows = $db->query(
                    'SELECT el.*, COUNT(elc.contact_id) AS contact_count
                     FROM email_lists el
                     LEFT JOIN email_list_contacts elc ON elc.list_id = el.id
                     WHERE el.brand_id = :brand_id
                     GROUP BY el.id
                     ORDER BY el.created_at DESC
                     LIMIT :lim',
                    [':brand_id' => $brandId, ':lim' => $limit]
                )->fetchAll();
                $apiJson(['data' => $rows, 'total' => count($rows)]);
            }

            if ($requestMethod === 'POST') {
                $brandId     = (int) ($jsonBody['brand_id'] ?? 0);
                $name        = trim($jsonBody['name'] ?? '');
                $description = trim($jsonBody['description'] ?? '');
                if (!$brandId || $name === '') {
                    $apiJson(['error' => 'brand_id e name são obrigatórios.', 'code' => 400], 400);
                }
                $db->query(
                    'INSERT INTO email_lists (brand_id, name, description) VALUES (:brand_id, :name, :desc)',
                    [':brand_id' => $brandId, ':name' => $name, ':desc' => $description ?: null]
                );
                $newId = $db->lastInsertId('email_lists_id_seq');
                $row   = $db->query('SELECT * FROM email_lists WHERE id = :id', [':id' => $newId])->fetch();
                $apiJson($row, 201);
            }
        }

        // ── /api/contacts ────────────────────────────────────────────────────
        if ($resource === 'contacts') {
            if ($requestMethod === 'GET') {
                $brandId = (int) ($_GET['brand_id'] ?? 0);
                $limit   = min((int) ($_GET['limit'] ?? 50), 500);
                $page    = max(1, (int) ($_GET['page'] ?? 1));
                $offset  = ($page - 1) * $limit;
                $listId  = (int) ($_GET['list_id'] ?? 0);
                $search  = trim($_GET['search'] ?? '');

                if (!$brandId) {
                    $apiJson(['error' => 'brand_id obrigatório.', 'code' => 400], 400);
                }

                $where  = ['ec.brand_id = :brand_id'];
                $params = [':brand_id' => $brandId];

                if ($listId) {
                    $where[]            = 'EXISTS (SELECT 1 FROM email_list_contacts elc WHERE elc.contact_id = ec.id AND elc.list_id = :list_id)';
                    $params[':list_id'] = $listId;
                }
                if ($search !== '') {
                    $where[]           = '(ec.email ILIKE :search OR ec.name ILIKE :search)';
                    $params[':search'] = '%' . $search . '%';
                }

                $whereClause = implode(' AND ', $where);

                $total = (int) $db->query(
                    "SELECT COUNT(*) FROM email_contacts ec WHERE {$whereClause}",
                    $params
                )->fetchColumn();

                $rows = $db->query(
                    "SELECT ec.* FROM email_contacts ec WHERE {$whereClause} ORDER BY ec.created_at DESC LIMIT :lim OFFSET :off",
                    array_merge($params, [':lim' => $limit, ':off' => $offset])
                )->fetchAll();

                $apiJson([
                    'data'        => $rows,
                    'total'       => $total,
                    'page'        => $page,
                    'total_pages' => (int) ceil($total / max(1, $limit)),
                ]);
            }

            if ($requestMethod === 'POST') {
                $brandId  = (int) ($jsonBody['brand_id'] ?? 0);
                $contacts = $jsonBody['contacts'] ?? null;

                // Support single contact OR array
                if ($contacts === null) {
                    $contacts = [['email' => $jsonBody['email'] ?? '', 'name' => $jsonBody['name'] ?? '']];
                }

                if (!$brandId) {
                    $apiJson(['error' => 'brand_id obrigatório.', 'code' => 400], 400);
                }

                $listId  = (int) ($jsonBody['list_id'] ?? 0);
                $created = 0;
                $skipped = 0;

                foreach ($contacts as $c) {
                    $email = strtolower(trim($c['email'] ?? ''));
                    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $skipped++;
                        continue;
                    }
                    $name         = trim($c['name'] ?? '');
                    $customFields = json_encode($c['custom_fields'] ?? []);

                    // Upsert
                    $existing = $db->query(
                        'SELECT id FROM email_contacts WHERE email = :email AND brand_id = :brand_id',
                        [':email' => $email, ':brand_id' => $brandId]
                    )->fetch();

                    if ($existing) {
                        $contactId = (int) $existing['id'];
                        $db->query(
                            'UPDATE email_contacts SET name = :name, custom_fields = :cf WHERE id = :id',
                            [':name' => $name, ':cf' => $customFields, ':id' => $contactId]
                        );
                    } else {
                        $db->query(
                            'INSERT INTO email_contacts (email, name, brand_id, custom_fields) VALUES (:email, :name, :brand_id, :cf)',
                            [':email' => $email, ':name' => $name, ':brand_id' => $brandId, ':cf' => $customFields]
                        );
                        $contactId = $db->lastInsertId('email_contacts_id_seq');
                        $created++;
                    }

                    if ($listId && $contactId) {
                        // Ignore duplicate pivot rows
                        try {
                            $db->query(
                                'INSERT INTO email_list_contacts (list_id, contact_id) VALUES (:list_id, :contact_id) ON CONFLICT DO NOTHING',
                                [':list_id' => $listId, ':contact_id' => $contactId]
                            );
                        } catch (\Throwable) {}
                    }
                }

                $apiJson(['created' => $created, 'skipped' => $skipped], 201);
            }

            // DELETE /api/contacts/:id
            if ($requestMethod === 'DELETE' && $resourceId) {
                $db->query('DELETE FROM email_contacts WHERE id = :id', [':id' => $resourceId]);
                $apiJson(['success' => true]);
            }
        }

        // ── /api/campaigns ───────────────────────────────────────────────────
        if ($resource === 'campaigns') {
            // POST /api/campaigns/:id/send
            if ($requestMethod === 'POST' && $resourceId && $action === 'send') {
                $db->query(
                    "UPDATE email_campaigns SET status = 'sending', updated_at = NOW() WHERE id = :id",
                    [':id' => $resourceId]
                );
                $row = $db->query('SELECT * FROM email_campaigns WHERE id = :id', [':id' => $resourceId])->fetch();
                $apiJson($row ?? ['error' => 'Campanha não encontrada.', 'code' => 404]);
            }

            // GET /api/campaigns/:id
            if ($requestMethod === 'GET' && $resourceId && !$action) {
                $row = $db->query(
                    'SELECT ec.*, el.name AS list_name FROM email_campaigns ec LEFT JOIN email_lists el ON el.id = ec.list_id WHERE ec.id = :id',
                    [':id' => $resourceId]
                )->fetch();
                if (!$row) {
                    $apiJson(['error' => 'Campanha não encontrada.', 'code' => 404], 404);
                }
                $apiJson($row);
            }

            // PUT /api/campaigns/:id
            if ($requestMethod === 'PUT' && $resourceId) {
                $allowed = ['name', 'subject', 'from_name', 'from_email', 'reply_to', 'html_content', 'text_content', 'status', 'scheduled_at', 'list_id'];
                $sets    = [];
                $params  = [':id' => $resourceId];
                foreach ($allowed as $field) {
                    if (array_key_exists($field, $jsonBody)) {
                        $sets[]              = "{$field} = :{$field}";
                        $params[":{$field}"] = $jsonBody[$field];
                    }
                }
                if ($sets) {
                    $db->query(
                        'UPDATE email_campaigns SET ' . implode(', ', $sets) . ', updated_at = NOW() WHERE id = :id',
                        $params
                    );
                }
                $row = $db->query('SELECT * FROM email_campaigns WHERE id = :id', [':id' => $resourceId])->fetch();
                $apiJson($row ?? ['error' => 'Campanha não encontrada.', 'code' => 404]);
            }

            // GET /api/campaigns (list)
            if ($requestMethod === 'GET' && !$resourceId) {
                $brandParam = $_GET['brand'] ?? null;
                $brandId    = (int) ($_GET['brand_id'] ?? 0);
                $limit      = min((int) ($_GET['limit'] ?? 20), 200);
                $page       = max(1, (int) ($_GET['page'] ?? 1));
                $offset     = ($page - 1) * $limit;

                // Dashboard stats mode: ?brand=dashboard&range=30d
                if ($brandParam === 'dashboard') {
                    $range = $_GET['range'] ?? '30d';
                    $days  = (int) filter_var($range, FILTER_SANITIZE_NUMBER_INT) ?: 30;

                    $statsRow = $db->query(
                        "SELECT
                            COUNT(*) FILTER (WHERE status = 'sent') AS campaigns_sent,
                            COALESCE(SUM((stats->>'sent')::int)    FILTER (WHERE status = 'sent'), 0) AS total_sent,
                            COALESCE(SUM((stats->>'opens')::int)   FILTER (WHERE status = 'sent'), 0) AS total_opens,
                            COALESCE(SUM((stats->>'clicks')::int)  FILTER (WHERE status = 'sent'), 0) AS total_clicks
                         FROM email_campaigns
                         WHERE created_at >= NOW() - INTERVAL '1 day' * :days",
                        [':days' => $days]
                    )->fetch();

                    $totalContacts = (int) $db->query("SELECT COUNT(*) FROM email_contacts WHERE status = 'active'")->fetchColumn();

                    $openRate  = $statsRow['total_sent'] > 0 ? round($statsRow['total_opens'] / $statsRow['total_sent'] * 100, 1) : 0;
                    $clickRate = $statsRow['total_sent'] > 0 ? round($statsRow['total_clicks'] / $statsRow['total_sent'] * 100, 1) : 0;

                    // Monthly contacts growth (last 6 months)
                    $growth = $db->query(
                        "SELECT TO_CHAR(DATE_TRUNC('month', created_at), 'Mon') AS month,
                                COUNT(*) AS count
                         FROM email_contacts
                         WHERE created_at >= NOW() - INTERVAL '6 months'
                         GROUP BY DATE_TRUNC('month', created_at)
                         ORDER BY DATE_TRUNC('month', created_at)"
                    )->fetchAll();

                    $apiJson([
                        'total_contacts'  => $totalContacts,
                        'campaigns_sent'  => (int) $statsRow['campaigns_sent'],
                        'open_rate'       => $openRate,
                        'click_rate'      => $clickRate,
                        'contacts_growth' => $growth,
                    ]);
                }

                $where  = $brandId ? ['ec.brand_id = :brand_id'] : ['1=1'];
                $params = $brandId ? [':brand_id' => $brandId] : [];

                $total = (int) $db->query(
                    'SELECT COUNT(*) FROM email_campaigns ec WHERE ' . implode(' AND ', $where),
                    $params
                )->fetchColumn();

                $rows = $db->query(
                    'SELECT ec.*, el.name AS list_name FROM email_campaigns ec
                     LEFT JOIN email_lists el ON el.id = ec.list_id
                     WHERE ' . implode(' AND ', $where) . '
                     ORDER BY ec.created_at DESC LIMIT :lim OFFSET :off',
                    array_merge($params, [':lim' => $limit, ':off' => $offset])
                )->fetchAll();

                $apiJson([
                    'data'        => $rows,
                    'total'       => $total,
                    'page'        => $page,
                    'total_pages' => (int) ceil($total / max(1, $limit)),
                ]);
            }

            // POST /api/campaigns
            if ($requestMethod === 'POST' && !$resourceId) {
                $brandId = (int) ($jsonBody['brand_id'] ?? 0);
                $name    = trim($jsonBody['name'] ?? '');
                if (!$brandId || $name === '') {
                    $apiJson(['error' => 'brand_id e name são obrigatórios.', 'code' => 400], 400);
                }
                $db->query(
                    'INSERT INTO email_campaigns (brand_id, list_id, name, subject, from_name, from_email, reply_to, html_content, text_content, status)
                     VALUES (:brand_id, :list_id, :name, :subject, :from_name, :from_email, :reply_to, :html_content, :text_content, :status)',
                    [
                        ':brand_id'     => $brandId,
                        ':list_id'      => ($jsonBody['list_id'] ?? null) ?: null,
                        ':name'         => $name,
                        ':subject'      => $jsonBody['subject'] ?? '',
                        ':from_name'    => $jsonBody['from_name'] ?? '',
                        ':from_email'   => $jsonBody['from_email'] ?? '',
                        ':reply_to'     => $jsonBody['reply_to'] ?? null,
                        ':html_content' => $jsonBody['html_content'] ?? null,
                        ':text_content' => $jsonBody['text_content'] ?? null,
                        ':status'       => $jsonBody['status'] ?? 'draft',
                    ]
                );
                $newId = $db->lastInsertId('email_campaigns_id_seq');
                $row   = $db->query('SELECT * FROM email_campaigns WHERE id = :id', [':id' => $newId])->fetch();
                $apiJson($row, 201);
            }
        }

        // ── /api/suppression ─────────────────────────────────────────────────
        if ($resource === 'suppression') {
            if ($requestMethod === 'GET') {
                $brandId = isset($_GET['brand_id']) ? (int) $_GET['brand_id'] : null;
                $limit   = min((int) ($_GET['limit'] ?? 50), 500);
                $page    = max(1, (int) ($_GET['page'] ?? 1));
                $offset  = ($page - 1) * $limit;

                $where  = $brandId ? ['(brand_id = :brand_id OR brand_id IS NULL)'] : ['1=1'];
                $params = $brandId ? [':brand_id' => $brandId] : [];

                $total = (int) $db->query(
                    'SELECT COUNT(*) FROM email_suppression WHERE ' . implode(' AND ', $where),
                    $params
                )->fetchColumn();

                $rows = $db->query(
                    'SELECT * FROM email_suppression WHERE ' . implode(' AND ', $where) . ' ORDER BY created_at DESC LIMIT :lim OFFSET :off',
                    array_merge($params, [':lim' => $limit, ':off' => $offset])
                )->fetchAll();

                $apiJson([
                    'data'        => $rows,
                    'total'       => $total,
                    'page'        => $page,
                    'total_pages' => (int) ceil($total / max(1, $limit)),
                ]);
            }

            if ($requestMethod === 'POST') {
                $email   = strtolower(trim($jsonBody['email'] ?? ''));
                $reason  = trim($jsonBody['reason'] ?? '');
                $brandId = ($jsonBody['brand_id'] ?? null) ? (int) $jsonBody['brand_id'] : null;

                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $apiJson(['error' => 'Email inválido.', 'code' => 400], 400);
                }

                $db->query(
                    'INSERT INTO email_suppression (email, reason, brand_id) VALUES (:email, :reason, :brand_id) ON CONFLICT DO NOTHING',
                    [':email' => $email, ':reason' => $reason ?: null, ':brand_id' => $brandId]
                );

                $apiJson(['success' => true], 201);
            }

            // DELETE /api/suppression/:id
            if ($requestMethod === 'DELETE' && $resourceId) {
                $db->query('DELETE FROM email_suppression WHERE id = :id', [':id' => $resourceId]);
                $apiJson(['success' => true]);
            }
        }

        // Fallback: unknown API route
        $apiJson(['error' => 'Endpoint não encontrado.', 'code' => 404], 404);

    } catch (\Throwable $e) {
        error_log('API error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
        $apiJson([
            'error'  => 'Erro interno do servidor.',
            'code'   => 500,
            'detail' => env('APP_DEBUG', false) ? $e->getMessage() : null,
        ], 500);
    }
}
// ─── End REST API block ───────────────────────────────────────────────────────

// Bootstrap router
$router = new \App\Core\Router();

// Auth routes
$router->get('/login', 'AuthController@showLogin');
$router->post('/login', 'AuthController@doLogin');
$router->get('/logout', 'AuthController@doLogout');

// Gallery
$router->get('/', 'GalleryController@index');
$router->get('/image/:id', 'GalleryController@show');
$router->post('/image/:id/delete', 'GalleryController@delete');

// Upload
$router->get('/upload', 'UploadController@showForm');
$router->post('/upload', 'UploadController@handle');

// Download
$router->get('/download/:id', 'DownloadController@single');
$router->post('/download/bulk', 'DownloadController@bulk');

// Converter
$router->get('/converter', 'ConverterController@index');
$router->post('/converter/process', 'ConverterController@process');
$router->post('/converter/estimate', 'ConverterController@estimate');

// Admin — Users
$router->get('/admin/users', 'AdminController@userList');
$router->get('/admin/users/create', 'AdminController@userCreate');
$router->post('/admin/users/create', 'AdminController@userStore');
$router->get('/admin/users/:id/edit', 'AdminController@userEdit');
$router->post('/admin/users/:id/edit', 'AdminController@userUpdate');
$router->post('/admin/users/:id/toggle', 'AdminController@userToggle');

// Admin — Brands
$router->get('/admin/brands', 'AdminController@brandList');
$router->get('/admin/brands/create', 'AdminController@brandCreate');
$router->post('/admin/brands/create', 'AdminController@brandStore');
$router->get('/admin/brands/:id/edit', 'AdminController@brandEdit');
$router->post('/admin/brands/:id/edit', 'AdminController@brandUpdate');
$router->post('/admin/brands/:id/delete', 'AdminController@brandDelete');

// Admin — Images
$router->post('/admin/images/:id/restore', 'AdminController@imageRestore');
$router->post('/admin/images/:id/hard-delete', 'AdminController@imageHardDelete');

// Email Marketing
$router->get('/email', 'EmailController@index');
$router->get('/email/campaign/:id', 'EmailController@campaign');

$router->dispatch();
