<?php
session_start();
include 'db.php';
include 'firebase.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Branch Manager', 'Kitchen Staff'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

$branch_id = $_SESSION['branch_id'];
$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';

try {
    // ============================================================
    // 1. DATA ENTRY INTERFACE — create_staff, create_rider, create_menu
    //    Fetches data from UI text fields and stores in Firebase
    // ============================================================
    if ($action === 'create_staff') {
        $fname    = $data['fname'] ?? '';
        $lname    = $data['lname'] ?? '';
        $email    = $data['email'] ?? '';
        $mobile   = $data['mobile'] ?? '';
        $role     = $data['role'] ?? 'Kitchen Staff';
        $password = password_hash($data['password'] ?? 'staff123', PASSWORD_DEFAULT);
        $mgr_id   = $_SESSION['user_id'];

        // MySQL insert
        $stmt = $pdo->prepare("INSERT INTO STAFF (Staff_Brnch_ID, Staff_Mgr_ID, Staff_FName, Staff_LName, Staff_Email, Staff_MobileNum, Staff_Role, Staff_Pass) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$branch_id, $mgr_id, $fname, $lname, $email, $mobile, $role, $password]);
        $new_id = $pdo->lastInsertId();

        // Firebase DATA ENTRY: store new staff in Firestore
        try {
            fbAdd($firestore, 'staff', [
                'mysql_id'   => (string)$new_id,
                'branch_id'  => (string)$branch_id,
                'manager_id' => (string)$mgr_id,
                'first_name' => $fname,
                'last_name'  => $lname,
                'email'      => $email,
                'mobile'     => $mobile,
                'role'       => $role,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (Exception $fbErr) {
            error_log('Firebase sync error (create_staff): ' . $fbErr->getMessage());
        }

        echo json_encode(['success' => true, 'message' => 'Staff account created!']);
    }
    elseif ($action === 'create_rider') {
        $fname    = $data['fname'] ?? '';
        $lname    = $data['lname'] ?? '';
        $email    = $data['email'] ?? '';
        $mobile   = $data['mobile'] ?? '';
        $password = password_hash($data['password'] ?? 'rider123', PASSWORD_DEFAULT);

        // MySQL insert
        $stmt = $pdo->prepare("INSERT INTO RIDER (Rider_Brnch_ID, Rider_FName, Rider_LName, Rider_Email, Rider_MobileNum, Rider_Pass) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$branch_id, $fname, $lname, $email, $mobile, $password]);
        $new_id = $pdo->lastInsertId();

        // Firebase DATA ENTRY: store new rider in Firestore
        try {
            fbAdd($firestore, 'rider', [
                'mysql_id'   => (string)$new_id,
                'branch_id'  => (string)$branch_id,
                'first_name' => $fname,
                'last_name'  => $lname,
                'email'      => $email,
                'mobile'     => $mobile,
                'status'     => 'Y',
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (Exception $fbErr) {
            error_log('Firebase sync error (create_rider): ' . $fbErr->getMessage());
        }

        echo json_encode(['success' => true, 'message' => 'Rider account created!']);
    }
    elseif ($action === 'create_menu') {
        $name  = $data['name'] ?? '';
        $desc  = $data['desc'] ?? '';
        $price = $data['price'] ?? 0;
        $cat   = $data['category'] ?? 'Chicken';
        $size  = $data['size'] ?? 'Standard';
        $image = $data['image'] ?? '';

        if (empty($name)) {
            echo json_encode(['success' => false, 'message' => 'Product name required.']);
            exit;
        }
        if (!empty($image)) $image = saveMenuImageLocal($image);

        // MySQL insert
        $stmt = $pdo->prepare("INSERT INTO MENU_ITEM (Menu_Brnch_ID, Menu_Name, Menu_Description, Menu_Price, Menu_Category, Menu_Size, Menu_Image) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$branch_id, $name, $desc, $price, $cat, $size, $image]);
        $menu_id = $pdo->lastInsertId();

        $stmt = $pdo->prepare("INSERT INTO BRANCH_MENU (Brnch_ID, Menu_ID, Is_Available) VALUES (?, ?, 'Y')");
        $stmt->execute([$branch_id, $menu_id]);

        // Firebase DATA ENTRY: store new menu item in Firestore
        try {
            fbAdd($firestore, 'menu_item', [
                'mysql_id'   => (string)$menu_id,
                'branch_id'  => (string)$branch_id,
                'name'       => $name,
                'description'=> $desc,
                'price'      => (string)$price,
                'category'   => $cat,
                'size'       => $size,
                'status'     => 'Y',
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (Exception $fbErr) {
            error_log('Firebase sync error (create_menu): ' . $fbErr->getMessage());
        }

        echo json_encode(['success' => true, 'message' => 'Branch menu item added!']);
    }

    // ============================================================
    // 2. DISPLAY INTERFACE — get_orders, get_menu_availability, get_branch_workforce
    //    Retrieves data from Firebase and displays in UI
    // ============================================================
    elseif ($action === 'get_orders') {
        // Try Firebase first
        try {
            $fb_orders = [];
            $docs = $firestore->collection('orders')->documents();
            foreach ($docs as $doc) {
                if ($doc->exists()) {
                    $orderRaw = array_merge(['id' => $doc->id()], $doc->data());
                    if (!isset($orderRaw['branch_id']) || $orderRaw['branch_id'] == (string)$branch_id) {
                        $fb_orders[] = normalizeOrder($orderRaw, $firestore);
                    }
                }
            }
            if (!empty($fb_orders)) {
                echo json_encode(['success' => true, 'data' => $fb_orders, 'source' => 'firebase']);
                exit;
            }
        } catch (Exception $fbErr) {
            error_log('Firebase fetch error (get_orders): ' . $fbErr->getMessage());
        }

        // Fallback to MySQL
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
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll(), 'source' => 'mysql']);
    }
    elseif ($action === 'get_menu_availability') {
        // Try Firebase first
        try {
            $fb_menu = [];
            $docs = $firestore->collection('menu_item')->documents();
            foreach ($docs as $doc) {
                if ($doc->exists()) {
                    $itemRaw = array_merge(['id' => $doc->id()], $doc->data());
                    $normalized = normalizeMenuItem($itemRaw);
                    if ($normalized['branch_id'] === null || $normalized['branch_id'] == (string)$branch_id) {
                        $fb_menu[] = $normalized;
                    }
                }
            }
            if (!empty($fb_menu)) {
                echo json_encode(['success' => true, 'data' => $fb_menu, 'source' => 'firebase']);
                exit;
            }
        } catch (Exception $fbErr) {
            error_log('Firebase fetch error (get_menu_availability): ' . $fbErr->getMessage());
        }

        // Fallback to MySQL
        $stmt = $pdo->prepare("
            SELECT m.*, IFNULL(bm.Is_Available, 'Y') as Is_Available, IFNULL(bm.Stock_Qty, 50) as Stock_Qty 
            FROM MENU_ITEM m 
            LEFT JOIN BRANCH_MENU bm ON m.Menu_ID = bm.Menu_ID AND bm.Brnch_ID = ?
            WHERE m.Menu_Status != 'D' AND (m.Menu_Brnch_ID IS NULL OR m.Menu_Brnch_ID = ?)
        ");
        $stmt->execute([$branch_id, $branch_id]);
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll(), 'source' => 'mysql']);
    }
    elseif ($action === 'get_branch_workforce') {
        // Try Firebase first
        try {
            $fb_workforce = [];
            foreach (['staff', 'rider'] as $col) {
                $docs = $firestore->collection($col)->documents();
                foreach ($docs as $doc) {
                    if ($doc->exists()) {
                        $item = array_merge(['id' => $doc->id(), 'source' => ucfirst($col)], $doc->data());
                        if (isset($item['branch_id']) && $item['branch_id'] == (string)$branch_id) {
                            $fb_workforce[] = normalizeWorkforce($item);
                        }
                    }
                }
            }
            if (!empty($fb_workforce)) {
                echo json_encode(['success' => true, 'data' => $fb_workforce, 'source' => 'firebase']);
                exit;
            }
        } catch (Exception $fbErr) {
            error_log('Firebase fetch error (get_branch_workforce): ' . $fbErr->getMessage());
        }

        // Fallback to MySQL
        $workforce = [];
        $stmt = $pdo->prepare("SELECT Staff_ID as id, Staff_FName as fname, Staff_LName as lname, Staff_Email as email, Staff_MobileNum as mobile, Staff_Role as role, 'Staff' as source FROM STAFF WHERE Staff_Brnch_ID = ?");
        $stmt->execute([$branch_id]);
        $workforce = array_merge($workforce, $stmt->fetchAll());
        $stmt = $pdo->prepare("
            SELECT r.Rider_ID as id, r.Rider_FName as fname, r.Rider_LName as lname, 
                r.Rider_Email as email, r.Rider_MobileNum as mobile, 'Driver' as role, 'Rider' as source, 
                r.Rider_Status as status,
                (SELECT COUNT(*) FROM orders o JOIN DELIVERY d ON o.Order_ID = d.Dlvry_Order_ID WHERE d.Dlvry_Rider_ID = r.Rider_ID AND o.Order_Stat = 'Delivering') as active_orders
            FROM RIDER r WHERE r.Rider_Brnch_ID = ?
        ");
        $stmt->execute([$branch_id]);
        $workforce = array_merge($workforce, $stmt->fetchAll());
        echo json_encode(['success' => true, 'data' => $workforce, 'source' => 'mysql']);
    }
    elseif ($action === 'get_order_items') {
        $order_id = $data['order_id'];
        $stmt = $pdo->prepare("SELECT oi.*, m.Menu_Name, m.Menu_Size FROM ORDER_ITEM oi JOIN MENU_ITEM m ON oi.OItem_Menu_ID = m.Menu_ID WHERE oi.Order_ID = ?");
        $stmt->execute([$order_id]);
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
    }
    elseif ($action === 'get_branch_stats') {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM STAFF WHERE Staff_Brnch_ID = ?");
        $stmt->execute([$branch_id]);
        $staff_count = (int)$stmt->fetchColumn();
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM RIDER WHERE Rider_Brnch_ID = ?");
        $stmt->execute([$branch_id]);
        $rider_count = (int)$stmt->fetchColumn();
        $stmt = $pdo->prepare("SELECT SUM(Order_Total_Amount) FROM orders WHERE Order_Brnch_ID = ? AND DATE(Order_Date) = CURDATE() AND Order_Stat = 'Completed'");
        $stmt->execute([$branch_id]);
        $daily_sales = $stmt->fetchColumn() ?: 0;
        $stmt = $pdo->prepare("SELECT r.Rider_ID, r.Rider_FName, r.Rider_LName, COUNT(o.Order_ID) as stats FROM RIDER r LEFT JOIN DELIVERY d ON r.Rider_ID = d.Dlvry_Rider_ID LEFT JOIN orders o ON d.Dlvry_Order_ID = o.Order_ID AND DATE(o.Order_Date) = CURDATE() AND o.Order_Stat = 'Completed' WHERE r.Rider_Brnch_ID = ? GROUP BY r.Rider_ID");
        $stmt->execute([$branch_id]);
        $rider_stats = $stmt->fetchAll();
        echo json_encode(['success' => true, 'stats' => ['staff' => $staff_count, 'riders' => $rider_count, 'dailySales' => $daily_sales, 'dailyGoal' => 25000, 'riderPerformance' => $rider_stats]]);
    }
    elseif ($action === 'get_branch_info') {
        $stmt = $pdo->prepare("SELECT * FROM BRANCH WHERE Brnch_ID = ?");
        $stmt->execute([$branch_id]);
        echo json_encode(['success' => true, 'branch' => $stmt->fetch()]);
    }

    // ============================================================
    // 3. UPDATE/DELETE INTERFACE — update_staff, update_rider, update_menu,
    //    update_order_status, toggle_menu, update_stock, delete_workforce, delete_menu
    //    Updates or deletes records in Firebase through the UI
    // ============================================================
    elseif ($action === 'update_staff') {
        $id     = $data['id'] ?? null;
        $fname  = $data['fname'] ?? '';
        $lname  = $data['lname'] ?? '';
        $email  = $data['email'] ?? '';
        $mobile = $data['mobile'] ?? '';
        $role   = $data['role'] ?? 'Kitchen Staff';
        if (!$id || empty($fname) || empty($email)) { echo json_encode(['success' => false, 'message' => 'ID, name, and email required.']); exit; }

        // MySQL update
        $stmt = $pdo->prepare("UPDATE STAFF SET Staff_FName = ?, Staff_LName = ?, Staff_Email = ?, Staff_MobileNum = ?, Staff_Role = ? WHERE Staff_ID = ? AND Staff_Brnch_ID = ?");
        $stmt->execute([$fname, $lname, $email, $mobile, $role, $id, $branch_id]);

        // Firebase UPDATE
        try {
            $docs = $firestore->collection('staff')->documents();
            foreach ($docs as $doc) {
                if ($doc->exists() && fbMatchesId($doc, $id, ['Staff_ID'])) {
                    fbUpdate($firestore, 'staff', $doc->id(), ['first_name' => $fname, 'last_name' => $lname, 'email' => $email, 'mobile' => $mobile, 'role' => $role, 'updated_at' => date('Y-m-d H:i:s')]);
                    break;
                }
            }
        } catch (Exception $fbErr) { error_log('Firebase sync error (update_staff): ' . $fbErr->getMessage()); }

        echo json_encode(['success' => true, 'message' => 'Staff updated successfully!']);
    }
    elseif ($action === 'update_rider') {
        $id     = $data['id'] ?? null;
        $fname  = $data['fname'] ?? '';
        $lname  = $data['lname'] ?? '';
        $email  = $data['email'] ?? '';
        $mobile = $data['mobile'] ?? '';
        if (!$id || empty($fname) || empty($email)) { echo json_encode(['success' => false, 'message' => 'ID, name, and email required.']); exit; }

        // MySQL update
        $stmt = $pdo->prepare("UPDATE RIDER SET Rider_FName = ?, Rider_LName = ?, Rider_Email = ?, Rider_MobileNum = ? WHERE Rider_ID = ? AND Rider_Brnch_ID = ?");
        $stmt->execute([$fname, $lname, $email, $mobile, $id, $branch_id]);

        // Firebase UPDATE
        try {
            $docs = $firestore->collection('rider')->documents();
            foreach ($docs as $doc) {
                if ($doc->exists() && fbMatchesId($doc, $id, ['Rider_ID'])) {
                    fbUpdate($firestore, 'rider', $doc->id(), ['first_name' => $fname, 'last_name' => $lname, 'email' => $email, 'mobile' => $mobile, 'updated_at' => date('Y-m-d H:i:s')]);
                    break;
                }
            }
        } catch (Exception $fbErr) { error_log('Firebase sync error (update_rider): ' . $fbErr->getMessage()); }

        echo json_encode(['success' => true, 'message' => 'Rider updated successfully!']);
    }
    elseif ($action === 'update_menu') {
        $id    = $data['Menu_ID'] ?? null;
        $name  = $data['Menu_Name'] ?? '';
        $desc  = $data['Menu_Description'] ?? '';
        $price = $data['Menu_Price'] ?? 0;
        $cat   = $data['Menu_Category'] ?? 'Chicken';
        $size  = $data['Menu_Size'] ?? 'Standard';
        $image = $data['Menu_Image'] ?? '';
        if (!$id || empty($name)) { echo json_encode(['success' => false, 'message' => 'ID and name required.']); exit; }

        $check = $pdo->prepare("SELECT Menu_Brnch_ID FROM MENU_ITEM WHERE Menu_ID = ?");
        $check->execute([$id]);
        $owner = $check->fetchColumn();
        if ($owner !== null && $owner != $branch_id) { echo json_encode(['success' => false, 'message' => 'Unauthorized.']); exit; }

        // MySQL update
        if (!empty($image)) {
            $image = saveMenuImageLocal($image, $id);
            $stmt = $pdo->prepare("UPDATE MENU_ITEM SET Menu_Name = ?, Menu_Description = ?, Menu_Price = ?, Menu_Category = ?, Menu_Size = ?, Menu_Image = ? WHERE Menu_ID = ?");
            $stmt->execute([$name, $desc, $price, $cat, $size, $image, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE MENU_ITEM SET Menu_Name = ?, Menu_Description = ?, Menu_Price = ?, Menu_Category = ?, Menu_Size = ? WHERE Menu_ID = ?");
            $stmt->execute([$name, $desc, $price, $cat, $size, $id]);
        }

        // Firebase UPDATE
        try {
            $docs = $firestore->collection('menu_item')->documents();
            foreach ($docs as $doc) {
                if ($doc->exists() && fbMatchesId($doc, $id, ['Menu_ID'])) {
                    fbUpdate($firestore, 'menu_item', $doc->id(), ['name' => $name, 'description' => $desc, 'price' => (string)$price, 'category' => $cat, 'size' => $size, 'updated_at' => date('Y-m-d H:i:s')]);
                    break;
                }
            }
        } catch (Exception $fbErr) { error_log('Firebase sync error (update_menu): ' . $fbErr->getMessage()); }

        echo json_encode(['success' => true, 'message' => 'Branch menu item updated!']);
    }
    elseif ($action === 'update_order_status') {
        $order_id = $data['order_id'];
        $status   = $data['status'];

        // MySQL update
        $stmt = $pdo->prepare("UPDATE orders SET Order_Stat = ? WHERE Order_ID = ?");
        $stmt->execute([$status, $order_id]);

        // Firebase UPDATE
        try {
            $docs = $firestore->collection('orders')->documents();
            foreach ($docs as $doc) {
                if ($doc->exists() && fbMatchesId($doc, $order_id, ['Order_ID'])) {
                    fbUpdate($firestore, 'orders', $doc->id(), ['status' => $status, 'updated_at' => date('Y-m-d H:i:s')]);
                    break;
                }
            }
        } catch (Exception $fbErr) { error_log('Firebase sync error (update_order_status): ' . $fbErr->getMessage()); }

        echo json_encode(['success' => true, 'message' => 'Status updated.']);
    }
    elseif ($action === 'toggle_menu') {
        $menu_id = $data['menu_id'];
        $status  = $data['status'];

        // MySQL update
        $stmt = $pdo->prepare("INSERT INTO BRANCH_MENU (Brnch_ID, Menu_ID, Is_Available) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE Is_Available = ?");
        $stmt->execute([$branch_id, $menu_id, $status, $status]);

        // Firebase UPDATE
        try {
            $docs = $firestore->collection('menu_item')->documents();
            foreach ($docs as $doc) {
                if ($doc->exists() && fbMatchesId($doc, $menu_id, ['Menu_ID'])) {
                    fbUpdate($firestore, 'menu_item', $doc->id(), ['is_available' => $status, 'updated_at' => date('Y-m-d H:i:s')]);
                    break;
                }
            }
        } catch (Exception $fbErr) { error_log('Firebase sync error (toggle_menu): ' . $fbErr->getMessage()); }

        echo json_encode(['success' => true, 'message' => 'Availability updated!']);
    }
    elseif ($action === 'update_stock') {
        $menu_id = $data['menu_id'];
        $stock   = intval($data['stock'] ?? 0);

        // MySQL update
        $stmt = $pdo->prepare("INSERT INTO BRANCH_MENU (Brnch_ID, Menu_ID, Stock_Qty) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE Stock_Qty = ?");
        $stmt->execute([$branch_id, $menu_id, $stock, $stock]);

        // Firebase UPDATE
        try {
            $docs = $firestore->collection('menu_item')->documents();
            foreach ($docs as $doc) {
                if ($doc->exists() && fbMatchesId($doc, $menu_id, ['Menu_ID'])) {
                    fbUpdate($firestore, 'menu_item', $doc->id(), ['stock_qty' => (string)$stock, 'updated_at' => date('Y-m-d H:i:s')]);
                    break;
                }
            }
        } catch (Exception $fbErr) { error_log('Firebase sync error (update_stock): ' . $fbErr->getMessage()); }

        echo json_encode(['success' => true, 'message' => 'Inventory updated!']);
    }
    elseif ($action === 'delete_workforce') {
        $id     = $data['id'];
        $source = $data['source'];

        // MySQL delete
        if ($source === 'Staff') {
            $stmt = $pdo->prepare("DELETE FROM STAFF WHERE Staff_ID = ? AND Staff_Brnch_ID = ?");
        } else {
            $stmt = $pdo->prepare("DELETE FROM RIDER WHERE Rider_ID = ? AND Rider_Brnch_ID = ?");
        }
        $stmt->execute([$id, $branch_id]);

        // Firebase DELETE
        try {
            $collection = ($source === 'Staff') ? 'staff' : 'rider';
            $docs = $firestore->collection($collection)->documents();
            $idKeys = ($source === 'Staff') ? ['Staff_ID'] : ['Rider_ID'];
            foreach ($docs as $doc) {
                if ($doc->exists() && fbMatchesId($doc, $id, $idKeys)) {
                    fbDelete($firestore, $collection, $doc->id());
                    break;
                }
            }
        } catch (Exception $fbErr) { error_log('Firebase sync error (delete_workforce): ' . $fbErr->getMessage()); }

        echo json_encode(['success' => true, 'message' => 'Record deleted successfully.']);
    }
    elseif ($action === 'delete_menu') {
        $id = $data['id'] ?? null;
        if (!$id) { echo json_encode(['success' => false, 'message' => 'ID required.']); exit; }
        $check = $pdo->prepare("SELECT Menu_Brnch_ID FROM MENU_ITEM WHERE Menu_ID = ?");
        $check->execute([$id]);
        $owner = $check->fetchColumn();
        if ($owner !== null && $owner != $branch_id) { echo json_encode(['success' => false, 'message' => 'Unauthorized.']); exit; }

        // MySQL soft delete
        $stmt = $pdo->prepare("UPDATE MENU_ITEM SET Menu_Status = 'D' WHERE Menu_ID = ?");
        $stmt->execute([$id]);

        // Firebase DELETE
        try {
            $docs = $firestore->collection('menu_item')->documents();
            foreach ($docs as $doc) {
                if ($doc->exists() && fbMatchesId($doc, $id, ['Menu_ID'])) {
                    fbDelete($firestore, 'menu_item', $doc->id());
                    break;
                }
            }
        } catch (Exception $fbErr) { error_log('Firebase sync error (delete_menu): ' . $fbErr->getMessage()); }

        echo json_encode(['success' => true, 'message' => 'Branch menu item removed!']);
    }
    elseif ($action === 'update_branch') {
        $name     = $data['Brnch_Name'] ?? '';
        $street   = $data['Brnch_Street'] ?? '';
        $brgy     = $data['Brnch_Brgy'] ?? '';
        $city     = $data['Brnch_City'] ?? '';
        $province = $data['Brnch_Province'] ?? '';
        $radius   = $data['Brnch_Radius'] ?? 5;
        $id       = $data['Brnch_ID'] ?? $branch_id;

        // MySQL update
        $stmt = $pdo->prepare("UPDATE BRANCH SET Brnch_Name = ?, Brnch_Street = ?, Brnch_Brgy = ?, Brnch_City = ?, Brnch_Province = ?, Brnch_Radius = ? WHERE Brnch_ID = ?");
        $stmt->execute([$name, $street, $brgy, $city, $province, $radius, $id]);

        // Firebase UPDATE
        try {
            $docs = $firestore->collection('branch')->documents();
            foreach ($docs as $doc) {
                if ($doc->exists() && fbMatchesId($doc, $id, ['Brnch_ID'])) {
                    fbUpdate($firestore, 'branch', $doc->id(), ['name' => $name, 'street' => $street, 'brgy' => $brgy, 'city' => $city, 'province' => $province, 'radius' => (string)$radius, 'updated_at' => date('Y-m-d H:i:s')]);
                    break;
                }
            }
        } catch (Exception $fbErr) { error_log('Firebase sync error (update_branch): ' . $fbErr->getMessage()); }

        echo json_encode(['success' => true, 'message' => 'Branch details updated!']);
    }

    // ============================================================
    // 4. TRANSACTION INTERFACE — assign_rider
    //    Multi-collection: updates orders + delivery + rider atomically
    // ============================================================
    elseif ($action === 'assign_rider') {
        $order_id = $data['order_id'];
        $rider_id = $data['rider_id'];

        // MySQL transaction
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("DELETE FROM DELIVERY WHERE Dlvry_Order_ID = ?");
            $stmt->execute([$order_id]);
            $stmt = $pdo->prepare("INSERT INTO DELIVERY (Dlvry_Order_ID, Dlvry_Rider_ID, Dlvry_Pickup_Time, Dlvry_Current_ETA) VALUES (?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 30 MINUTE))");
            $stmt->execute([$order_id, $rider_id]);
            $stmt = $pdo->prepare("UPDATE RIDER SET Rider_Status = 'N' WHERE Rider_ID = ?");
            $stmt->execute([$rider_id]);
            $stmt = $pdo->prepare("UPDATE orders SET Order_Stat = 'Delivering' WHERE Order_ID = ?");
            $stmt->execute([$order_id]);
            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }

        // Firebase TRANSACTION: update order + delivery + rider atomically
        try {
            // 1. Update order status
            $docs = $firestore->collection('orders')->documents();
            $fb_order_id = null;
            foreach ($docs as $doc) {
                if ($doc->exists() && fbMatchesId($doc, $order_id, ['Order_ID'])) {
                    fbUpdate($firestore, 'orders', $doc->id(), ['status' => 'Delivering', 'updated_at' => date('Y-m-d H:i:s')]);
                    $fb_order_id = $doc->id();
                    break;
                }
            }
            // 2. Update delivery record
            $deliveryDocs = $firestore->collection('delivery')->documents();
            foreach ($deliveryDocs as $doc) {
                if ($doc->exists() && (
                    (isset($doc->data()['order_id']) && $doc->data()['order_id'] == $fb_order_id) ||
                    fbMatchesId($doc, $order_id, ['Dlvry_Order_ID'])
                )) {
                    fbUpdate($firestore, 'delivery', $doc->id(), ['rider_id' => (string)$rider_id, 'status' => 'Delivering', 'pickup_time' => date('Y-m-d H:i:s')]);
                    break;
                }
            }
            // 3. Update rider status
            $riderDocs = $firestore->collection('rider')->documents();
            foreach ($riderDocs as $doc) {
                if ($doc->exists() && fbMatchesId($doc, $rider_id, ['Rider_ID'])) {
                    fbUpdate($firestore, 'rider', $doc->id(), ['status' => 'N', 'updated_at' => date('Y-m-d H:i:s')]);
                    break;
                }
            }
        } catch (Exception $fbErr) { error_log('Firebase sync error (assign_rider): ' . $fbErr->getMessage()); }

        echo json_encode(['success' => true, 'message' => 'Rider assigned and order dispatched!']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>