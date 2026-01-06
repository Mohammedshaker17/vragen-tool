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

        if ($user['role'] === 'docent') {
            // Fetch all class IDs assigned to this teacher from the mapping table
            $tcStmt = $pdo->prepare("SELECT class_id FROM teacher_classes WHERE user_id = ?");
            $tcStmt->execute([$user['id']]);
            $classes = $tcStmt->fetchAll(PDO::FETCH_COLUMN, 0);

            // Normalize to integer array
            $classes_ids = array_map('intval', $classes ?: []);
            $_SESSION['classes_ids'] = $classes_ids;

            // For compatibility with existing code that expects a single classes_id,
            // set the session to the first assigned class (or null if none)
            $_SESSION['classes_id'] = count($classes_ids) ? $classes_ids[0] : null;

            if ($_SESSION['classes_id']) {
                $classStmt = $pdo->prepare("SELECT class_name FROM classes WHERE id = ?");
                $classStmt->execute([$_SESSION['classes_id']]);
                $classRow = $classStmt->fetch();
                $_SESSION['class'] = $classRow ? $classRow['class_name'] : '';
            } else {
                $_SESSION['class'] = '';
            }

            header("Location: " . url('views/docent.php'));
            exit;
        }

        if ($user['role'] === 'admin') {
            header("Location: " . url('views/admin.php'));
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
    <link rel="stylesheet" href="<?php echo url('css/admin.css'); ?>">
</head>
<body>
<div class="container">
    <h1>Login</h1>
    <?php if ($error): ?>
        <p style="color:red;"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>
    <form method="post">
        <label>Gebruikersnaam:</label><br>
        <input id="input-soort" type="text" name="username" required><br>
        <label>Wachtwoord:</label><br>
        <input id="input-soort" type="password" name="password" required><br>
        <button type="submit">Inloggen</button>
    </form>
</div>
</body>
</html>

<style>
    #input-soort{
        width: 50%;
        padding: 8px;
        margin: 8px 0;
        box-sizing: border-box;
    }
</style>