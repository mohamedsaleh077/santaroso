<?php

include_once $_SERVER['DOCUMENT_ROOT'] . '/includes/autoload.php';

use Objects\Session;
use Objects\Validation;
use Objects\FileHandler;
use Objects\Dbh;

$dbh = Dbh::getInstance();
$session = new Session();
$errorHandler = new Validation();

if ($_SERVER['REQUEST_METHOD'] !== 'POST'){
    die("Invalid request method");
}

// Required fields: CSRF token, board id
if (!isset($_POST['token']) || !isset($_POST['board_id'])){
    die("Missing all required values");
}

$csrfToken = $_POST['token'];
$boardId = $_POST['board_id'];
$name = $_POST['name'] ?? 'Anonymous';
$body = $_POST['body'] ?? '';

// Basic validations
$errorHandler->CSRF($csrfToken, $_SESSION['CSRF_TOKEN'] ?? '');
$errorHandler->emptyCheck([$boardId]);
// Optional fields but keep length constraints if provided
$errorHandler->maxOneLine([$name], 50);
$errorHandler->maxParagraphe(['post_body' => $body], 5000);

// Rate limit: allow only one action every 60 seconds per session
if ($session->isLastRequestWithinTimeframe()) {
    $remaining = $session->getRateLimitRemaining();
    $errorHandler->setError('rate_limit', 'Please wait ' . $remaining . ' seconds before creating another thread.');
}

// Server-side Google reCAPTCHA v2 verification
$recaptchaResponse = $_POST['g-recaptcha-response'] ?? '';
if (empty($recaptchaResponse)) {
    $errorHandler->setError('captcha', 'Please complete the reCAPTCHA challenge.');
} else {
    $config = parse_ini_file($_SERVER['DOCUMENT_ROOT'] . '/config.ini', true);
    $captchaSecret = $config['tokens']['SideServerCaptcha'] ?? '';

    if (empty($captchaSecret)) {
        // If secret key is not configured, treat as failure for security
        $errorHandler->setError('captcha', 'reCAPTCHA configuration error. Please contact the administrator.');
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
            $errorHandler->setError('captcha', 'Failed to verify reCAPTCHA. Please try again.');
        } else {
            $captchaResult = json_decode($verify, true);
            if (!is_array($captchaResult) || !($captchaResult['success'] ?? false)) {
                $codes = isset($captchaResult['"error-codes"']) ? $captchaResult['"error-codes"'] : ($captchaResult['error-codes'] ?? []);
                $detail = is_array($codes) && !empty($codes) ? (' (' . implode(', ', $codes) . ')') : '';
                $errorHandler->setError('captcha', 'reCAPTCHA validation failed' . $detail . '.');
            }
        }
    }
}

// Handle file upload if provided
$uploadedFilename = null;
if (isset($_FILES['file']) && is_array($_FILES['file'])) {
    $err = $_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($err !== UPLOAD_ERR_NO_FILE) {
        if ($err !== UPLOAD_ERR_OK) {
            // Map common upload errors
            $map = [
                UPLOAD_ERR_INI_SIZE => 'Uploaded file exceeds the maximum allowed size.',
                UPLOAD_ERR_FORM_SIZE => 'Uploaded file exceeds the form limit.',
                UPLOAD_ERR_PARTIAL => 'Uploaded file was only partially uploaded. Please try again.',
                UPLOAD_ERR_NO_TMP_DIR => 'Server is missing a temporary folder.',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
                UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload.',
            ];
            $errorHandler->setError('upload', $map[$err] ?? 'Unknown upload error.');
        } else {
            if (!empty($_FILES['file']['tmp_name'])) {
                // Pass full file array to FileHandler
                $fh = new FileHandler($_FILES['file']);
                $result = $fh->upload();
                if (!$result || !($result['ok'] ?? 0)) {
                    $errs = $result['error'] ?? ['upload' => 'Failed to upload file.'];
                    foreach ($errs as $k => $v) {
                        $errorHandler->setError('file_' . $k, $v);
                    }
                } else {
                    $uploadedFilename = $result['filename'] ?? null;
                }
            } else {
                $errorHandler->setError('upload', 'No uploaded file found.');
            }
        }
    }
}

if ($errorHandler->getErrors()){
    $session->setSession("errors", $errorHandler->getErrors());
    header("Location: /board.php?board_id=" . urlencode($boardId));
    die();
}

// Prepare values
$boardIdInt = (int)$boardId;
$userName = trim($name) !== '' ? trim($name) : 'Anonymous';
$bodyText = trim($body);
$titleText = $bodyText !== '' ? mb_substr(preg_replace('/\s+/', ' ', $bodyText), 0, 255) : 'Untitled';
$mediaFile = $uploadedFilename; // may be null

// Insert the thread
$query = "INSERT INTO threads (user_name, board_id, title, body, media) VALUES (:user_name, :board_id, :title, :body, :media)";
$params = [
    ':user_name' => $userName,
    ':board_id' => $boardIdInt,
    ':title' => $titleText,
    ':body' => $bodyText !== '' ? $bodyText : null,
    ':media' => $mediaFile
];
$res = $dbh->Query($query, $params);

$pdo = $dbh->getConnection();
$newid = $pdo->lastInsertId();
$parms = [
    ':ref_id' => $newid,
    ':ip' => $_SERVER['REMOTE_ADDR']
];
$dbh->Query('INSERT INTO users_ips_actions (ref_id ,item_type_id, ip) VALUES (:ref_id, "thread", :ip)', $parms);

if ($res === false) {
    $errorHandler->setError('database', 'Failed to create the thread. Please try again.');
    $session->setSession("errors", $errorHandler->getErrors());
    header("Location: /board.php?board_id=" . urlencode($boardId));
    die();
}

// Mark the time of this successful action for rate limiting
$session->lastRequest();

header("Location: /board.php?board_id=" . urlencode($boardId));
exit;
