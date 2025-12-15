<?php
include_once $_SERVER['DOCUMENT_ROOT'] . '/includes/ipCheck.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
// Ensures the script only processes GET requests
    die("Invalid request method");
}

if (!isset($_GET['ip'])) {
// Checks for the required 'ip' parameter
    die("Missing required IP address");
}

// Include the necessary setup file
include_once $_SERVER['DOCUMENT_ROOT'] . '/includes/autoload.php';

// Use the required namespaces
use Objects\Dbh;
use Objects\Session;

// Initialize session handling (assuming Session class handles session start/management)
$session = new Session();

// Check if the user is authenticated as an administrator
if (!isset($_SESSION['adminLogin'])) {
    header("Location: ./login.php");
    die();
}

// Get the database connection instance
$dbh = Dbh::getInstance();

$ip = $_GET['ip'];

// Validate the IP address using PHP's filter_var
if (!filter_var($ip, FILTER_VALIDATE_IP)) {
    die("Missing or invalid IP address");
}

try {
// 1. Check the current ban status for this IP using the Dbh::Query method.
// The Dbh::Query method for a SELECT returns an array of results or false on failure.
    $results = $dbh->Query("SELECT ban FROM users_ips_actions WHERE ip = :ip LIMIT 1", [':ip' => $ip]);

    $is_currently_banned = false;

// Check if the query returned a valid array result (not false and not empty)
    if (is_array($results) && !empty($results)) {
// The first element of the array contains the row data
        $row = $results[0];
// Cast to boolean to check the status. MySQL BOOLEAN is stored as 0 or 1.
        $is_currently_banned = (bool)$row['ban'];
    } elseif ($results === false) {
// If the query failed and returned false (as defined in Dbh::Query)
        die("Database error occurred while checking IP status.");
    }

// 2. Determine the new status and the redirect message
    $new_ban_status = $is_currently_banned ? 0 : 1; // Toggle status (0 for unban, 1 for ban)
    $redirect_message = $is_currently_banned ? 'userisunbanned' : 'userisbanned';

// 3. Update the ban status for ALL actions associated with this IP
// Dbh::Query for an UPDATE returns the row count or false on failure.
    $update_result = $dbh->Query("UPDATE users_ips_actions SET ban = :status WHERE ip = :ip", [
        ':status' => $new_ban_status,
        ':ip' => $ip
    ]);

    if ($update_result === false) {
        die("Database error occurred while trying to toggle the IP ban status.");
    }

// 4. Redirect back to the admin page with the result message
    header("Location: /admin.php?done=" . $redirect_message);
    die();
} catch (PDOException $e) {
// This catch block might be redundant if Dbh::Query handles all exceptions,
// but it's kept for robustness and style consistency.
    error_log($e);
    die("A non-query database error occurred.");
}