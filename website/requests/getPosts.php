<?php

use Objects\Dbh;

// Autoload classes
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/autoload.php';

header('Content-Type: application/json; charset=utf-8');

// Enforce POST-only
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed. Use POST.']);
    exit;
}

// Read and validate inputs
$boardId = $_POST['board_id'] ?? null;
$page = $_POST['page'] ?? null; // page number for pagination (1-based)
$keyword = isset($_POST['keyword']) ? trim((string)$_POST['keyword']) : '';

if ($boardId === null || $page === null) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required parameters: board_id and page']);
    exit;
}

// Cast and validate numeric values
$boardId = filter_var($boardId, FILTER_VALIDATE_INT);
$page = filter_var($page, FILTER_VALIDATE_INT);
if ($boardId === false || $boardId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid board_id']);
    exit;
}
if ($page === false || $page < 1) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid page number']);
    exit;
}

$limit = 10; // fixed page size
$offset = ($page - 1) * $limit;

try {
    $dbh = Dbh::getInstance();
    $pdo = $dbh->getConnection();

    $sql = "SELECT t.id, t.user_name, t.body, t.media, t.created_at, COALESCE(COUNT(c.id), 0) AS comments_count\n            FROM threads t\n            LEFT JOIN comments c ON c.thread_id = t.id\n            WHERE t.board_id = :board_id";
    $params = [':board_id' => $boardId];

    if ($keyword !== '') {
        // Search in title or body
        $sql .= " AND (t.title LIKE :kw OR t.body LIKE :kw)";
        $params[':kw'] = '%' . $keyword . '%';
    }

    $sql .= " GROUP BY t.id ORDER BY t.created_at DESC, t.id DESC LIMIT $limit OFFSET $offset"; // validated ints injected

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $data = [];
    foreach ($rows as $r) {
        // Format created_at as "14 Dec 2025, 8:16PM"
        $ts = strtotime($r['created_at'] ?? '');
        $formattedDate = $ts ? date('j M Y, g:iA', $ts) : null;
        $data[] = [
            'thread_id' => (int)$r['id'],
            'name' => $r['user_name'],
            'body' => $r['body'],
            'media' => $r['media'],
            'created_at' => $formattedDate,
            'comments_count' => (int)($r['comments_count'] ?? 0),
        ];
    }

    echo json_encode($data);
    exit;
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal Server Error']);
    exit;
}
