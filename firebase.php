<?php
// Initialize Firestore gracefully to prevent crashing on host environments (e.g. Cloud Run, or local XAMPP without composer)

$firestore = null;
$firebase_error = "";
$firebase_sync_errors = [];

// Simple Dummy Class to catch any methods called on Firebase and throw them as an Exception.
// This allows caller's try-catch (which catches Exception) to catch it and fall back to MySQL gracefully.
class DummyFirestore {
    private $errorMsg;
    public function __construct($msg = "") {
        $this->errorMsg = $msg ?: "Firebase service account is not configured or could not be loaded.";
    }
    public function getErrorMsg() {
        return $this->errorMsg;
    }
    public function collection($name) {
        throw new Exception($this->errorMsg);
    }
}

// Function to find a service account file in root directory or hardcoded paths
function findServiceAccountFile() {
    $windowsPath = 'C:/xampp/db_export/manginasaldb-firebase-adminsdk-fbsvc-947b854f43.json';
    if (file_exists($windowsPath)) {
        return $windowsPath;
    }

    $files = glob(__DIR__ . '/*.json');
    if ($files) {
        foreach ($files as $file) {
            $name = basename($file);
            if (in_array($name, ['package.json', 'package-lock.json', 'composer.json', 'metadata.json', 'tsconfig.json'])) {
                continue;
            }
            $content = @file_get_contents($file);
            if ($content) {
                $decoded = json_decode($content, true);
                if (is_array($decoded) && isset($decoded['type']) && $decoded['type'] === 'service_account') {
                    return $file;
                }
            }
        }
    }
    return null;
}

try {
    if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
        // Attempt automated self-installation of Composer dependencies in the runtime environment
        $last_attempt_file = __DIR__ . '/.composer_install_attempt';
        $lock_file = __DIR__ . '/.composer_install_lock';
        
        // Prevent concurrent execution or too frequent runs (wait at least 10 minutes between attempts)
        if (!file_exists($lock_file) && (!file_exists($last_attempt_file) || (time() - filemtime($last_attempt_file) > 600))) {
            @touch($lock_file);
            @touch($last_attempt_file);
            
            // Set HOME environment variable to a writable directory in case Composer needs it
            putenv('HOME=/tmp');
            
            // Check if composer command is available globally
            $composer_path = exec('which composer 2>&1', $output, $return_var);
            if ($return_var === 0) {
                $cmd = "composer install --no-interaction --no-dev --optimize-autoloader 2>&1";
                exec($cmd, $cmd_output, $cmd_return);
            } else {
                // Download composer.phar
                $composer_url = "https://getcomposer.org/composer-stable.phar";
                $composer_file = __DIR__ . "/composer.phar";
                $download = @file_put_contents($composer_file, @file_get_contents($composer_url));
                if ($download !== false) {
                    $cmd = "php composer.phar install --no-interaction --no-dev --optimize-autoloader 2>&1";
                    exec($cmd, $cmd_output, $cmd_return);
                    @unlink($composer_file);
                }
            }
            @unlink($lock_file);
        }
    }

    if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
        throw new Exception("Composer vendors not installed in this environment (vendor/autoload.php is missing). Automatic background helper is preparing libraries.");
    }

    require_once __DIR__ . '/vendor/autoload.php';

    // Verify Kreait Factory class exists
    if (!class_exists('Kreait\Firebase\Factory')) {
        throw new Exception("Kreait\\Firebase\\Factory class not found. Please run 'composer require kreait/firebase-php'.");
    }

    $factory = new \Kreait\Firebase\Factory();
    $credFile = findServiceAccountFile();
    if ($credFile) {
        $factory = $factory->withServiceAccount($credFile);
    }
    // In GCP Environments (like Cloud Run), if no JSON service account is explicitly provided,
    // the SDK will automatically fallback to Application Default Credentials (ADC) via Metadata Server.
    $firestore = $factory->createFirestore()->database();

} catch (Exception $e) {
    $firebase_error = $e->getMessage();
    $firestore = new DummyFirestore("Firebase/Firestore Error: " . $firebase_error);
}

// Log and save a sync error at runtime
function fbRecordError($action, $msg) {
    global $firebase_sync_errors;
    $firebase_sync_errors[] = "$action: $msg";
}

