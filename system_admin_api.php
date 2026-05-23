<?php
session_start();
include 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'System Admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';

try {
    if ($action === 'create_branch') {
        $name = $data['name'] ?? '';
        $street = $data['street'] ?? '';
        $brgy = $data['brgy'] ?? '';
        $city = $data['city'] ?? '';
        $province = $data['province'] ?? '';
        $radius = $data['radius'] ?? 5.00;

        if (empty($name) || empty($city)) {
            echo json_encode(['success' => false, 'message' => 'Branch name and city are required.']);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO BRANCH (Brnch_Name, Brnch_Street, Brnch_Brgy, Brnch_City, Brnch_Province, Brnch_Radius) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $street, $brgy, $city, $province, $radius]);

        echo json_encode(['success' => true, 'message' => 'Branch created successfully!']);
    } 
    elseif ($action === 'create_manager') {
        $fname = $data['fname'] ?? '';
        $lname = $data['lname'] ?? '';
        $email = $data['email'] ?? '';
        $mobile = $data['mobile'] ?? '';
        $password = $data['password'] ?? 'manager123';
        $branch_id = $data['branch_id'] ?? null;

        if (empty($fname) || empty($email) || empty($branch_id)) {
            echo json_encode(['success' => false, 'message' => 'All fields required.']);
            exit;
        }

        // 1. Check if Branch already has a manager
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM BRANCH_MANAGER WHERE Mgr_Brnch_ID = ?");
        $stmt->execute([$branch_id]);
        if ($stmt->fetchColumn() > 0) {
            echo json_encode(['success' => false, 'message' => 'This branch already has a manager assigned.']);
            exit;
        }

        // 2. Check if Email already exists in any user table
        $tables = ['SYSTEM_ADMIN', 'BRANCH_MANAGER', 'STAFF', 'RIDER', 'CUSTOMER'];
        foreach($tables as $t) {
            $prefix = ($t === 'SYSTEM_ADMIN') ? 'Admin' : (($t === 'BRANCH_MANAGER') ? 'Mgr' : (($t === 'RIDER') ? 'Rider' : (($t === 'CUSTOMER') ? 'Cust' : 'Staff')));
            $col = $prefix . '_Email';
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM $t WHERE $col = ?");
            $stmt->execute([$email]);
            if ($stmt->fetchColumn() > 0) {
                echo json_encode(['success' => false, 'message' => 'A user with this email already exists.']);
                exit;
            }
        }

        $hashed = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("INSERT INTO BRANCH_MANAGER (Mgr_Brnch_ID, Mgr_FName, Mgr_LName, Mgr_Email, Mgr_MobileNum, Mgr_Pass) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$branch_id, $fname, $lname, $email, $mobile, $hashed]);

        echo json_encode(['success' => true, 'message' => 'Manager account created!']);
    }
    elseif ($action === 'update_branch') {
        $id = $data['Brnch_ID'] ?? null;
        $name = $data['Brnch_Name'] ?? '';
        $street = $data['Brnch_Street'] ?? '';
        $brgy = $data['Brnch_Brgy'] ?? '';
        $city = $data['Brnch_City'] ?? '';
        $province = $data['Brnch_Province'] ?? '';
        $radius = $data['Brnch_Radius'] ?? 5.00;

        if (!$id || empty($name) || empty($city)) {
            echo json_encode(['success' => false, 'message' => 'ID, name, and city are required.']);
            exit;
        }

        $stmt = $pdo->prepare("UPDATE BRANCH SET Brnch_Name = ?, Brnch_Street = ?, Brnch_Brgy = ?, Brnch_City = ?, Brnch_Province = ?, Brnch_Radius = ? WHERE Brnch_ID = ?");
        $stmt->execute([$name, $street, $brgy, $city, $province, $radius, $id]);

        echo json_encode(['success' => true, 'message' => 'Branch updated successfully!']);
    }
    elseif ($action === 'update_menu') {
        $id = $data['Menu_ID'] ?? null;
        $name = $data['Menu_Name'] ?? '';
        $desc = $data['Menu_Description'] ?? '';
        $price = $data['Menu_Price'] ?? 0;
        $cat = $data['Menu_Category'] ?? '';
        $size = $data['Menu_Size'] ?? 'Standard';
        $image = $data['Menu_Image'] ?? '';

        if (!$id || empty($name) || empty($cat)) {
            echo json_encode(['success' => false, 'message' => 'ID, name, and category required.']);
            exit;
        }

        if (!empty($image)) {
            $image = saveMenuImageLocal($image, $id);
        }

        $stmt = $pdo->prepare("UPDATE MENU_ITEM SET Menu_Name = ?, Menu_Description = ?, Menu_Price = ?, Menu_Category = ?, Menu_Size = ?, Menu_Image = ? WHERE Menu_ID = ?");
        $stmt->execute([$name, $desc, $price, $cat, $size, $image, $id]);

        echo json_encode(['success' => true, 'message' => 'Menu item updated!']);
    }
    elseif ($action === 'update_manager') {
        $id = $data['id'] ?? null;
        $fname = $data['fname'] ?? '';
        $lname = $data['lname'] ?? '';
        $email = $data['email'] ?? '';
        $mobile = $data['mobile'] ?? '';
        $branch_id = $data['Mgr_Brnch_ID'] ?? null;

        if (!$id || empty($fname) || empty($email)) {
            echo json_encode(['success' => false, 'message' => 'ID, name, and email are required.']);
            exit;
        }

        $stmt = $pdo->prepare("UPDATE BRANCH_MANAGER SET Mgr_FName = ?, Mgr_LName = ?, Mgr_Email = ?, Mgr_MobileNum = ?, Mgr_Brnch_ID = ? WHERE Mgr_ID = ?");
        $stmt->execute([$fname, $lname, $email, $mobile, $branch_id, $id]);

        echo json_encode(['success' => true, 'message' => 'Manager updated successfully!']);
    }
    elseif ($action === 'update_manager_status') {
        $id = $data['id'] ?? null;
        $status = $data['status'] ?? '';

        if (!$id || !$status) {
            echo json_encode(['success' => false, 'message' => 'ID and status required.']);
            exit;
        }

        $stmt = $pdo->prepare("UPDATE BRANCH_MANAGER SET Mgr_Status = ? WHERE Mgr_ID = ?");
        $stmt->execute([$status, $id]);

        echo json_encode(['success' => true, 'message' => 'Manager status updated!']);
    }
    elseif ($action === 'delete_branch') {
        $id = $data['id'] ?? null;
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'Branch ID required.']);
            exit;
        }

        // Optional: Check if branch has staff/riders before deleting or let foreign keys handle it
        $stmt = $pdo->prepare("DELETE FROM BRANCH WHERE Brnch_ID = ?");
        $stmt->execute([$id]);

        echo json_encode(['success' => true, 'message' => 'Branch deleted successfully!']);
    }
    elseif ($action === 'delete_manager') {
        $id = $data['id'] ?? null;
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'Manager ID required.']);
            exit;
        }

        $stmt = $pdo->prepare("DELETE FROM BRANCH_MANAGER WHERE Mgr_ID = ?");
        $stmt->execute([$id]);

        echo json_encode(['success' => true, 'message' => 'Manager deleted successfully!']);
    }
    elseif ($action === 'create_menu') {
        $name = $data['name'] ?? '';
        $desc = $data['desc'] ?? '';
        $price = $data['price'] ?? 0;
        $cat = $data['category'] ?? '';
        $size = $data['size'] ?? 'Standard';
        $image = $data['image'] ?? '';

        if (empty($name) || empty($cat)) {
            echo json_encode(['success' => false, 'message' => 'Menu name and category required.']);
            exit;
        }

        if (!empty($image)) {
            $image = saveMenuImageLocal($image);
        }

        // Ensure image column exists and has enough capacity for base64
        try {
            $pdo->exec("ALTER TABLE MENU_ITEM MODIFY COLUMN Menu_Image LONGTEXT");
        } catch (Exception $e) {
            try {
                $pdo->exec("ALTER TABLE MENU_ITEM ADD COLUMN Menu_Image LONGTEXT AFTER Menu_Description");
            } catch (Exception $e2) { /* already exists and handled */ }
        }

        $stmt = $pdo->prepare("INSERT INTO MENU_ITEM (Menu_Name, Menu_Description, Menu_Price, Menu_Category, Menu_Size, Menu_Image) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $desc, $price, $cat, $size, $image]);

        echo json_encode(['success' => true, 'message' => 'Global menu item added!']);
    }
    elseif ($action === 'get_menu') {
        $stmt = $pdo->query("SELECT * FROM MENU_ITEM WHERE Menu_Status != 'D'");
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
    }
    elseif ($action === 'delete_menu') {
        $id = $data['id'] ?? null;
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'ID required.']);
            exit;
        }
        // Use soft delete 'D' to avoid foreign key issues with old orders
        $stmt = $pdo->prepare("UPDATE MENU_ITEM SET Menu_Status = 'D' WHERE Menu_ID = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true, 'message' => 'Menu item removed from active catalog!']);
    }
    elseif ($action === 'update_menu_status') {
        $id = $data['id'] ?? null;
        $status = $data['status'] ?? 'Y';
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'ID required.']);
            exit;
        }
        $stmt = $pdo->prepare("UPDATE MENU_ITEM SET Menu_Status = ? WHERE Menu_ID = ?");
        $stmt->execute([$status, $id]);
        echo json_encode(['success' => true, 'message' => 'Status updated!']);
    }
    elseif ($action === 'get_branches') {
        $stmt = $pdo->query("
            SELECT b.*, m.Mgr_FName as fname, m.Mgr_LName as lname 
            FROM BRANCH b 
            LEFT JOIN BRANCH_MANAGER m ON b.Brnch_ID = m.Mgr_Brnch_ID
        ");
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
    }
    elseif ($action === 'get_stats') {
        $branchCount = $pdo->query("SELECT COUNT(*) FROM BRANCH")->fetchColumn();
        $managerCount = $pdo->query("SELECT COUNT(*) FROM BRANCH_MANAGER")->fetchColumn();
        $staffCount = $pdo->query("SELECT COUNT(*) FROM STAFF")->fetchColumn();
        $riderCount = $pdo->query("SELECT COUNT(*) FROM RIDER")->fetchColumn();
        echo json_encode([
            'success' => true, 
            'stats' => [
                'branches' => $branchCount,
                'managers' => $managerCount,
                'staff' => $staffCount,
                'riders' => $riderCount
            ]
        ]);
    }
    elseif ($action === 'get_staff') {
        $stmt = $pdo->query("
            SELECT s.Staff_ID as id, s.Staff_Brnch_ID, s.Staff_Mgr_ID, s.Staff_FName as fname, s.Staff_LName as lname, s.Staff_Email as email, s.Staff_MobileNum as mobile, s.Staff_Role as role, s.Staff_Status as status, b.Brnch_Name, m.Mgr_FName, m.Mgr_LName 
            FROM STAFF s 
            LEFT JOIN BRANCH b ON s.Staff_Brnch_ID = b.Brnch_ID 
            LEFT JOIN BRANCH_MANAGER m ON s.Staff_Mgr_ID = m.Mgr_ID
            WHERE s.Staff_Role = 'Kitchen Staff'
        ");
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
    }
    elseif ($action === 'get_riders') {
        $stmt = $pdo->query("
            SELECT r.Rider_ID as id, r.Rider_Brnch_ID, r.Rider_FName as fname, r.Rider_LName as lname, r.Rider_Email as email, r.Rider_MobileNum as mobile, r.Rider_Status as status, b.Brnch_Name 
            FROM RIDER r 
            LEFT JOIN BRANCH b ON r.Rider_Brnch_ID = b.Brnch_ID
        ");
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
    }
    elseif ($action === 'get_managers') {
        $stmt = $pdo->query("
            SELECT m.Mgr_ID as id, m.Mgr_Brnch_ID, m.Mgr_FName as fname, m.Mgr_LName as lname, m.Mgr_Email as email, m.Mgr_MobileNum as mobile, m.Mgr_Status as status, b.Brnch_Name 
            FROM BRANCH_MANAGER m 
            LEFT JOIN BRANCH b ON m.Mgr_Brnch_ID = b.Brnch_ID
        ");
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
    }
    elseif ($action === 'get_all_users') {
        // Fetch from all tables
        $users = [];
        
        // 1. Admins
        $stmt = $pdo->query("SELECT Admin_ID as id, Admin_FName as fname, Admin_LName as lname, Admin_Email as email, Admin_MobileNum as mobile, 'System Admin' as role, 'Admin' as source FROM SYSTEM_ADMIN");
        $users = array_merge($users, $stmt->fetchAll());

        // 2. Managers
        $stmt = $pdo->query("SELECT Mgr_ID as id, Mgr_FName as fname, Mgr_LName as lname, Mgr_Email as email, Mgr_MobileNum as mobile, 'Branch Manager' as role, 'Manager' as source FROM BRANCH_MANAGER");
        $users = array_merge($users, $stmt->fetchAll());

        // 3. Staff
        $stmt = $pdo->query("SELECT Staff_ID as id, Staff_FName as fname, Staff_LName as lname, Staff_Email as email, Staff_MobileNum as mobile, Staff_Role as role, 'Staff' as source FROM STAFF");
        $users = array_merge($users, $stmt->fetchAll());
        
        // 4. Customers
        $stmt = $pdo->query("SELECT Cust_ID as id, Cust_FName as fname, Cust_LName as lname, Cust_Email as email, Cust_Num as mobile, 'Customer' as role, 'Customer' as source FROM CUSTOMER");
        $users = array_merge($users, $stmt->fetchAll());
        
        // 5. Riders
        $stmt = $pdo->query("SELECT Rider_ID as id, Rider_FName as fname, Rider_LName as lname, Rider_Email as email, Rider_MobileNum as mobile, 'Driver' as role, 'Rider' as source FROM RIDER");
        $users = array_merge($users, $stmt->fetchAll());
        
        echo json_encode(['success' => true, 'data' => $users]);
    }
    elseif ($action === 'get_reports') {
        $dailySales = $pdo->query("SELECT SUM(Order_Total_Amount) FROM orders WHERE DATE(Order_Date) = CURDATE() AND Order_Stat = 'Completed'")->fetchColumn() ?: 0;
        $totalOrders = $pdo->query("SELECT COUNT(*) FROM orders WHERE DATE(Order_Date) = CURDATE()")->fetchColumn() ?: 0;
        
        $topItems = $pdo->query("
            SELECT m.Menu_Name, SUM(oi.OItem_Quantity) as total_qty
            FROM ORDER_ITEM oi
            JOIN MENU_ITEM m ON oi.OItem_Menu_ID = m.Menu_ID
            GROUP BY m.Menu_ID
            ORDER BY total_qty DESC
            LIMIT 5
        ")->fetchAll();
        
        echo json_encode(['success' => true, 'data' => [
                'dailySales' => $dailySales,
                'totalOrders' => $totalOrders,
                'topItems' => $topItems
            ]
        ]);
    }
    elseif ($action === 'delete_user') {
        $id = $data['id'] ?? null;
        $source = $data['source'] ?? '';

        if (!$id || !$source) {
            echo json_encode(['success' => false, 'message' => 'ID and source required.']);
            exit;
        }

        $tableMap = [
            'Admin' => ['table' => 'SYSTEM_ADMIN', 'col' => 'Admin_ID'],
            'Manager' => ['table' => 'BRANCH_MANAGER', 'col' => 'Mgr_ID'],
            'Staff' => ['table' => 'STAFF', 'col' => 'Staff_ID'],
            'Customer' => ['table' => 'CUSTOMER', 'col' => 'Cust_ID'],
            'Rider' => ['table' => 'RIDER', 'col' => 'Rider_ID']
        ];

        if (!isset($tableMap[$source])) {
            echo json_encode(['success' => false, 'message' => 'Invalid source.']);
            exit;
        }

        $table = $tableMap[$source]['table'];
        $col = $tableMap[$source]['col'];

        // Don't allow deleting current admin
        if ($source === 'Admin' && $id == $_SESSION['user_id']) {
            echo json_encode(['success' => false, 'message' => 'You cannot delete your own account.']);
            exit;
        }

        $stmt = $pdo->prepare("DELETE FROM $table WHERE $col = ?");
        $stmt->execute([$id]);

        echo json_encode(['success' => true, 'message' => 'User deleted successfully!']);
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

        $stmt = $pdo->prepare("UPDATE STAFF SET Staff_FName = ?, Staff_LName = ?, Staff_Email = ?, Staff_MobileNum = ?, Staff_Role = ? WHERE Staff_ID = ?");
        $stmt->execute([$fname, $lname, $email, $mobile, $role, $id]);

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

        $stmt = $pdo->prepare("UPDATE RIDER SET Rider_FName = ?, Rider_LName = ?, Rider_Email = ?, Rider_MobileNum = ? WHERE Rider_ID = ?");
        $stmt->execute([$fname, $lname, $email, $mobile, $id]);

        echo json_encode(['success' => true, 'message' => 'Rider updated successfully!']);
    }
} catch (Exception $e) {
     echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
