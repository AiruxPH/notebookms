<?php
// verify_normalization.php
require 'includes/db.php';

echo "<h1>Normalization Verification</h1>";

// 1. Check Categories Table
$res = mysqli_query($conn, "SHOW TABLES LIKE 'categories'");
if (mysqli_num_rows($res) > 0) {
    echo "<p style='color:green'>[OK] Table 'categories' exists.</p>";
} else {
    echo "<p style='color:red'>[FAIL] Table 'categories' NOT found.</p>";
}

// 2. Check Notes Table Columns
$res = mysqli_query($conn, "SHOW COLUMNS FROM notes");
$has_cat_id = false;
$has_cat_old = false;
while ($row = mysqli_fetch_assoc($res)) {
    if ($row['Field'] == 'category_id')
        $has_cat_id = true;
    if ($row['Field'] == 'category')
        $has_cat_old = true;
}

if ($has_cat_id) {
    echo "<p style='color:green'>[OK] Column 'category_id' exists in 'notes'.</p>";
} else {
    echo "<p style='color:red'>[FAIL] Column 'category_id' MISSING in 'notes'.</p>";
}

if (!$has_cat_old) {
    echo "<p style='color:green'>[OK] Column 'category' (old) is GONE from 'notes'.</p>";
} else {
    echo "<p style='color:orange'>[WARN] Column 'category' (old) still exists in 'notes'. You should DROP it.</p>";
}

// 3. Check Data Integrity
$res = mysqli_query($conn, "SELECT id, title, category_id FROM notes LIMIT 5");
echo "<h3>Sample Notes:</h3><ul>";
while ($row = mysqli_fetch_assoc($res)) {
    echo "<li>Note ID {$row['id']}: '{$row['title']}' - category_id: " . ($row['category_id'] !== null ? $row['category_id'] : "NULL (Bad!)") . "</li>";
}
echo "</ul>";

echo "<p>Done.</p>";
?>