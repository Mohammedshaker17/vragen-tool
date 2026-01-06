<?php
// File: `views/docent.php` - updated to allow teacher to pick which class to view

session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'docent') {
    header("Location: " . url('login.php'));
    exit;
}

// Ensure we know the current user id (login sets username)
$userId = null;
if (!empty($_SESSION['username'])) {
    $uStmt = $pdo->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
    $uStmt->execute([$_SESSION['username']]);
    $uRow = $uStmt->fetch();
    if ($uRow) $userId = (int)$uRow['id'];
}

// Handle class selection form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['selected_class'])) {
    $selected = (int)$_POST['selected_class'];
    if ($selected > 0) {
        $_SESSION['classes_id'] = $selected;
        // also set human-readable class name
        $cstmt = $pdo->prepare("SELECT class_name FROM classes WHERE id = ? LIMIT 1");
        $cstmt->execute([$selected]);
        $crow = $cstmt->fetch();
        $_SESSION['class'] = $crow ? $crow['class_name'] : '';
    } else {
        // Clear selection
        unset($_SESSION['classes_id']);
        $_SESSION['class'] = '';
    }
    // reload to show selected class students
    header("Location: " . url('views/docent.php'));
    exit;
}

// Fetch classes assigned to this teacher
$assignedClasses = [];
if ($userId) {
    try {
        $cstmt = $pdo->prepare("
            SELECT c.id, c.class_name
            FROM teacher_classes tc
            JOIN classes c ON tc.class_id = c.id
            WHERE tc.user_id = ?
            ORDER BY c.class_name ASC
        ");
        $cstmt->execute([$userId]);
        $assignedClasses = $cstmt->fetchAll();
    } catch (Exception $e) {
        $assignedClasses = [];
    }
}

// Determine active class: prefer session value, fallback to first assigned class
$activeClassId = null;
if (isset($_SESSION['classes_id']) && (int)$_SESSION['classes_id'] > 0) {
    $activeClassId = (int)$_SESSION['classes_id'];
} elseif (!empty($assignedClasses)) {
    $activeClassId = (int)$assignedClasses[0]['id'];
    // keep session in sync
    $_SESSION['classes_id'] = $activeClassId;
    $_SESSION['class'] = $assignedClasses[0]['class_name'];
}

// Handle student deletion (teacher removes a student and all their submissions for the active class)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_student') {
    $studentName = trim($_POST['student_name'] ?? '');
    if ($studentName === '' || $activeClassId === null) {
        // invalid request or no active class
        header("Location: " . url('views/docent.php'));
        exit;
    }

    try {
        $pdo->beginTransaction();

        // Find all submission ids for this student in the active class
        $sstmt = $pdo->prepare("SELECT id FROM submissions WHERE student_name = ? AND classes_id = ?");
        $sstmt->execute([$studentName, $activeClassId]);
        $ids = $sstmt->fetchAll(PDO::FETCH_COLUMN, 0);

        if (!empty($ids)) {
            // safe since values are integers fetched from DB
            $ids = array_map('intval', $ids);
            $in = implode(',', $ids);

            // delete related responses first
            $pdo->exec("DELETE FROM responses WHERE submission_id IN ($in)");

            // then delete submissions
            $pdo->exec("DELETE FROM submissions WHERE id IN ($in)");
        }

        $pdo->commit();
        header("Location: " . url('views/docent.php'));
        exit;
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        // swallow or optionally log; reload page with no crash
        header("Location: " . url('views/docent.php'));
        exit;
    }
}

