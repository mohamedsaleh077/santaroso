<?php

include_once $_SERVER['DOCUMENT_ROOT'] . '/includes/autoload.php';

use Objects\Session;
use Objects\Validation;
use Objects\FileHandler;
use Objects\Dbh;

$session = new Session();
$validator = new Validation();
$dbh = Dbh::getInstance();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    die('Invalid request method');
}

// Required fields
if (!isset($_POST['token']) || !isset($_POST['thread_id'])) {
    die('Missing all required values');
}

$csrfToken = $_POST['token'];
$threadId = $_POST['thread_id'];
$name = $_POST['name'] ?? 'Anonymous';
$body = $_POST['body'] ?? '';

// Basic validations
$validator->CSRF($csrfToken, $_SESSION['CSRF_TOKEN'] ?? '');
$validator->emptyCheck([$threadId]);
$validator->maxOneLine([$name], 50);
$validator->maxParagraphe([$body], 5000);

// Rate limit: allow only one action every 60 seconds per session
if ($session->isLastRequestWithinTimeframe()) {
    $remaining = $session->getRateLimitRemaining();
    $validator->setError('rate_limit', 'Please wait ' . $remaining . ' seconds before creating another comment.');
}

// reCAPTCHA v2 server-side verification (same as CreatePost)
$recaptchaResponse = $_POST['g-recaptcha-response'] ?? '';
if (empty($recaptchaResponse)) {
    $validator->setError('captcha', 'Please complete the reCAPTCHA challenge.');
} else {
    $config = parse_ini_file($_SERVER['DOCUMENT_ROOT'] . '/config.ini', true);
    $captchaSecret = $config['tokens']['captcha'] ?? '';
    if (empty($captchaSecret)) {
        $validator->setError('captcha', 'reCAPTCHA configuration error. Please contact the administrator.');
    } else {
        $postData = http_build_query([
            'secret' => $captchaSecret,
            'response' => $recaptchaResponse,
            'remoteip' => $_SERVER['REMOTE_ADDR'] ?? ''
        ]);
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-type: application/x-www-form-urlencoded\r\n" .
                            "Content-Length: " . strlen($postData) . "\r\n",
                'content' => $postData,
                'timeout' => 5
            ]
        ]);
        $verify = @file_get_contents('https://www.google.com/recaptcha/api/siteverify', false, $context);
        if ($verify === false) {
            $validator->setError('captcha', 'Failed to verify reCAPTCHA. Please try again.');
        } else {
            $captchaResult = json_decode($verify, true);
            if (!is_array($captchaResult) || !($captchaResult['success'] ?? false)) {
                $codes = isset($captchaResult['"error-codes"']) ? $captchaResult['"error-codes"'] : ($captchaResult['error-codes'] ?? []);
                $detail = is_array($codes) && !empty($codes) ? (' (' . implode(', ', $codes) . ')') : '';
                $validator->setError('captcha', 'reCAPTCHA validation failed' . $detail . '.');
            }
        }
    }
}

// Handle optional file upload
$uploadedFilename = null;
if (isset($_FILES['file']) && is_array($_FILES['file'])) {
    $err = $_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($err !== UPLOAD_ERR_NO_FILE) {
        if ($err !== UPLOAD_ERR_OK) {
            $map = [
                UPLOAD_ERR_INI_SIZE => 'Uploaded file exceeds the maximum allowed size.',
                UPLOAD_ERR_FORM_SIZE => 'Uploaded file exceeds the form limit.',
                UPLOAD_ERR_PARTIAL => 'Uploaded file was only partially uploaded. Please try again.',
                UPLOAD_ERR_NO_TMP_DIR => 'Server is missing a temporary folder.',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
                UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload.',
            ];
            $validator->setError('upload', $map[$err] ?? 'Unknown upload error.');
        } else {
            if (!empty($_FILES['file']['tmp_name'])) {
                $fh = new FileHandler($_FILES['file']);
                $result = $fh->upload();
                if (!$result || !($result['ok'] ?? 0)) {
                    $errs = $result['error'] ?? ['upload' => 'Failed to upload file.'];
                    foreach ($errs as $k => $v) {
                        $validator->setError('file_' . $k, $v);
                    }
                } else {
                    $uploadedFilename = $result['filename'] ?? null;
                }
            } else {
                $validator->setError('upload', 'No uploaded file found.');
            }
        }
    }
}

if ($validator->getErrors()) {
    $session->setSession('errors', $validator->getErrors());
    header('Location: /thread.php?id=' . urlencode($threadId));
    exit;
}

// Prepare values
$threadIdInt = (int)$threadId;
$userName = trim($name) !== '' ? trim($name) : 'Anonymous';
$bodyText = trim($body);
$mediaFile = $uploadedFilename; // may be null

// Optional: ensure thread exists to avoid orphan insert
try {
    $pdo = $dbh->getConnection();
    $stmt = $pdo->prepare('SELECT id FROM threads WHERE id = :id');
    $stmt->execute([':id' => $threadIdInt]);
    if (!$stmt->fetch()) {
        $validator->setError('thread', 'The thread you are replying to does not exist.');
        $session->setSession('errors', $validator->getErrors());
        header('Location: /thread.php?id=' . urlencode($threadId));
        exit;
    }

    // Insert comment
    $q = 'INSERT INTO comments (user_name, thread_id, body, media) VALUES (:user_name, :thread_id, :body, :media)';
    $params = [
        ':user_name' => $userName,
        ':thread_id' => $threadIdInt,
        ':body' => $bodyText,
        ':media' => $mediaFile
    ];
    $res = $dbh->Query($q, $params);
    if ($res === false) {
        $validator->setError('database', 'Failed to create the comment. Please try again.');
        $session->setSession('errors', $validator->getErrors());
        header('Location: /thread.php?id=' . urlencode($threadId));
        exit;
    }

    // Mark the time of this successful action for rate limiting
    $session->lastRequest();

    header('Location: /thread.php?id=' . urlencode($threadId));
    exit;
} catch (Throwable $e) {
    $validator->setError('exception', 'Internal error. Please try again later.');
    $session->setSession('errors', $validator->getErrors());
    header('Location: /thread.php?id=' . urlencode($threadId));
    exit;
}
