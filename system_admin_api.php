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
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM STAFF WHERE Staff_Brnch_ID = ? AND Staff_Role = 'Branch Manager'");
        $stmt->execute([$branch_id]);
        if ($stmt->fetchColumn() > 0) {
            echo json_encode(['success' => false, 'message' => 'This branch already has a manager assigned.']);
            exit;
        }

        // 2. Check if Email already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM STAFF WHERE Staff_Email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetchColumn() > 0) {
            echo json_encode(['success' => false, 'message' => 'A user with this email already exists.']);
            exit;
        }

        $hashed = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("INSERT INTO STAFF (Staff_Brnch_ID, Staff_FName, Staff_LName, Staff_Email, Staff_MobileNum, Staff_Role, Staff_Pass) VALUES (?, ?, ?, ?, ?, 'Branch Manager', ?)");
        $stmt->execute([$branch_id, $fname, $lname, $email, $mobile, $hashed]);

        echo json_encode(['success' => true, 'message' => 'Manager account created!']);
    }
    elseif ($action === 'create_menu') {
        $name = $data['name'] ?? '';
        $desc = $data['desc'] ?? '';
        $price = $data['price'] ?? 0;
        $cat = $data['category'] ?? '';
        $size = $data['size'] ?? 'Standard';

        if (empty($name) || empty($cat)) {
            echo json_encode(['success' => false, 'message' => 'Menu name and category required.']);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO MENU_ITEM (Menu_Name, Menu_Description, Menu_Price, Menu_Category, Menu_Size) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$name, $desc, $price, $cat, $size]);

        echo json_encode(['success' => true, 'message' => 'Global menu item added!']);
    }
    elseif ($action === 'get_menu') {
        $stmt = $pdo->query("SELECT * FROM MENU_ITEM");
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
    }
    elseif ($action === 'get_branches') {
        $stmt = $pdo->query("
            SELECT b.*, s.Staff_FName, s.Staff_LName 
            FROM BRANCH b 
            LEFT JOIN STAFF s ON b.Brnch_ID = s.Staff_Brnch_ID AND s.Staff_Role = 'Branch Manager'
        ");
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
    }
    elseif ($action === 'get_stats') {
        $branchCount = $pdo->query("SELECT COUNT(*) FROM BRANCH")->fetchColumn();
        $managerCount = $pdo->query("SELECT COUNT(*) FROM STAFF WHERE Staff_Role = 'Branch Manager'")->fetchColumn();
        echo json_encode([
            'success' => true, 
            'stats' => [
                'branches' => $branchCount,
                'managers' => $managerCount
            ]
        ]);
    }
    elseif ($action === 'get_managers') {
        $stmt = $pdo->query("
            SELECT s.*, b.Brnch_Name 
            FROM STAFF s 
            LEFT JOIN BRANCH b ON s.Staff_Brnch_ID = b.Brnch_ID 
            WHERE s.Staff_Role = 'Branch Manager'
        ");
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
    }
} catch (Exception $e) {
     echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
