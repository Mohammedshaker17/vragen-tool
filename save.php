<?php
$host = '127.0.0.1';
$db = 'studentregie';
$user = 'root';
$pass = 'Nick2008!';
$charset = 'utf8mb4';
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];
header("Content-Type: application/json");

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (Exception $e) {
    echo json_encode(['error' => 'DB connect failed']);
    exit;
}

if (isset($_COOKIE['submitted'])) {
    echo json_encode(['error' => 'Je hebt dit formulier al ingevuld. Probeer het later opnieuw.']);
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
    setcookie("submitted", "1", time() + 60 * 60 * 12);
    echo json_encode([
        'success' => true,
        'submission_id' => (int)$submission_id,
        'name' => $name,
        'class' => $class
    ]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['error' => 'Opslaan mislukt']);
}
