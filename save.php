<?php
/* Verwerkt en slaat enquête antwoorden op in de database */

require_once __DIR__ . '/database.php';
header("Content-Type: application/json");

if (isset($_COOKIE['submission_id'])) {
    echo json_encode(['error' => 'Je hebt dit formulier al ingevuld.']);
    exit;
}

$stmt = $pdo->prepare("SELECT COUNT(*) FROM submissions WHERE student_name = ?");
$stmt->execute([$_POST['student_name']]);
if ($stmt->fetchColumn() > 0) {
    echo json_encode(['success' => false, 'error' => 'Deze naam is al in gebruik.']);
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

if ($name === '' || $class === '' || count($responses) < 16) {
    echo json_encode(['error' => 'Naam, klas of antwoorden ontbreken.']);
    exit;
}

try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("INSERT INTO submissions (student_name, student_class, open_text) VALUES (?, ?, ?)");
    $stmt->execute([$name, $class, $open_text]);
    $submission_id = $pdo->lastInsertId();
    $rstmt = $pdo->prepare("INSERT INTO responses (submission_id, question_number, value) VALUES (?, ?, ?)");
    foreach ($responses as $qnum => $val) {
        $rstmt->execute([$submission_id, $qnum, $val]);
    }

    $pdo->commit();
    setcookie("submission_id", $submission_id, time() + 60 * 60 * 24 * 30, "/");
    echo json_encode(['success' => true, 'submission_id' => (int)$submission_id, 'name' => $name, 'class' => $class]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['error' => 'Opslaan mislukt']);
}