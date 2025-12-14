<?php
if (!isset($_GET['board_id'])) {
    die("wth?");
}

$id = $_GET['board_id'];
include_once $_SERVER['DOCUMENT_ROOT'] . '/includes/autoload.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/includes/show_errors.php';

use Objects\Dbh;
use Objects\Session;

$session = new Session();
$remaining = $session->getRateLimitRemaining();

$query = "SELECT name, description FROM boards WHERE id = :id";
$params = array(':id' => $id);
$dbh = Dbh::getInstance();
$result = $dbh->Query($query, $params);

$count = $dbh->Query("SELECT COUNT(*) AS num FROM threads WHERE board_id = :id", array(':id' => $id));
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="stylesheet" href="./assets/bootstrap4/css/bootstrap.min.css">
    <title><?= htmlspecialchars($result[0]['name']) ?></title>
</head>
<body>
<div class="container">
    <h1><a href="./index.php">SANTAROSO PROJECT 2026</a></h1>
    <h1>Welcome to <?= htmlspecialchars($result[0]['name']) ?> board</h1>
    <h6><?= htmlspecialchars($result[0]['description']) ?></h6>
    <h3>Make a thread lol and join our open <?= $count[0]['num'] ?> thread!</h3>
    <form action="./requests/CreatePost.inc.php" method="post" enctype="multipart/form-data" class="row g-3">
        <input type="hidden" name="token" id="token" value="<?php echo $_SESSION['CSRF_TOKEN']; ?>">
        <input type="hidden" name="board_id" value="<?= htmlspecialchars($id) ?>">
        <div class="mb-3 col-12">
            <label for="exampleFormControlInput1" class="form-label">your name</label>
            <input type="text" class="form-control" id="exampleFormControlInput1" placeholder="Default: Anonymous"
                   maxlength="255" name="name">
        </div>
        <div class="mb-3 col-12">
            <label for="exampleFormControlTextarea1" class="form-label">Post Body</label>
            <textarea class="form-control" id="exampleFormControlTextarea1" rows="5" maxlength="5000"
                      name="body"></textarea>
        </div>
        <div class="mb-3 col-12">
            <label for="formFile" class="form-label">attach media</label>
            <input class="form-control" type="file" id="formFile" name="file">
        </div>
        <div id="passwordHelpBlock" class="form-text col-12">
            Your name must be 2-255 characters long, and your post must be 5000 characters or less. you can upload up to
            10MB
            file, JPEG/PNG/GIF/MP4/MP3 are allowed!
        </div>
        <div id="recaptcha" class="g-recaptcha col-12" data-sitekey="6Lf1iCssAAAAAPpcdDZTp9DYksfKv0JMWpRF2qk8"></div>
        <div class="mb-3 col-auto d-flex align-items-end align-items-center">
            <input type="submit" class="btn btn-secondary mb-0" value="POST" style="display: none" id="submit" disabled>
            <span id="time-remaining" class="text-muted ml-2" style="display:inline-block"></span>
        </div>
        <?php
        show_errors($session->getSession("errors") ?? []);
        $session->unsetSession("errors");
        ?>
    </form>
    <hr>

<h3>Threads</h3>
<div id="feed" class="card-columns"></div>
<div id="more" class="my-3 text-center text-muted"></div>
</div>
<style>
  .hover-shadow { transition: box-shadow 0.2s ease, transform 0.2s ease; }
  .hover-shadow:hover { box-shadow: 0 0.5rem 1rem rgba(0,0,0,.15)!important; transform: translateY(-2px); }
</style>
<script src="https://www.google.com/recaptcha/api.js?onload=onloadCallback&render=explicit" async defer></script>
<script src="assets/getThreads.js"></script>
<script>
    // Initial remaining seconds from server
    var initialRemaining = <?php echo (int)$remaining; ?>;
    var remaining = initialRemaining;
    var submitBtn = document.getElementById('submit');
    var timeSpan = document.getElementById('time-remaining');
    var captchaSolved = false;

    function updateUI() {
        // Show time remaining text
        if (remaining > 0) {
            timeSpan.textContent = 'Please wait ' + remaining + 's before posting again';
        } else {
            timeSpan.textContent = '';
        }
        // Show submit button only after captcha solved
        submitBtn.style.display = captchaSolved ? 'inline-block' : 'none';
        // Enable only if both captcha solved and no remaining time
        submitBtn.disabled = !(captchaSolved && remaining <= 0);
    }

    // Countdown timer
    updateUI();
    var timer = setInterval(function () {
        if (remaining > 0) {
            remaining--;
            updateUI();
        }
        if (remaining <= 0) {
            clearInterval(timer);
        }
    }, 1000);

    function onCaptchaSolved() {
        captchaSolved = true;
        updateUI();
    }

    function onCaptchaExpired() {
        captchaSolved = false;
        updateUI();
    }

    function onloadCallback() {
        if (window.grecaptcha && document.getElementById('recaptcha')) {
            window.grecaptcha.render('recaptcha', {
                sitekey: '6Lf1iCssAAAAAPpcdDZTp9DYksfKv0JMWpRF2qk8',
                callback: onCaptchaSolved,
                'expired-callback': onCaptchaExpired
            });
        }
        updateUI();
    }
</script>