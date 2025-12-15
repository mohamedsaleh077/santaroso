<?php
include_once $_SERVER['DOCUMENT_ROOT'] . '/includes/autoload.php';

use Objects\Session;

$session = new Session();
$session->destroy();

header("Location: /index.php");
die();
