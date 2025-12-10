<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/database.php';

$name = isset($_GET['name']) ? trim($_GET['name']) : '';

if ($name === '') {
    echo json_encode(['error' => 'Missing name']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id, student_class, created_at FROM submissions WHERE student_name = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$name]);
    $row = $stmt->fetch();
    if (!$row) {
        echo json_encode(['error' => 'No submission found']);
        exit;
    }

    $submission_id = (int)$row['id'];
    $student_class = $row['student_class'];
    $stmt = $pdo->prepare("SELECT qm.dimension AS dim, AVG(r.value) AS avg_value FROM responses r JOIN question_map qm ON r.question_number = qm.question_number WHERE r.submission_id = ? GROUP BY qm.dimension");
    $stmt->execute([$submission_id]);
    $dims = ['C' => 0, 'A' => 0, 'R' => 0, 'E' => 0];
    while ($r = $stmt->fetch()) {
        $dims[strtoupper($r['dim'])] = round((float)$r['avg_value'], 2);
    }

    $rstmt = $pdo->prepare("SELECT question_number,value FROM responses WHERE submission_id = ?");
    $rstmt->execute([$submission_id]);
    $raw = $rstmt->fetchAll();
    $avgQuery = "SELECT qm.dimension AS dim, AVG(r.value) AS avg_value FROM responses r JOIN question_map qm ON r.question_number = qm.question_number JOIN submissions s ON r.submission_id=s.id WHERE s.student_class=? GROUP BY qm.dimension";
    $astmt = $pdo->prepare($avgQuery);
    $astmt->execute([$student_class]);
    $overall = ['C' => 0, 'A' => 0, 'R' => 0, 'E' => 0];
    while ($ar = $astmt->fetch()) {
        $overall[strtoupper($ar['dim'])] = round((float)$ar['avg_value'], 2);
    }

    echo json_encode(['success' => true, 'name' => $name, 'submission_id' => $submission_id, 'created_at' => $row['created_at'], 'individual' => $dims, 'responses' => $raw, 'overall' => $overall]);

} catch (Exception $e) {
    echo json_encode(['error' => 'Server error']);
    exit;
}