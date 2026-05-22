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
     
     // Automatic Schema Updates
     // 1. Address columns
     $columns = $pdo->query("SHOW COLUMNS FROM ADDRESS")->fetchAll(PDO::FETCH_COLUMN);
     if (!in_array('Add_Brgy', $columns)) {
         $pdo->exec("ALTER TABLE ADDRESS ADD COLUMN Add_Brgy VARCHAR(50) NOT NULL AFTER Add_City");
     }
     if (!in_array('Add_PostalCode', $columns)) {
         $pdo->exec("ALTER TABLE ADDRESS ADD COLUMN Add_PostalCode VARCHAR(20) AFTER Add_Landmark");
     }

     // 2. Menu Item Branch mapping
     $menu_items_columns = $pdo->query("SHOW COLUMNS FROM MENU_ITEM")->fetchAll(PDO::FETCH_COLUMN);
     if (!in_array('Menu_Brnch_ID', $menu_items_columns)) {
         try {
             $pdo->exec("ALTER TABLE MENU_ITEM ADD COLUMN Menu_Brnch_ID INT NULL AFTER Menu_ID");
             $pdo->exec("ALTER TABLE MENU_ITEM ADD FOREIGN KEY (Menu_Brnch_ID) REFERENCES BRANCH(Brnch_ID) ON DELETE CASCADE");
         } catch (Exception $e) {
             // Ignore error in case of redunant alterations
         }
     }

     // 3. Branch Menu stock level column
     $branch_menu_columns = $pdo->query("SHOW COLUMNS FROM BRANCH_MENU")->fetchAll(PDO::FETCH_COLUMN);
     if (!in_array('Stock_Qty', $branch_menu_columns)) {
         try {
             $pdo->exec("ALTER TABLE BRANCH_MENU ADD COLUMN Stock_Qty INT NOT NULL DEFAULT 50");
         } catch (Exception $e) {
             // Ignore error in case of redundant alterations
         }
     }
} catch (\PDOException $e) {
     die("Database error: Please make sure you have run 'SOURCE database.sql' in your MySQL terminal. " . $e->getMessage());
}
?>
