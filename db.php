<?php
$host = 'localhost';
$port = '3307'; // Change to '33060' if yours is different in XAMPP
$db   = 'mang_inasal_db'; 
$user = 'root';
$pass = ''; // Default XAMPP password is empty
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     die("Database error: Please make sure you have run 'SOURCE database.sql' in your MySQL terminal. " . $e->getMessage());
}
?>
