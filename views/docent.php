<?php
/* Overzichtspagina voor docenten om studenten uit hun klas te selecteren en grafieken te bekijken */

session_start();

require_once __DIR__ . '/../config.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != "docent") {
    header("Location: " . url('login.php'));
    exit;
}

require_once __DIR__ . '/../database.php';

$stmt = $pdo->prepare("SELECT student_name FROM submissions WHERE student_class=? GROUP BY student_name ORDER BY student_name ASC");
$stmt->execute([$_SESSION['class']]);
$students = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>
<!doctype html>
<html lang="nl">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <title>Klassen overzicht</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="<?php echo url('css/grafiek.css'); ?>">
</head>
<body>
<div class="container">
    <h1 style="font-size: 3rem">Overzicht van klas <?php echo htmlspecialchars($_SESSION['class']); ?></h1>
    <h1 style="font-size: 2rem">Vergelijk je studenten met elkaar.</h1>
    <select id="selectStudent">
        <option value="">-- Selecteer student --</option>
        <?php foreach ($students as $s): ?>
            <option value="<?php echo htmlspecialchars($s); ?>"><?php echo htmlspecialchars($s); ?></option>
        <?php endforeach; ?>
    </select>
    <canvas id="radarChart"></canvas>
</div>

<script>
    const BASE_URL = '<?php echo BASE_URL; ?>';
</script>
<script src="<?php echo url('js/docent.js'); ?>"></script>
</body>
</html>