<?php
session_start();
include 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Branch Manager', 'Kitchen Staff'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

$branch_id = $_SESSION['branch_id'];
$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';

try {
    if ($action === 'create_staff') {
        $fname = $data['fname'] ?? '';
        $lname = $data['lname'] ?? '';
        $email = $data['email'] ?? '';
        $mobile = $data['mobile'] ?? '';
        $role = $data['role'] ?? 'Kitchen Staff';
        $password = password_hash($data['password'] ?? 'staff123', PASSWORD_DEFAULT);
        $mgr_id = $_SESSION['user_id']; // Manager ID from session

        $stmt = $pdo->prepare("INSERT INTO STAFF (Staff_Brnch_ID, Staff_Mgr_ID, Staff_FName, Staff_LName, Staff_Email, Staff_MobileNum, Staff_Role, Staff_Pass) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$branch_id, $mgr_id, $fname, $lname, $email, $mobile, $role, $password]);

        echo json_encode(['success' => true, 'message' => 'Staff account created!']);
    }
    elseif ($action === 'create_rider') {
        $fname = $data['fname'] ?? '';
        $lname = $data['lname'] ?? '';
        $email = $data['email'] ?? '';
        $mobile = $data['mobile'] ?? '';
        $password = password_hash($data['password'] ?? 'rider123', PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("INSERT INTO RIDER (Rider_Brnch_ID, Rider_FName, Rider_LName, Rider_Email, Rider_MobileNum, Rider_Pass) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$branch_id, $fname, $lname, $email, $mobile, $password]);

        echo json_encode(['success' => true, 'message' => 'Rider account created!']);
    }
    elseif ($action === 'update_staff') {
        $id = $data['id'] ?? null;
        $fname = $data['fname'] ?? '';
        $lname = $data['lname'] ?? '';
        $email = $data['email'] ?? '';
        $mobile = $data['mobile'] ?? '';
        $role = $data['role'] ?? 'Kitchen Staff';

        if (!$id || empty($fname) || empty($email)) {
            echo json_encode(['success' => false, 'message' => 'ID, name, and email required.']);
            exit;
        }

        $stmt = $pdo->prepare("UPDATE STAFF SET Staff_FName = ?, Staff_LName = ?, Staff_Email = ?, Staff_MobileNum = ?, Staff_Role = ? WHERE Staff_ID = ? AND Staff_Brnch_ID = ?");
        $stmt->execute([$fname, $lname, $email, $mobile, $role, $id, $branch_id]);

        echo json_encode(['success' => true, 'message' => 'Staff updated successfully!']);
    }
    elseif ($action === 'update_rider') {
        $id = $data['id'] ?? null;
        $fname = $data['fname'] ?? '';
        $lname = $data['lname'] ?? '';
        $email = $data['email'] ?? '';
        $mobile = $data['mobile'] ?? '';

        if (!$id || empty($fname) || empty($email)) {
            echo json_encode(['success' => false, 'message' => 'ID, name, and email required.']);
            exit;
        }

        $stmt = $pdo->prepare("UPDATE RIDER SET Rider_FName = ?, Rider_LName = ?, Rider_Email = ?, Rider_MobileNum = ? WHERE Rider_ID = ? AND Rider_Brnch_ID = ?");
        $stmt->execute([$fname, $lname, $email, $mobile, $id, $branch_id]);

        echo json_encode(['success' => true, 'message' => 'Rider updated successfully!']);
    }
    elseif ($action === 'get_menu_availability') {
        // Get all menu items and their status for this branch (excluding soft-deleted)
        $stmt = $pdo->prepare("
            SELECT m.*, IFNULL(bm.Is_Available, 'Y') as Is_Available, IFNULL(bm.Stock_Qty, 50) as Stock_Qty 
            FROM MENU_ITEM m 
            LEFT JOIN BRANCH_MENU bm ON m.Menu_ID = bm.Menu_ID AND bm.Brnch_ID = ?
            WHERE m.Menu_Status != 'D' AND (m.Menu_Brnch_ID IS NULL OR m.Menu_Brnch_ID = ?)
        ");
        $stmt->execute([$branch_id, $branch_id]);
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
    }
    elseif ($action === 'get_branch_workforce') {
        $workforce = [];
        
        // Staff
        $stmt = $pdo->prepare("SELECT Staff_ID as id, Staff_FName as fname, Staff_LName as lname, Staff_Email as email, Staff_MobileNum as mobile, Staff_Role as role, 'Staff' as source FROM STAFF WHERE Staff_Brnch_ID = ?");
        $stmt->execute([$branch_id]);
        $workforce = array_merge($workforce, $stmt->fetchAll());
        
        // Riders with active order count
        $stmt = $pdo->prepare("
            SELECT 
                r.Rider_ID as id, r.Rider_FName as fname, r.Rider_LName as lname, 
                r.Rider_Email as email, r.Rider_MobileNum as mobile, 'Driver' as role, 'Rider' as source, 
                r.Rider_Status as status,
                (SELECT COUNT(*) FROM orders o 
                 JOIN DELIVERY d ON o.Order_ID = d.Dlvry_Order_ID 
                 WHERE d.Dlvry_Rider_ID = r.Rider_ID AND o.Order_Stat = 'Delivering') as active_orders
            FROM RIDER r 
            WHERE r.Rider_Brnch_ID = ?
        ");
        $stmt->execute([$branch_id]);
        $workforce = array_merge($workforce, $stmt->fetchAll());
        
        echo json_encode(['success' => true, 'data' => $workforce]);
    }
    elseif ($action === 'delete_workforce') {
        $id = $data['id'];
        $source = $data['source']; // 'Staff' or 'Rider'
        
        if ($source === 'Staff') {
            $stmt = $pdo->prepare("DELETE FROM STAFF WHERE Staff_ID = ? AND Staff_Brnch_ID = ?");
        } else {
            $stmt = $pdo->prepare("DELETE FROM RIDER WHERE Rider_ID = ? AND Rider_Brnch_ID = ?");
        }
        $stmt->execute([$id, $branch_id]);
        echo json_encode(['success' => true, 'message' => 'Record deleted successfully.']);
    }
    elseif ($action === 'get_branch_stats') {
        // Staff count
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM STAFF WHERE Staff_Brnch_ID = ?");
        $stmt->execute([$branch_id]);
        $staff_count = (int)$stmt->fetchColumn();

        // Rider count
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM RIDER WHERE Rider_Brnch_ID = ?");
        $stmt->execute([$branch_id]);
        $rider_count = (int)$stmt->fetchColumn();

        // Extra sanity check: count from workforce directly if above is weird
        // (Just kidding, integers should be enough).

        // Daily Sales
        $stmt = $pdo->prepare("SELECT SUM(Order_Total_Amount) FROM orders WHERE Order_Brnch_ID = ? AND DATE(Order_Date) = CURDATE() AND Order_Stat = 'Completed'");
        $stmt->execute([$branch_id]);
        $daily_sales = $stmt->fetchColumn() ?: 0;

        // Rider performance (successful deliveries today)
        $stmt = $pdo->prepare("
            SELECT r.Rider_ID, r.Rider_FName, r.Rider_LName, COUNT(o.Order_ID) as stats
            FROM RIDER r
            LEFT JOIN DELIVERY d ON r.Rider_ID = d.Dlvry_Rider_ID
            LEFT JOIN orders o ON d.Dlvry_Order_ID = o.Order_ID AND DATE(o.Order_Date) = CURDATE() AND o.Order_Stat = 'Completed'
            WHERE r.Rider_Brnch_ID = ?
            GROUP BY r.Rider_ID
        ");
        $stmt->execute([$branch_id]);
        $rider_stats = $stmt->fetchAll();

        echo json_encode([
            'success' => true, 
            'stats' => [
                'staff' => $staff_count,
                'riders' => $rider_count,
                'dailySales' => $daily_sales,
                'dailyGoal' => 25000,
                'riderPerformance' => $rider_stats
            ]
        ]);
    }
    elseif ($action === 'get_branch_info') {
        $stmt = $pdo->prepare("SELECT * FROM BRANCH WHERE Brnch_ID = ?");
        $stmt->execute([$branch_id]);
        $branch = $stmt->fetch();
        echo json_encode(['success' => true, 'branch' => $branch]);
    }
    elseif ($action === 'update_branch') {
        $name = $data['Brnch_Name'] ?? '';
        $street = $data['Brnch_Street'] ?? '';
        $brgy = $data['Brnch_Brgy'] ?? '';
        $city = $data['Brnch_City'] ?? '';
        $province = $data['Brnch_Province'] ?? '';
        $radius = $data['Brnch_Radius'] ?? 5;
        $id = $data['Brnch_ID'] ?? $branch_id;

        $stmt = $pdo->prepare("UPDATE BRANCH SET Brnch_Name = ?, Brnch_Street = ?, Brnch_Brgy = ?, Brnch_City = ?, Brnch_Province = ?, Brnch_Radius = ? WHERE Brnch_ID = ?");
        $stmt->execute([$name, $street, $brgy, $city, $province, $radius, $id]);
        echo json_encode(['success' => true, 'message' => 'Branch details updated!']);
    }
    elseif ($action === 'get_orders') {
        $stmt = $pdo->prepare("
            SELECT o.*, c.Cust_FName, c.Cust_LName, r.Rider_FName, r.Rider_LName, p.Pay_Method, p.Pay_Status
            FROM orders o
            JOIN CUSTOMER c ON o.Order_Cust_ID = c.Cust_ID
            LEFT JOIN DELIVERY d ON o.Order_ID = d.Dlvry_Order_ID
            LEFT JOIN RIDER r ON d.Dlvry_Rider_ID = r.Rider_ID
            LEFT JOIN PAYMENT p ON o.Order_ID = p.Pay_Order_ID
            WHERE o.Order_Brnch_ID = ?
            ORDER BY o.Order_ID DESC
        ");
        $stmt->execute([$branch_id]);
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
    }
    elseif ($action === 'get_order_items') {
        $order_id = $data['order_id'];
        $stmt = $pdo->prepare("
            SELECT oi.*, m.Menu_Name, m.Menu_Size
            FROM ORDER_ITEM oi
            JOIN MENU_ITEM m ON oi.OItem_Menu_ID = m.Menu_ID
            WHERE oi.Order_ID = ?
        ");
        $stmt->execute([$order_id]);
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
    }
    elseif ($action === 'assign_rider') {
        $order_id = $data['order_id'];
        $rider_id = $data['rider_id'];

        $pdo->beginTransaction();
        try {
            // Delete existing delivery if any
            $stmt = $pdo->prepare("DELETE FROM DELIVERY WHERE Dlvry_Order_ID = ?");
            $stmt->execute([$order_id]);

            // Create new delivery
            $stmt = $pdo->prepare("INSERT INTO DELIVERY (Dlvry_Order_ID, Dlvry_Rider_ID, Dlvry_Pickup_Time, Dlvry_Current_ETA) VALUES (?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 30 MINUTE))");
            $stmt->execute([$order_id, $rider_id]);

            // Update rider availability - Rider becomes Busy ('N')
            $stmt = $pdo->prepare("UPDATE RIDER SET Rider_Status = 'N' WHERE Rider_ID = ?");
            $stmt->execute([$rider_id]);

            // Update order status
            $stmt = $pdo->prepare("UPDATE orders SET Order_Stat = 'Delivering' WHERE Order_ID = ?");
            $stmt->execute([$order_id]);

            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Rider assigned and order dispatched!']);
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
    elseif ($action === 'update_order_status') {
        $order_id = $data['order_id'];
        $status = $data['status'];
        $stmt = $pdo->prepare("UPDATE orders SET Order_Stat = ? WHERE Order_ID = ?");
        $stmt->execute([$status, $order_id]);
        echo json_encode(['success' => true, 'message' => 'Status updated.']);
    }
    elseif ($action === 'toggle_menu') {
        $menu_id = $data['menu_id'];
        $status = $data['status']; // 'Y' or 'N'

        $stmt = $pdo->prepare("INSERT INTO BRANCH_MENU (Brnch_ID, Menu_ID, Is_Available) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE Is_Available = ?");
        $stmt->execute([$branch_id, $menu_id, $status, $status]);

        echo json_encode(['success' => true, 'message' => 'Availability updated!']);
    }
    elseif ($action === 'update_stock') {
        $menu_id = $data['menu_id'];
        $stock = intval($data['stock'] ?? 0);

        $stmt = $pdo->prepare("INSERT INTO BRANCH_MENU (Brnch_ID, Menu_ID, Stock_Qty) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE Stock_Qty = ?");
        $stmt->execute([$branch_id, $menu_id, $stock, $stock]);

        echo json_encode(['success' => true, 'message' => 'Inventory updated!']);
    }
    elseif ($action === 'create_menu') {
        $name = $data['name'] ?? '';
        $desc = $data['desc'] ?? '';
        $price = $data['price'] ?? 0;
        $cat = $data['category'] ?? 'Chicken';
        $size = $data['size'] ?? 'Standard';
        $image = $data['image'] ?? '';

        if (empty($name)) {
            echo json_encode(['success' => false, 'message' => 'Product name required.']);
            exit;
        }

        if (!empty($image)) {
            $image = saveMenuImageLocal($image);
        }

        $stmt = $pdo->prepare("INSERT INTO MENU_ITEM (Menu_Brnch_ID, Menu_Name, Menu_Description, Menu_Price, Menu_Category, Menu_Size, Menu_Image) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$branch_id, $name, $desc, $price, $cat, $size, $image]);

        $menu_id = $pdo->lastInsertId();
        $stmt = $pdo->prepare("INSERT INTO BRANCH_MENU (Brnch_ID, Menu_ID, Is_Available) VALUES (?, ?, 'Y')");
        $stmt->execute([$branch_id, $menu_id]);

        echo json_encode(['success' => true, 'message' => 'Branch menu item added!']);
    }
    elseif ($action === 'update_menu') {
        $id = $data['Menu_ID'] ?? null;
        $name = $data['Menu_Name'] ?? '';
        $desc = $data['Menu_Description'] ?? '';
        $price = $data['Menu_Price'] ?? 0;
        $cat = $data['Menu_Category'] ?? 'Chicken';
        $size = $data['Menu_Size'] ?? 'Standard';
        $image = $data['Menu_Image'] ?? '';

        if (!$id || empty($name)) {
            echo json_encode(['success' => false, 'message' => 'ID and name required.']);
            exit;
        }

        // Verify ownership
        $check = $pdo->prepare("SELECT Menu_Brnch_ID FROM MENU_ITEM WHERE Menu_ID = ?");
        $check->execute([$id]);
        $owner = $check->fetchColumn();
        if ($owner !== null && $owner != $branch_id) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized: You can only edit core items or items belonging to your branch.']);
            exit;
        }

        if (!empty($image)) {
            $image = saveMenuImageLocal($image, $id);
            $stmt = $pdo->prepare("UPDATE MENU_ITEM SET Menu_Name = ?, Menu_Description = ?, Menu_Price = ?, Menu_Category = ?, Menu_Size = ?, Menu_Image = ? WHERE Menu_ID = ?");
            $stmt->execute([$name, $desc, $price, $cat, $size, $image, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE MENU_ITEM SET Menu_Name = ?, Menu_Description = ?, Menu_Price = ?, Menu_Category = ?, Menu_Size = ? WHERE Menu_ID = ?");
            $stmt->execute([$name, $desc, $price, $cat, $size, $id]);
        }

        echo json_encode(['success' => true, 'message' => 'Branch menu item updated!']);
    }
    elseif ($action === 'delete_menu') {
        $id = $data['id'] ?? null;
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'ID required.']);
            exit;
        }

        // Verify ownership
        $check = $pdo->prepare("SELECT Menu_Brnch_ID FROM MENU_ITEM WHERE Menu_ID = ?");
        $check->execute([$id]);
        $owner = $check->fetchColumn();
        if ($owner !== null && $owner != $branch_id) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized: You can only delete core items or items belonging to your branch.']);
            exit;
        }

        $stmt = $pdo->prepare("UPDATE MENU_ITEM SET Menu_Status = 'D' WHERE Menu_ID = ?");
        $stmt->execute([$id]);

        echo json_encode(['success' => true, 'message' => 'Branch menu item removed!']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
