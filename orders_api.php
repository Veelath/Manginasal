<?php
session_start();
include 'db.php';
include 'firebase.php'; // ← Firebase sync added
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';

if (!isset($_SESSION['user_id'])) {
    if ($action !== 'get_branches') {
        echo json_encode(['success' => false, 'message' => 'Not logged in.']);
        exit;
    }
}

$user_id = $_SESSION['user_id'] ?? null;
$role = $_SESSION['role'] ?? null;

// Ensure database schema is updated with new columns
function ensureSchemaUpdate($pdo) {
    $columns = $pdo->query("SHOW COLUMNS FROM ADDRESS")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('Add_Brgy', $columns)) {
        $pdo->exec("ALTER TABLE ADDRESS ADD COLUMN Add_Brgy VARCHAR(50) NOT NULL AFTER Add_City");
    }
    if (!in_array('Add_PostalCode', $columns)) {
        $pdo->exec("ALTER TABLE ADDRESS ADD COLUMN Add_PostalCode VARCHAR(20) AFTER Add_Landmark");
    }
}

try {
    ensureSchemaUpdate($pdo);

    // ============================================================
    // 1. DATA ENTRY INTERFACE — place_order
    //    Stores new order data from UI fields into Firebase
    // ============================================================
    if ($action === 'get_branches') {
        $stmt = $pdo->query("SELECT * FROM BRANCH WHERE Brnch_Status = 'Y'");
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
    }
    elseif ($action === 'get_branch_menu') {
        $branch_id = $data['branch_id'];
        $stmt = $pdo->prepare("
            SELECT m.*, b.Brnch_Name as Creator_Branch_Name, IFNULL(bm.Stock_Qty, 50) as Stock_Qty 
            FROM MENU_ITEM m 
            LEFT JOIN BRANCH b ON m.Menu_Brnch_ID = b.Brnch_ID
            LEFT JOIN BRANCH_MENU bm ON m.Menu_ID = bm.Menu_ID AND bm.Brnch_ID = ?
            WHERE m.Menu_Status = 'Y' 
              AND (m.Menu_Brnch_ID IS NULL OR m.Menu_Brnch_ID = ?)
              AND (bm.Is_Available IS NULL OR bm.Is_Available = 'Y')
        ");
        $stmt->execute([$branch_id, $branch_id]);
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
    }
    elseif ($action === 'place_order') {
        // ── DATA ENTRY: fetches data from UI text fields and stores in Firebase ──
        if ($role !== 'Customer') {
            echo json_encode(['success' => false, 'message' => 'Only customers can place orders.']);
            exit;
        }

        $branch_id      = $data['branch_id'];
        $type           = $data['type'];
        $items          = $data['items'];
        $total          = floatval($data['total']);
        $recipient_name = $data['name'];
        $recipient_num  = $data['num'];
        $payment_method = $data['payment_method'] ?? null;
        $manual_address = $data['address'] ?? null;
        $address_id     = $data['address_id'] ?? null;

        if (empty($items)) {
            echo json_encode(['success' => false, 'message' => 'Your tray is empty.']);
            exit;
        }
        if (!$payment_method) {
            echo json_encode(['success' => false, 'message' => 'Payment method is required.']);
            exit;
        }
        if ($type === 'Delivery') {
            if ($total < 200) {
                echo json_encode(['success' => false, 'message' => 'Minimum order for delivery is ₱200.']);
                exit;
            }
            if (!$manual_address && !$address_id) {
                $stmt = $pdo->prepare("SELECT * FROM ADDRESS WHERE Add_Cust_ID = ? AND Add_City != 'N/A' AND Add_Street NOT LIKE '%Branch Pickup%' LIMIT 1");
                $stmt->execute([$user_id]);
                $addr = $stmt->fetch();
                if (!$addr) {
                    echo json_encode(['success' => false, 'message' => 'A valid delivery address is required.']);
                    exit;
                }
            } elseif ($manual_address) {
                if (empty(trim($manual_address['street'])) || empty(trim($manual_address['city'])) || empty(trim($manual_address['brgy']))) {
                    echo json_encode(['success' => false, 'message' => 'Please provide a complete delivery address.']);
                    exit;
                }
            }
        }

        $order_code = 'MNGR-' . strtoupper(substr(uniqid(), 7));

        $pdo->beginTransaction();

        // Handle address
        if ($manual_address) {
            $stmt = $pdo->prepare("INSERT INTO ADDRESS (Add_Cust_ID, Add_Province, Add_City, Add_Brgy, Add_Street, Add_Landmark, Add_PostalCode, Add_Label) VALUES (?, ?, ?, ?, ?, ?, ?, 'Order Address')");
            $stmt->execute([
                $user_id,
                $manual_address['province'],
                $manual_address['city'],
                $manual_address['brgy'] ?? '',
                $manual_address['street'],
                $manual_address['landmark'] ?? '',
                $manual_address['postal'] ?? '',
            ]);
            $address_id = $pdo->lastInsertId();
        } elseif (!$address_id) {
            $stmt = $pdo->prepare("SELECT Add_ID FROM ADDRESS WHERE Add_Cust_ID = ? ORDER BY Add_ID DESC LIMIT 1");
            $stmt->execute([$user_id]);
            $addr = $stmt->fetch();
            if (!$addr) {
                $stmt = $pdo->prepare("INSERT INTO ADDRESS (Add_Cust_ID, Add_Province, Add_City, Add_Street, Add_Label) VALUES (?, 'N/A', 'N/A', 'Branch Pickup/Dine-in', 'Store')");
                $stmt->execute([$user_id]);
                $address_id = $pdo->lastInsertId();
            } else {
                $address_id = $addr['Add_ID'];
            }
        }

        // Insert order into MySQL
        $stmt = $pdo->prepare("
            INSERT INTO orders (
                Order_Cust_ID, Order_Brnch_ID, Order_Add_ID, Order_Code,
                Order_Type, Order_Recipient_Name, Order_Recipient_Num,
                Order_Subtotal, Order_Total_Amount, Order_Stat
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending')
        ");
        $stmt->execute([
            $user_id, $branch_id, $address_id, $order_code,
            $type, $recipient_name, $recipient_num,
            $total, $total
        ]);
        $order_id = $pdo->lastInsertId();

        // Insert payment into MySQL
        $stmt = $pdo->prepare("INSERT INTO PAYMENT (Pay_Order_ID, Pay_Amount, Pay_Method, Pay_Status) VALUES (?, ?, ?, 'Pending')");
        $stmt->execute([$order_id, $total, $payment_method]);

        // Insert order items + update stock in MySQL
        foreach ($items as $idx => $item) {
            $stmt = $pdo->prepare("
                INSERT INTO ORDER_ITEM (
                    Order_ID, OItem_ID, OItem_Menu_ID, OItem_Base_Price,
                    OItem_Unit_Price, OItem_Quantity
                ) VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $order_id, $idx + 1, $item['menu_id'], $item['price'],
                $item['price'], $item['qty']
            ]);
            $stmtStock = $pdo->prepare("
                INSERT INTO BRANCH_MENU (Brnch_ID, Menu_ID, Is_Available, Stock_Qty)
                VALUES (?, ?, 'Y', GREATEST(0, 50 - ?))
                ON DUPLICATE KEY UPDATE Stock_Qty = GREATEST(0, Stock_Qty - ?)
            ");
            $stmtStock->execute([$branch_id, $item['menu_id'], $item['qty'], $item['qty']]);
        }

        $pdo->commit();

        // ── FIREBASE TRANSACTION: saves order, order_items, payment, delivery atomically ──
        try {
            $fb_order_items = array_map(function($item) {
                return [
                    'menu_id'    => (string)$item['menu_id'],
                    'price'      => (string)$item['price'],
                    'qty'        => (string)$item['qty'],
                ];
            }, $items);

            $fb_order_id = fbPlaceOrder(
                $firestore,
                // ORDER data (Data Entry Interface)
                [
                    'mysql_order_id'    => (string)$order_id,
                    'mysql_id'          => (string)$order_id,
                    'order_code'        => $order_code,
                    'customer_id'       => (string)$user_id,
                    'branch_id'         => (string)$branch_id,
                    'type'              => $type,
                    'recipient_name'    => $recipient_name,
                    'recipient_num'     => $recipient_num,
                    'total'             => (string)$total,
                    'status'            => 'Pending',
                    'created_at'        => date('Y-m-d H:i:s'),
                ],
                // ORDER ITEMS
                $fb_order_items,
                // PAYMENT data
                [
                    'mysql_order_id'    => (string)$order_id,
                    'mysql_id'          => (string)$order_id,
                    'method'  => $payment_method,
                    'amount'  => (string)$total,
                    'status'  => 'Pending',
                ],
                // DELIVERY data
                [
                    'mysql_order_id'    => (string)$order_id,
                    'mysql_id'          => (string)$order_id,
                    'address'    => $manual_address ? ($manual_address['street'] . ', ' . $manual_address['city']) : '',
                    'status'     => 'Pending',
                    'rider_id'   => '',
                    'eta'        => '',
                ]
            );
        } catch (Exception $fbErr) {
            // Firebase sync failed silently — MySQL already committed
            error_log('Firebase sync error (place_order): ' . $fbErr->getMessage());
        }

        echo json_encode(['success' => true, 'message' => "Order placed successfully! Order Code: $order_code", 'order_id' => $order_id]);
    }

    // ============================================================
    // 2. DISPLAY INTERFACE — get_customer_orders
    //    Retrieves order data from Firebase and returns to UI
    // ============================================================
    elseif ($action === 'get_customer_orders') {
        // Try Firebase first, fallback to MySQL
        try {
            $fb_orders = [];
            $docs = $firestore->collection('orders')->documents();
            foreach ($docs as $doc) {
                if ($doc->exists()) {
                    $order = array_merge(['id' => $doc->id()], $doc->data());
                    if (isset($order['customer_id']) && $order['customer_id'] == (string)$user_id) {
                        // Get order items from Firebase
                        $itemDocs = $firestore->collection('order_item')->documents();
                        $order['Order_Items'] = [];
                        foreach ($itemDocs as $itemDoc) {
                            if ($itemDoc->exists()) {
                                $item = $itemDoc->data();
                                if (isset($item['order_id']) && $item['order_id'] === $doc->id()) {
                                    $order['Order_Items'][] = $item;
                                }
                            }
                        }
                        $fb_orders[] = $order;
                    }
                }
            }
            if (!empty($fb_orders)) {
                echo json_encode(['success' => true, 'data' => $fb_orders, 'source' => 'firebase']);
                exit;
            }
        } catch (Exception $fbErr) {
            error_log('Firebase fetch error (get_customer_orders): ' . $fbErr->getMessage());
        }

        // Fallback to MySQL
        $stmt = $pdo->prepare("
            SELECT o.*, p.Pay_Method, p.Pay_Status, d.Dlvry_Pickup_Time, d.Dlvry_Arrival_Time, d.Dlvry_Current_ETA, r.Rider_FName, r.Rider_LName, r.Rider_MobileNum
            FROM orders o
            LEFT JOIN PAYMENT p ON o.Order_ID = p.Pay_Order_ID
            LEFT JOIN DELIVERY d ON o.Order_ID = d.Dlvry_Order_ID
            LEFT JOIN RIDER r ON d.Dlvry_Rider_ID = r.Rider_ID
            WHERE o.Order_Cust_ID = ?
            ORDER BY o.Order_ID DESC
        ");
        $stmt->execute([$user_id]);
        $orders = $stmt->fetchAll();
        foreach ($orders as &$order) {
            $stmtItems = $pdo->prepare("SELECT oi.*, m.Menu_Name FROM ORDER_ITEM oi JOIN MENU_ITEM m ON oi.OItem_Menu_ID = m.Menu_ID WHERE oi.Order_ID = ?");
            $stmtItems->execute([$order['Order_ID']]);
            $order['Order_Items'] = $stmtItems->fetchAll();
        }
        echo json_encode(['success' => true, 'data' => $orders, 'source' => 'mysql']);
    }

    // ============================================================
    // 3. UPDATE/DELETE INTERFACE — complete_delivery, update_eta
    //    Updates or deletes records in Firebase through the UI
    // ============================================================
    elseif ($action === 'complete_delivery') {
        if ($role !== 'Driver') {
            echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
            exit;
        }
        $order_id = $data['order_id'];

        // MySQL update
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("UPDATE orders SET Order_Stat = 'Completed' WHERE Order_ID = ?");
        $stmt->execute([$order_id]);
        $stmt = $pdo->prepare("UPDATE DELIVERY SET Dlvry_Arrival_Time = NOW() WHERE Dlvry_Order_ID = ?");
        $stmt->execute([$order_id]);
        $stmt = $pdo->prepare("UPDATE PAYMENT SET Pay_Status = 'Paid' WHERE Pay_Order_ID = ? AND Pay_Method = 'Cash (COD)'");
        $stmt->execute([$order_id]);
        $stmt = $pdo->prepare("SELECT Dlvry_Rider_ID FROM DELIVERY WHERE Dlvry_Order_ID = ?");
        $stmt->execute([$order_id]);
        $rider_id = $stmt->fetchColumn();
        if ($rider_id) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM orders o JOIN DELIVERY d ON o.Order_ID = d.Dlvry_Order_ID WHERE d.Dlvry_Rider_ID = ? AND o.Order_Stat = 'Delivering'");
            $stmt->execute([$rider_id]);
            $remaining = $stmt->fetchColumn();
            if ($remaining == 0) {
                $stmt = $pdo->prepare("UPDATE RIDER SET Rider_Status = 'Y' WHERE Rider_ID = ?");
                $stmt->execute([$rider_id]);
            }
        }
        $pdo->commit();

        // ── FIREBASE UPDATE: update order status in Firestore ──
        try {
            $docs = $firestore->collection('orders')->documents();
            foreach ($docs as $doc) {
                if ($doc->exists() && fbMatchesId($doc, $order_id, ['Order_ID'])) {
                    fbUpdate($firestore, 'orders', $doc->id(), [
                        'status'       => 'Completed',
                        'completed_at' => date('Y-m-d H:i:s'),
                    ]);
                    // Update payment status in Firebase
                    $payDocs = $firestore->collection('payment')->documents();
                    foreach ($payDocs as $payDoc) {
                        if ($payDoc->exists() && (
                            (isset($payDoc->data()['order_id']) && $payDoc->data()['order_id'] === $doc->id()) ||
                            fbMatchesId($payDoc, $order_id, ['Pay_Order_ID'])
                        )) {
                            fbUpdate($firestore, 'payment', $payDoc->id(), ['status' => 'Paid']);
                        }
                    }
                    break;
                }
            }
        } catch (Exception $fbErr) {
            error_log('Firebase sync error (complete_delivery): ' . $fbErr->getMessage());
        }

        echo json_encode(['success' => true, 'message' => 'Delivery completed!']);
    }
    elseif ($action === 'update_eta') {
        if ($role !== 'Driver') {
            echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
            exit;
        }
        $order_id = $data['order_id'];
        $eta      = $data['eta'];

        if (preg_match('/^([01][0-9]|2[0-3]):([0-5][0-9])$/', $eta)) {
            $eta_datetime = date('Y-m-d') . ' ' . $eta . ':00';
        } else {
            $eta_datetime = $eta;
        }

        // MySQL update
        $stmt = $pdo->prepare("UPDATE DELIVERY SET Dlvry_Current_ETA = ? WHERE Dlvry_Order_ID = ?");
        $stmt->execute([$eta_datetime, $order_id]);

        // ── FIREBASE UPDATE: update ETA in Firestore delivery collection ──
        try {
            $deliveryDocs = $firestore->collection('delivery')->documents();
            foreach ($deliveryDocs as $doc) {
                if ($doc->exists() && fbMatchesId($doc, $order_id, ['Dlvry_Order_ID'])) {
                    fbUpdate($firestore, 'delivery', $doc->id(), ['eta' => $eta_datetime]);
                    break;
                }
            }
        } catch (Exception $fbErr) {
            error_log('Firebase sync error (update_eta): ' . $fbErr->getMessage());
        }

        echo json_encode(['success' => true, 'message' => 'ETA updated successfully!']);
    }

    // ============================================================
    // 4. TRANSACTION INTERFACE — place_order handles this above
    //    fbPlaceOrder() writes to orders + order_item + payment + delivery atomically
    // ============================================================

    elseif ($action === 'get_profile') {
        if ($role === 'Customer') {
            $stmt = $pdo->prepare("SELECT Cust_FName, Cust_LName, Cust_Email, Cust_Num FROM CUSTOMER WHERE Cust_ID = ?");
            $stmt->execute([$user_id]);
            $profile = $stmt->fetch();
            $profileData = ['Cust_FName' => $profile['Cust_FName'], 'Cust_LName' => $profile['Cust_LName'], 'Cust_Email' => $profile['Cust_Email'], 'Cust_MobileNum' => $profile['Cust_Num']];
        } elseif ($role === 'System Admin') {
            $stmt = $pdo->prepare("SELECT Admin_FName, Admin_LName, Admin_Email, Admin_MobileNum FROM SYSTEM_ADMIN WHERE Admin_ID = ?");
            $stmt->execute([$user_id]);
            $profile = $stmt->fetch();
            $profileData = ['Cust_FName' => $profile['Admin_FName'], 'Cust_LName' => $profile['Admin_LName'], 'Cust_Email' => $profile['Admin_Email'], 'Cust_MobileNum' => $profile['Admin_MobileNum']];
        } elseif ($role === 'Branch Manager') {
            $stmt = $pdo->prepare("SELECT Mgr_FName, Mgr_LName, Mgr_Email, Mgr_MobileNum FROM BRANCH_MANAGER WHERE Mgr_ID = ?");
            $stmt->execute([$user_id]);
            $profile = $stmt->fetch();
            $profileData = ['Cust_FName' => $profile['Mgr_FName'], 'Cust_LName' => $profile['Mgr_LName'], 'Cust_Email' => $profile['Mgr_Email'], 'Cust_MobileNum' => $profile['Mgr_MobileNum']];
        } elseif ($role === 'Driver') {
            $stmt = $pdo->prepare("SELECT Rider_FName, Rider_LName, Rider_Email, Rider_MobileNum FROM RIDER WHERE Rider_ID = ?");
            $stmt->execute([$user_id]);
            $profile = $stmt->fetch();
            $profileData = ['Cust_FName' => $profile['Rider_FName'], 'Cust_LName' => $profile['Rider_LName'], 'Cust_Email' => $profile['Rider_Email'], 'Cust_MobileNum' => $profile['Rider_MobileNum']];
        } else {
            $stmt = $pdo->prepare("SELECT Staff_FName, Staff_LName, Staff_Email, Staff_MobileNum FROM STAFF WHERE Staff_ID = ?");
            $stmt->execute([$user_id]);
            $profile = $stmt->fetch();
            $profileData = ['Cust_FName' => $profile['Staff_FName'], 'Cust_LName' => $profile['Staff_LName'], 'Cust_Email' => $profile['Staff_Email'], 'Cust_MobileNum' => $profile['Staff_MobileNum']];
        }
        $stmt = $pdo->prepare("SELECT * FROM ADDRESS WHERE Add_Cust_ID = ? ORDER BY Add_ID DESC");
        $stmt->execute([$user_id]);
        $addresses = $stmt->fetchAll();
        echo json_encode(['success' => true, 'profile' => $profileData, 'addresses' => $addresses]);
    }
    elseif ($action === 'update_profile') {
        $fname  = trim($data['fname']);
        $lname  = trim($data['lname']);
        $mobile = trim($data['mobile']);

        if (empty($fname) || empty($lname) || empty($mobile)) {
            echo json_encode(['success' => false, 'message' => 'All fields are required.']);
            exit;
        }
        if (!preg_match('/^[0-9]{11}$/', $mobile)) {
            echo json_encode(['success' => false, 'message' => 'Mobile number must be 11 digits.']);
            exit;
        }

        if ($role === 'Customer') {
            $stmt = $pdo->prepare("UPDATE CUSTOMER SET Cust_FName = ?, Cust_LName = ?, Cust_Num = ? WHERE Cust_ID = ?");
        } elseif ($role === 'System Admin') {
            $stmt = $pdo->prepare("UPDATE SYSTEM_ADMIN SET Admin_FName = ?, Admin_LName = ?, Admin_MobileNum = ? WHERE Admin_ID = ?");
        } elseif ($role === 'Branch Manager') {
            $stmt = $pdo->prepare("UPDATE BRANCH_MANAGER SET Mgr_FName = ?, Mgr_LName = ?, Mgr_MobileNum = ? WHERE Mgr_ID = ?");
        } elseif ($role === 'Driver') {
            $stmt = $pdo->prepare("UPDATE RIDER SET Rider_FName = ?, Rider_LName = ?, Rider_MobileNum = ? WHERE Rider_ID = ?");
        } else {
            $stmt = $pdo->prepare("UPDATE STAFF SET Staff_FName = ?, Staff_LName = ?, Staff_MobileNum = ? WHERE Staff_ID = ?");
        }
        $stmt->execute([$fname, $lname, $mobile, $user_id]);

        // ── FIREBASE UPDATE: sync profile update to Firestore ──
        try {
            $collection = '';
            if ($role === 'Customer') $collection = 'customer';
            elseif ($role === 'Driver') $collection = 'rider';
            elseif ($role === 'Branch Manager') $collection = 'branch_manager';
            elseif ($role === 'System Admin') $collection = 'system_admin';
            else $collection = 'staff';

            if ($collection) {
                $docs = $firestore->collection($collection)->documents();
                $idKeysMap = ['customer' => ['Cust_ID'], 'rider' => ['Rider_ID'], 'branch_manager' => ['Mgr_ID'], 'system_admin' => ['Admin_ID'], 'staff' => ['Staff_ID']];
                $idKeys = $idKeysMap[$collection] ?? [];
                foreach ($docs as $doc) {
                    if ($doc->exists() && fbMatchesId($doc, $user_id, $idKeys)) {
                        fbUpdate($firestore, $collection, $doc->id(), [
                            'first_name'  => $fname,
                            'last_name'   => $lname,
                            'mobile'      => $mobile,
                            'updated_at'  => date('Y-m-d H:i:s'),
                        ]);
                        break;
                    }
                }
            }
        } catch (Exception $fbErr) {
            error_log('Firebase sync error (update_profile): ' . $fbErr->getMessage());
        }

        echo json_encode(['success' => true, 'message' => 'Profile updated successfully!']);
    }
    elseif ($action === 'add_address') {
        $label    = trim($data['label']);
        $province = trim($data['province']);
        $city     = trim($data['city']);
        $brgy     = trim($data['brgy']);
        $street   = trim($data['street']);
        $unit     = trim($data['unit'] ?? '');
        $building = trim($data['building'] ?? '');
        $landmark = trim($data['landmark'] ?? '');
        $postal   = trim($data['postal'] ?? '');

        if (empty($label) || empty($province) || empty($city) || empty($brgy) || empty($street)) {
            echo json_encode(['success' => false, 'message' => 'Label, Province, City, Barangay, and Street are required.']);
            exit;
        }

        // MySQL insert
        $stmt = $pdo->prepare("INSERT INTO ADDRESS (Add_Cust_ID, Add_Province, Add_City, Add_Brgy, Add_Street, Add_UnitNum, Add_Building, Add_Landmark, Add_PostalCode, Add_Label) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $province, $city, $brgy, $street, $unit, $building, $landmark, $postal, $label]);
        $new_address_id = $pdo->lastInsertId();

        // ── FIREBASE DATA ENTRY: store new address in Firestore ──
        try {
            fbAdd($firestore, 'address', [
                'mysql_id'    => (string)$new_address_id,
                'customer_id' => (string)$user_id,
                'label'       => $label,
                'province'    => $province,
                'city'        => $city,
                'brgy'        => $brgy,
                'street'      => $street,
                'unit'        => $unit,
                'building'    => $building,
                'landmark'    => $landmark,
                'postal'      => $postal,
                'created_at'  => date('Y-m-d H:i:s'),
            ]);
        } catch (Exception $fbErr) {
            error_log('Firebase sync error (add_address): ' . $fbErr->getMessage());
        }

        echo json_encode(['success' => true, 'message' => 'Address added successfully!']);
    }
    elseif ($action === 'delete_address') {
        $id = $data['id'];

        // MySQL delete
        $stmt = $pdo->prepare("DELETE FROM ADDRESS WHERE Add_ID = ? AND Add_Cust_ID = ?");
        $stmt->execute([$id, $user_id]);

        // ── FIREBASE DELETE: remove address from Firestore ──
        try {
            $docs = $firestore->collection('address')->documents();
            foreach ($docs as $doc) {
                if ($doc->exists() && fbMatchesId($doc, $id, ['Add_ID'])) {
                    fbDelete($firestore, 'address', $doc->id());
                    break;
                }
            }
        } catch (Exception $fbErr) {
            error_log('Firebase sync error (delete_address): ' . $fbErr->getMessage());
        }

        echo json_encode(['success' => true, 'message' => 'Address removed.']);
    }
    elseif ($action === 'get_rider_deliveries') {
        if ($role !== 'Driver') {
            echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
            exit;
        }
        $stmt = $pdo->prepare("
            SELECT o.*, c.Cust_FName, c.Cust_LName, a.Add_Street, a.Add_City, p.Pay_Method, p.Pay_Amount, p.Pay_Status, d.Dlvry_Current_ETA
            FROM orders o
            JOIN CUSTOMER c ON o.Order_Cust_ID = c.Cust_ID
            JOIN ADDRESS a ON o.Order_Add_ID = a.Add_ID
            JOIN DELIVERY d ON o.Order_ID = d.Dlvry_Order_ID
            LEFT JOIN PAYMENT p ON o.Order_ID = p.Pay_Order_ID
            WHERE d.Dlvry_Rider_ID = ? AND o.Order_Stat = 'Delivering'
        ");
        $stmt->execute([$user_id]);
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
    }
    elseif ($action === 'get_rider_history') {
        if ($role !== 'Driver') {
            echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
            exit;
        }
        $stmt = $pdo->prepare("
            SELECT o.*, c.Cust_FName, c.Cust_LName, a.Add_Street, a.Add_City, p.Pay_Method, p.Pay_Amount, p.Pay_Status, d.Dlvry_Arrival_Time
            FROM orders o
            JOIN CUSTOMER c ON o.Order_Cust_ID = c.Cust_ID
            JOIN ADDRESS a ON o.Order_Add_ID = a.Add_ID
            JOIN DELIVERY d ON o.Order_ID = d.Dlvry_Order_ID
            LEFT JOIN PAYMENT p ON o.Order_ID = p.Pay_Order_ID
            WHERE d.Dlvry_Rider_ID = ? AND o.Order_Stat = 'Completed'
            ORDER BY o.Order_ID DESC
            LIMIT 50
        ");
        $stmt->execute([$user_id]);
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}