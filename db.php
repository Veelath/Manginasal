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

     // 4. Clean and compress legacy oversized image strings from the MENU_ITEM database table
     if (!function_exists('compressExistingBase64')) {
         function compressExistingBase64($base64Str, $maxDim = 150, $quality = 50) {
             if (empty($base64Str)) {
                 return $base64Str;
             }
             
             // If already nice and short, keep it as is
             if (strlen($base64Str) < 10000) {
                 return $base64Str;
             }

             // Standard elegant, highly compact SVG green plate placeholder as vector fallback
             // Displays inline beautifully in the browser and takes only 268 characters of database space!
             $svgFallback = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAxMDAgMTAwIiBmaWxsPSJub25lIiBzdHJva2U9IiMwMDY3MzgiIHN0cm9rZS13aWR0aD0iNCI+PGNpcmNsZSBjeD0iNTAiIGN5PSI1MCIgcj0iNDAiLz48cGF0aCBkPSJNMzAgNDBoNDBNMzAgNTBoNDBNMzAgNjBoNDAiLz48L3N2Zz4=';

             // Check if PHP GD extension is installed and loaded
             if (!function_exists('imagecreatefromstring')) {
                 return $svgFallback;
             }

             // Extract the raw binary image data
             $rawData = '';
             if (strpos($base64Str, 'data:') === 0) {
                 $parts = explode(',', $base64Str);
                 if (count($parts) >= 2) {
                     $rawData = @base64_decode($parts[1]);
                 }
             } else {
                 $rawData = @base64_decode($base64Str);
             }

             if (!$rawData) {
                 return $svgFallback;
             }

             // Create from binary data using GD
             $srcImg = @imagecreatefromstring($rawData);
             if (!$srcImg) {
                 return $svgFallback;
             }

             $width = imagesx($srcImg);
             $height = imagesy($srcImg);
             if ($width <= 0 || $height <= 0) {
                 imagedestroy($srcImg);
                 return $svgFallback;
             }

             // Calculate aspect ratio scale
             if ($width > $maxDim || $height > $maxDim) {
                 if ($width > $height) {
                     $newWidth = $maxDim;
                     $newHeight = intval(($height / $width) * $maxDim);
                 } else {
                     $newHeight = $maxDim;
                     $newWidth = intval(($width / $height) * $maxDim);
                 }
             } else {
                 $newWidth = $width;
                 $newHeight = $height;
             }

             $dstImg = imagecreatetruecolor($newWidth, $newHeight);
             
             // Handle transparency if PNG/GIF
             imagealphablending($dstImg, false);
             imagesavealpha($dstImg, true);
             
             imagecopyresampled($dstImg, $srcImg, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
             
             ob_start();
             imagejpeg($dstImg, null, $quality);
             $compressedData = ob_get_clean();
             
             imagedestroy($srcImg);
             imagedestroy($dstImg);

             if ($compressedData) {
                 return 'data:image/jpeg;base64,' . base64_encode($compressedData);
             }

             return $svgFallback;
         }
     }

     try {
         $stmt = $pdo->query("SELECT Menu_ID, Menu_Image FROM MENU_ITEM WHERE Menu_Image IS NOT NULL AND LENGTH(Menu_Image) > 10000");
         $oversizedItems = $stmt->fetchAll();
         foreach ($oversizedItems as $item) {
             $compressedStr = compressExistingBase64($item['Menu_Image']);
             if ($compressedStr !== $item['Menu_Image']) {
                 $updateQuery = $pdo->prepare("UPDATE MENU_ITEM SET Menu_Image = ? WHERE Menu_ID = ?");
                 $updateQuery->execute([$compressedStr, $item['Menu_ID']]);
             }
         }
     } catch (Exception $e) {
         // Fail-safe to avoid blocking page execution
     }

} catch (\PDOException $e) {
     die("Database error: Please make sure you have run 'SOURCE database.sql' in your MySQL terminal. " . $e->getMessage());
}
?>
