<?php

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/database.php';

try {
    $stmt = $pdo->query("
        SELECT
            q.idquestions AS id,
            q.question_number,
            q.question_text,
            q.dimension
        FROM questions q
        ORDER BY q.question_number ASC
    ");
    $questions = $stmt->fetchAll();

    $choicesStmt = $pdo->query("
        SELECT
            idchoices AS id,
            choice_text,
            choice_value
        FROM choices
        ORDER BY idchoices ASC
    ");
    $choices = $choicesStmt->fetchAll();

    echo json_encode([
        'success' => true,
        'questions' => $questions,
        'choices' => $choices
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Kan geen vragen ophalen: ' . $e->getMessage()]);
}
