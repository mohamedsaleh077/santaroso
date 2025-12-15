<?php
include_once $_SERVER['DOCUMENT_ROOT'] . '/includes/autoload.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/includes/show_errors.php';
use Objects\Session;

$session = new Session();
if ($session->getSession("adminLogin")){
    header("Location: /admin.php");
    die();
}
$config = parse_ini_file($_SERVER['DOCUMENT_ROOT'] . '/config.ini', true);
$ImageBoardName = $config['const']['name'];
$CaptchaClientToken = $config['tokens']['ClientCaptcha'];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>LogIn</title>
    <link rel="stylesheet" href="./assets/bootstrap4/css/bootstrap.min.css">
</head>
<body>
<div class="container bg-info  mt-2 text-light p-5">
    <h1><?= $ImageBoardName ?></h1>
    <h3>Log in</h3>
    <form action="./requests/AdminLogin.inc.php" method="post" class="row g-3">
        <input type="hidden" name="token" id="token" value="<?php echo $_SESSION['CSRF_TOKEN']; ?>">
        <div class="mb-3 col-auto">
            <label for="exampleFormControlInput1" class="form-label">user name</label>
            <input type="text" name="username" class="form-control" id="exampleFormControlInput1" placeholder="username" maxlength="255" required>
        </div>
        <div class="mb-3 col-auto">
            <label for="exampleFormControlInput1" class="form-label">password</label>
            <input type="password" name="password" class="form-control" id="exampleFormControlInput1" placeholder="password" maxlength="255" required>
        </div>
        <div id="recaptcha" class="g-recaptcha" data-sitekey="6Lf1iCssAAAAAPpcdDZTp9DYksfKv0JMWpRF2qk8"></div>
        <div class="mb-3 col-auto d-flex align-items-end">
            <input type="submit" class="btn btn-secondary mb-0" value="Log in" style="display: none" id="submit">
        </div>
    </form>
    <?php
        show_errors($session->getSession("errors") ?? []);
        $session->unsetSession("errors");
    ?>
</div>
<script src="https://www.google.com/recaptcha/api.js?onload=onloadCallback&render=explicit" async defer></script>
<script>
    let thing = document.getElementById("submit");
    function onloadCallback() {
        if (window.grecaptcha && document.getElementById('recaptcha')) {
            window.grecaptcha.render('recaptcha', {
                sitekey: '<?= $CaptchaClientToken ?>'
            });
        }
        thing.style.display = "block";
    }
</script>

</body>
</html>