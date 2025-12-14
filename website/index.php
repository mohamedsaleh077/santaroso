<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>SANTAROSO</title>
    <link rel="stylesheet" href="./assets/bootstrap4/css/bootstrap.min.css">
</head>
<body>
<div class="container">
    <h1>SANTAROSO-PROJECT 2026</h1>
    <p>Lightweight Image Board, not useful and just a dump place to post dump things anonymously lmao</p>
    <h3>RULES, YOU MUST FOLLOW IT OR I WILL KICK YOUR IP LMAO</h3>
    <ul>
        <li>No NSFW</li>
        <li>No Spam</li>
        <li>No AI SHIT</li>
        <li>YOU ARE RESPONSIBLE FOR WHAT THE HELL YOU ARE POSTING LMAO.</li>
    </ul>
    <h3>OUR BOARDS!</h3>
    <ul>
    <?php
        include_once $_SERVER['DOCUMENT_ROOT'] . '/includes/autoload.php';
        use Objects\Dbh;

        $query = "SELECT id, name, description FROM boards";
        $dbh = Dbh::getInstance();
        $result = $dbh->Query($query);
        foreach ($result as $board) {
            echo "<li><a href='/board.php?board_id=" . htmlspecialchars($board['id']) . "'>" . htmlspecialchars($board['name']) . "</a> : " . htmlspecialchars($board['description']) ."</li>";
        }
    ?>
    </ul>
</div>
</body>
</html>