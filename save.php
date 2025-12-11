<?php
/* Verwerkt en slaat enquête antwoorden op in de database (nieuwe structuur) */

require_once __DIR__ . '/database.php';
header("Content-Type: application/json");

if (isset($_COOKIE['submission_id'])) {
    echo json_encode(['error' => 'Je hebt dit formulier al ingevuld.']);
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
    echo json_encode(['error' => 'Naam of klas ontbreekt.']);
    exit;
}

try {
    $classCheck = $pdo->prepare("SELECT id FROM classes WHERE class_name = ?");
    $classCheck->execute([$class]);
    $classRow = $classCheck->fetch();

    if (!$classRow) {
        echo json_encode(['error' => 'Ongeldige klas geselecteerd.']);
        exit;
    }
    $classes_id = $classRow['id'];

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM submissions WHERE student_name = ?");
    $stmt->execute([$name]);
    if ($stmt->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'error' => 'Deze naam is al in gebruik.']);
        exit;
    }

    $questionCount = $pdo->query("SELECT COUNT(*) FROM questions")->fetchColumn();
    if (count($responses) < $questionCount) {
        echo json_encode(['error' => "Niet alle vragen zijn beantwoord. Verwacht: $questionCount, ontvangen: " . count($responses)]);
        exit;
    }

    $pdo->beginTransaction();

    $stmt = $pdo->prepare("INSERT INTO submissions (student_name, classes_id, open_text) VALUES (?, ?, ?)");
    $stmt->execute([$name, $classes_id, $open_text]);
    $submission_id = $pdo->lastInsertId();

    $rstmt = $pdo->prepare("
        INSERT INTO responses (submission_id, questions_idquestions, value) 
        SELECT ?, q.id, ?
        FROM questions q
        WHERE q.question_number = ?
    ");

    foreach ($responses as $qnum => $val) {
        $rstmt->execute([$submission_id, $val, $qnum]);
    }

    $pdo->commit();
    setcookie("submission_id", $submission_id, time() + 60 * 60 * 24 * 30, "/");
    echo json_encode([
        'success' => true,
        'submission_id' => (int)$submission_id,
        'name' => $name,
        'class' => $class
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['error' => 'Opslaan mislukt: ' . $e->getMessage()]);
}