<?php
include_once $_SERVER['DOCUMENT_ROOT'] . '/includes/ipCheck.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST'){
    die("Invalid request method");
}

if (!isset($_POST['name']) || !isset($_POST['disp']) || !isset($_POST['token'])){
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
$name = $_POST['name'];
$disc = $_POST['disp'];
$csrfToken = $_POST['token'];
$values = array($name, $disc);

$errorHandler->CSRF($csrfToken, $session->getCsrfToken());
$errorHandler->emptyCheck($values);
$errorHandler->maxOneLine([$name], 255);
$errorHandler->emptyCheck([$disc], 1000);

if ($errorHandler->getErrors()){
    $session->setSession("errors", $errorHandler->getErrors());
    header("Location: /admin.php");
    die();
}

try{
    $result = $dbh->query("INSERT INTO boards (name, description) VALUES (:name, :disc);",
        [":name" => $name, ":disc" => $disc]);
} catch (PDOException $e){
    error_log("errors", $e->getMessage());
    die("Database error");
}

header("Location: /admin.php?done=newboardcreated");
die();