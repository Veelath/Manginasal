<?php
$host = 'localhost';
$db   = 'mang_inasal_db';
$user = 'root';
$pass = ''; // Default XAMPP password is empty
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     // If database doesn't exist, we'll try to create it and the table for convenience
     if ($e->getCode() == 1049) {
          try {
               $tmp_pdo = new PDO("mysql:host=$host", $user, $pass);
               $tmp_pdo->exec("CREATE DATABASE IF NOT EXISTS mang_inasal_db");
               $pdo = new PDO($dsn, $user, $pass, $options);
               $pdo->exec("CREATE TABLE IF NOT EXISTS users (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    email VARCHAR(255) UNIQUE NOT NULL,
                    password VARCHAR(255) NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
               )");
          } catch (\PDOException $err) {
               die("Database error: " . $err->getMessage());
          }
     } else {
          die("Database error: " . $e->getMessage());
     }
}
?>
