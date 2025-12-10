<?php
/* API endpoint om alle beschikbare klassen op te halen */

header('Content-Type: application/json');
require_once __DIR__ . '/database.php';

try {
    $stmt = $pdo->query("SELECT class_name FROM classes ORDER BY class_name ASC");
    $classes = [];
    while ($row = $stmt->fetch()) {
        $classes[] = $row['class_name'];
    }
    echo json_encode(['success' => true, 'classes' => $classes]);
} catch (Exception $e) {
    echo json_encode(['error' => 'Kan geen klassen ophalen.']);
}