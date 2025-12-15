<?php
include_once $_SERVER['DOCUMENT_ROOT'] . '/includes/autoload.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/includes/show_errors.php';

use Objects\Session;

$session = new Session();
$remaining = $session->getRateLimitRemaining();
if (!isset($_GET['id'])) {
    die("wth?");
}

$id = $_GET['id'];

$config = parse_ini_file($_SERVER['DOCUMENT_ROOT'] . '/config.ini', true);
$CaptchaClientToken = $config['tokens']['ClientCaptcha'];
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title></title>
    <link rel="stylesheet" href="./assets/bootstrap4/css/bootstrap.min.css">
</head>
<body class="bg-info">
<div class="container bg-info-subtle">
    <p><a href="javascript:history.back()" id="back-link"><- back</a></p>
    <h3 id="thread-title">Loading...</h3>
    <h6 id="thread-date"></h6>
    <div class="card border border-black mb-4 p-2">
        <div class="mb-3" id="thread-media"></div>
        <div id="thread-body" class="" style="overflow-wrap: break-word"></div>
    </div>
    <hr>
    <h1>Make a comment lol</h1>
    <button class="btn btn-primary mb-3" type="button" data-bs-toggle="collapse" data-bs-target="#collapseExample"
            aria-expanded="false" aria-controls="collapseExample">
        Make a thread
    </button>
    <div class="collapse bg-body-secondary p-5" id="collapseExample">
        <form action="./requests/CreateComment.php" method="post" enctype="multipart/form-data" class="row g-3">
            <input type="hidden" name="token" id="token" value="<?php echo $_SESSION['CSRF_TOKEN']; ?>">
            <input type="hidden" name="thread_id" value="<?= htmlspecialchars($id) ?>">
            <div class="mb-3 col-12">
                <label for="exampleFormControlInput1" class="form-label">your name</label>
                <input type="text" class="form-control" id="exampleFormControlInput1" placeholder="Default: Anonymous"
                       maxlength="50" name="name">
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
                Your name must be 2-255 characters long, and your post must be 5000 characters or less. you can upload
                up to
                10MB
                file, JPEG/PNG/GIF/MP4/MP3 are allowed!
            </div>
            <div id="recaptcha" class="g-recaptcha col-12"
                 data-sitekey="6Lf1iCssAAAAAPpcdDZTp9DYksfKv0JMWpRF2qk8"></div>
            <div class="mb-3 col-auto d-flex align-items-end align-items-center gap-2">
                <input type="submit" class="btn btn-secondary mb-0" value="COMMENT" style="display: none" id="submit"
                       disabled>
                <span id="time-remaining" class="text-muted ms-2" style="display:inline-block"></span>
            </div>
        </form>
    </div>
    <?php
    show_errors($session->getSession("errors") ?? []);
    $session->unsetSession("errors");
    ?>
    <hr>
    <h1>Comments</h1>
    <div id="comments" class="px-5"></div>
    <div id="comments-more" class="mt-3 text-center"></div>
</div>
<script src="./assets/jquery-3.7.1.min.js.js"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" crossorigin="anonymous"></script>
<script src="./assets/bootstrap4/js/bootstrap.min.js" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script> <!-- for markdown support -->
<script src="./assets/getFullThread.js"></script>
<script src="https://www.google.com/recaptcha/api.js?onload=onloadCallback&render=explicit" async defer></script>
<!-- for recaptcha -->
<script>
    var initialRemaining = <?php echo (int)$remaining; ?>;
    var remaining = initialRemaining;
    var submitBtn = document.getElementById('submit');
    var timeSpan = document.getElementById('time-remaining');
    var captchaSolved = false;

    function updateUI() {
        if (remaining > 0) {
            timeSpan.textContent = 'Please wait ' + remaining + 's before commenting again';
        } else {
            timeSpan.textContent = '';
        }
        submitBtn.style.display = captchaSolved ? 'inline-block' : 'none';
        submitBtn.disabled = !(captchaSolved && remaining <= 0);
    }

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
                sitekey: '<?= $CaptchaClientToken ?>',
                callback: onCaptchaSolved,
                'expired-callback': onCaptchaExpired
            });
        }
        updateUI();
    }
</script>
</body>
</html>
