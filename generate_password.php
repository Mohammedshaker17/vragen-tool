<?php
/*
 * Password Hash Generator
 * Use this to generate password hashes for manual database insertion
 * Access via: http://your-domain/generate_password.php?password=your_password
 *
 * DELETE THIS FILE after you're done using it! (security risk)
 */

if (isset($_GET['password'])) {
    $password = $_GET['password'];
    $hash = password_hash($password, PASSWORD_DEFAULT);
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Password Hash Generator</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                padding: 20px;
            }

            .hash {
                background: #f0f0f0;
                padding: 10px;
                border-radius: 5px;
                word-break: break-all;
            }

            .warning {
                background: #ffe6e6;
                padding: 10px;
                border-left: 4px solid red;
                margin: 20px 0;
            }
        </style>
    </head>
    <body>
    <h1>Password Hash Generated</h1>
    <p><strong>Original Password:</strong> <?php echo htmlspecialchars($password); ?></p>
    <p><strong>Hashed Password (copy this):</strong></p>
    <div class="hash"><?php echo htmlspecialchars($hash); ?></div>

    <h2>SQL Insert Example:</h2>
    <div class="hash">
        INSERT INTO users (username, password, role, class, classes_id) VALUES<br>
        ('username_here', '<?php echo $hash; ?>', 'docent', 'ICT1A', 1);
    </div>

    <div class="warning">
        <strong>⚠️ SECURITY WARNING:</strong><br>
        Delete this file (generate_password.php) after you're done using it!<br>
        Anyone with access to your website can generate password hashes if this file exists.
    </div>
    </body>
    </html>
    <?php
} else {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Password Hash Generator</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                padding: 20px;
            }

            input {
                padding: 8px;
                width: 300px;
            }

            button {
                padding: 8px 20px;
            }

            .warning {
                background: #ffe6e6;
                padding: 10px;
                border-left: 4px solid red;
                margin: 20px 0;
            }
        </style>
    </head>
    <body>
    <h1>Password Hash Generator</h1>
    <form method="get">
        <label>Enter password to hash:</label><br>
        <input type="text" name="password" required>
        <button type="submit">Generate Hash</button>
    </form>

    <div class="warning">
        <strong>⚠️ SECURITY WARNING:</strong><br>
        Delete this file after you're done using it!
    </div>
    </body>
    </html>
    <?php
}
?>
