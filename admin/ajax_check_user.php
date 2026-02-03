<?php
include '../includes/data_access.php';
session_start();

if (!is_admin()) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$username = $_GET['username'] ?? '';

header('Content-Type: application/json');

if (empty($username)) {
    echo json_encode(['exists' => false, 'role' => null]);
    exit();
}

$user = get_user_by_username($username);

if ($user) {
    echo json_encode(['exists' => true, 'role' => $user['role']]);
} else {
    echo json_encode(['exists' => false, 'role' => null]);
}
exit();
?>