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

    $credFile = findServiceAccountFile();
    if (!$credFile) {
        throw new Exception("Firebase Service Account credentials JSON file not found in " . __DIR__ . "/ or hardcoded paths. Please place your *.json credentials file in the project folder.");
    }

    $factory = (new \Kreait\Firebase\Factory)->withServiceAccount($credFile);
    $firestore = $factory->createFirestore()->database();

} catch (Exception $e) {
    $firebase_error = $e->getMessage();
    $firestore = new DummyFirestore("Firebase/Firestore Error: " . $firebase_error);
}

// Add document
function fbAdd($firestore, $collection, $data) {
    return $firestore->collection($collection)->add($data);
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
?>
