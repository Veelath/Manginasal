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

if (empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Email is required.']);
    exit;
}

if ($action !== 'reset_password' && empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Password is required.']);
    exit;
}

try {
    if ($action === 'reset_password') {
        // RESET PASSWORD LOGIC
        // In a real app, this would send an email. 
        // For this demo, we'll reset it to 'admin123' if it's the admin, 
        // or a default for others, and return success.
        
        $newPass = 'admin123';
        $hashed = password_hash($newPass, PASSWORD_DEFAULT);
        
        // Try STAFF first
        $stmt = $pdo->prepare("UPDATE STAFF SET Staff_Pass = ? WHERE Staff_Email = ?");
        $stmt->execute([$hashed, $email]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => "Password reset for staff! New password: $newPass"]);
            exit;
        }

        // Try CUSTOMER
        $stmt = $pdo->prepare("UPDATE CUSTOMER SET Cust_Pass = ? WHERE Cust_Email = ?");
        $stmt->execute([$hashed, $email]);

        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => "Password reset for customer! New password: $newPass"]);
            exit;
        }

        echo json_encode(['success' => false, 'message' => 'Email not found in our records.']);
        exit;
    }
    elseif ($action === 'signup') {
        $fname = $data['fname'] ?? '';
        $lname = $data['lname'] ?? '';
        $mobile = $data['mobile'] ?? '';

        if (empty($fname) || empty($lname) || empty($mobile)) {
            echo json_encode(['success' => false, 'message' => 'All fields are required for signup.']);
            exit;
        }

        if (strpos($email, '@') === false) {
            echo json_encode(['success' => false, 'message' => 'Account creation requires a valid email with @ symbol.']);
            exit;
        }

        if (!preg_match('/^[0-9]{11}$/', $mobile)) {
            echo json_encode(['success' => false, 'message' => 'Mobile number must be exactly 11 numeric digits.']);
            exit;
        }

        // Signups are usually for CUSTOMERS
        $stmt = $pdo->prepare("SELECT * FROM CUSTOMER WHERE Cust_Email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Account already exists.']);
            exit;
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Comprehensive signup
        $stmt = $pdo->prepare("INSERT INTO CUSTOMER (Cust_FName, Cust_LName, Cust_Num, Cust_Email, Cust_Pass) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$fname, $lname, $mobile, $email, $hashedPassword]);

        echo json_encode(['success' => true, 'message' => 'Customer account created!']);
    } 
    elseif ($action === 'login') {
        // AUTO-CREATE ADMIN if SYSTEM_ADMIN table is empty
        $stmt = $pdo->prepare("SELECT * FROM SYSTEM_ADMIN WHERE Admin_Email = 'admin@inasal.com'");
        $stmt->execute();
        if (!$stmt->fetch()) {
            $adminPass = password_hash('admin123', PASSWORD_DEFAULT);
            try {
                $stmt = $pdo->prepare("INSERT IGNORE INTO SYSTEM_ADMIN (Admin_FName, Admin_LName, Admin_Email, Admin_MobileNum, Admin_Pass, Admin_Status) VALUES ('System', 'Admin', 'admin@inasal.com', '09123456789', ?, 'Active')");
                $stmt->execute([$adminPass]);
            } catch (Exception $e) {
                // Table might not exist yet
            }
        }

        $user = null;

        // 1. Check Customer Table
        $stmt = $pdo->prepare("SELECT Cust_ID as id, Cust_Email as email, Cust_Pass as pass, 'Customer' as role, NULL as branch_id FROM CUSTOMER WHERE Cust_Email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // 2. Check System Admin Table
        if (!$user) {
            $stmt = $pdo->prepare("SELECT Admin_ID as id, Admin_FName as name, Admin_Pass as pass, 'System Admin' as role, NULL as branch_id FROM SYSTEM_ADMIN WHERE Admin_Email = ?");
            $stmt->execute([$email]); 
            $user = $stmt->fetch();
        }

        // 3. Check Branch Manager Table
        if (!$user) {
            $stmt = $pdo->prepare("SELECT Mgr_ID as id, Mgr_FName as name, Mgr_Pass as pass, 'Branch Manager' as role, Mgr_Brnch_ID as branch_id FROM BRANCH_MANAGER WHERE Mgr_Email = ?");
            $stmt->execute([$email]); 
            $user = $stmt->fetch();
        }

        // 4. Check Staff Table
        if (!$user) {
            $stmt = $pdo->prepare("SELECT Staff_ID as id, Staff_FName as name, Staff_Pass as pass, Staff_Role as role, Staff_Brnch_ID as branch_id FROM STAFF WHERE Staff_Email = ?");
            $stmt->execute([$email]); 
            $user = $stmt->fetch();
        }

        // 5. Check Rider Table
        if (!$user) {
            $stmt = $pdo->prepare("SELECT Rider_ID as id, Rider_FName as name, Rider_Pass as pass, 'Driver' as role, Rider_Brnch_ID as branch_id FROM RIDER WHERE Rider_Email = ?");
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
        $_SESSION['branch_id'] = $user['branch_id'] ?? null;

        echo json_encode(['success' => true, 'message' => "Welcome! Logged in as " . $user['role']]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>
