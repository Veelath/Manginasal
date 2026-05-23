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

/**
 * Helper to save base64 menu images as real files in an uploads/ directory on the web server.
 * Returns the relative file path to be stored in the database.
 */
function saveMenuImageLocal($base64Str, $itemId = null) {
    if (empty($base64Str)) {
        return '';
    }

    // If already local file path or external URL, return it as-is
    if (strpos($base64Str, 'uploads/') === 0 || strpos($base64Str, 'http://') === 0 || strpos($base64Str, 'https://') === 0) {
        return $base64Str;
    }

    // Ensure uploads directory exists
    $uploadDir = __DIR__ . '/uploads';
    if (!is_dir($uploadDir)) {
        @mkdir($uploadDir, 0777, true);
    }

    $ext = 'jpg';
    $rawData = null;

    if (strpos($base64Str, 'data:') === 0) {
        $parts = explode(',', $base64Str);
        if (count($parts) >= 2) {
            $mimePart = $parts[0];
            $base64Data = $parts[1];

            if (strpos($mimePart, 'image/png') !== false) {
                $ext = 'png';
            } elseif (strpos($mimePart, 'image/gif') !== false) {
                $ext = 'gif';
            } elseif (strpos($mimePart, 'image/webp') !== false) {
                $ext = 'webp';
            } elseif (strpos($mimePart, 'image/svg+xml') !== false) {
                $ext = 'svg';
            }

            $rawData = @base64_decode($base64Data);
        }
    } else {
        $rawData = @base64_decode($base64Str);
    }

    if (!$rawData) {
        return $base64Str; // Return as-is if decoding failed
    }

    // Use a clean, deterministic file name
    $prefix = $itemId ? 'menu_' . $itemId : 'menu_item_' . uniqid();
    $fileName = $prefix . '_' . time() . '.' . $ext;
    $filePath = $uploadDir . '/' . $fileName;

    if (@file_put_contents($filePath, $rawData)) {
        return 'uploads/' . $fileName;
    }

    return $base64Str;
}

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

     // 4. Clean and convert legacy oversized base64 images from database to local files automatically
     try {
         $stmt = $pdo->query("SELECT Menu_ID, Menu_Image FROM MENU_ITEM WHERE Menu_Image IS NOT NULL AND Menu_Image NOT LIKE 'uploads/%'");
         $itemsToMigrate = $stmt->fetchAll();
         foreach ($itemsToMigrate as $item) {
             $menuId = $item['Menu_ID'];
             $base64Str = $item['Menu_Image'];

             if (empty($base64Str)) {
                 continue;
             }

             // If it is a base64 string, migrate and save it
             if (strpos($base64Str, 'data:') === 0 || strlen($base64Str) > 200) {
                 $filePathRelative = saveMenuImageLocal($base64Str, $menuId);
                 if ($filePathRelative !== $base64Str) {
                     $update = $pdo->prepare("UPDATE MENU_ITEM SET Menu_Image = ? WHERE Menu_ID = ?");
                     $update->execute([$filePathRelative, $menuId]);
                 }
             }
         }
     } catch (Exception $e) {
         // Fail-safe to avoid blocking page execution
     }

} catch (\PDOException $e) {
     die("Database error: Please make sure you have run 'SOURCE database.sql' in your MySQL terminal. " . $e->getMessage());
}
?>
