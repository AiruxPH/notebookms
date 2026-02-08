<?php
include 'includes/db.php';

$tables = ['users', 'categories', 'notes', 'pages'];
foreach ($tables as $t) {
    echo "<h3>Columns in '$t' table:</h3><ul>";
    $res = mysqli_query($conn, "SHOW COLUMNS FROM $t");
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            echo "<li>" . $row['Field'] . " (" . $row['Type'] . ")</li>";
        }
    } else {
        echo "<li>Table not found or error: " . mysqli_error($conn) . "</li>";
    }
    echo "</ul>";
}
?>