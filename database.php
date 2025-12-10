<?php
require_once __DIR__ . '/config.php';

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
} catch (Exception $e) {
    die('Database connectie mislukt: ' . $e->getMessage());
}