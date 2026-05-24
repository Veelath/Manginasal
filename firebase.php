<?php
require_once __DIR__ . '/vendor/autoload.php';

use Kreait\Firebase\Factory;

$factory = (new Factory)
    ->withServiceAccount('C:/xampp/db_export/manginasaldb-firebase-adminsdk-fbsvc-947b854f43.json');

$firestore = $factory->createFirestore()->database();

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