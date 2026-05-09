<?php
include 'db.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

$email = $data['email'] ?? '';
$password = $data['password'] ?? '';
$action = $data['action'] ?? '';

if (empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Please fill all fields.']);
    exit;
}

try {
    if ($action === 'signup') {
        // Signups are usually for CUSTOMERS
        $stmt = $pdo->prepare("SELECT * FROM CUSTOMER WHERE Cust_Email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Account already exists.']);
            exit;
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Simple signup (Names can be updated later in profile)
        $stmt = $pdo->prepare("INSERT INTO CUSTOMER (Cust_FName, Cust_LName, Cust_Num, Cust_Email, Cust_Pass) VALUES ('New', 'User', '00000000000', ?, ?)");
        $stmt->execute([$email, $hashedPassword]);

        echo json_encode(['success' => true, 'message' => 'Customer account created!']);
    } 
    elseif ($action === 'login') {
        $user = null;
        $role = 'Customer';

        // 1. Check Customer Table
        $stmt = $pdo->prepare("SELECT Cust_ID as id, Cust_Email as email, Cust_Pass as pass, 'Customer' as role FROM CUSTOMER WHERE Cust_Email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // 2. If not found, check Staff Table
        if (!$user) {
            $stmt = $pdo->prepare("SELECT Staff_ID as id, Staff_FName as name, Staff_Pass as pass, Staff_Role as role FROM STAFF WHERE Staff_Email = ?");
            $stmt->execute([$email]); 
            $user = $stmt->fetch();
        }

        // 3. If still not found, check Rider Table
        if (!$user) {
            $stmt = $pdo->prepare("SELECT Rider_ID as id, Rider_FName as name, Rider_Pass as pass, 'Driver' as role FROM RIDER WHERE Rider_Email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
        }

        if (!$user || !password_verify($password, $user['pass'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid credentials.']);
            exit;
        }

        session_start();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];

        echo json_encode(['success' => true, 'message' => "Welcome! Logged in as " . $user['role']]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>
