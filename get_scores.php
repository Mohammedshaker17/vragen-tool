<?php
/* API endpoint om scores en gemiddelden van een student op te halen (nieuwe database structuur) */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/database.php';

$name = isset($_GET['name']) ? trim($_GET['name']) : '';

if ($name === '') {
    echo json_encode(['error' => 'Missing name']);
    exit;
}

try {
    if ($name === '__average__') {
        session_start();
        if (!isset($_SESSION['classes_id'])) {
            echo json_encode(['error' => 'No class in session']);
            exit;
        }
        $classes_id = $_SESSION['classes_id'];

        $avgQuery = "SELECT q.dimension AS dim, AVG(r.value) AS avg_value
                     FROM responses r
                     JOIN questions q ON r.questions_idquestions = q.id
                     JOIN submissions s ON r.submission_id = s.id
                     WHERE s.classes_id = ?
                     GROUP BY q.dimension";
        $astmt = $pdo->prepare($avgQuery);
        $astmt->execute([$classes_id]);
        $overall = ['C' => 0, 'A' => 0, 'R' => 0, 'E' => 0];
        while ($ar = $astmt->fetch()) {
            $overall[strtoupper($ar['dim'])] = round((float)$ar['avg_value'], 2);
        }
        echo json_encode(['success' => true, 'name' => '__average__', 'overall' => $overall]);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT s.id, s.classes_id, s.created_at, c.class_name 
        FROM submissions s
        JOIN classes c ON s.classes_id = c.id
        WHERE s.student_name = ? 
        ORDER BY s.id DESC 
        LIMIT 1
    ");
    $stmt->execute([$name]);
    $row = $stmt->fetch();

    if (!$row) {
        echo json_encode(['error' => 'Student not found']);
        exit;
    }

    $submission_id = (int)$row['id'];
    $classes_id = $row['classes_id'];
    $student_class = $row['class_name'];

    $stmt = $pdo->prepare("
        SELECT q.dimension AS dim, AVG(r.value) AS avg_value
        FROM responses r
        JOIN questions q ON r.questions_idquestions = q.id
        WHERE r.submission_id = ?
        GROUP BY q.dimension
    ");
    $stmt->execute([$submission_id]);
    $dims = ['C' => 0, 'A' => 0, 'R' => 0, 'E' => 0];
    while ($r = $stmt->fetch()) {
        $dims[strtoupper($r['dim'])] = round((float)$r['avg_value'], 2);
    }

    $rstmt = $pdo->prepare("
        SELECT q.question_number, r.value 
        FROM responses r
        JOIN questions q ON r.questions_idquestions = q.id
        WHERE r.submission_id = ?
        ORDER BY q.question_number
    ");
    $rstmt->execute([$submission_id]);
    $raw = $rstmt->fetchAll();

    $avgQuery = "
        SELECT q.dimension AS dim, AVG(r.value) AS avg_value
        FROM responses r
        JOIN questions q ON r.questions_idquestions = q.id
        JOIN submissions s ON r.submission_id = s.id
        WHERE s.classes_id = ?
        GROUP BY q.dimension
    ";
    $astmt = $pdo->prepare($avgQuery);
    $astmt->execute([$classes_id]);
    $overall = ['C' => 0, 'A' => 0, 'R' => 0, 'E' => 0];
    while ($ar = $astmt->fetch()) {
        $overall[strtoupper($ar['dim'])] = round((float)$ar['avg_value'], 2);
    }

    echo json_encode([
        'success' => true,
        'name' => $name,
        'submission_id' => $submission_id,
        'created_at' => $row['created_at'],
        'student_class' => $student_class,
        'individual' => $dims,
        'responses' => $raw,
        'overall' => $overall
    ]);

} catch (Exception $e) {
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
    exit;
}