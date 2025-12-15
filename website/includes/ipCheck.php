<?php
include_once $_SERVER['DOCUMENT_ROOT'] . '/includes/autoload.php';
use Objects\Dbh;

$pdo = Dbh::getInstance();
$userIp = $_SERVER['REMOTE_ADDR'];

$query = "SELECT ban FROM users_ips_actions WHERE ip = :ip;";
$params = array(':ip' => $userIp);

$isBanned = $pdo->Query($query, $params);

if ($isBanned[0]['ban'] === 1){
    die("kys");
}