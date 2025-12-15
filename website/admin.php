<?php
include_once $_SERVER['DOCUMENT_ROOT'] . '/includes/autoload.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/includes/show_errors.php';

use Objects\Session;
use Objects\Dbh;

$session = new Session();

if (!$session->getSession("adminLogin")) {
    header("Location: /login.php");
    die();
}

$config = parse_ini_file($_SERVER['DOCUMENT_ROOT'] . '/config.ini', true);
$ImageBoardName = $config['const']['name'];

$counter_file = 'hit.txt';
$count = (int)file_get_contents($counter_file);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Admin Panel</title>
    <link rel="stylesheet" href="./assets/bootstrap4/css/bootstrap.min.css">
    <link rel="stylesheet" href="./assets/styles.css">
</head>
<body class="bg-success">
<div class="container bg-success-subtle p-3">
    <h1 class="pb-3 pt-3 text-xl-center"><a href="./index.php"><?= $ImageBoardName ?></a></h1>
    <h2>SantarosoMyAdmin</h2>
    <ul class="nav nav-pills mb-3" id="pills-tab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="pills-home-tab" data-bs-toggle="pill" data-bs-target="#pills-Home"
                    type="button" role="tab" aria-controls="pills-home" aria-selected="true">Home
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="pills-boards-tab" data-bs-toggle="pill" data-bs-target="#pills-boards"
                    type="button" role="tab" aria-controls="pills-home" aria-selected="true">Manage Boards
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="pills-content-tab" data-bs-toggle="pill" data-bs-target="#pills-content"
                    type="button" role="tab" aria-controls="pills-profile" aria-selected="false">Manage Platform Content
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="pills-reports-tab" data-bs-toggle="pill" data-bs-target="#pills-reports"
                    type="button" role="tab" aria-controls="pills-contact" aria-selected="false">Review Reports
            </button>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="./requests/logout.php">LogOut</a>
        </li>
    </ul>

    <div class="tab-content" id="pills-tabContent">
        <div class="tab-pane fade show active" id="pills-Home" role="tabpanel" aria-labelledby="pills-home-tab">
            <p>your platform is working fine</p>
            <hr>
            <h3>Statics:</h3>
            <ul>
                <?php
                $pdo = Dbh::getInstance();
                $countBoards = $pdo->Query("SELECT COUNT(*) AS num FROM boards");
                $countThreads = $pdo->Query("SELECT COUNT(*) AS num  FROM threads");
                $countComments = $pdo->Query("SELECT COUNT(*) AS num  FROM comments");
                ?>
                <li>Visits(home page): <?= $count ?></li>
                <li>boards: <?= $countBoards[0]['num'] ?></li>
                <li>ThreadS: <?= $countThreads[0]['num'] ?></li>
                <li>Comments: <?= $countComments[0]['num'] ?></li>
            </ul>
            <hr>
            <h3>Change Admin Credentials</h3>
            <form action="./requests/updateUserPass.php" method="post">
                <input type="hidden" name="token" id="token" value="<?php echo $_SESSION['CSRF_TOKEN']; ?>">
                <input type="text" class="form-control m-1" name="user" placeholder="username" required maxlength="250"
                       style="max-width: 300px">
                <input type="password" class="form-control m-1" name="pwd" placeholder="Password" required
                       maxlength="250" style="max-width: 300px">
                <button type="submit" class="btn btn-primary m-1" style="max-width: 200px">Save</button>
            </form>
            <?php
            show_errors($session->getSession("errors") ?? []);
            $session->unsetSession("errors");
            ?>
        </div>
        <div class="tab-pane fade" id="pills-boards" role="tabpanel" aria-labelledby="pills-profile-tab">
            <h3>Manage Boards</h3>
            <hr>
            <form action="./requests/newBoard.php" method="post">
                <input type="hidden" name="token" id="token" value="<?php echo $_SESSION['CSRF_TOKEN']; ?>">
                <input type="text" class="form-control mb-2" name="name" placeholder="board name" required maxlength="250">
                <textarea class="form-control" id="exampleFormControlTextarea1" rows="2" maxlength="1000"
                          name="disp" placeholder="say something about this board"></textarea>
                <button type="submit" class="btn btn-primary mt-2" style="max-width: 200px">create</button>
            </form>
            <hr>
            <ul>
            <?php
                $boards = $pdo->Query("SELECT * FROM boards");
                foreach ($boards as $board){
            ?>
            <li><?= $board['name'] ?> : <?= $board['description'] ?> :
                <a href="./requests/delBoard.php?id=<?= $board['id'] ?>">Delete</a>
            </li>
            <?php
                }
            ?>
            </ul>
        </div>
        <div class="tab-pane fade" id="pills-content" role="tabpanel" aria-labelledby="pills-content">
            conenten management
        </div>
        <div class="tab-pane fade" id="pills-reports" role="tabpanel" aria-labelledby="pills-reports">
            report report
        </div>
    </div>

    <?php if (isset($_GET['done']) && $_GET['done'] == "updateuserpass") { ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <strong>Done!</strong> username and password for admin is updated!
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    <?php } else if (isset($_GET['done']) && $_GET['done'] == "delBoard") { ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
        <strong>Done!</strong> board is deleted!
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    <?php } else if (isset($_GET['done']) && $_GET['done'] == "newboardcreated") { ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <strong>Done!</strong> board is Created!
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    <?php } ?>
</div>

<script src="./assets/jquery-3.7.1.min.js.js"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" crossorigin="anonymous"></script>
<script src="./assets/bootstrap4/js/bootstrap.min.js" crossorigin="anonymous"></script>
</body>
</html>
