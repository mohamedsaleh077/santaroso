<?php

include_once $_SERVER['DOCUMENT_ROOT'] . '/includes/autoload.php';

use Objects\Dbh;
use Objects\Session;
use Objects\Validation;

$session = new Session();
$errorHandler = new Validation();
$dbh = Dbh::getInstance();

if ($_SERVER['REQUEST_METHOD'] !== 'POST'){
    die("Invalid request method");
}

// Basic required fields check (including CSRF token)
if (!isset($_POST['username']) || !isset($_POST['password']) || !isset($_POST['token'])){
    die("Missing all required values");
}

$username = $_POST['username'];
$password = $_POST['password'];
$csrfToken = $_POST['token'];
$values = array($username, $password);

// CSRF validation
$errorHandler->CSRF($csrfToken, $_SESSION['CSRF_TOKEN'] ?? '');
$errorHandler->emptyCheck($values);
$errorHandler->max255($values);

// Server-side Google reCAPTCHA v2 verification
$recaptchaResponse = $_POST['g-recaptcha-response'] ?? '';
if (empty($recaptchaResponse)) {
    $errorHandler->setError('captcha', 'Please complete the reCAPTCHA challenge.');
} else {
    $config = parse_ini_file($_SERVER['DOCUMENT_ROOT'] . '/config.ini', true);
    $captchaSecret = $config['tokens']['captcha'] ?? '';

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

if ($errorHandler->getErrors()){
    $session->setSession("errors", $errorHandler->getErrors());
    header("Location: /login.php");
    die();
}

$query = "SELECT * FROM admins WHERE user_name = :user_name";
$params = array(':user_name' => $username);
$result = $dbh->Query($query, $params);

if (!$result){
    $errorHandler->setError("login_Error_username", "Invalid username.");
}

if ($result && $result[0]['password'] !== $password){
    $errorHandler->setError("login_Error_password", "Invalid password.");
}

if ($errorHandler->getErrors()){
    $session->setSession("errors", $errorHandler->getErrors());
    header("Location: /login.php");
    die();
}
$session->setSession("adminLogin", $result[0]['user_name']);
header("Location: /admin.php");
die();
