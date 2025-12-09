<?php
header('Content-Type: application/json');
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

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    $stmt = $pdo->query("SELECT class_name FROM classes ORDER BY class_name ASC");
    $classes = [];
    while ($row = $stmt->fetch()) {
        $classes[] = $row['class_name'];
    }
    echo json_encode(['success' => true, 'classes' => $classes]);
} catch (Exception $e) {
    echo json_encode(['error' => 'Kan geen klassen ophalen.']);
}
