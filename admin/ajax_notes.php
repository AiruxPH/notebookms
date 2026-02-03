<?php
include '../includes/data_access.php';
session_start();

if (!is_admin()) {
    die("Unauthorized");
}

$uid = intval($_GET['uid'] ?? 0);
$search = $_GET['q'] ?? '';
$status = $_GET['status'] ?? 'all'; // all, pinned, archived

if ($uid <= 0) {
    die("Invalid User ID");
}

global $conn;
$q_esc = mysqli_real_escape_string($conn, $search);

$sql = "SELECT n.*, c.name as category_name, c.color as category_color 
        FROM notes n 
        LEFT JOIN categories c ON n.category_id = c.id
        WHERE n.user_id = $uid";

if ($search) {
    $sql .= " AND n.title LIKE '%$q_esc%'";
}

if ($status === 'pinned') {
    $sql .= " AND n.is_pinned = 1";
} elseif ($status === 'archived') {
    $sql .= " AND n.is_archived = 1";
}

$sql .= " ORDER BY n.is_pinned DESC, n.date_last DESC";

$result = mysqli_query($conn, $sql);
$notes = [];
while ($row = mysqli_fetch_assoc($result)) {
    $notes[] = $row;
}

if (count($notes) > 0) {
    echo '<table class="admin-table" style="font-size: 13px;">';
    echo '<thead><tr><th>Title</th><th>Category</th><th>Status</th><th>Last Modified</th></tr></thead>';
    echo '<tbody>';
    foreach ($notes as $n) {
        $status_tags = [];
        if ($n['is_pinned'])
            $status_tags[] = '<span style="color: #fbc02d; font-weight: bold;">Pinned</span>';
        if ($n['is_archived'])
            $status_tags[] = '<span style="color: #757575; font-weight: bold;">Archived</span>';
        $status_str = empty($status_tags) ? 'Normal' : implode(', ', $status_tags);

        $cat_style = "background: {$n['category_color']}; padding: 2px 6px; border-radius: 4px; border: 1px solid rgba(0,0,0,0.1);";

        echo '<tr>';
        echo '<td>' . htmlspecialchars($n['title']) . '</td>';
        echo '<td><span style="' . $cat_style . '">' . htmlspecialchars($n['category_name'] ?: 'General') . '</span></td>';
        echo '<td>' . $status_str . '</td>';
        echo '<td>' . date("M j, Y H:i", strtotime($n['date_last'])) . '</td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
} else {
    echo '<p style="text-align: center; padding: 20px; color: #777;">No notes found.</p>';
}
?>