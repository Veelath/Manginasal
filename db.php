<?php
$db_file = __DIR__ . '/database.sqlite';

try {
    $pdo = new PDO("sqlite:$db_file");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Initialize database if it's empty
    $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll();
    if (empty($tables)) {
        $sql = file_get_contents(__DIR__ . '/database.sql');
        
        // Basic conversion from MySQL to SQLite syntax
        $sql = preg_replace('/DROP DATABASE IF EXISTS.*;/i', '', $sql);
        $sql = preg_replace('/CREATE DATABASE.*;/i', '', $sql);
        $sql = preg_replace('/USE .*;/i', '', $sql);
        $sql = preg_replace('/AUTO_INCREMENT/i', 'AUTOINCREMENT', $sql);
        $sql = preg_replace('/ENGINE=.*;/i', ';', $sql);
        $sql = str_replace('INT PRIMARY KEY AUTOINCREMENT', 'INTEGER PRIMARY KEY AUTOINCREMENT', $sql);
        $sql = str_replace('DECIMAL(', 'REAL(', $sql);
        
        $pdo->exec($sql);
    }
} catch (\PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
