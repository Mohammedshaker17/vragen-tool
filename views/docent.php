<?php
/* Overzichtspagina voor docenten (nieuwe database structuur) */

session_start();

require_once __DIR__ . '/../config.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != "docent") {
    header("Location: " . url('login.php'));
    exit;
}

require_once __DIR__ . '/../database.php';

$stmt = $pdo->prepare("
    SELECT DISTINCT s.student_name 
    FROM submissions s
    WHERE s.classes_id = ?
    ORDER BY s.student_name ASC
");
$stmt->execute([$_SESSION['classes_id']]);
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
    <h1>Welkom <?php echo htmlspecialchars($_SESSION['username']); ?></h1>
    <form method="post" action="<?php echo url('logout.php'); ?>" style="margin-top:16px;">
        <button type="submit">Uitloggen</button>
    </form>
    <h1 style="font-size: 3rem">Overzicht van klas <?php echo htmlspecialchars($_SESSION['class']); ?></h1>
    <h1 style="font-size: 2rem">Vergelijk je studenten met elkaar.</h1>
    <div class="flex-row">
        <div style="flex:1;">
            <canvas id="radarChart"></canvas>
        </div>
        <div class="dropdown" id="studentDropdown">
            <button type="button" class="dropdown-btn" id="dropdownToggle">Selecteer studenten &#9662;</button>
            <div class="dropdown-content" id="studentCheckboxes">
                <label>
                    <input type="checkbox" value="__average__" checked>
                    Gemiddelde klas
                </label>
                <?php foreach ($students as $s): ?>
                    <label>
                        <input type="checkbox" value="<?php echo htmlspecialchars($s); ?>">
                        <?php echo htmlspecialchars($s); ?>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script>
    const BASE_URL = '<?php echo BASE_URL; ?>';

    const dropdown = document.getElementById('studentDropdown');
    const toggleBtn = document.getElementById('dropdownToggle');
    toggleBtn.addEventListener('click', function(e) {
        dropdown.classList.toggle('open');
    });
    document.addEventListener('click', function(e) {
        if (!dropdown.contains(e.target)) dropdown.classList.remove('open');
    });
</script>
<script src="<?php echo url('js/docent.js'); ?>"></script>
</body>
</html>

<style>
    .flex-row {
        display: flex;
        flex-direction: row;
        align-items: flex-start;
        gap: 24px;
    }
    .dropdown {
        position: relative;
        min-width: 220px;
    }
    .dropdown-btn {
        width: 100%;
        padding: 10px 12px;
        background: #a02b64;
        color: #fff;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-size: 1rem;
        text-align: left;
    }
    .dropdown-content {
        display: none;
        position: absolute;
        right: 0;
        top: 110%;
        background: #fff;
        min-width: 220px;
        max-height: 320px;
        overflow-y: auto;
        box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        border-radius: 6px;
        z-index: 10;
        padding: 8px 0;
    }
    .dropdown.open .dropdown-content {
        display: block;
    }
    .dropdown-content label {
        display: flex;
        align-items: center;
        padding: 6px 16px;
        cursor: pointer;
        font-size: 1rem;
    }
    .dropdown-content label:hover {
        background: #f0f0f0;
    }
    .dropdown-content input[type=checkbox] {
        margin-right: 8px;
    }
    @media (max-width: 900px) {
        .flex-row { flex-direction: column; }
        .dropdown { min-width: 100%; }
    }
</style>