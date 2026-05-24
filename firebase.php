<?php
// Initialize Firestore gracefully to prevent crashing on host environments (e.g. Cloud Run, or local XAMPP without composer)

$firestore = null;
$firebase_error = "";

// Simple Dummy Class to catch any methods called on Firebase and throw them as an Exception.
// This allows caller's try-catch (which catches Exception) to catch it and fall back to MySQL gracefully.
class DummyFirestore {
    private $errorMsg;
    public function __construct($msg = "") {
        $this->errorMsg = $msg ?: "Firebase service account is not configured or could not be loaded.";
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
        throw new Exception("Composer vendors not installed in this environment (vendor/autoload.php is missing).");
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

// Add document
function fbAdd($firestore, $collection, $data) {
    return $firestore->collection($collection)->add($data);
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
    $docs = $firestore->collection($collection)->documents();
    $result = [];
    foreach ($docs as $doc) {
        if ($doc->exists()) {
            $result[] = array_merge(['id' => $doc->id()], $doc->data());
        }
    }
    return $result;
}

// Get one document by ID
function fbGetOne($firestore, $collection, $id) {
    $doc = $firestore->collection($collection)->document($id)->snapshot();
    if ($doc->exists()) {
        return array_merge(['id' => $doc->id()], $doc->data());
    }
    return null;
}

// Update document
function fbUpdate($firestore, $collection, $id, $data) {
    $updates = [];
    foreach ($data as $key => $value) {
        $updates[] = ['path' => $key, 'value' => $value];
    }
    $firestore->collection($collection)->document($id)->update($updates);
}

// Delete document
function fbDelete($firestore, $collection, $id) {
    $firestore->collection($collection)->document($id)->delete();
}

// Transaction: Place Order (touches orders, order_item, payment, delivery)
function fbPlaceOrder($firestore, $orderData, $orderItems, $paymentData, $deliveryData) {
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
}

// Reconcile and Sync Firebase Customer registrations into MySQL
function syncFirebaseCustomersToMySql($pdo, $firestore) {
    if (!$firestore || $firestore instanceof DummyFirestore) {
        return;
    }
    try {
        $docs = $firestore->collection('customer')->documents();
        foreach ($docs as $doc) {
            if ($doc->exists()) {
                $data = $doc->data();
                $email = $data['Cust_Email'] ?? $data['email'] ?? '';
                if (empty($email)) continue;
                
                // Check if email exists in mysql
                $check = $pdo->prepare("SELECT Cust_ID FROM CUSTOMER WHERE Cust_Email = ?");
                $check->execute([$email]);
                $cust = $check->fetch();
                
                if (!$cust) {
                    // Missing in MySQL! Let's insert it
                    $fname = $data['Cust_FName'] ?? $data['first_name'] ?? $data['fname'] ?? 'New';
                    $lname = $data['Cust_LName'] ?? $data['last_name'] ?? $data['lname'] ?? 'Customer';
                    $num = $data['Cust_Num'] ?? $data['mobile'] ?? $data['mobile_num'] ?? '09000000000';
                    $pass = $data['Cust_Pass'] ?? $data['password'] ?? password_hash('password', PASSWORD_DEFAULT);
                    
                    $insert = $pdo->prepare("INSERT INTO CUSTOMER (Cust_FName, Cust_LName, Cust_Num, Cust_Email, Cust_Pass) VALUES (?, ?, ?, ?, ?)");
                    $insert->execute([$fname, $lname, $num, $email, $pass]);
                    $newId = $pdo->lastInsertId();
                    
                    // Update Firebase with correct mysql_id
                    fbUpdate($firestore, 'customer', $doc->id(), [
                        'Cust_ID' => (string)$newId,
                        'mysql_id' => (string)$newId
                    ]);
                } else {
                    // If exists but Cust_ID is different or missing in Firebase, update Firestore
                    $mysqlId = $cust['Cust_ID'];
                    if (!isset($data['Cust_ID']) || $data['Cust_ID'] != $mysqlId) {
                        fbUpdate($firestore, 'customer', $doc->id(), [
                            'Cust_ID' => (string)$mysqlId,
                            'mysql_id' => (string)$mysqlId
                        ]);
                    }
                }
            }
        }
    } catch (Exception $e) {
        error_log('Error syncing Firebase customers to MySQL: ' . $e->getMessage());
    }
}

// Reconcile and Sync Firebase Menu items into MySQL (addresses "menu items didn't sync")
function syncFirebaseMenuToMySql($pdo, $firestore) {
    if (!$firestore || $firestore instanceof DummyFirestore) {
        return;
    }
    try {
        $docs = $firestore->collection('menu_item')->documents();
        foreach ($docs as $doc) {
            if ($doc->exists()) {
                $data = $doc->data();
                $name = $data['Menu_Name'] ?? $data['name'] ?? '';
                if (empty($name)) continue;
                
                // Check if menu item exists in mysql by name
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
                    // Insert into MySQL
                    $insert = $pdo->prepare("INSERT INTO MENU_ITEM (Menu_Category, Menu_Name, Menu_Description, Menu_Image, Menu_Price, Menu_Status, Menu_Size, Menu_Brnch_ID) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $insert->execute([$category, $name, $desc, $image, $price, $status, $size, $branchId]);
                    $newId = $pdo->lastInsertId();
                    
                    // Update Firebase with correct mysql_id
                    fbUpdate($firestore, 'menu_item', $doc->id(), [
                        'Menu_ID' => (string)$newId,
                        'mysql_id' => (string)$newId
                    ]);
                } else {
                    // Update Firebase record ID if missing or mismatched
                    $mysqlId = $item['Menu_ID'];
                    if (!isset($data['Menu_ID']) || $data['Menu_ID'] != $mysqlId) {
                        fbUpdate($firestore, 'menu_item', $doc->id(), [
                            'Menu_ID' => (string)$mysqlId,
                            'mysql_id' => (string)$mysqlId
                        ]);
                    }
                }
            }
        }
    } catch (Exception $e) {
        error_log('Error syncing Firebase menu items to MySQL: ' . $e->getMessage());
    }
}

// Reconcile and Sync Firebase Branches into MySQL
function syncFirebaseBranchesToMySql($pdo, $firestore) {
    if (!$firestore || $firestore instanceof DummyFirestore) {
        return;
    }
    try {
        $docs = $firestore->collection('branch')->documents();
        foreach ($docs as $doc) {
            if ($doc->exists()) {
                $data = $doc->data();
                $name = $data['Brnch_Name'] ?? $data['name'] ?? '';
                if (empty($name)) continue;
                
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
                } else {
                    $mysqlId = $branch['Brnch_ID'];
                    if (!isset($data['Brnch_ID']) || $data['Brnch_ID'] != $mysqlId) {
                        fbUpdate($firestore, 'branch', $doc->id(), [
                            'Brnch_ID' => (string)$mysqlId,
                            'mysql_id' => (string)$mysqlId
                        ]);
                    }
                }
            }
        }
    } catch (Exception $e) {
        error_log('Error syncing Firebase branches to MySQL: ' . $e->getMessage());
    }
}
?>
