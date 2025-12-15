<?php
include_once $_SERVER['DOCUMENT_ROOT'] . '/includes/autoload.php';
use Objects\Dbh;
use Objects\Session;

$config = parse_ini_file($_SERVER['DOCUMENT_ROOT'] . '/config.ini', true);
$ImageBoardName = $config['const']['name'];

$counter_file = 'hit.txt';

if (!file_exists($counter_file)) {
    file_put_contents($counter_file, 0);
}

$count = (int)file_get_contents($counter_file);
$session = new Session();

if (!isset($_SESSION['hasVisited'])) {
    $count++;
    file_put_contents($counter_file, $count);
    $session->setSession('hasVisited', true);
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?= $ImageBoardName ?></title>
    <link rel="stylesheet" href="./assets/bootstrap4/css/bootstrap.min.css">
    <link rel="stylesheet" href="./assets/styles.css">
</head>
<body class="bg-danger  bg-gradient">
<div class="container bg-danger-subtle">
    <h1 class="pb-3 pt-3 text-xl-center"><?= $ImageBoardName ?></h1>
    <p>Lightweight Image Board, not useful and just a dump place to post dump things anonymously lmao</p>
    <p>we have <?= $count ?> visitor!</p>
    <h3>RULES, YOU MUST FOLLOW IT OR I WILL KICK YOUR IP LMAO</h3>
    <ul>
        <li>No NSFW</li>
        <li>No Spam</li>
        <li>No AI SHIT</li>
        <li>YOU ARE RESPONSIBLE FOR WHAT THE HELL YOU ARE POSTING LMAO.</li>
    </ul>
    <h3>OUR BOARDS!</h3>
    <table class="table table-hover table-striped border-primary">
        <thead>
        <tr>
            <th scope="col">Name</th>
            <th scope="col">Description</th>
            <th scope="col">Visit</th>
        </tr>
        </thead>
        <tbody>
        <?php
        $query = "SELECT id, name, description FROM boards";
        $dbh = Dbh::getInstance();
        $result = $dbh->Query($query);
        foreach ($result as $board) {
            echo "<tr><td>" . htmlspecialchars($board['name']) . "</td>";
            echo "<td>" . htmlspecialchars($board['description']) . "</td>";
            echo "<td><a href='/board.php?board_id=" . htmlspecialchars($board['id']) . "'> GO TO</a></td></tr>";
        }
        ?>
        </tbody>
    </table>
    <hr>
    <h3>Top 9 Threads</h3>
    <div class="custom-card-group g-4 p-3 bg-light">
        <?php
        $query = "SELECT 
            t.id, 
            t.user_name,
            t.body,
            t.media,
            t.created_at,
            b.name AS board_name,
            b.id AS board_id,
            COUNT(c.id) AS comment_count
        FROM 
            threads t
        LEFT JOIN 
            comments c ON t.id = c.thread_id
        INNER JOIN 
            boards b ON t.board_id = b.id
        GROUP BY 
            t.id
        ORDER BY 
            comment_count DESC
            LIMIT 9;";

        $result = $dbh->Query($query);
        function makeHTMLmedia($filename)
        {
            if ($filename === NULL){
                return '';
            }
            if ($filename !== '' && (str_ends_with($filename, 'mp4') || str_ends_with($filename, 'mp3'))) {
                $media = substr($filename, 0, strrpos($filename, '.')) . '.jpg';
            } else {
                $media = $filename;
            }

            $uploadsBase = '/uploads/';
            $imgSrcThumb = $uploadsBase . 'thumb_' . $media;
            return '<img class="card-img-top" src="' . $imgSrcThumb . '" alt="">';
        }

        foreach ($result as $thread) {
            $body = substr($thread['body'],0, 100);
            $board_link = '';            ?>
            <a href="/thread.php?id=<?= htmlspecialchars($thread['id']) ?>">
                <div class="card m-2 hover-shadow custom-card">
                        <?= makeHTMLmedia($thread['media']); ?>
                    <div class="card-body">
                        <h5 class="card-title">
                            <a href="/thread.php?id=<?= htmlspecialchars($thread['id']) ?>" class="stretched-link text-reset text-decoration-none">
                                <?= htmlspecialchars($thread['user_name']) ?>
                            </a> on
                            <a href="/board.php?board_id=<?= htmlspecialchars($thread['board_id']) ?>" target="_blank">
                                <?= htmlspecialchars($thread['board_name']) ?>
                            </a>
                            board
                        </h5>
                        <p class="card-text">
                            <?= htmlspecialchars($body) ?>
                        </p>
                    </div>
                    <div class="card-footer">
                        <small class="text-muted">
                            <?= htmlspecialchars($thread['created_at']) ?> with <?= htmlspecialchars($thread['comment_count']) ?> comments
                        </small>
                    </div>
                </div>
            </a>
        <?php } ?>
    </div>


    <hr>
    <p>you can heck the source code and more about the project on: <a
                href="https://github.com/mohamedsaleh077/santaroso" target="_blank">GitHub</a></p>
    <p class="p-0 m-0">Made with love by an Egyptian. 2025-></p>
</div>

<script src="./assets/jquery-3.7.1.min.js.js"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" crossorigin="anonymous"></script>
<script src="./assets/bootstrap4/js/bootstrap.min.js" crossorigin="anonymous"></script>

</body>
</html>