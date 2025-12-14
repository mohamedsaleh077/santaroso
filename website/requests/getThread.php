<?php

use Objects\Dbh;

require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/autoload.php';

header('Content-Type: application/json; charset=utf-8');

// Enforce POST-only
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed. Use POST.']);
    exit;
}

$threadId = $_POST['thread_id'] ?? null;
$commentPage = $_POST['comment_page'] ?? 1;

$threadId = filter_var($threadId, FILTER_VALIDATE_INT);
$commentPage = filter_var($commentPage, FILTER_VALIDATE_INT);
if ($threadId === false || $threadId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid thread_id']);
    exit;
}
if ($commentPage === false || $commentPage < 1) {
    $commentPage = 1;
}

$limit = 10;
$offset = ($commentPage - 1) * $limit;

try {
    $dbh = Dbh::getInstance()->getConnection();

    // Fetch thread
    $stmt = $dbh->prepare("SELECT id, user_name, board_id, title, body, media, created_at FROM threads WHERE id = :id");
    $stmt->execute([':id' => $threadId]);
    $thread = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$thread) {
        echo json_encode(['not_found' => true, 'message' => 'No post found with the given id']);
        exit;
    }

    // Fetch comments paginated, newest first
    $stmtC = $dbh->prepare("SELECT id, user_name, body, media, created_at FROM comments WHERE thread_id = :tid ORDER BY created_at DESC, id DESC LIMIT $limit OFFSET $offset");
    $stmtC->execute([':tid' => $threadId]);
    $comments = $stmtC->fetchAll(PDO::FETCH_ASSOC);

    // Determine if more comments exist
    $stmtCount = $dbh->prepare("SELECT COUNT(*) AS cnt FROM comments WHERE thread_id = :tid");
    $stmtCount->execute([':tid' => $threadId]);
    $totalComments = (int)($stmtCount->fetch(PDO::FETCH_ASSOC)['cnt'] ?? 0);
    $hasMore = ($offset + $limit) < $totalComments;

    // Format dates and shape response
    $formatDate = function ($dt) {
        $ts = strtotime($dt ?? '');
        return $ts ? date('j M Y, g:iA', $ts) : null;
    };

    $threadOut = [
        'id' => (int)$thread['id'],
        'name' => $thread['user_name'],
        'board_id' => (int)$thread['board_id'],
        'title' => $thread['title'],
        'body' => $thread['body'],
        'media' => $thread['media'],
        'created_at' => $formatDate($thread['created_at']),
    ];

    $commentsOut = [];
    foreach ($comments as $c) {
        $commentsOut[] = [
            'id' => (int)$c['id'],
            'name' => $c['user_name'],
            'body' => $c['body'],
            'media' => $c['media'],
            'created_at' => $formatDate($c['created_at']),
        ];
    }

    echo json_encode([
        'thread' => $threadOut,
        'comments' => $commentsOut,
        'pagination' => [
            'page' => $commentPage,
            'per_page' => $limit,
            'has_more' => $hasMore,
            'total' => $totalComments
        ]
    ]);
    exit;
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal Server Error']);
    exit;
}
