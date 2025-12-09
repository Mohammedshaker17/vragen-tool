<?php
header('Content-Type: application/json; charset=utf-8');
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
$name = isset($_GET['name']) ? trim($_GET['name']) : '';
if (isset($_GET['list'])) {
    try {
        $pdo = new PDO($dsn, $user, $pass, $options);
        $stmt = $pdo->query("SELECT DISTINCT student_name FROM submissions ORDER BY student_name ASC");
        $names = [];
        while ($row = $stmt->fetch()) {
            $names[] = $row['student_name'];
        }
        echo json_encode(['success' => true, 'names' => $names]);
    } catch (Exception $e) {
        echo json_encode(['error' => 'Server error']);
    }
    exit;
}
if ($name === '') {
    echo json_encode(['error' => 'Missing name']);
    exit;
}
try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    $stmt = $pdo->prepare("SELECT id, created_at FROM submissions WHERE student_name = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$name]);
    $row = $stmt->fetch();
    if (!$row) {
        echo json_encode(['error' => 'No submission found for this name']);
        exit;
    }
    $submission_id = (int)$row['id'];
    $q = "
        SELECT qm.dimension AS dim, AVG(r.value) AS avg_value
        FROM responses r
        JOIN question_map qm ON r.question_number = qm.question_number
        WHERE r.submission_id = ?
        GROUP BY qm.dimension
    ";
    $stmt = $pdo->prepare($q);
    $stmt->execute([$submission_id]);
    $dims = ['C' => 0, 'A' => 0, 'R' => 0, 'E' => 0];
    while ($r = $stmt->fetch()) {
        $d = strtoupper($r['dim']);
        if (isset($dims[$d])) {
            $dims[$d] = round((float)$r['avg_value'], 2);
        }
    }
    $rstmt = $pdo->prepare("SELECT question_number, value FROM responses WHERE submission_id = ?");
    $rstmt->execute([$submission_id]);
    $raw = $rstmt->fetchAll();
    $avgQuery = "
        SELECT qm.dimension AS dim, AVG(r.value) AS avg_value
        FROM responses r
        JOIN question_map qm ON r.question_number = qm.question_number
        JOIN (
            SELECT student_name, MAX(id) AS latest_id
            FROM submissions
            GROUP BY student_name
        ) s ON r.submission_id = s.latest_id
        GROUP BY qm.dimension
    ";
    $astmt = $pdo->prepare($avgQuery);
    $astmt->execute();
    $overall = ['C' => 0, 'A' => 0, 'R' => 0, 'E' => 0];
    while ($ar = $astmt->fetch()) {
        $d = strtoupper($ar['dim']);
        if (isset($overall[$d])) {
            $overall[$d] = round((float)$ar['avg_value'], 2);
        }
    }
    echo json_encode([
        'success' => true,
        'name' => $name,
        'submission_id' => $submission_id,
        'created_at' => $row['created_at'],
        'individual' => $dims,
        'responses' => $raw,
        'overall' => $overall
    ]);
} catch (Exception $e) {
    echo json_encode(['error' => 'Server error']);
    exit;
}
