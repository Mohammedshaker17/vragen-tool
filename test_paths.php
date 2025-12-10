<?php
// Place this file in the ROOT directory (same level as config.php)
require_once __DIR__ . '/config.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Path Testing - Vragen Tool</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            background: #f5f5f5;
            max-width: 1200px;
            margin: 0 auto;
        }

        .info {
            background: white;
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
            border-left: 4px solid #4CAF50;
        }

        .error {
            background: white;
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
            border-left: 4px solid #f44336;
        }

        .warning {
            background: white;
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
            border-left: 4px solid #ff9800;
        }

        h1 {
            color: #333;
        }

        h2 {
            color: #666;
            margin-top: 0;
        }

        code {
            background: #eee;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }

        .success {
            color: green;
            font-weight: bold;
        }

        .fail {
            color: red;
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }

        th, td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background: #f0f0f0;
            font-weight: bold;
        }

        a {
            color: #1976D2;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
<h1>🔍 Path Diagnostics - Vragen Tool</h1>

<div class="info">
    <h2>✓ Configuration Loaded Successfully</h2>
    <table>
        <tr>
            <th>Constant</th>
            <th>Value</th>
        </tr>
        <tr>
            <td><strong>BASE_URL</strong></td>
            <td><code><?php echo htmlspecialchars(BASE_URL); ?></code></td>
        </tr>
        <tr>
            <td><strong>BASE_PATH</strong></td>
            <td><code><?php echo htmlspecialchars(BASE_PATH); ?></code></td>
        </tr>
    </table>
</div>

<div class="info">
    <h2>📍 Server Information</h2>
    <table>
        <tr>
            <th>Variable</th>
            <th>Value</th>
        </tr>
        <tr>
            <td>SCRIPT_NAME</td>
            <td><code><?php echo htmlspecialchars($_SERVER['SCRIPT_NAME']); ?></code></td>
        </tr>
        <tr>
            <td>DOCUMENT_ROOT</td>
            <td><code><?php echo htmlspecialchars($_SERVER['DOCUMENT_ROOT']); ?></code></td>
        </tr>
        <tr>
            <td>REQUEST_URI</td>
            <td><code><?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?></code></td>
        </tr>
        <tr>
            <td>HTTP_HOST</td>
            <td><code><?php echo htmlspecialchars($_SERVER['HTTP_HOST']); ?></code></td>
        </tr>
    </table>
</div>

<div class="info">
    <h2>🔗 Generated URLs</h2>
    <table>
        <tr>
            <th>Function Call</th>
            <th>Generated URL</th>
        </tr>
        <tr>
            <td><code>url('css/index.css')</code></td>
            <td><code><?php echo htmlspecialchars(url('css/index.css')); ?></code></td>
        </tr>
        <tr>
            <td><code>url('js/index.js')</code></td>
            <td><code><?php echo htmlspecialchars(url('js/index.js')); ?></code></td>
        </tr>
        <tr>
            <td><code>url('login.php')</code></td>
            <td><code><?php echo htmlspecialchars(url('login.php')); ?></code></td>
        </tr>
        <tr>
            <td><code>url('views/index.php')</code></td>
            <td><code><?php echo htmlspecialchars(url('views/index.php')); ?></code></td>
        </tr>
        <tr>
            <td><code>url('get_scores.php')</code></td>
            <td><code><?php echo htmlspecialchars(url('get_scores.php')); ?></code></td>
        </tr>
    </table>
</div>

<div class="info">
    <h2>📁 File System Checks</h2>
    <table>
        <tr>
            <th>File</th>
            <th>Status</th>
            <th>Full Path</th>
        </tr>
        <?php
        $files_to_check = [
            'config.php',
            'database.php',
            'login.php',
            'grafiek.php',
            'save.php',
            'get_classes.php',
            'get_scores.php',
            'css/index.css',
            'js/index.js',
            'js/grafiek.js',
            'js/docent.js',
            'views/index.php',
            'views/admin.php',
            'views/docent.php'
        ];

        foreach ($files_to_check as $file) {
            $fullPath = BASE_PATH . '/' . $file;
            $exists = file_exists($fullPath);
            echo '<tr>';
            echo '<td><strong>' . htmlspecialchars($file) . '</strong></td>';
            echo '<td>' . ($exists ? '<span class="success">✓ EXISTS</span>' : '<span class="fail">✗ NOT FOUND</span>') . '</td>';
            echo '<td><code>' . htmlspecialchars($fullPath) . '</code></td>';
            echo '</tr>';
        }
        ?>
    </table>
</div>

<div class="info">
    <h2>🗄️ Database Connection Test</h2>
    <?php
    try {
        require_once __DIR__ . '/database.php';
        echo '<p class="success">✓ Database connection successful!</p>';
        echo '<p>Connected to database: <code>' . htmlspecialchars($db) . '</code></p>';
    } catch (Exception $e) {
        echo '<p class="fail">✗ Database connection failed</p>';
        echo '<p>Error: <code>' . htmlspecialchars($e->getMessage()) . '</code></p>';
    }
    ?>
</div>

<div class="info">
    <h2>🚀 Quick Links</h2>
    <p><strong>Use these links to navigate your application:</strong></p>
    <ul>
        <li><a href="<?php echo url('views/index.php'); ?>" target="_blank">📝 Student Survey Form</a> -
            <code><?php echo url('views/index.php'); ?></code></li>
        <li><a href="<?php echo url('login.php'); ?>" target="_blank">🔐 Login Page</a> -
            <code><?php echo url('login.php'); ?></code></li>
        <li><a href="<?php echo url('grafiek.php'); ?>" target="_blank">📊 Graph Page</a> -
            <code><?php echo url('grafiek.php'); ?></code></li>
    </ul>
</div>

<div class="warning">
    <h2>⚠️ Important Notes</h2>
    <ul>
        <li>If you see any "NOT FOUND" files above, make sure you've updated all files with the new versions</li>
        <li>All URLs are automatically generated based on your server configuration</li>
        <li>This works on localhost, live servers, and in subdirectories</li>
        <li>Remove or secure this test file before going to production!</li>
    </ul>
</div>

</body>
</html>