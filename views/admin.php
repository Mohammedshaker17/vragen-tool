<?php
session_start();

// Include config from parent directory (we're in views/ folder)
require_once __DIR__ . '/../config.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != "admin") {
    header("Location: " . url('login.php'));
    exit;
}

require_once __DIR__ . '/../database.php';

if (isset($_POST['username'], $_POST['password'], $_POST['class'])) {
    $stmt = $pdo->prepare("INSERT INTO users (username,password,role,class) VALUES (?, ?, 'docent', ?)");
    $stmt->execute([$_POST['username'], password_hash($_POST['password'], PASSWORD_DEFAULT), $_POST['class']]);
}

$stmt = $pdo->query("SELECT username,class FROM users WHERE role='docent'");
$docenten = $stmt->fetchAll();
$stmt = $pdo->query("SELECT class_name FROM classes ORDER BY class_name ASC");
$classes = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>
<!doctype html>
<html lang="nl">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <title>Admin</title>
    <link rel="stylesheet" href="<?php echo url('css/index.css'); ?>">
</head>
<body>
<div class="container">
    <h1>Admin: docenten beheren</h1>
    <form method="post">
        <label>Gebruikersnaam:</label><br>
        <input name="username" required><br>

        <label>Wachtwoord:</label><br>
        <input name="password" type="password" required><br>

        <label>Klas:</label><br>
        <select name="class" required>
            <option value="">-- Selecteer klas --</option>
            <?php foreach ($classes as $c): ?>
                <option value="<?php echo htmlspecialchars($c); ?>"><?php echo htmlspecialchars($c); ?></option>
            <?php endforeach; ?>
        </select><br>

        <button type="submit">Docent toevoegen</button>
    </form>

    <h2>Bestaande docenten</h2>
    <ul>
        <?php foreach ($docenten as $d): ?>
            <li><?php echo htmlspecialchars($d['username']); ?> - <?php echo htmlspecialchars($d['class']); ?></li>
        <?php endforeach; ?>
    </ul>
</div>
</body>
</html>