// Check current Firebase connection and setup health
function fbGetStatus() {
    global $firebase_error, $firestore;

    // Check Composer
    if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
        return [
            'connected' => false,
            'error' => "Composer third-party vendors are not installed in the system. The Firebase PHP Admin SDK cannot be loaded without autoload.",
            'detailed_guide' => "Background installer is initializing. Try refreshing in 10-15 seconds. If that fails, ensure your environment has Internet access to run install, or upload a pre-built 'vendor/' folder containing 'kreait/firebase-php' dependencies to the root catalog."
        ];
    }

    // Check Credential File
    $credFile = findServiceAccountFile();
    if (!$credFile) {
        return [
            'connected' => false,
            'error' => "Firestore service account credential JSON file not found. The app searched under root and common server export paths, but no valid key file was detected.",
            'detailed_guide' => "To fix: Create or download a service account private key JSON from Google/Firebase console and upload it to the project's root folder."
        ];
    }

    if ($firestore instanceof DummyFirestore) {
        return [
            'connected' => false,
            'error' => $firestore->getErrorMsg(),
            'detailed_guide' => "Please verify credentials and Firestore database setup."
        ];
    }

    if (!$firestore) {
        return [
            'connected' => false,
            'error' => "Firestore driver instance is uninitialized.",
            'detailed_guide' => "Please ensure Firebase/Firestore SDK configurations are correct."
        ];
    }

    return [
        'connected' => true,
        'error' => ""
    ];
}

// Handle global JSON outputs automatically
ob_start(function($buffer) {
    $trimmed = trim($buffer);
    if (empty($trimmed)) {
        return $buffer;
    }

    $first_char = substr($trimmed, 0, 1);
    if ($first_char !== '{' && $first_char !== '[') {
        return $buffer;
    }

    $decoded = json_decode($buffer, true);
    if (is_array($decoded)) {
        $decoded['firebase_status'] = fbGetStatus();
        global $firebase_sync_errors;
        if (!empty($firebase_sync_errors)) {
            $decoded['firebase_sync_errors'] = $firebase_sync_errors;
        }
        return json_encode($decoded);
    }
    return $buffer;
});

// Add document
function fbAdd($firestore, $collection, $data) {
    global $firebase_sync_errors;
    try {
        if (!$firestore || $firestore instanceof DummyFirestore) {
            $msg = $firestore ? $firestore->getErrorMsg() : "Firebase is uninitialized.";
            throw new Exception($msg);
        }
        return $firestore->collection($collection)->add($data);
    } catch (Exception $e) {
        fbRecordError("Add to '$collection'", $e->getMessage());
        throw $e;
    }
}

// Helper to check if a Firestore document matches a given MySQL ID (e.g. mysql_id, uppercase keys, or doc ID itself)
function fbMatchesId($doc, $mysqlId, $idKeys = []) {
    if (!$doc || !$doc->exists()) {
        return false;
    }
    $data = $doc->data();
    
    // Check possible lowercase keys
    if (isset($data['mysql_id']) && $data['mysql_id'] == $mysqlId) {
        return true;
    }
    if (isset($data['mysql_order_id']) && $data['mysql_order_id'] == $mysqlId) {
        return true;
    }
    if (isset($data['id']) && $data['id'] == $mysqlId) {
        return true;
    }
    
    // Check specific uppercase keys provided
    foreach ($idKeys as $key) {
        if (isset($data[$key]) && $data[$key] == $mysqlId) {
            return true;
        }
    }
    
    // Check native Firestore document ID
    if ($doc->id() == $mysqlId) {
        return true;
    }
    
    return false;
}

