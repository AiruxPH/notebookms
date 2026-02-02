<?php
require 'includes/db.php';
require 'includes/data_access.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
echo "<h1>Debug Note ID: $id</h1>";

if ($id > 0) {
    $note = get_note($id);
    echo "<h2>Note Metadata</h2>";
    echo "<pre>";
    print_r($note);
    echo "</pre>";

    $page1 = get_note_page($id, 1);
    echo "<h2>Page 1 Content (Raw)</h2>";
    echo "Length: " . strlen($page1) . "<br>";
    echo "Content: <textarea style='width:100%; height: 200px;'>" . htmlspecialchars($page1) . "</textarea>";

    // Check pages table directly
    echo "<h2>Pages Table Dump</h2>";
    $query = mysqli_query($conn, "SELECT * FROM pages WHERE note_id = $id");
    while ($row = mysqli_fetch_assoc($query)) {
        echo "<pre>";
        print_r($row);
        echo "</pre>";
    }
} else {
    echo "Pass ?id=NOTE_ID to url";
}
