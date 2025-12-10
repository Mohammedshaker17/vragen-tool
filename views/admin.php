<?php
/* Admin pagina voor het beheren van docenten accounts */

session_start();

require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../config.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != "admin") {
    header("Location: " . url('login.php'));
    exit;
}

if (isset($_POST['username'], $_POST['password'], $_POST['class'])) {
    $stmt = $pdo->prepare("INSERT INTO users (username,password,role,class) VALUES (?, ?, 'docent', ?)");
    $stmt->execute([
        $_POST['username'],
        password_hash($_POST['password'], PASSWORD_DEFAULT),
        $_POST['class']
    ]);

    header("Location: " . url("views/admin.php"));
    exit;
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
    <link rel="stylesheet" href="<?php echo url('css/admin.css'); ?>">
</head>
<body>
<div class="container">
    <h1>Welkom admin.</h1>
    <form method="post" action="<?php echo url('logout.php'); ?>" style="margin-top:16px;">
        <button type="submit">Uitloggen</button>
    </form>
    <h3>Hier kan jij accounts aanmaken voor de docenten, en die verbinden aan de klassen zodat de docent hun klas
        grafiek kan bekijken.</h3>
    <form method="post">
        <label>Kies een gebruikersnaam:</label><br>
        <input id="input-soort" name="username" required><br>

        <label>Maak een wachtwoord:</label><br>
        <input id="input-soort" name="password" type="password" required><br>

        <label>Verbind de docent met een klas:</label><br>
        <select id="selecteer-klas" name="class" required>
            <option value="">-- Selecteer klas --</option>
            <?php foreach ($classes as $c): ?>
                <option value="<?php echo htmlspecialchars($c); ?>"><?php echo htmlspecialchars($c); ?></option>
            <?php endforeach; ?>
        </select><br>

        <button type="submit">Docent account aanmaken</button>
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