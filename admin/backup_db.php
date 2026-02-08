<?php
// admin/backup_db.php
// Script to be run via Cron Job (e.g., every 15 mins)

// Adjust path to db.php since we are in admin/ folder
include '../includes/db.php';

// Configuration
$backup_dir = __DIR__ . '/backups/';
$retention_days = 3; // Keep backups for 3 days

// Create Directory if not exists
if (!file_exists($backup_dir)) {
    mkdir($backup_dir, 0755, true);
    // Create .htaccess to protect backups from public access
    file_put_contents($backup_dir . '.htaccess', 'Deny from all');
}

// Ensure we have DB connection
if (!$conn) {
    die("Database connection failed.");
}

// filename
$filename = 'db_backup_' . date('Y-m-d_H-i-s') . '.sql';
$filepath = $backup_dir . $filename;

// 1. Get All Tables
$tables = [];
$result = mysqli_query($conn, "SHOW TABLES");
while ($row = mysqli_fetch_row($result)) {
    $tables[] = $row[0];
}

$return = "";

// 2. Cycle through each table
foreach ($tables as $table) {
    $result = mysqli_query($conn, "SELECT * FROM $table");
    $num_fields = mysqli_num_fields($result);

    // Drop Table
    $return .= "DROP TABLE IF EXISTS `$table`;\n";

    // Create Table Structure
    $row2 = mysqli_fetch_row(mysqli_query($conn, "SHOW CREATE TABLE $table"));
    $return .= "\n\n" . $row2[1] . ";\n\n";

    // Insert Data
    for ($i = 0; $i < $num_fields; $i++) {
        while ($row = mysqli_fetch_row($result)) {
            $return .= "INSERT INTO `$table` VALUES(";
            for ($j = 0; $j < $num_fields; $j++) {
                $row[$j] = addslashes($row[$j]);
                $row[$j] = str_replace("\n", "\\n", $row[$j]);
                if (isset($row[$j])) {
                    $return .= '"' . $row[$j] . '"';
                } else {
                    $return .= '""';
                }
                if ($j < ($num_fields - 1)) {
                    $return .= ',';
                }
            }
            $return .= ");\n";
        }
    }
    $return .= "\n\n\n";
}

// 3. Save File
if (file_put_contents($filepath, $return)) {
    echo "Backup created successfully: $filename<br>";
} else {
    echo "Error creating backup file.<br>";
}

// 4. Retention Policy (Cleanup)
$files = glob($backup_dir . "*.sql");
$now = time();

foreach ($files as $file) {
    if (is_file($file)) {
        if ($now - filemtime($file) >= 60 * 60 * 24 * $retention_days) { // 3 days
            unlink($file);
            echo "Deleted old backup: " . basename($file) . "<br>";
        }
    }
}

echo "Backup process completed.";
?>