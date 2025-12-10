<?php
/* Toont de persoonlijke grafiek van een student met optie om te vergelijken met klasgemiddelde */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';

$submission_id = isset($_GET['submission_id']) ? (int)$_GET['submission_id'] : 0;

if ($submission_id) {
    $stmt = $pdo->prepare("SELECT student_name, student_class FROM submissions WHERE id = ?");
    $stmt->execute([$submission_id]);
    $row = $stmt->fetch();
    $name = $row ? $row['student_name'] : '';
    $class = $row ? $row['student_class'] : '';
} else {
    $name = isset($_GET['name']) ? trim($_GET['name']) : '';
    $class = '';
    if ($name) {
        $stmt = $pdo->prepare("SELECT student_class FROM submissions WHERE student_name=? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$name]);
        $r = $stmt->fetch();
        $class = $r ? $r['student_class'] : '';
    }
}
?>
<!doctype html>
<html lang="nl">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <title>Jouw grafiek</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="<?php echo url('css/grafiek.css'); ?>">
</head>
<body>
<div class="container">
    <h1>Jouw persoonlijke grafiek</h1>
    <p>
        Naam: <strong id="studentName"><?php echo htmlspecialchars($name); ?></strong><br>
    </p>

    <label for="compareName">Vergelijk met klasgemiddelde:</label>
    <select id="compareName">
        <option value="">Geen vergelijking</option>
        <?php if ($class): ?>
            <option value="__average__">Vergelijk met <?php echo htmlspecialchars($class); ?></option>
        <?php endif; ?>
    </select>
    <span id="compareLabel"></span>
    <canvas id="radarChart"></canvas>
</div>

<script>
    const BASE_URL = '<?php echo BASE_URL; ?>';
    const studentName = <?php echo json_encode($name); ?>;
    const studentClass = <?php echo json_encode($class); ?>;
</script>
<script src="<?php echo url('js/grafiek.js'); ?>"></script>
</body>
</html>