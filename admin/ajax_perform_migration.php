<?php
include '../includes/data_access.php';
session_start();

if (!is_admin()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$from_user = $_POST['from_username'] ?? '';
$to_user = $_POST['to_username'] ?? '';

header('Content-Type: application/json');

if (empty($from_user) || empty($to_user)) {
    echo json_encode(['success' => false, 'message' => 'Both usernames required.']);
    exit();
}

$source = get_user_by_username($from_user);
$target = get_user_by_username($to_user);

if (!$source || !$target) {
    echo json_encode(['success' => false, 'message' => 'User not found.']);
    exit();
}

if ($source['role'] === 'admin' || $target['role'] === 'admin') {
    echo json_encode(['success' => false, 'message' => 'Admin accounts are restricted.']);
    exit();
}

if ($source['id'] == $target['id']) {
    echo json_encode(['success' => false, 'message' => 'Source and target must be different.']);
    exit();
}

// Get counts for victory message
$summary = get_user_migration_summary($source['id']);
$note_count = $summary['note_count'];
$cat_count = $summary['category_count'];

if (migrate_user_data($source['id'], $target['id'])) {
    echo json_encode([
        'success' => true,
        'message' => "Successfully moved $note_count notes and $cat_count categories!",
        'counts' => ['notes' => $note_count, 'categories' => $cat_count]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Migration failed.']);
}
exit();
?>