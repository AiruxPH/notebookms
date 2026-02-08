<?php
include 'includes/data_access.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['available' => false, 'message' => 'Unauthorized']);
    exit();
}

$username = isset($_GET['username']) ? trim($_GET['username']) : '';
$current_uid = get_current_user_id();

if (strlen($username) < 3) {
    echo json_encode(['available' => false, 'message' => 'Too short (min 3 chars)']);
    exit();
}

if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
    echo json_encode(['available' => false, 'message' => 'Invalid characters (a-z, 0-9, _)']);
    exit();
}

// Check if exists
$username_esc = mysqli_real_escape_string($conn, $username);
$sql = "SELECT user_id FROM users WHERE username = '$username_esc' AND user_id != $current_uid";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) > 0) {
    echo json_encode(['available' => false, 'message' => 'Check: Username taken']);
} else {
    echo json_encode(['available' => true, 'message' => 'Check: Available']);
}
