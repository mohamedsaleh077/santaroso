<?php
if ($_SERVER['REQUEST_METHOD'] !== 'GET'){
    die("Invalid request method");
}

if (!isset($_GET['id'])){
    die("Missing all required values");
}

include_once $_SERVER['DOCUMENT_ROOT'] . '/includes/autoload.php';

use Objects\Dbh;
use Objects\Session;

$session = new Session();

if (!isset($_SESSION['adminLogin'])){
    header("Location: ./login.php");
    die();
}

$dbh = Dbh::getInstance();

$id = (int)$_GET['id'];

if(!is_int($id)){
    die("Missing id");
}

try {
    $dbh->query("DELETE FROM boards WHERE id = :id", [':id' => $id]);
    header("Location: /admin.php?done=delBoard");
    die();
}catch (PDOException $e){
    error_log($e);
    die("Database error");
}
