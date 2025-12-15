<?php
include_once $_SERVER['DOCUMENT_ROOT'] . '/includes/ipCheck.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST'){
    die("Invalid request method");
}

if (!isset($_POST['user']) || !isset($_POST['pwd']) || !isset($_POST['token'])){
    die("Missing all required values");
}

include_once $_SERVER['DOCUMENT_ROOT'] . '/includes/autoload.php';

use Objects\Dbh;
use Objects\Session;
use Objects\Validation;

$session = new Session();

if (!isset($_SESSION['adminLogin'])){
    header("Location: ./login.php");
    die();
}

$errorHandler = new Validation();
$dbh = Dbh::getInstance();

$id = $session->getSession("adminLogin");
$username = $_POST['user'];
$password = $_POST['pwd'];
$csrfToken = $_POST['token'];
$values = array($username, $password);

$errorHandler->CSRF($csrfToken, $session->getCsrfToken());
$errorHandler->emptyCheck($values);
$errorHandler->maxOneLine($values, 255);

if ($errorHandler->getErrors()){
    $session->setSession("errors", $errorHandler->getErrors());
    header("Location: /admin.php");
    die();
}

try{
    $result = $dbh->query("UPDATE admins SET admins.password = :pwd , admins.user_name = :user  WHERE id = :id;",
        [":pwd" => $password, ":user" => $username, ":id" => $id]);
} catch (PDOException $e){
    error_log("errors", $e->getMessage());
    die("Database error");
}

header("Location: /admin.php?done=updateuserpass");
die();