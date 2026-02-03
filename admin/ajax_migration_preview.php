<?php
include '../includes/data_access.php';
session_start();

if (!is_admin()) {
    die("Unauthorized");
}

$from_user = $_GET['from'] ?? '';

if (empty($from_user)) {
    die("Source user required");
}

$user = get_user_by_username($from_user);

if (!$user) {
    echo '<p style="color: red;">Source user not found.</p>';
    exit();
}

$summary = get_user_migration_summary($user['id']);

if ($summary['note_count'] === 0 && $summary['category_count'] === 0) {
    echo '<div style="padding: 20px; text-align: center; background: #fff3e0; border: 1px solid #ffe0b2; border-radius: 4px;">';
    echo '<strong>No data to move.</strong><br>User "' . htmlspecialchars($from_user) . '" has no notes or custom categories.';
    echo '</div>';
    echo '<input type="hidden" id="can_migrate" value="0">';
} else {
    echo '<div style="margin-bottom: 15px;">';
    echo '<p>Are you sure you want to move <strong>' . $summary['note_count'] . '</strong> notes and <strong>' . $summary['category_count'] . '</strong> categories?</p>';
    echo '</div>';

    if ($summary['note_count'] > 0) {
        echo '<div style="max-height: 200px; overflow-y: auto; background: #f9f9f9; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px;">';
        echo '<ul style="margin: 0; padding-left: 20px;">';
        foreach ($summary['notes'] as $title) {
            echo '<li>' . htmlspecialchars($title) . '</li>';
        }
        echo '</ul>';
        echo '</div>';
    }
    echo '<input type="hidden" id="can_migrate" value="1">';
}
?>