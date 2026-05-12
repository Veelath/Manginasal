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
        $total = $data['total'];
        $recipient_name = $data['name'];
        $recipient_num = $data['num'];

        $order_code = 'MNGR-' . strtoupper(substr(uniqid(), 7));

        $pdo->beginTransaction();
        
        // Find or create address
        $stmt = $pdo->prepare("SELECT Add_ID FROM ADDRESS WHERE Add_Cust_ID = ? LIMIT 1");
        $stmt->execute([$user_id]);
        $addr = $stmt->fetch();
        
        if (!$addr) {
            $stmt = $pdo->prepare("INSERT INTO ADDRESS (Add_Cust_ID, Add_Province, Add_City, Add_Street, Add_Label) VALUES (?, 'Metro Manila', 'Manila', 'Default St', 'Home')");
            $stmt->execute([$user_id]);
            $address_id = $pdo->lastInsertId();
        } else {
            $address_id = $addr['Add_ID'];
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
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE Order_Cust_ID = ? ORDER BY Order_ID DESC");
        $stmt->execute([$user_id]);
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
    }
    elseif ($action === 'get_profile') {
        $stmt = $pdo->prepare("SELECT Cust_FName, Cust_LName, Cust_Email, Cust_MobileNum FROM CUSTOMER WHERE Cust_ID = ?");
        $stmt->execute([$user_id]);
        $profile = $stmt->fetch();
        
        $stmt = $pdo->prepare("SELECT * FROM ADDRESS WHERE Add_Cust_ID = ?");
        $stmt->execute([$user_id]);
        $addresses = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'profile' => $profile, 'addresses' => $addresses]);
    }
    elseif ($action === 'get_rider_deliveries') {
        if ($role !== 'Driver') {
            echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
            exit;
        }
        $stmt = $pdo->prepare("
            SELECT o.*, c.Cust_FName, c.Cust_LName, a.Add_Street, a.Add_City
            FROM orders o
            JOIN CUSTOMER c ON o.Order_Cust_ID = c.Cust_ID
            JOIN ADDRESS a ON o.Order_Add_ID = a.Add_ID
            JOIN DELIVERY d ON o.Order_ID = d.Dlvry_Order_ID
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
        
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Delivery completed!']);
    }
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => "Database error: " . $e->getMessage()]);
}
