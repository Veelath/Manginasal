<?php
session_start();
include 'db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Branch Manager') {
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

        $stmt = $pdo->prepare("INSERT INTO STAFF (Staff_Brnch_ID, Staff_FName, Staff_LName, Staff_Email, Staff_MobileNum, Staff_Role, Staff_Pass) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$branch_id, $fname, $lname, $email, $mobile, $role, $password]);

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
    elseif ($action === 'get_menu_availability') {
        // Get all menu items and their status for this branch
        $stmt = $pdo->prepare("
            SELECT m.*, IFNULL(bm.Is_Available, 'Y') as Is_Available 
            FROM MENU_ITEM m 
            LEFT JOIN BRANCH_MENU bm ON m.Menu_ID = bm.Menu_ID AND bm.Brnch_ID = ?
        ");
        $stmt->execute([$branch_id]);
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
    }
    elseif ($action === 'toggle_menu') {
        $menu_id = $data['menu_id'];
        $status = $data['status']; // 'Y' or 'N'

        $stmt = $pdo->prepare("INSERT INTO BRANCH_MENU (Brnch_ID, Menu_ID, Is_Available) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE Is_Available = ?");
        $stmt->execute([$branch_id, $menu_id, $status, $status]);

        echo json_encode(['success' => true, 'message' => 'Availability updated!']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