// Data Normalization Utilities to bridge uppercase MySQL keys and lowercase/custom Firestore console entries
function normalizeMenuItem($data) {
    $mapped = [];
    
    $id = $data['Menu_ID'] ?? $data['mysql_id'] ?? $data['id'] ?? '';
    $mapped['id'] = $id;
    $mapped['Menu_ID'] = $id;
    
    $branchId = $data['Menu_Brnch_ID'] ?? $data['branch_id'] ?? null;
    $mapped['Menu_Brnch_ID'] = ($branchId === 'NULL' || $branchId === null || $branchId === '') ? null : $branchId;
    $mapped['branch_id'] = $mapped['Menu_Brnch_ID'];
    
    $name = $data['Menu_Name'] ?? $data['name'] ?? '';
    $mapped['Menu_Name'] = $name;
    $mapped['name'] = $name;
    
    $desc = $data['Menu_Description'] ?? $data['description'] ?? '';
    $mapped['Menu_Description'] = $desc;
    $mapped['description'] = $desc;
    
    $price = $data['Menu_Price'] ?? $data['price'] ?? '0.00';
    $mapped['Menu_Price'] = $price;
    $mapped['price'] = $price;
    
    $cat = $data['Menu_Category'] ?? $data['category'] ?? '';
    $mapped['Menu_Category'] = $cat;
    $mapped['category'] = $cat;
    
    $size = $data['Menu_Size'] ?? $data['size'] ?? '';
    $mapped['Menu_Size'] = $size;
    $mapped['size'] = $size;
    
    $img = $data['Menu_Image'] ?? $data['image'] ?? '';
    $mapped['Menu_Image'] = $img;
    $mapped['image'] = $img;
    
    $status = $data['Menu_Status'] ?? $data['status'] ?? 'Y';
    $mapped['Menu_Status'] = $status;
    $mapped['status'] = $status;
    
    $isAvail = $data['Is_Available'] ?? $data['is_available'] ?? $data['status'] ?? $data['Menu_Status'] ?? 'Y';
    $mapped['Is_Available'] = $isAvail;
    $mapped['is_available'] = $isAvail;
    
    $stock = $data['Stock_Qty'] ?? $data['stock_qty'] ?? 50;
    $mapped['Stock_Qty'] = (int)$stock;
    $mapped['stock_qty'] = (int)$stock;
    
    return $mapped;
}

function normalizeOrder($data, $firestore = null) {
    $mapped = [];
    
    $id = $data['id'] ?? $data['Order_ID'] ?? '';
    $mapped['id'] = $id;
    $mapped['Order_ID'] = $id;
    
    $code = $data['order_code'] ?? $data['Order_Code'] ?? '';
    $mapped['Order_Code'] = $code;
    $mapped['order_code'] = $code;
    
    $custId = $data['customer_id'] ?? $data['Order_Cust_ID'] ?? '';
    $mapped['Order_Cust_ID'] = $custId;
    $mapped['customer_id'] = $custId;
    
    $branchId = $data['branch_id'] ?? $data['Order_Brnch_ID'] ?? '';
    $mapped['Order_Brnch_ID'] = $branchId;
    $mapped['branch_id'] = $branchId;
    
    $type = $data['type'] ?? $data['Order_Type'] ?? 'Dine-In';
    $mapped['Order_Type'] = $type;
    $mapped['type'] = $type;
    
    $rcpName = $data['recipient_name'] ?? $data['Order_Recipient_Name'] ?? '';
    $mapped['Order_Recipient_Name'] = $rcpName;
    $mapped['recipient_name'] = $rcpName;
    
    $rcpNum = $data['recipient_num'] ?? $data['Order_Recipient_Num'] ?? '';
    $mapped['Order_Recipient_Num'] = $rcpNum;
    $mapped['recipient_num'] = $rcpNum;
    
    $total = $data['total'] ?? $data['Order_Total_Amount'] ?? '0.00';
    $mapped['Order_Total_Amount'] = $total;
    $mapped['total'] = $total;
    
    $status = $data['status'] ?? $data['Order_Stat'] ?? 'Pending';
    $mapped['Order_Stat'] = $status;
    $mapped['status'] = $status;
    
    $date = $data['created_at'] ?? $data['Order_Date'] ?? date('Y-m-d H:i:s');
    $mapped['Order_Date'] = $date;
    $mapped['created_at'] = $date;
    
    $recipParts = explode(' ', $rcpName, 2);
    $mapped['Cust_FName'] = $recipParts[0] ?? 'Guest';
    $mapped['Cust_LName'] = $recipParts[1] ?? 'Customer';
    
    $mapped['Pay_Method'] = 'Cash';
    $mapped['Pay_Status'] = 'Pending';
    $mapped['Rider_FName'] = '';
    $mapped['Rider_LName'] = '';
    
    if ($firestore) {
        try {
            $payDocs = $firestore->collection('payment')->documents();
            foreach ($payDocs as $payDoc) {
                if ($payDoc->exists()) {
                    $pData = $payDoc->data();
                    if (isset($pData['order_id']) && $pData['order_id'] == $id) {
                        $mapped['Pay_Method'] = $pData['method'] ?? $pData['Pay_Method'] ?? 'Cash';
                        $mapped['Pay_Status'] = $pData['status'] ?? $pData['Pay_Status'] ?? 'Pending';
                        break;
                    }
                }
            }
        } catch (Exception $e) {}
        
        try {
            $delDocs = $firestore->collection('delivery')->documents();
            foreach ($delDocs as $delDoc) {
                if ($delDoc->exists()) {
                    $delData = $delDoc->data();
                    if (isset($delData['order_id']) && $delData['order_id'] == $id) {
                        $riderId = $delData['rider_id'] ?? '';
                        if (!empty($riderId)) {
                            $rDocs = $firestore->collection('rider')->documents();
                            foreach ($rDocs as $rDoc) {
                                if ($rDoc->exists()) {
                                    $rData = $rDoc->data();
                                    if ($rDoc->id() == $riderId || (isset($rData['mysql_id']) && $rData['mysql_id'] == $riderId)) {
                                        $mapped['Rider_FName'] = $rData['first_name'] ?? $rData['Rider_FName'] ?? '';
                                        $mapped['Rider_LName'] = $rData['last_name'] ?? $rData['Rider_LName'] ?? '';
                                        break;
                                    }
                                }
                            }
                        }
                        break;
                    }
                }
            }
        } catch (Exception $e) {}
    }
    
    return $mapped;
}

