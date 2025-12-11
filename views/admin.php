<?php
/* Admin pagina voor het beheren van docenten accounts (nieuwe database structuur) */

session_start();

require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/../config.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != "admin") {
    header("Location: " . url('login.php'));
    exit;
}

if (isset($_POST['username'], $_POST['password'], $_POST['classes_id'])) {
    $stmt = $pdo->prepare("INSERT INTO users (username, password, role, classes_id) VALUES (?, ?, 'docent', ?)");
    $stmt->execute([
        $_POST['username'],
        password_hash($_POST['password'], PASSWORD_DEFAULT),
        $_POST['classes_id']
    ]);

    header("Location: " . url("views/admin.php"));
    exit;
}

$stmt = $pdo->query("
    SELECT u.username, c.class_name, u.classes_id 
    FROM users u
    LEFT JOIN classes c ON u.classes_id = c.id
    WHERE u.role='docent'
    ORDER BY u.username
");
$docenten = $stmt->fetchAll();

$stmt = $pdo->query("SELECT id, class_name FROM classes ORDER BY class_name ASC");
$classes = $stmt->fetchAll();
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
        <select id="selecteer-klas" name="classes_id" required>
            <option value="">-- Selecteer klas --</option>
            <?php foreach ($classes as $c): ?>
                <option value="<?php echo htmlspecialchars($c['id']); ?>">
                    <?php echo htmlspecialchars($c['class_name']); ?>
                </option>
            <?php endforeach; ?>
        </select><br>

        <button type="submit">Docent account aanmaken</button>
    </form>

    <h2>Bestaande docenten</h2>
    <ul>
        <?php foreach ($docenten as $d): ?>
            <li>
                <?php echo htmlspecialchars($d['username']); ?> -
                <?php echo htmlspecialchars($d['class_name'] ?? 'Geen klas'); ?>
            </li>
        <?php endforeach; ?>
    </ul>

    <h2>Beheer klassen en vragen</h2>
    <p>Klassen en vragen kunnen alleen via de database worden beheerd.</p>
    <p>Gebruik phpMyAdmin of een andere database tool om:</p>
    <ul>
        <li>Klassen toe te voegen in de <strong>classes</strong> tabel</li>
        <li>Vragen toe te voegen/wijzigen in de <strong>questions</strong> tabel</li>
        <li>Antwoordopties te beheren in de <strong>choices</strong> tabel</li>
    </ul>
</div>
</body>
</html>