<?php

header("Content-Type: application/json; charset=utf-8");
require_once __DIR__ . '/database.php';

if (!isset($pdo) || !($pdo instanceof PDO)) {
    echo json_encode(['success' => false, 'error' => 'Database connection not available.']);
    exit;
}

if (isset($_COOKIE['submission_id'])) {
    echo json_encode(['success' => false, 'error' => 'Je hebt dit formulier al ingevuld.']);
    exit;
}

$name = isset($_POST['student_name']) ? trim($_POST['student_name']) : '';
$class = isset($_POST['student_klas']) ? trim($_POST['student_klas']) : '';
$open_text = isset($_POST['open_text']) ? trim($_POST['open_text']) : '';
$responses = [];

if (!empty($_POST['responses'])) {
    $decoded = json_decode($_POST['responses'], true);
    if (is_array($decoded)) $responses = $decoded;
}

if ($name === '' || $class === '') {
    echo json_encode(['success' => false, 'error' => 'Naam of klas ontbreekt.']);
    exit;
}

try {

    $classCheck = $pdo->prepare("SELECT id FROM classes WHERE class_name = ?");
    $classCheck->execute([$class]);
    $classRow = $classCheck->fetch();
    if (!$classRow) {
        echo json_encode(['success' => false, 'error' => 'Ongeldige klas geselecteerd.']);
        exit;
    }
    $classes_id = $classRow['id'];

    // Name must be unique
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM submissions WHERE student_name = ?");
    $stmt->execute([$name]);
    if ((int)$stmt->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'error' => 'Deze naam is al in gebruik.']);
        exit;
    }

    $qstmt = $pdo->query("SELECT idquestions, question_number FROM questions");
    $byId = [];
    $byNumber = [];
    $questionNumberById = [];  // NEW
    $totalQuestions = 0;

    while ($q = $qstmt->fetch()) {
        $id = (int)$q['idquestions'];
        $num = isset($q['question_number']) ? (int)$q['question_number'] : 0;

        $byId[$id] = $id;
        if ($num > 0) $byNumber[$num] = $id;

        $questionNumberById[$id] = $num; // NEW

        $totalQuestions++;
    }

    if ($totalQuestions === 0) {
        echo json_encode(['success' => false, 'error' => 'Geen vragen gevonden in de database.']);
        exit;
    }

    $mapped = [];
    $unmapped = [];

    foreach ($responses as $rawKey => $val) {

        $numeric = (int)preg_replace('/\D/', '', (string)$rawKey);
        if ($numeric <= 0) {
            $unmapped[] = (string)$rawKey;
            continue;
        }

        if (isset($byId[$numeric])) {
            $qid = $numeric;
        } elseif (isset($byNumber[$numeric])) {
            $qid = $byNumber[$numeric];
        } else {
            $unmapped[] = (string)$rawKey;
            continue;
        }

        $mapped[(int)$qid] =
            ($val === null || $val === '') ? null : (is_numeric($val) ? (int)$val : $val);
    }

    if (!empty($unmapped)) {
        echo json_encode(['success' => false, 'error' => 'Some responses could not be mapped to questions.', 'unmapped' => $unmapped]);
        exit;
    }

    if (count($mapped) < $totalQuestions) {
        echo json_encode(['success' => false, 'error' => "Niet alle vragen beantwoord of gemapt. Verwacht: $totalQuestions, gemapt: " . count($mapped)]);
        exit;
    }

    $pdo->beginTransaction();

    $insSub = $pdo->prepare("INSERT INTO submissions (student_name, classes_id, open_text) VALUES (?, ?, ?)");
    $insSub->execute([$name, $classes_id, $open_text]);
    $submission_id = (int)$pdo->lastInsertId();

    $rstmt = $pdo->prepare("
        INSERT INTO responses 
            (submission_id, questions_idquestions, question_number, value) 
        VALUES (?, ?, ?, ?)
    ");

    foreach ($mapped as $qid => $value) {
        $qnum = isset($questionNumberById[$qid]) ? $questionNumberById[$qid] : 0;

        if ($value === null) {
            $rstmt->bindValue(1, $submission_id, PDO::PARAM_INT);
            $rstmt->bindValue(2, $qid, PDO::PARAM_INT);
            $rstmt->bindValue(3, $qnum, PDO::PARAM_INT);
            $rstmt->bindValue(4, null, PDO::PARAM_NULL);
            $rstmt->execute();
        } else {
            $rstmt->execute([$submission_id, $qid, $qnum, $value]);
        }
    }

    $pdo->commit();

    setcookie("submission_id", (string)$submission_id, time() + 60 * 60 * 24 * 30, "/");

    echo json_encode(['success' => true, 'submission_id' => $submission_id, 'name' => $name, 'class' => $class]);
    exit;

} catch (Exception $e) {
    if ($pdo instanceof PDO && $pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'error' => 'Opslaan mislukt: ' . $e->getMessage()]);
    exit;
}
?>