function normalizeWorkforce($data) {
    if (isset($data['first_name'])) {
        $data['fname'] = $data['first_name'];
    }
    if (isset($data['last_name'])) {
        $data['lname'] = $data['last_name'];
    }
    return $data;
}

// Get all documents
function fbGetAll($firestore, $collection) {
    global $firebase_sync_errors;
    try {
        if (!$firestore || $firestore instanceof DummyFirestore) {
            $msg = $firestore ? $firestore->getErrorMsg() : "Firebase is uninitialized.";
            throw new Exception($msg);
        }
        $docs = $firestore->collection($collection)->documents();
        $result = [];
        foreach ($docs as $doc) {
            if ($doc->exists()) {
                $result[] = array_merge(['id' => $doc->id()], $doc->data());
            }
        }
        return $result;
    } catch (Exception $e) {
        fbRecordError("Get all from '$collection'", $e->getMessage());
        throw $e;
    }
}

// Get one document by ID
function fbGetOne($firestore, $collection, $id) {
    global $firebase_sync_errors;
    try {
        if (!$firestore || $firestore instanceof DummyFirestore) {
            $msg = $firestore ? $firestore->getErrorMsg() : "Firebase is uninitialized.";
            throw new Exception($msg);
        }
        $doc = $firestore->collection($collection)->document($id)->snapshot();
        if ($doc->exists()) {
            return array_merge(['id' => $doc->id()], $doc->data());
        }
        return null;
    } catch (Exception $e) {
        fbRecordError("Get doc '$id' from '$collection'", $e->getMessage());
        throw $e;
    }
}

// Update document
function fbUpdate($firestore, $collection, $id, $data) {
    global $firebase_sync_errors;
    try {
        if (!$firestore || $firestore instanceof DummyFirestore) {
            $msg = $firestore ? $firestore->getErrorMsg() : "Firebase is uninitialized.";
            throw new Exception($msg);
        }
        $updates = [];
        foreach ($data as $key => $value) {
            $updates[] = ['path' => $key, 'value' => $value];
        }
        $firestore->collection($collection)->document($id)->update($updates);
    } catch (Exception $e) {
        fbRecordError("Update doc '$id' in '$collection'", $e->getMessage());
        throw $e;
    }
}

// Delete document
function fbDelete($firestore, $collection, $id) {
    global $firebase_sync_errors;
    try {
        if (!$firestore || $firestore instanceof DummyFirestore) {
            $msg = $firestore ? $firestore->getErrorMsg() : "Firebase is uninitialized.";
            throw new Exception($msg);
        }
        $firestore->collection($collection)->document($id)->delete();
    } catch (Exception $e) {
        fbRecordError("Delete doc '$id' from '$collection'", $e->getMessage());
        throw $e;
    }
}

