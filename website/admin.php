<?php
include_once $_SERVER['DOCUMENT_ROOT'] . '/includes/ipCheck.php';
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
    <script src="https://cdn.tailwindcss.com"></script>
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
                <li>Threads: <?= $countThreads[0]['num'] ?></li>
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
                <input type="text" class="form-control mb-2" name="name" placeholder="board name" required
                       maxlength="250">
                <textarea class="form-control" id="exampleFormControlTextarea1" rows="2" maxlength="1000"
                          name="disp" placeholder="say something about this board"></textarea>
                <button type="submit" class="btn btn-primary mt-2" style="max-width: 200px">create</button>
            </form>
            <hr>
            <h3>list of boards</h3>
            <ul>
                <?php
                $boards = $pdo->Query("SELECT * FROM boards");
                foreach ($boards as $board) {
                    ?>
                        <div>
                            <li><?= $board['name'] ?> : <?= $board['description'] ?> :
                                <a class="btn btn-primary"
                                   href="./requests/delBoard.php?id=<?= $board['id'] ?>">Delete</a>
                            </li>
                        </div>
                <?php } ?>
            </ul>
        </div>
        <div class="tab-pane fade" id="pills-content" role="tabpanel" aria-labelledby="pills-content">
            <?php
            $query = "SELECT
                            u.id AS action_id,
                            u.ip,
                            u.ban,
                            u.item_type_id,
                            u.ref_id,
                            COALESCE(t.user_name, c.user_name) AS name,
                            COALESCE(t.body, c.body) AS body,
                            COALESCE(t.media, c.media) AS media
                        FROM
                            users_ips_actions u
                        LEFT JOIN
                            threads t ON u.ref_id = t.id AND u.item_type_id = 'thread'
                        LEFT JOIN
                            comments c ON u.ref_id = c.id AND u.item_type_id = 'comment';";
            $results = $pdo->query($query);
            ?>
            <div class="max-w-7xl mx-auto bg-white shadow-xl rounded-xl p-4 md:p-6">
                <h1 class="text-3xl font-bold text-gray-800 mb-6 border-b pb-2">User Content & Action Log</h1>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-indigo-600 text-black">
                        <tr>
                            <th scope="col"
                                class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wider rounded-tl-lg">
                                ID
                            </th>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wider">IP
                                Address
                            </th>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wider">
                                Type / Ref ID
                            </th>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wider">
                                User Name
                            </th>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wider">
                                Content Body
                            </th>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wider">
                                Media
                            </th>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wider">Ban
                                Status
                            </th>
                            <th scope="col"
                                class="px-3 py-3 text-left text-xs font-medium uppercase tracking-wider rounded-tr-lg">
                                Actions
                            </th>
                        </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                        <?php
                        // Check if results exist and iterate over them
                        if (!empty($results)) {
                            foreach ($results as $row) {
                                // Determine the content type code for the delete link
                                $item_type = htmlspecialchars($row['item_type_id'] ?? 'unknown');
                                $ref_id = htmlspecialchars($row['ref_id'] ?? '');

                                // 'c' for comment, 'p' for thread/post (as per user request)
                                $type_code = ($item_type === 'comment') ? 'c' : 'p';

                                // Construct the DELETE URL, ensuring parameters are safely encoded
                                $delete_url = "./requests/delCont.php?id=" . urlencode($ref_id) . "&type=" . urlencode($type_code);

                                // Construct the BAN URL, ensuring the IP is safely encoded
                                $ip = htmlspecialchars($row['ip'] ?? '');
                                $ban_url = "./requests/ban.php?ip=" . urlencode($ip);

                                // Ban status color
                                $ban_status = ($row['ban'] == 1)
                                        ? '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-danger">Banned</span>'
                                        : '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-success">Active</span>';

                                // Media info display
                                $media_display = $row['media'] ? 'Yes' : 'No';

                                // --- Logic to truncate body content to 100 characters ---
                                $raw_body = $row['body'] ?? '';
                                $display_body = $raw_body;
                                if (strlen($raw_body) > 100) {
                                    $display_body = substr($raw_body, 0, 100) . '...';
                                }
                                // --- End Truncation Logic ---

                                ?>
                                <tr>
                                    <td class="px-3 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($row['action_id'] ?? 'N/A'); ?>
                                    </td>
                                    <td class="px-3 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo $ip; ?>
                                    </td>
                                    <td class="px-3 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <span class="font-semibold capitalize"><?php echo $item_type; ?></span>
                                        (ID: <?php echo $ref_id; ?>)
                                    </td>
                                    <td class="px-3 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo htmlspecialchars($row['name'] ?? 'N/A'); ?>
                                    </td>
                                    <td class="px-3 py-4 text-sm text-gray-500 content-cell"
                                        title="<?php echo htmlspecialchars($raw_body); /* Full body in title */ ?>">
                                        <?php echo htmlspecialchars($display_body); /* Truncated body */ ?>
                                    </td>
                                    <td class="px-3 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo $media_display; ?>
                                    </td>
                                    <td class="px-3 py-4 whitespace-nowrap">
                                        <?php echo $ban_status; ?>
                                    </td>
                                    <td class="px-3 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                        <!-- Delete Button/Link -->
                                        <a href="<?php echo $delete_url; ?>"
                                           class="text-red-600 hover:text-red-900 bg-red-50 hover:bg-red-100 px-3 py-1 rounded-lg transition duration-150">
                                            Delete
                                        </a>

                                        <!-- Ban Button/Link (Only show if not already banned) -->
                                        <?php if ($row['ban'] == 0) : ?>
                                            <a href="<?php echo $ban_url; ?>"
                                               class="text-indigo-600 hover:text-indigo-900 bg-indigo-50 hover:bg-indigo-100 px-3 py-1 rounded-lg transition duration-150">
                                                Ban IP
                                            </a>
                                        <?php else : ?>
                                            <a href="<?php echo $ban_url; ?>"
                                               class="text-indigo-600 hover:text-indigo-900 bg-indigo-50 hover:bg-indigo-100 px-3 py-1 rounded-lg transition duration-150">
                                                unban
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php
                            }
                        } else {
                            // Display a message if no results are found
                            echo '<tr><td colspan="8" class="px-6 py-4 text-center text-gray-500">No actions found.</td></tr>';
                        }
                        ?>
                        </tbody>
                    </table>
                </div>

            </div>
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
    <?php } else if (isset($_GET['done']) && $_GET['done'] == "userisbanned") { ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <strong>Done!</strong> user is banned!
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
