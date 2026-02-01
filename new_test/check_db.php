<?php
include 'includes/db.php';

$res = mysqli_query($conn, "SHOW COLUMNS FROM notes");
echo "<h3>Columns in 'notes' table:</h3><ul>";
while ($row = mysqli_fetch_assoc($res)) {
    echo "<li>" . $row['Field'] . "</li>";
}
echo "</ul>";
?>