// Transaction: Place Order (touches orders, order_item, payment, delivery)
function fbPlaceOrder($firestore, $orderData, $orderItems, $paymentData, $deliveryData) {
    global $firebase_sync_errors;
    try {
        if (!$firestore || $firestore instanceof DummyFirestore) {
            $msg = $firestore ? $firestore->getErrorMsg() : "Firebase is uninitialized.";
            throw new Exception($msg);
        }
        // 1. Create order
        $orderRef = $firestore->collection('orders')->add($orderData);
        $orderId = $orderRef->id();

        // 2. Add order items
        foreach ($orderItems as $item) {
            $item['order_id'] = $orderId;
            $firestore->collection('order_item')->add($item);
        }

        // 3. Create payment
        $paymentData['order_id'] = $orderId;
        $firestore->collection('payment')->add($paymentData);

        // 4. Create delivery
        $deliveryData['order_id'] = $orderId;
        $firestore->collection('delivery')->add($deliveryData);

        return $orderId;
    } catch (Exception $e) {
        fbRecordError("Place order transaction", $e->getMessage());
        throw $e;
    }
}

// Reconcile and Sync Firebase Customer registrations into MySQL + Push MySQL customers to Firestore
function syncFirebaseCustomersToMySql($pdo, $firestore) {
    if (!$firestore || $firestore instanceof DummyFirestore) {
        return;
    }
    try {
        $docs = $firestore->collection('customer')->documents();
        $firebase_emails = [];
        $firebase_mysql_ids = [];
        
        foreach ($docs as $doc) {
            if ($doc->exists()) {
                $data = $doc->data();
                $email = $data['Cust_Email'] ?? $data['email'] ?? '';
                if (!empty($email)) {
                    $firebase_emails[strtolower($email)] = $doc->id();
                }
                $mysqlId = $data['mysql_id'] ?? $data['Cust_ID'] ?? '';
                if (!empty($mysqlId)) {
                    $firebase_mysql_ids[$mysqlId] = $doc->id();
                }
                
                // Firestore -> MySQL import direction
                if (!empty($email)) {
                    $check = $pdo->prepare("SELECT Cust_ID FROM CUSTOMER WHERE Cust_Email = ?");
                    $check->execute([$email]);
                    $cust = $check->fetch();
                    
                    if (!$cust) {
                        $fname = $data['Cust_FName'] ?? $data['first_name'] ?? $data['fname'] ?? 'New';
                        $lname = $data['Cust_LName'] ?? $data['last_name'] ?? $data['lname'] ?? 'Customer';
                        $num = $data['Cust_Num'] ?? $data['mobile'] ?? $data['mobile_num'] ?? '09000000000';
                        $pass = $data['Cust_Pass'] ?? $data['password'] ?? password_hash('password', PASSWORD_DEFAULT);
                        
                        $insert = $pdo->prepare("INSERT INTO CUSTOMER (Cust_FName, Cust_LName, Cust_Num, Cust_Email, Cust_Pass) VALUES (?, ?, ?, ?, ?)");
                        $insert->execute([$fname, $lname, $num, $email, $pass]);
                        $newId = $pdo->lastInsertId();
                        
                        fbUpdate($firestore, 'customer', $doc->id(), [
                            'Cust_ID' => (string)$newId,
                            'mysql_id' => (string)$newId
                        ]);
                        $firebase_mysql_ids[$newId] = $doc->id();
                    } else {
                        $mysqlId = $cust['Cust_ID'];
                        if (!isset($data['Cust_ID']) || $data['Cust_ID'] != $mysqlId) {
                            fbUpdate($firestore, 'customer', $doc->id(), [
                                'Cust_ID' => (string)$mysqlId,
                                'mysql_id' => (string)$mysqlId
                            ]);
                        }
                        $firebase_mysql_ids[$mysqlId] = $doc->id();
                    }
                }
            }
        }
        
        // MySQL -> Firestore push direction
        $mysql_custs = $pdo->query("SELECT * FROM CUSTOMER")->fetchAll();
        foreach ($mysql_custs as $cust) {
            $mysqlId = $cust['Cust_ID'];
            $email = strtolower($cust['Cust_Email']);
            
            if (!isset($firebase_mysql_ids[$mysqlId]) && !isset($firebase_emails[$email])) {
                fbAdd($firestore, 'customer', [
                    'Cust_ID' => (string)$mysqlId,
                    'mysql_id' => (string)$mysqlId,
                    'Cust_FName' => $cust['Cust_FName'],
                    'Cust_LName' => $cust['Cust_LName'],
                    'Cust_Num' => $cust['Cust_Num'],
                    'Cust_Email' => $cust['Cust_Email'],
                    'Cust_Pass' => $cust['Cust_Pass']
                ]);
            }
        }
    } catch (Exception $e) {
        error_log('Error syncing Firebase customers: ' . $e->getMessage());
    }
}

