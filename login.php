<?php
session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';

$error = '';

if (isset($_POST['username'], $_POST['password'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$_POST['username']]);
    $user = $stmt->fetch();
    if ($user && password_verify($_POST['password'], $user['password'])) {
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        if ($user['role'] === 'docent') $_SESSION['class'] = $user['class'];

        if ($user['role'] === 'admin') {
            header("Location: " . url('views/admin.php'));
            exit;
        }
        if ($user['role'] === 'docent') {
            header("Location: " . url('views/docent.php'));
            exit;
        }
    } else {
        $error = "Ongeldige gebruikersnaam of wachtwoord";
    }
}
?>
<!doctype html>
<html lang="nl">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <title>Login</title>
    <link rel="stylesheet" href="<?php echo url('css/index.css'); ?>">
</head>
<body>
<div class="container">
    <h1>Login</h1>
    <?php if ($error): ?>
        <p style="color:red;"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>
    <form method="post">
        <label>Gebruikersnaam:</label><br>
        <input type="text" name="username" required><br>
        <label>Wachtwoord:</label><br>
        <input type="password" name="password" required><br>
        <button type="submit">Inloggen</button>
    </form>
</div>
</body>
</html>