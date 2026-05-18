<?php
session_start();
include 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';

try {
    if ($action === 'get_branches') {
        $stmt = $pdo->query("SELECT * FROM BRANCH WHERE Brnch_Status = 'Y'");
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
    }
    elseif ($action === 'get_branch_menu') {
        $branch_id = $data['branch_id'];
        $stmt = $pdo->prepare("
            SELECT m.* 
            FROM MENU_ITEM m 
            LEFT JOIN BRANCH_MENU bm ON m.Menu_ID = bm.Menu_ID AND bm.Brnch_ID = ?
            WHERE m.Menu_Status = 'Y' AND (bm.Is_Available IS NULL OR bm.Is_Available = 'Y')
        ");
        $stmt->execute([$branch_id]);
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
    }
    elseif ($action === 'place_order') {
        if ($role !== 'Customer') {
            echo json_encode(['success' => false, 'message' => 'Only customers can place orders.']);
            exit;
        }

        $branch_id = $data['branch_id'];
        $type = $data['type']; // Delivery, Take-out, Dine-in
        $items = $data['items']; // Array of {menu_id, qty, price}
        $total = floatval($data['total']);
        $recipient_name = $data['name'];
        $recipient_num = $data['num'];
        $payment_method = $data['payment_method'] ?? null;
        $manual_address = $data['address'] ?? null; // {province, city, street, brgy, landmark}

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

            if (!$manual_address) {
                // Check if user has a valid existing address (not a dummy one)
                $stmt = $pdo->prepare("SELECT * FROM ADDRESS WHERE Add_Cust_ID = ? AND Add_City != 'N/A' AND Add_Street NOT LIKE '%Branch Pickup%' LIMIT 1");
                $stmt->execute([$user_id]);
                $addr = $stmt->fetch();
                if (!$addr) {
                    echo json_encode(['success' => false, 'message' => 'A valid delivery address is required. Please use manual entry if you haven\'t set one up.']);
                    exit;
                }
            } else {
                // Validate manual address fields
                if (empty(trim($manual_address['street'])) || empty(trim($manual_address['city'])) || empty(trim($manual_address['brgy']))) {
                    echo json_encode(['success' => false, 'message' => 'Please provide a complete delivery address (Street, Barangay, and City).']);
                    exit;
                }
            }
        }

        $order_code = 'MNGR-' . strtoupper(substr(uniqid(), 7));

        $pdo->beginTransaction();
        
        // Find or create address
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
        } else {
            // For Delivery, use the same strict filter as validation
            if ($type === 'Delivery') {
                $stmt = $pdo->prepare("SELECT Add_ID FROM ADDRESS WHERE Add_Cust_ID = ? AND Add_City != 'N/A' AND Add_Street NOT LIKE '%Branch Pickup%' ORDER BY Add_ID DESC LIMIT 1");
            } else {
                $stmt = $pdo->prepare("SELECT Add_ID FROM ADDRESS WHERE Add_Cust_ID = ? ORDER BY Add_ID DESC LIMIT 1");
            }
            $stmt->execute([$user_id]);
            $addr = $stmt->fetch();
            
            if (!$addr) {
                // For non-delivery, create dummy if none exists
                $stmt = $pdo->prepare("INSERT INTO ADDRESS (Add_Cust_ID, Add_Province, Add_City, Add_Street, Add_Label) VALUES (?, 'N/A', 'N/A', 'Branch Pickup/Dine-in', 'Store')");
                $stmt->execute([$user_id]);
                $address_id = $pdo->lastInsertId();
            } else {
                $address_id = $addr['Add_ID'];
            }
        }
        
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

        // Create Payment record
        $stmt = $pdo->prepare("INSERT INTO PAYMENT (Pay_Order_ID, Pay_Amount, Pay_Method, Pay_Status) VALUES (?, ?, ?, 'Pending')");
        $stmt->execute([$order_id, $total, $payment_method]);

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
        }

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => "Order placed successfully! Order Code: $order_code", 'order_id' => $order_id]);
    }
    elseif ($action === 'get_customer_orders') {
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
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
    }
    elseif ($action === 'get_profile') {
        $stmt = $pdo->prepare("SELECT Cust_FName, Cust_LName, Cust_Email, Cust_Num FROM CUSTOMER WHERE Cust_ID = ?");
        $stmt->execute([$user_id]);
        $profile = $stmt->fetch();
        
        $stmt = $pdo->prepare("SELECT * FROM ADDRESS WHERE Add_Cust_ID = ? ORDER BY Add_ID DESC");
        $stmt->execute([$user_id]);
        $addresses = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true, 
            'profile' => [
                'Cust_FName' => $profile['Cust_FName'],
                'Cust_LName' => $profile['Cust_LName'],
                'Cust_Email' => $profile['Cust_Email'],
                'Cust_MobileNum' => $profile['Cust_Num']
            ], 
            'addresses' => $addresses
        ]);
    }
    elseif ($action === 'update_profile') {
        $fname = trim($data['fname']);
        $lname = trim($data['lname']);
        $mobile = trim($data['mobile']);

        if (empty($fname) || empty($lname) || empty($mobile)) {
            echo json_encode(['success' => false, 'message' => 'All fields are required.']);
            exit;
        }

        if (!preg_match('/^[0-9]{11}$/', $mobile)) {
            echo json_encode(['success' => false, 'message' => 'Mobile number must be 11 digits.']);
            exit;
        }

        $stmt = $pdo->prepare("UPDATE CUSTOMER SET Cust_FName = ?, Cust_LName = ?, Cust_Num = ? WHERE Cust_ID = ?");
        $stmt->execute([$fname, $lname, $mobile, $user_id]);

        echo json_encode(['success' => true, 'message' => 'Profile updated successfully!']);
    }
    elseif ($action === 'add_address') {
        $label = trim($data['label']);
        $province = trim($data['province']);
        $city = trim($data['city']);
        $brgy = trim($data['brgy']);
        $street = trim($data['street']);
        $unit = trim($data['unit'] ?? '');
        $building = trim($data['building'] ?? '');
        $landmark = trim($data['landmark'] ?? '');
        $postal = trim($data['postal'] ?? '');

        if (empty($label) || empty($province) || empty($city) || empty($brgy) || empty($street)) {
            echo json_encode(['success' => false, 'message' => 'Label, Province, City, Barangay, and Street are required.']);
            exit;
        }

        // Migration check: Ensure columns exist (for safety if user didn't re-run SQL)
        try {
            $pdo->exec("ALTER TABLE ADDRESS ADD COLUMN Add_Brgy VARCHAR(50) NOT NULL AFTER Add_City");
            $pdo->exec("ALTER TABLE ADDRESS ADD COLUMN Add_PostalCode VARCHAR(10) AFTER Add_Landmark");
        } catch (Exception $e) { /* already exists */ }

        $stmt = $pdo->prepare("INSERT INTO ADDRESS (Add_Cust_ID, Add_Province, Add_City, Add_Brgy, Add_Street, Add_UnitNum, Add_Building, Add_Landmark, Add_PostalCode, Add_Label) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $province, $city, $brgy, $street, $unit, $building, $landmark, $postal, $label]);

        echo json_encode(['success' => true, 'message' => 'Address added successfully!']);
    }
    elseif ($action === 'delete_address') {
        $id = $data['id'];
        $stmt = $pdo->prepare("DELETE FROM ADDRESS WHERE Add_ID = ? AND Add_Cust_ID = ?");
        $stmt->execute([$id, $user_id]);
        echo json_encode(['success' => true, 'message' => 'Address removed.']);
    }
    elseif ($action === 'get_rider_deliveries') {
        if ($role !== 'Driver') {
            echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
            exit;
        }
        $stmt = $pdo->prepare("
            SELECT o.*, c.Cust_FName, c.Cust_LName, a.Add_Street, a.Add_City, p.Pay_Method, p.Pay_Amount
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
    elseif ($action === 'complete_delivery') {
        if ($role !== 'Driver') {
            echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
            exit;
        }
        $order_id = $data['order_id'];
        
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("UPDATE orders SET Order_Stat = 'Completed' WHERE Order_ID = ?");
        $stmt->execute([$order_id]);
        
        $stmt = $pdo->prepare("UPDATE DELIVERY SET Dlvry_Arrival_Time = NOW() WHERE Dlvry_Order_ID = ?");
        $stmt->execute([$order_id]);

        // Get Rider ID for this delivery
        $stmt = $pdo->prepare("SELECT Dlvry_Rider_ID FROM DELIVERY WHERE Dlvry_Order_ID = ?");
        $stmt->execute([$order_id]);
        $rider_id = $stmt->fetchColumn();

        // Check if rider has any other active deliveries
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
        echo json_encode(['success' => true, 'message' => 'Delivery completed!']);
    }
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => "Database error: " . $e->getMessage()]);
}