// Reconcile and Sync Firebase Menu items into MySQL + Push MySQL menu items to Firestore
function syncFirebaseMenuToMySql($pdo, $firestore) {
    if (!$firestore || $firestore instanceof DummyFirestore) {
        return;
    }
    try {
        $docs = $firestore->collection('menu_item')->documents();
        $firebase_names = [];
        $firebase_mysql_ids = [];
        
        foreach ($docs as $doc) {
            if ($doc->exists()) {
                $data = $doc->data();
                $name = $data['Menu_Name'] ?? $data['name'] ?? '';
                if (!empty($name)) {
                    $firebase_names[strtolower($name)] = $doc->id();
                }
                $mysqlId = $data['mysql_id'] ?? $data['Menu_ID'] ?? '';
                if (!empty($mysqlId)) {
                    $firebase_mysql_ids[$mysqlId] = $doc->id();
                }
                
                // Firestore -> MySQL import direction
                if (!empty($name)) {
                    $check = $pdo->prepare("SELECT Menu_ID FROM MENU_ITEM WHERE Menu_Name = ?");
                    $check->execute([$name]);
                    $item = $check->fetch();
                    
                    $category = $data['Menu_Category'] ?? $data['category'] ?? 'Chicken';
                    $price = $data['Menu_Price'] ?? $data['price'] ?? 0;
                    $desc = $data['Menu_Description'] ?? $data['description'] ?? '';
                    $image = $data['Menu_Image'] ?? $data['image'] ?? '';
                    $size = $data['Menu_Size'] ?? $data['size'] ?? 'Standard';
                    $status = $data['Menu_Status'] ?? $data['status'] ?? 'Y';
                    $branchId = $data['Menu_Brnch_ID'] ?? $data['branch_id'] ?? null;
                    $branchId = ($branchId === 'NULL' || $branchId === '' || $branchId === null) ? null : $branchId;
                    
                    if (!$item) {
                        $insert = $pdo->prepare("INSERT INTO MENU_ITEM (Menu_Category, Menu_Name, Menu_Description, Menu_Image, Menu_Price, Menu_Status, Menu_Size, Menu_Brnch_ID) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                        $insert->execute([$category, $name, $desc, $image, $price, $status, $size, $branchId]);
                        $newId = $pdo->lastInsertId();
                        
                        fbUpdate($firestore, 'menu_item', $doc->id(), [
                            'Menu_ID' => (string)$newId,
                            'mysql_id' => (string)$newId
                        ]);
                        $firebase_mysql_ids[$newId] = $doc->id();
                    } else {
                        $mysqlId = $item['Menu_ID'];
                        if (!isset($data['Menu_ID']) || $data['Menu_ID'] != $mysqlId) {
                            fbUpdate($firestore, 'menu_item', $doc->id(), [
                                'Menu_ID' => (string)$mysqlId,
                                'mysql_id' => (string)$mysqlId
                            ]);
                        }
                        $firebase_mysql_ids[$mysqlId] = $doc->id();
                    }
                }
            }
        }
        
        // MySQL -> Firestore push direction
        $mysql_items = $pdo->query("SELECT * FROM MENU_ITEM")->fetchAll();
        foreach ($mysql_items as $item) {
            $mysqlId = $item['Menu_ID'];
            $name = strtolower($item['Menu_Name']);
            
            if (!isset($firebase_mysql_ids[$mysqlId]) && !isset($firebase_names[$name])) {
                fbAdd($firestore, 'menu_item', [
                    'Menu_ID' => (string)$mysqlId,
                    'mysql_id' => (string)$mysqlId,
                    'Menu_Category' => $item['Menu_Category'],
                    'Menu_Name' => $item['Menu_Name'],
                    'Menu_Description' => $item['Menu_Description'],
                    'Menu_Image' => $item['Menu_Image'],
                    'Menu_Price' => $item['Menu_Price'],
                    'Menu_Status' => $item['Menu_Status'],
                    'Menu_Size' => $item['Menu_Size'],
                    'Menu_Brnch_ID' => $item['Menu_Brnch_ID']
                ]);
            }
        }
    } catch (Exception $e) {
        error_log('Error syncing Firebase menu items: ' . $e->getMessage());
    }
}

// Reconcile and Sync Firebase Branches into MySQL + Push MySQL branches to Firestore
function syncFirebaseBranchesToMySql($pdo, $firestore) {
    if (!$firestore || $firestore instanceof DummyFirestore) {
        return;
    }
    try {
        $docs = $firestore->collection('branch')->documents();
        $firebase_names = [];
        $firebase_mysql_ids = [];
        
        foreach ($docs as $doc) {
            if ($doc->exists()) {
                $data = $doc->data();
                $name = $data['Brnch_Name'] ?? $data['name'] ?? '';
                if (!empty($name)) {
                    $firebase_names[strtolower($name)] = $doc->id();
                }
                $mysqlId = $data['mysql_id'] ?? $data['Brnch_ID'] ?? '';
                if (!empty($mysqlId)) {
                    $firebase_mysql_ids[$mysqlId] = $doc->id();
                }
                
                // Firestore -> MySQL import direction
                if (!empty($name)) {
                    $check = $pdo->prepare("SELECT Brnch_ID FROM BRANCH WHERE Brnch_Name = ?");
                    $check->execute([$name]);
                    $branch = $check->fetch();
                    
                    $street = $data['Brnch_Street'] ?? $data['street'] ?? '';
                    $brgy = $data['Brnch_Brgy'] ?? $data['brgy'] ?? '';
                    $city = $data['Brnch_City'] ?? $data['city'] ?? '';
                    $province = $data['Brnch_Province'] ?? $data['province'] ?? '';
                    $radius = $data['Brnch_Radius'] ?? $data['radius'] ?? 5.00;
                    $status = $data['Brnch_Status'] ?? $data['status'] ?? 'Y';
                    
                    if (!$branch) {
                        $insert = $pdo->prepare("INSERT INTO BRANCH (Brnch_Name, Brnch_Street, Brnch_Brgy, Brnch_City, Brnch_Province, Brnch_Radius, Brnch_Status) VALUES (?, ?, ?, ?, ?, ?, ?)");
                        $insert->execute([$name, $street, $brgy, $city, $province, $radius, $status]);
                        $newId = $pdo->lastInsertId();
                        
                        fbUpdate($firestore, 'branch', $doc->id(), [
                            'Brnch_ID' => (string)$newId,
                            'mysql_id' => (string)$newId
                        ]);
                        $firebase_mysql_ids[$newId] = $doc->id();
                    } else {
                        $mysqlId = $branch['Brnch_ID'];
                        if (!isset($data['Brnch_ID']) || $data['Brnch_ID'] != $mysqlId) {
                            fbUpdate($firestore, 'branch', $doc->id(), [
                                'Brnch_ID' => (string)$mysqlId,
                                'mysql_id' => (string)$mysqlId
                            ]);
                        }
                        $firebase_mysql_ids[$mysqlId] = $doc->id();
                    }
                }
            }
        }
        
        // MySQL -> Firestore push direction
        $mysql_branches = $pdo->query("SELECT * FROM BRANCH")->fetchAll();
        foreach ($mysql_branches as $branch) {
            $mysqlId = $branch['Brnch_ID'];
            $name = strtolower($branch['Brnch_Name']);
            
            if (!isset($firebase_mysql_ids[$mysqlId]) && !isset($firebase_names[$name])) {
                fbAdd($firestore, 'branch', [
                    'Brnch_ID' => (string)$mysqlId,
                    'mysql_id' => (string)$mysqlId,
                    'Brnch_Name' => $branch['Brnch_Name'],
                    'Brnch_Street' => $branch['Brnch_Street'],
                    'Brnch_Brgy' => $branch['Brnch_Brgy'],
                    'Brnch_City' => $branch['Brnch_City'],
                    'Brnch_Province' => $branch['Brnch_Province'],
                    'Brnch_Radius' => $branch['Brnch_Radius'],
                    'Brnch_Status' => $branch['Brnch_Status']
                ]);
            }
        }
    } catch (Exception $e) {
        error_log('Error syncing Firebase branches: ' . $e->getMessage());
    }
}
?>
