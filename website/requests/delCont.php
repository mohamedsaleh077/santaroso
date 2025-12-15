<?php
include_once $_SERVER['DOCUMENT_ROOT'] . '/includes/ipCheck.php';
// Enforce only GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET'){
    die("Invalid request method");
}

// Check for required parameters 'id' and 'type'
if (!isset($_GET['id']) || !isset($_GET['type'])){
    die("Missing all required values (id and type)");
}

// Include the necessary setup file
include_once $_SERVER['DOCUMENT_ROOT'] . '/includes/autoload.php';

// Use the required namespaces
use Objects\Dbh;
use Objects\Session;

// Initialize session handling
$session = new Session();

// Check if the user is authenticated as an administrator
if (!isset($_SESSION['adminLogin'])){
    header("Location: ./login.php");
    die();
}

// --- Parameter Validation ---

$id = $_GET['id'];
$type = strtolower($_GET['type']);

// Validate ID: Must be an integer
if (!is_numeric($id) || $id <= 0) {
    die("Missing or invalid content ID");
}
$id = (int)$id; // Cast to integer for safety

// Validate Type: Must be 'p' (post/thread) or 'c' (comment)
if ($type === 'p') {
    $table = 'threads';
    $item_type_id = 'thread'; // Value for users_ips_actions table
    $redirect_msg = 'delThread';
} elseif ($type === 'c') {
    $table = 'comments';
    $item_type_id = 'comment'; // Value for users_ips_actions table
    $redirect_msg = 'delComment';
} else {
    // Die if the type is neither 'p' nor 'c'
    die("Access Denied: Invalid content type");
}

// --- Database Interaction ---

$dbh = Dbh::getInstance();

try {
    // 1. Check if the content exists and fetch the media filename
    // Dbh::Query for a SELECT returns an array of results or false on failure.
    $results = $dbh->Query("SELECT media FROM {$table} WHERE id = :id", [':id' => $id]);

    if ($results === false) {
        // Query failed
        die("Database error during content check.");
    }

    if (empty($results)) {
        // Content does not exist
        die("no content for this id");
    }

    $media_filename = $results[0]['media'];

    // 2. Delete associated media files if a media filename exists
    if (!empty($media_filename)) {
        // We assume the media filename includes the extension (e.g., 'image123.jpg')
        $path_parts = pathinfo($media_filename);
        $base_name = $path_parts['filename']; // e.g., 'image123'

        // Full paths for the primary media file and potential thumbnails
        $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/';
        $media_path = $upload_dir . $media_filename;

        // Attempt to delete primary media file
        if (file_exists($media_path)) {
            if (!unlink($media_path)) {
                error_log("Failed to delete primary media file: {$media_path}");
            }
        }

        // Attempt to delete thumbnail files (.png and .jpg, as per request)
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

    // 3. Delete the record from the content table (threads or comments)
    // Dbh::Query for a DELETE returns the row count or false on failure.
    $delete_content_result = $dbh->Query("DELETE FROM {$table} WHERE id = :id", [':id' => $id]);

    if ($delete_content_result === false) {
        die("Database error occurred while trying to delete the content.");
    }

    // 4. Delete the corresponding record(s) from the users_ips_actions table
    // The item is identified by its ID (ref_id) and type (item_type_id).
    // Dbh::Query for a DELETE returns the row count or false on failure.
    $delete_action_result = $dbh->Query(
        "DELETE FROM users_ips_actions WHERE ref_id = :ref_id AND item_type_id = :item_type_id",
        [':ref_id' => $id, ':item_type_id' => $item_type_id]
    );

    if ($delete_action_result === false) {
        // Log an error but do not die, as the main content was successfully deleted.
        error_log("Failed to delete records from users_ips_actions for ref_id {$id} and type {$item_type_id}.");
    }

    // 5. Redirect back to the admin page with the result message
    header("Location: /admin.php?done=" . $redirect_msg);
    die();

} catch (PDOException $e){
    // This catches PDO-related errors outside of Dbh::Query (though Dbh::Query should catch most)
    error_log($e);
    die("A non-query database error occurred.");
}