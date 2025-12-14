<?php

include_once $_SERVER['DOCUMENT_ROOT'] . '/includes/autoload.php';
use Objects\Session;
$session = new Session();

if (!$session->getSession("adminLogin")){
    header("Location: /admin/login.php");
    die();
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Admin Panel</title>
</head>
<body>

</body>
</html>
