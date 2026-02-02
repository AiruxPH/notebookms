<?php
// Send headers to prevent this page from being cached itself
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="favicon.png?v=<?php echo time(); ?>" type="image/png">
    <title>System Refresh - Notebook</title>
    <style>
        body {
            font-family: 'Courier New', Courier, monospace;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
            background: #fdfdad;
            /* Matches app theme */
            color: #333;
            text-align: center;
        }

        .spinner {
            border: 4px solid rgba(0, 0, 0, 0.1);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border-left-color: #333;
            animation: spin 1s linear infinite;
            margin-bottom: 20px;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }
    </style>
</head>

<body>
    <div class="spinner"></div>
    <div style="font-size: 18px; font-weight: bold;">System Refresh</div>
    <div id="status" style="font-size: 14px; margin-top: 10px; color: #666;">Initializing...</div>

    <script>
        const status = document.getElementById('status');

        // Step 1: Clear Local Storage (Fixes stuck UI states)
        try {
            status.innerText = "Clearing local settings...";
            localStorage.clear();
            sessionStorage.clear();
        } catch (e) {
            console.error(e);
        }

        // Step 2: Force Redirect with Cache Busting
        setTimeout(() => {
            status.innerText = "Redirecting to Dashboard...";
            // Add timestamp to force browser to re-request the page
            window.location.replace("dashboard.php?fresh=" + Date.now());
        }, 800);
    </script>
</body>

</html>