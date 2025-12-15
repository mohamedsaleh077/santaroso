<?php
if ($_SERVER['REQUEST_METHOD'] !== 'GET'){
    die("Invalid request method");
}

if (!isset($_GET['id'])){
    die("Missing all required values");
}

include_once $_SERVER['DOCUMENT_ROOT'] . '/includes/autoload.php';

use Objects\Dbh;
use Objects\Session;
use PDO; // Required for fetching modes if Dbh::Query were not used

$session = new Session();

if (!isset($_SESSION['adminLogin'])){
    header("Location: ./login.php");
    die();
}

// --- Helper Function for Media Deletion ---
/**
 * Deletes the primary media file and potential thumbnail files from the /uploads/ directory.
 * @param string $media_filename The filename (e.g., 'image123.jpg').
 */
function deleteMediaFiles($media_filename) {
    if (empty($media_filename)) {
        return;
    }

    // Extract base name for thumbnail checks
    $path_parts = pathinfo($media_filename);
    $base_name = $path_parts['filename'];

    $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/';
    $media_path = $upload_dir . $media_filename;

    // Attempt to delete primary media file
    if (file_exists($media_path)) {
        if (!unlink($media_path)) {
            error_log("Failed to delete primary media file: {$media_path}");
        }
    }

    // Attempt to delete thumbnail files (.png and .jpg, as per previous logic)
    $thumb_paths_to_check = [
        $upload_dir . 'thumb_' . $base_name . '.png',
        $upload_dir . 'thumb_' . $base_name . '.jpg',
    ];

    foreach ($thumb_paths_to_check as $thumb_path) {
        if (file_exists($thumb_path)) {
            if (!unlink($thumb_path)) {
                error_log("Failed to delete thumbnail file: {$thumb_path}");
            }
        }
    }
}
// --- End Helper Function ---

$dbh = Dbh::getInstance();

$id = $_GET['id'];

// Validate ID: Must be an integer and positive
if (!is_numeric($id) || $id <= 0) {
    die("Missing or invalid board id");
}
$board_id = (int)$id;

try {
    // 1. Check if the board exists
    $board_check = $dbh->Query("SELECT id FROM boards WHERE id = :id", [':id' => $board_id]);
    if ($board_check === false) {
        die("Database error during board existence check.");
    }
    if (empty($board_check)) {
        die("Board does not exist.");
    }

    // --- Collect Data for Media and Log Cleanup ---

    // 2. Fetch all related threads to get media and IDs
    $threads = $dbh->Query("SELECT id, media FROM threads WHERE board_id = :id", [':id' => $board_id]);
    if ($threads === false) { die("Database error during threads fetch."); }

    $thread_ids = [];
    $media_to_delete = [];

    foreach ($threads as $thread) {
        $thread_ids[] = (int)$thread['id'];
        if (!empty($thread['media'])) {
            $media_to_delete[] = $thread['media'];
        }
    }

    $comment_ids = [];

    // 3. If threads exist, fetch all related comments to get media and IDs
    if (!empty($thread_ids)) {
        // Construct a safe query with named parameters for the IN clause
        $comment_query_params = [];
        $comment_query = "SELECT id, media FROM comments WHERE thread_id IN (";
        foreach ($thread_ids as $i => $tid) {
            $param_name = ":tid{$i}";
            $comment_query .= $param_name . ($i < count($thread_ids) - 1 ? ',' : '');
            $comment_query_params[$param_name] = $tid;
        }
        $comment_query .= ")";

        $comments = $dbh->Query($comment_query, $comment_query_params);
        if ($comments === false) { die("Database error during comments fetch."); }

        foreach ($comments as $comment) {
            $comment_ids[] = (int)$comment['id'];
            if (!empty($comment['media'])) {
                $media_to_delete[] = $comment['media'];
            }
        }
    }

    // --- Execution of Cleanup ---

    // 4. Cleanup: Delete all media files
    foreach ($media_to_delete as $filename) {
        deleteMediaFiles($filename);
    }

    // 5. Cleanup: Delete logs from users_ips_actions (must happen before main delete)
    if (!empty($thread_ids)) {
        // Delete logs for threads
        $thread_log_params = ['item_type_id' => 'thread'];
        $thread_log_query = "DELETE FROM users_ips_actions WHERE item_type_id = :item_type_id AND ref_id IN (";
        foreach ($thread_ids as $i => $tid) {
            $param_name = ":ref_id_t{$i}";
            $thread_log_query .= $param_name . ($i < count($thread_ids) - 1 ? ',' : '');
            $thread_log_params[$param_name] = $tid;
        }
        $thread_log_query .= ")";
        $dbh->Query($thread_log_query, $thread_log_params);
    }

    if (!empty($comment_ids)) {
        // Delete logs for comments
        $comment_log_params = ['item_type_id' => 'comment'];
        $comment_log_query = "DELETE FROM users_ips_actions WHERE item_type_id = :item_type_id AND ref_id IN (";
        foreach ($comment_ids as $i => $cid) {
            $param_name = ":ref_id_c{$i}";
            $comment_log_query .= $param_name . ($i < count($comment_ids) - 1 ? ',' : '');
            $comment_log_params[$param_name] = $cid;
        }
        $comment_log_query .= ")";
        $dbh->Query($comment_log_query, $comment_log_params);
    }

    // 6. Delete the board (This triggers ON DELETE CASCADE for threads and comments)
    $delete_board_result = $dbh->Query("DELETE FROM boards WHERE id = :id", [':id' => $board_id]);

    if ($delete_board_result === false) {
        die("Database error occurred while trying to delete the board.");
    }

    header("Location: /admin.php?done=delBoard");
    die();

} catch (PDOException $e){
    error_log($e);
    die("Database error");
}
