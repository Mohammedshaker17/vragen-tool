<?php
/* API endpoint to fetch all questions with their answer choices */

header('Content-Type: application/json');
require_once __DIR__ . '/database.php';

try {
    $stmt = $pdo->query("
        SELECT 
            q.id,
            q.question_number,
            q.question_text,
            q.dimension
        FROM questions q
        ORDER BY q.question_number ASC
    ");
    $questions = $stmt->fetchAll();

    $choicesStmt = $pdo->query("
        SELECT 
            id,
            choice_text,
            choice_value
        FROM choices
        ORDER BY id ASC
    ");
    $choices = $choicesStmt->fetchAll();

    echo json_encode([
        'success' => true,
        'questions' => $questions,
        'choices' => $choices
    ]);
} catch (Exception $e) {
    echo json_encode(['error' => 'Kan geen vragen ophalen: ' . $e->getMessage()]);
}