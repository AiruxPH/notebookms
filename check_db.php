<?php
include 'includes/db.php';
?>
<!DOCTYPE html>
<html>

<head>
    <title>Database Schema Check - Notebook-BAR</title>
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <style>
        .schema-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            overflow: hidden;
        }

        .schema-header {
            background: #f5f5f5;
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
            font-weight: bold;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .schema-table {
            width: 100%;
            border-collapse: collapse;
        }

        .schema-table th,
        .schema-table td {
            padding: 12px 20px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .schema-table th {
            background: #fafafa;
            color: #666;
            font-size: 13px;
            text-transform: uppercase;
        }

        .schema-table tr:last-child td {
            border-bottom: none;
        }

        .type-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-family: monospace;
            background: #e3f2fd;
            color: #1565c0;
        }

        .key-badge {
            background: #fff8e1;
            color: #f57f17;
            font-weight: bold;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 11px;
            border: 1px solid #ffecb3;
        }
    </style>
</head>

<body style="background-color: #f9f9f9;">

    <header>
        <div class="header-inner">
            <h1><a href="dashboard.php">Notebook-BAR</a> <span
                    style="font-size: 14px; font-weight: normal; opacity: 0.7;">Database Check</span></h1>
            <nav>
                <a href="dashboard.php">Dashboard</a>
                <a href="index.php">Notes</a>
            </nav>
        </div>
    </header>

    <div class="container" style="max-width: 1000px; margin: 30px auto;">

        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2>Database Schema Inspection</h2>
            <a href="dashboard.php" class="btn btn-primary">Back to Dashboard</a>
        </div>

        <?php
        $tables = ['users', 'categories', 'notes', 'pages'];

        foreach ($tables as $t) {
            // Get Table Status (Row Count, Size)
            $status_res = mysqli_query($conn, "SHOW TABLE STATUS LIKE '$t'");
            $status = mysqli_fetch_assoc($status_res);
            $rows = $status ? $status['Rows'] : '?';
            $engine = $status ? $status['Engine'] : '?';

            echo "<div class='schema-card'>";
            echo "<div class='schema-header'>";
            echo "<span><i class='fa-solid fa-table'></i> Table: <span style='color: #2e7d32; font-size: 1.1em;'>$t</span></span>";
            echo "<span style='font-size: 0.9em; color: #888;'>Rows: <b>$rows</b> | Engine: $engine</span>";
            echo "</div>";

            echo "<table class='schema-table'>";
            echo "<thead><tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr></thead>";
            echo "<tbody>";

            $res = mysqli_query($conn, "SHOW COLUMNS FROM $t");
            if ($res) {
                while ($row = mysqli_fetch_assoc($res)) {
                    echo "<tr>";
                    echo "<td><b>" . htmlspecialchars($row['Field']) . "</b></td>";
                    echo "<td><span class='type-badge'>" . htmlspecialchars($row['Type']) . "</span></td>";
                    echo "<td>" . htmlspecialchars($row['Null']) . "</td>";

                    $key = $row['Key'];
                    if ($key == 'PRI')
                        $key = "<span class='key-badge'>PRIMARY</span>";
                    elseif ($key == 'MUL')
                        $key = "<span class='key-badge' style='background:#f3e5f5; color:#7b1fa2; border-color:#e1bee7;'>INDEX</span>";
                    elseif ($key == 'UNI')
                        $key = "<span class='key-badge' style='background:#e0f2f1; color:#00695c; border-color:#b2dfdb;'>UNIQUE</span>";

                    echo "<td>" . $key . "</td>";
                    echo "<td>" . ($row['Default'] === null ? '<span style="color:#ccc;">NULL</span>' : htmlspecialchars($row['Default'])) . "</td>";
                    echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='6' style='color: red; text-align: center;'>Error: " . mysqli_error($conn) . "</td></tr>";
            }

            echo "</tbody></table>";
            echo "</div>";
        }
        ?>

        <div style="text-align: center; margin-top: 40px; color: #999; font-size: 13px;">
            Notebook-BAR Database Diagnostic Tool
        </div>

    </div>

</body>

</html>