$students = [];
try {
    if ($activeClassId === null) {
        // no class selected/assigned -> leave students empty
        $students = [];
    } else {
        $stmt = $pdo->prepare("
            SELECT DISTINCT student_name
            FROM submissions
            WHERE classes_id = ?
            ORDER BY student_name ASC
        ");
        $stmt->execute([$activeClassId]);
        while ($row = $stmt->fetch()) {
            $students[] = $row['student_name'];
        }
    }
} catch (Exception $e) {
    $students = [];
}
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
    <h1>Welkom <?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?></h1>
    <form method="post" action="<?php echo url('logout.php'); ?>" style="margin-top:16px;">
        <button type="submit">Uitloggen</button>
    </form>

    <div style="margin-top:18px; display:flex; gap:12px; align-items:center;">
        <h2 style="margin:0;">Klas:</h2>

        <?php if (empty($assignedClasses)): ?>
            <div style="color:#666;">Geen klassen toegewezen. Neem contact op met admin.</div>
        <?php else: ?>
            <form id="classSelectForm" method="post" style="margin:0;">
                <label for="selected_class" class="sr-only">Selecteer klas</label>
                <select name="selected_class" id="selected_class" onchange="document.getElementById('classSelectForm').submit();" >
                    <?php foreach ($assignedClasses as $c): ?>
                        <option value="<?php echo (int)$c['id']; ?>" <?php echo ($activeClassId === (int)$c['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($c['class_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        <?php endif; ?>
    </div>

    <h1 style="font-size: 3rem; margin-top:16px;">Overzicht van klas <?php echo htmlspecialchars($_SESSION['class'] ?? ''); ?></h1>
    <h1 style="font-size: 2rem">Vergelijk je studenten met elkaar.</h1>
    <div class="flex-row">
        <div style="flex:1; min-height: 360px;">
            <canvas id="radarChart"></canvas>
        </div>

        <div style="min-width:260px; max-width:320px;">
            <div class="dropdown" id="studentDropdown" style="margin-bottom:12px;">
                <button type="button" class="dropdown-btn" id="dropdownToggle">Selecteer studenten &#9662;</button>
                <div class="dropdown-content" id="studentCheckboxes">
                    <div style="padding:6px 16px;">
                        <label style="display:flex; align-items:center;">
                            <input type="checkbox" value="__average__" checked>
                            <span style="margin-left:8px;">Gemiddelde klas</span>
                        </label>
                    </div>
                    <?php foreach ($students as $s): ?>
                        <div class="student-row" style="display:flex; align-items:center; justify-content:space-between; padding:6px 12px;">
                            <label style="display:flex; align-items:center; gap:8px;">
                                <input type="checkbox" value="<?php echo htmlspecialchars($s); ?>">
                                <?php echo htmlspecialchars($s); ?>
                            </label>

                            <form method="post" style="margin:0;" onsubmit="return confirm('Weet je het zeker? Dit verwijdert alle inzendingen van deze student voor deze klas.');">
                                <input type="hidden" name="action" value="delete_student">
                                <input type="hidden" name="student_name" value="<?php echo htmlspecialchars($s); ?>">
                                <button type="submit" class="small-btn" style="background:#c0392b;color:#fff;border:none;padding:6px 8px;border-radius:4px;">Verwijder</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <h3 style="margin:6px 0 8px 0;">Open antwoorden (vraag 17)</h3>
            <div id="openResponses" class="responses" aria-live="polite">
                <p style="color:#666;">Selecteer één of meerdere studenten om open antwoorden te zien.</p>
            </div>
        </div>
    </div>
</div>

<script>
    const BASE_URL = '<?php echo BASE_URL; ?>';

    // Dropdown toggle
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

    .student-row {
        border-top: 1px solid #f0f0f0;
    }

    .responses {
        background: #fff;
        border: 1px solid #e0e0e0;
        border-radius: 6px;
        padding: 8px;
        max-height: 420px;
        overflow-y: auto;
        box-shadow: 0 1px 4px rgba(0,0,0,0.04);
    }
    .responses .open-item {
        padding: 8px;
        border-bottom: 1px solid #f0f0f0;
    }
    .responses .open-item:last-child {
        border-bottom: none;
    }
    .responses .open-item strong {
        display: block;
        margin-bottom: 6px;
        color: #333;
    }
    .responses .open-item p {
        margin: 0;
        color: #222;
        white-space: pre-wrap;
    }

    #selected_class {
        padding: 8px;
        border-radius: 6px;
        border: 1px solid #ccc;
        font-size: 1rem;
    }

    @media (max-width: 900px) {
        .flex-row { flex-direction: column; }
        .dropdown { min-width: 100%; }
    }
</style>