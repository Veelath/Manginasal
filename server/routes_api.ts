import { Router } from 'express';
import bcrypt from 'bcryptjs';
import { db, dbFirestore, saveMenuImageLocal, firestoreAdd, firestoreUpdate, firestoreDelete, syncFirebaseCustomersToMySql, syncFirebaseMenuToMySql, syncFirebaseBranchesToMySql } from './db_sqlite.js';

export const apiRouter = Router();

// Help retrieve date-now in YYYY-MM-DD HH:mm:ss format
function mysqlNow() {
  return new Date().toISOString().slice(0, 19).replace('T', ' ');
}

// Global middleware to handle session access check where needed
function getSessionUser(req: any) {
  if (!req.session?.user_id) {
    return null;
  }
  return {
    id: req.session.user_id,
    role: req.session.role,
    branch_id: req.session.branch_id,
    email: req.session.email,
    name: req.session.name
  };
}

// Helper to wrap JSON with Firebase connection status
function sendWithFirebase(res: any, payload: any) {
  res.json({
    ...payload,
    firebase_status: {
      connected: !!dbFirestore,
      error: dbFirestore ? '' : 'Firebase connection uninitialized or credential missing'
    }
  });
}

// ============================================================
// 1. ORDERS API PROXIES (orders_api.php)
// ============================================================
apiRouter.post('/orders_api.php', async (req, res) => {
  const user = getSessionUser(req);
  const data = req.body || {};
  const action = data.action || '';

  if (!action) {
    return sendWithFirebase(res, { success: false, message: 'Invalid request.' });
  }

  try {
    if (action === 'get_branches') {
      const branches = db.prepare('SELECT * FROM BRANCH WHERE Brnch_Status = \'Y\'').all();
      return sendWithFirebase(res, { success: true, branches });
    }

    else if (action === 'get_menu') {
      const branchId = data.branch_id || null;
      let menuItems: any[] = [];
      if (branchId) {
        menuItems = db.prepare(`
          SELECT m.*, IFNULL(bm.Is_Available, 'Y') as Is_Available, IFNULL(bm.Stock_Qty, 50) as Stock_Qty 
          FROM MENU_ITEM m 
          LEFT JOIN BRANCH_MENU bm ON m.Menu_ID = bm.Menu_ID AND bm.Brnch_ID = ?
          WHERE m.Menu_Status = 'Y' AND (m.Menu_Brnch_ID IS NULL OR m.Menu_Brnch_ID = ?)
        `).all(branchId, branchId);
      } else {
        menuItems = db.prepare('SELECT m.*, \'Y\' as Is_Available, 50 as Stock_Qty FROM MENU_ITEM m WHERE m.Menu_Status = \'Y\'').all();
      }
      return sendWithFirebase(res, { success: true, menu: menuItems });
    }

    else if (action === 'get_customer_addresses') {
      if (!user) return sendWithFirebase(res, { success: false, message: 'Unauthorized.' });
      const addresses = db.prepare('SELECT * FROM ADDRESS WHERE Add_Cust_ID = ?').all(user.id);
      return sendWithFirebase(res, { success: true, data: addresses });
    }

    else if (action === 'add_customer_address') {
      if (!user) return sendWithFirebase(res, { success: false, message: 'Unauthorized.' });
      const province = data.add_province || '';
      const city = data.add_city || '';
      const brgy = data.add_brgy || '';
      const street = data.add_street || '';
      const unit = data.add_unit || '';
      const bldg = data.add_bldg || '';
      const lndmrk = data.add_lndmrk || '';
      const postal = data.add_postal || '';
      const label = data.add_label || 'Home';

      db.prepare(`
        INSERT INTO ADDRESS (Add_Cust_ID, Add_Province, Add_City, Add_Brgy, Add_Street, Add_UnitNum, Add_Building, Add_Landmark, Add_PostalCode, Add_Label)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
      `).run(user.id, province, city, brgy, street, unit, bldg, lndmrk, postal, label);

      return sendWithFirebase(res, { success: true, message: 'Address added!' });
    }

    else if (action === 'place_order') {
      if (!user) return sendWithFirebase(res, { success: false, message: 'Unauthorized.' });
      
      const b_id = Number(data.branch_id);
      const add_id = Number(data.address_id);
      const pay_method = data.pay_method;
      const ref_num = data.ref_num || '';
      const sub = Number(data.subtotal);
      const delivery_fee = Number(data.delivery_fee || 0);
      const disc = Number(data.discount_amount || 0);
      const total = Number(data.total_amount);
      const is_bulk = data.is_bulk || 'N';
      const r_name = data.recipient_name || user.name || 'Recipient';
      const r_num = data.recipient_num || '09000000000';
      const sched_date = data.sched_date || null;
      const sched_time = data.sched_time || null;
      const payment_status = (pay_method === 'GCash' || pay_method === 'Maya') ? 'Paid' : 'Pending';

      const items = data.items || [];
      if (!items.length) {
        return sendWithFirebase(res, { success: false, message: 'Cannot place order without items.' });
      }

      const order_code = 'MIO-' + Math.floor(100000 + Math.random() * 900000);

      const dbTx = db.transaction(() => {
        const orderInsert = db.prepare(`
          INSERT INTO orders (Order_Cust_ID, Order_Brnch_ID, Order_Add_ID, Order_Code, Order_Type, Order_Is_Bulk, Order_Recipient_Name, Order_Recipient_Num, Order_Sched_Date, Order_Sched_Time_Slot, Order_Subtotal, Order_Dlvry_Fee, Order_Disc_Amount, Order_Total_Amount, Order_Stat)
          VALUES (?, ?, ?, ?, 'Delivery', ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending')
        `).run(user.id, b_id, add_id, order_code, is_bulk, r_name, r_num, sched_date, sched_time, sub, delivery_fee, disc, total);

        const order_id = orderInsert.lastInsertRowid;

        db.prepare('INSERT INTO PAYMENT (Pay_Order_ID, Pay_Amount, Pay_Method, Pay_Status, Pay_Transac_RefNum) VALUES (?, ?, ?, ?, ?)').run(order_id, total, pay_method, payment_status, ref_num);

        if (data.disc_type && data.disc_id_num && data.disc_id_name) {
          db.prepare('INSERT INTO DISCOUNT_VERIFICATION (Order_ID, Disc_Type, Disc_ID_Num, Disc_ID_Name) VALUES (?, ?, ?, ?)').run(order_id, data.disc_type, data.disc_id_num, data.disc_id_name);
        }

        // Add items
        let oitem_id = 1;
        for (const itm of items) {
          db.prepare(`
            INSERT INTO ORDER_ITEM (Order_ID, OItem_ID, OItem_Menu_ID, OItem_Base_Price, OItem_Custom_Total, OItem_Unit_Price, OItem_Quantity, OItem_Special_Instruct)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
          `).run(order_id, oitem_id, itm.Menu_ID, itm.Menu_Price, 0, itm.Menu_Price, itm.quantity, itm.special_instruct || '');

          // Update stock if branch order
          db.prepare(`
            INSERT OR IGNORE INTO BRANCH_MENU (Brnch_ID, Menu_ID, Is_Available, Stock_Qty) VALUES (?, ?, 'Y', 50)
          `).run(b_id, itm.Menu_ID);
          db.prepare(`
            UPDATE BRANCH_MENU SET Stock_Qty = MAX(0, Stock_Qty - ?) WHERE Brnch_ID = ? AND Menu_ID = ?
          `).run(itm.quantity, b_id, itm.Menu_ID);

          oitem_id++;
        }

        return order_id;
      });

      const order_id = dbTx();

      // Sync to Firebase in background if possible
      try {
        if (dbFirestore) {
          const docId = `order_${order_id}`;
          await dbFirestore.collection('orders').doc(docId).set({
            mysql_id: String(order_id),
            customer_id: String(user.id),
            branch_id: String(b_id),
            address_id: String(add_id),
            order_code,
            order_type: 'Delivery',
            is_bulk,
            recipient_name: r_name,
            recipient_num: r_num,
            subtotal: sub,
            delivery_fee,
            discount_amount: disc,
            total_amount: total,
            status: 'Pending',
            payment_method: pay_method,
            payment_status,
            created_at: mysqlNow()
          });

          for (const itm of items) {
             await dbFirestore.collection('order_item').add({
               order_id: docId,
               menu_id: String(itm.Menu_ID),
               menu_name: itm.Menu_Name,
               quantity: Number(itm.quantity),
               price: Number(itm.Menu_Price),
               special_instructions: itm.special_instruct || ''
             });
          }
        }
      } catch (err) {
        console.error('Firebase place_order sync error:', err);
      }

      return sendWithFirebase(res, { success: true, message: `Order placed successfully! Order Code: ${order_code}`, order_id });
    }

    else if (action === 'get_customer_orders') {
      if (!user) return sendWithFirebase(res, { success: false, message: 'Unauthorized.' });

      // Fallback or prefer MySQL
      const orders = db.prepare(`
        SELECT o.*, p.Pay_Method, p.Pay_Status, d.Dlvry_Pickup_Time, d.Dlvry_Arrival_Time, d.Dlvry_Current_ETA, r.Rider_FName, r.Rider_LName, r.Rider_MobileNum
        FROM orders o
        LEFT JOIN PAYMENT p ON o.Order_ID = p.Pay_Order_ID
        LEFT JOIN DELIVERY d ON o.Order_ID = d.Dlvry_Order_ID
        LEFT JOIN RIDER r ON d.Dlvry_Rider_ID = r.Rider_ID
        WHERE o.Order_Cust_ID = ?
        ORDER BY o.Order_ID DESC
      `).all(user.id) as any[];

      for (const order of orders) {
        order.Order_Items = db.prepare(`
          SELECT oi.*, m.Menu_Name 
          FROM ORDER_ITEM oi 
          JOIN MENU_ITEM m ON oi.OItem_Menu_ID = m.Menu_ID 
          WHERE oi.Order_ID = ?
        `).all(order.Order_ID);
      }

      return sendWithFirebase(res, { success: true, data: orders, source: 'mysql' });
    }

    else if (action === 'complete_delivery') {
      if (user?.role !== 'Driver') {
        return sendWithFirebase(res, { success: false, message: 'Unauthorized. Driver status required.' });
      }
      const order_id = Number(data.order_id);

      db.prepare('UPDATE orders SET Order_Stat = \'Completed\' WHERE Order_ID = ?').run(order_id);
      db.prepare('UPDATE DELIVERY SET Dlvry_Arrival_Time = ? WHERE Dlvry_Order_ID = ?').run(mysqlNow(), order_id);
      db.prepare('UPDATE PAYMENT SET Pay_Status = \'Paid\' WHERE Pay_Order_ID = ? AND Pay_Method = \'Cash (COD)\'').run(order_id);

      // Async Firestore update
      try {
        await firestoreUpdate('orders', order_id, { status: 'Completed', payment_status: 'Paid' }, 'mysql_id');
      } catch (err) {
        console.error('Firebase complete_delivery sync error:', err);
      }

      return sendWithFirebase(res, { success: true, message: 'Delivery completed!' });
    }

    else if (action === 'update_eta') {
      if (user?.role !== 'Driver') {
        return sendWithFirebase(res, { success: false, message: 'Unauthorized.' });
      }
      const order_id = Number(data.order_id);
      const eta = data.eta;

      db.prepare('UPDATE DELIVERY SET Dlvry_Current_ETA = ? WHERE Dlvry_Order_ID = ?').run(eta, order_id);

      try {
        await firestoreUpdate('orders', order_id, { current_eta: eta }, 'mysql_id');
      } catch (err) {
        console.error('Firebase update_eta sync error:', err);
      }

      return sendWithFirebase(res, { success: true, message: 'ETA updated!' });
    }

    else if (action === 'get_dispatch_queue') {
      if (user?.role !== 'Driver') {
        return sendWithFirebase(res, { success: false, message: 'Unauthorized.' });
      }
      // Driver orders for driver's branch
      const queue = db.prepare(`
        SELECT o.*, d.Dlvry_Pickup_Time, d.Dlvry_Arrival_Time, d.Dlvry_Current_ETA,
               a.Add_Street, a.Add_Brgy, a.Add_City, a.Add_Province, a.Add_Landmark,
               p.Pay_Method, p.Pay_Status
        FROM orders o
        JOIN DELIVERY d ON o.Order_ID = d.Dlvry_Order_ID
        JOIN ADDRESS a ON o.Order_Add_ID = a.Add_ID
        JOIN PAYMENT p ON o.Order_ID = p.Pay_Order_ID
        WHERE d.Dlvry_Rider_ID = ? AND o.Order_Brnch_ID = ?
        ORDER BY o.Order_ID DESC
      `).all(user.id, user.branch_id);

      return sendWithFirebase(res, { success: true, data: queue });
    }

    else if (action === 'accept_order') {
      if (user?.role !== 'Driver') {
        return sendWithFirebase(res, { success: false, message: 'Unauthorized.' });
      }
      const order_id = Number(data.order_id);
      db.prepare('UPDATE orders SET Order_Stat = \'In Transit\' WHERE Order_ID = ?').run(order_id);
      db.prepare('UPDATE DELIVERY SET Dlvry_Pickup_Time = ? WHERE Dlvry_Order_ID = ?').run(mysqlNow(), order_id);

      try {
        await firestoreUpdate('orders', order_id, { status: 'In Transit' }, 'mysql_id');
      } catch (err) {
        console.error('Firebase accept_order sync error:', err);
      }

      return sendWithFirebase(res, { success: true, message: 'Delivery started!' });
    }

    else {
      return sendWithFirebase(res, { success: false, message: 'Unknown action: ' + action });
    }
  } catch (err: any) {
    return sendWithFirebase(res, { success: false, message: 'Server error: ' + err.message });
  }
});

// ============================================================
// 2. BRANCH MANAGER API (branch_manager_api.php)
// ============================================================
apiRouter.post('/branch_manager_api.php', async (req, res) => {
  const user = getSessionUser(req);
  if (!user || (user.role !== 'Branch Manager' && user.role !== 'System Admin')) {
    return sendWithFirebase(res, { success: false, message: 'Access Denied. Branch Manager role required.' });
  }

  const branch_id = user.branch_id || Number(req.body.branch_id || req.query.branch_id || 1);
  const data = req.body || {};
  const action = data.action || '';

  try {
    if (action === 'create_staff') {
      const fname = data.fname;
      const lname = data.lname;
      const email = data.email;
      const mobile = data.mobile;
      const role = data.role || 'Kitchen Staff';
      const rawPass = data.password || 'password';
      const hashPass = bcrypt.hashSync(rawPass, 10);

      const insert = db.prepare(`
        INSERT INTO STAFF (Staff_Brnch_ID, Staff_Mgr_ID, Staff_FName, Staff_LName, Staff_Email, Staff_MobileNum, Staff_Role, Staff_Pass)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
      `).run(branch_id, user.id, fname, lname, email, mobile, role, hashPass);

      const staff_id = insert.lastInsertRowid;

      try {
        await firestoreAdd('staff', {
          first_name: fname,
          last_name: lname,
          email,
          mobile,
          role,
          branch_id: String(branch_id),
          mysql_id: String(staff_id)
        });
      } catch (err) {
        console.error('Firebase create_staff sync error:', err);
      }

      return sendWithFirebase(res, { success: true, message: 'Branch Staff Created!' });
    }

    else if (action === 'create_rider') {
      const fname = data.fname;
      const lname = data.lname;
      const email = data.email;
      const mobile = data.mobile;
      const rawPass = data.password || 'password';
      const hashPass = bcrypt.hashSync(rawPass, 10);

      const insert = db.prepare(`
        INSERT INTO RIDER (Rider_Brnch_ID, Rider_FName, Rider_LName, Rider_Email, Rider_MobileNum, Rider_Pass)
        VALUES (?, ?, ?, ?, ?, ?)
      `).run(branch_id, fname, lname, email, mobile, hashPass);

      const rider_id = insert.lastInsertRowid;

      try {
        await firestoreAdd('rider', {
          first_name: fname,
          last_name: lname,
          email,
          mobile,
          branch_id: String(branch_id),
          mysql_id: String(rider_id)
        });
      } catch (err) {
        console.error('Firebase create_rider sync error:', err);
      }

      return sendWithFirebase(res, { success: true, message: 'Delivery Rider Created!' });
    }

    else if (action === 'create_menu') {
      const category = data.category || 'Chicken';
      const name = data.name;
      const desc = data.description || '';
      const size = data.size || 'Standard';
      const price = Number(data.price || 0);
      const imgRaw = data.image || '';

      const insert = db.prepare(`
        INSERT INTO MENU_ITEM (Menu_Brnch_ID, Menu_Category, Menu_Name, Menu_Size, Menu_Description, Menu_Image, Menu_Price)
        VALUES (?, ?, ?, ?, ?, '', ?)
      `).run(branch_id, category, name, size, desc, price);

      const menu_id = insert.lastInsertRowid;
      const finalImg = saveMenuImageLocal(imgRaw, menu_id);
      db.prepare('UPDATE MENU_ITEM SET Menu_Image = ? WHERE Menu_ID = ?').run(finalImg, menu_id);

      db.prepare('INSERT INTO BRANCH_MENU (Brnch_ID, Menu_ID, Is_Available, Stock_Qty) VALUES (?, ?, \'Y\', 50)').run(branch_id, menu_id);

      try {
        await firestoreAdd('menu_item', {
          Menu_ID: String(menu_id),
          mysql_id: String(menu_id),
          Menu_Brnch_ID: branch_id ? String(branch_id) : 'NULL',
          Menu_Category: category,
          Menu_Name: name,
          Menu_Size: size,
          Menu_Description: desc,
          Menu_Image: finalImg,
          Menu_Price: price,
          Menu_Status: 'Y'
        });
      } catch (err) {
         console.error('Firebase create_menu sync error:', err);
      }

      return sendWithFirebase(res, { success: true, message: 'Branch Menu Item Created!' });
    }

    else if (action === 'get_branch_menu') {
      const menu = db.prepare(`
        SELECT m.*, IFNULL(bm.Is_Available, 'Y') as Is_Available, IFNULL(bm.Stock_Qty, 50) as Stock_Qty 
        FROM MENU_ITEM m
        LEFT JOIN BRANCH_MENU bm ON m.Menu_ID = bm.Menu_ID AND bm.Brnch_ID = ?
        WHERE bm.Brnch_ID = ? OR m.Menu_Brnch_ID IS NULL OR m.Menu_Brnch_ID = ?
      `).all(branch_id, branch_id, branch_id);
      return sendWithFirebase(res, { success: true, data: menu });
    }

    else if (action === 'get_branch_workforce') {
      const staff = db.prepare('SELECT Staff_ID as id, Staff_FName as fname, Staff_LName as lname, Staff_Email as email, Staff_MobileNum as mobile, Staff_Role as role, \'Staff\' as workforce_type FROM STAFF WHERE Staff_Brnch_ID = ?').all(branch_id);
      const riders = db.prepare('SELECT Rider_ID as id, Rider_FName as fname, Rider_LName as lname, Rider_Email as email, Rider_MobileNum as mobile, \'Driver\' as role, \'Rider\' as workforce_type FROM RIDER WHERE Rider_Brnch_ID = ?').all(branch_id);
      const workforce = [...staff, ...riders];
      return sendWithFirebase(res, { success: true, data: workforce });
    }

    else if (action === 'get_branch_orders') {
      const orders = db.prepare(`
        SELECT o.*, c.Cust_FName, c.Cust_LName, c.Cust_Num,
               a.Add_Street, a.Add_Brgy, a.Add_City, a.Add_Province, a.Add_Landmark,
               d.Dlvry_Pickup_Time, d.Dlvry_Arrival_Time, d.Dlvry_Current_ETA,
               r.Rider_FName, r.Rider_LName, r.Rider_MobileNum, p.Pay_Method, p.Pay_Status
        FROM orders o
        JOIN CUSTOMER c ON o.Order_Cust_ID = c.Cust_ID
        JOIN ADDRESS a ON o.Order_Add_ID = a.Add_ID
        JOIN PAYMENT p ON o.Order_ID = p.Pay_Order_ID
        LEFT JOIN DELIVERY d ON o.Order_ID = d.Dlvry_Order_ID
        LEFT JOIN RIDER r ON d.Dlvry_Rider_ID = r.Rider_ID
        WHERE o.Order_Brnch_ID = ?
        ORDER BY o.Order_ID DESC
      `).all(branch_id) as any[];

      return sendWithFirebase(res, { success: true, data: orders });
    }

    else if (action === 'get_order_items') {
      const order_id = Number(data.order_id);
      const items = db.prepare(`
        SELECT oi.*, m.Menu_Name, m.Menu_Size 
        FROM ORDER_ITEM oi 
        JOIN MENU_ITEM m ON oi.OItem_Menu_ID = m.Menu_ID 
        WHERE oi.Order_ID = ?
      `).all(order_id);
      return sendWithFirebase(res, { success: true, data: items });
    }

    else if (action === 'get_branch_stats') {
      const sCount = (db.prepare('SELECT COUNT(*) as count FROM STAFF WHERE Staff_Brnch_ID = ?').get(branch_id) as any).count;
      const rCount = (db.prepare('SELECT COUNT(*) as count FROM RIDER WHERE Rider_Brnch_ID = ?').get(branch_id) as any).count;
      const sales = (db.prepare('SELECT SUM(Order_Total_Amount) as sum FROM orders WHERE Order_Brnch_ID = ? AND Order_Stat = \'Completed\'').get(branch_id) as any).sum || 0;
      
      const riders = db.prepare(`
        SELECT r.Rider_ID, r.Rider_FName, r.Rider_LName, COUNT(o.Order_ID) as stats
        FROM RIDER r
        LEFT JOIN DELIVERY d ON r.Rider_ID = d.Dlvry_Rider_ID
        LEFT JOIN orders o ON d.Dlvry_Order_ID = o.Order_ID AND o.Order_Stat = 'Completed'
        WHERE r.Rider_Brnch_ID = ?
        GROUP BY r.Rider_ID
      `).all(branch_id);

      return sendWithFirebase(res, {
        success: true,
        stats: {
          staff: sCount,
          riders: rCount,
          dailySales: sales,
          dailyGoal: 25000,
          riderPerformance: riders
        }
      });
    }

    else if (action === 'get_branch_info') {
      const info = db.prepare('SELECT * FROM BRANCH WHERE Brnch_ID = ?').get(branch_id);
      return sendWithFirebase(res, { success: true, branch: info });
    }

    else if (action === 'update_staff') {
      const id = Number(data.id);
      const fname = data.fname;
      const lname = data.lname;
      const email = data.email;
      const mobile = data.mobile;
      const role = data.role || 'Kitchen Staff';

      db.prepare('UPDATE STAFF SET Staff_FName = ?, Staff_LName = ?, Staff_Email = ?, Staff_MobileNum = ?, Staff_Role = ? WHERE Staff_ID = ? AND Staff_Brnch_ID = ?').run(fname, lname, email, mobile, role, id, branch_id);

      try {
        await firestoreUpdate('staff', id, { first_name: fname, last_name: lname, email, mobile, role }, 'mysql_id');
      } catch (err) {
        console.error('Firebase staff sync error:', err);
      }

      return sendWithFirebase(res, { success: true, message: 'Staff records updated!' });
    }

    else if (action === 'update_rider') {
      const id = Number(data.id);
      const fname = data.fname;
      const lname = data.lname;
      const email = data.email;
      const mobile = data.mobile;

      db.prepare('UPDATE RIDER SET Rider_FName = ?, Rider_LName = ?, Rider_Email = ?, Rider_MobileNum = ? WHERE Rider_ID = ? AND Rider_Brnch_ID = ?').run(fname, lname, email, mobile, id, branch_id);

      try {
        await firestoreUpdate('rider', id, { first_name: fname, last_name: lname, email, mobile }, 'mysql_id');
      } catch (err) {
        console.error('Firebase update_rider sync error:', err);
      }

      return sendWithFirebase(res, { success: true, message: 'Rider record updated!' });
    }

    else if (action === 'update_menu') {
      const id = Number(data.id);
      const name = data.name;
      const category = data.category || 'Chicken';
      const price = Number(data.price || 0);
      const desc = data.description || '';
      const size = data.size || 'Standard';
      const imgRaw = data.image || '';

      db.prepare('UPDATE MENU_ITEM SET Menu_Name = ?, Menu_Category = ?, Menu_Price = ?, Menu_Description = ?, Menu_Size = ? WHERE Menu_ID = ?').run(name, category, price, desc, size, id);

      if (imgRaw) {
        const finalImg = saveMenuImageLocal(imgRaw, id);
        db.prepare('UPDATE MENU_ITEM SET Menu_Image = ? WHERE Menu_ID = ?').run(finalImg, id);
      }

      const updated = db.prepare('SELECT * FROM MENU_ITEM WHERE Menu_ID = ?').get(id) as any;

      try {
        await firestoreUpdate('menu_item', id, {
          Menu_Name: name,
          Menu_Category: category,
          Menu_Price: price,
          Menu_Description: desc,
          Menu_Size: size,
          Menu_Image: updated.Menu_Image
        }, 'mysql_id');
      } catch (err) {
        console.error('Firebase update_menu sync error:', err);
      }

      return sendWithFirebase(res, { success: true, message: 'Menu Item updated!' });
    }

    else if (action === 'toggle_menu') {
      const id = Number(data.id);
      const avail = data.available; // 'Y' or 'N'

      db.prepare('INSERT OR IGNORE INTO BRANCH_MENU (Brnch_ID, Menu_ID, Is_Available, Stock_Qty) VALUES (?, ?, \'Y\', 50)').run(branch_id, id);
      db.prepare('UPDATE BRANCH_MENU SET Is_Available = ? WHERE Brnch_ID = ? AND Menu_ID = ?').run(avail, branch_id, id);

      return sendWithFirebase(res, { success: true, message: 'Menu availability toggled!' });
    }

    else if (action === 'update_stock') {
      const id = Number(data.id);
      const qty = Number(data.qty);

      db.prepare('INSERT OR IGNORE INTO BRANCH_MENU (Brnch_ID, Menu_ID, Is_Available, Stock_Qty) VALUES (?, ?, \'Y\', 50)').run(branch_id, id);
      db.prepare('UPDATE BRANCH_MENU SET Stock_Qty = ? WHERE Brnch_ID = ? AND Menu_ID = ?').run(qty, branch_id, id);

      return sendWithFirebase(res, { success: true, message: 'Stock levels updated!' });
    }

    else if (action === 'update_order_status') {
      const order_id = Number(data.order_id);
      const stat = data.status;
      const rider_id = data.rider_id ? Number(data.rider_id) : null;

      db.transaction(() => {
        db.prepare('UPDATE orders SET Order_Stat = ? WHERE Order_ID = ?').run(stat, order_id);

        if (rider_id) {
          // Check if DELIVERY row exists
          const dlvry = db.prepare('SELECT * FROM DELIVERY WHERE Dlvry_Order_ID = ?').get(order_id);
          if (!dlvry) {
            db.prepare('INSERT INTO DELIVERY (Dlvry_Order_ID, Dlvry_Rider_ID) VALUES (?, ?)').run(order_id, rider_id);
          } else {
            db.prepare('UPDATE DELIVERY SET Dlvry_Rider_ID = ? WHERE Dlvry_Order_ID = ?').run(rider_id, order_id);
          }
        }
      })();

      try {
        await firestoreUpdate('orders', order_id, {
          status: stat,
          rider_id: rider_id ? String(rider_id) : 'NULL'
        }, 'mysql_id');
      } catch (err) {
        console.error('Firebase update_order_status sync error:', err);
      }

      return sendWithFirebase(res, { success: true, message: 'Order status updated!' });
    }

    else if (action === 'delete_workforce') {
      const id = Number(data.id);
      const type = data.type; // 'Staff' or 'Rider'

      if (type === 'Staff') {
        db.prepare('DELETE FROM STAFF WHERE Staff_ID = ? AND Staff_Brnch_ID = ?').run(id, branch_id);
        await firestoreDelete('staff', id, 'mysql_id');
      } else {
        db.prepare('DELETE FROM RIDER WHERE Rider_ID = ? AND Rider_Brnch_ID = ?').run(id, branch_id);
        await firestoreDelete('rider', id, 'mysql_id');
      }

      return sendWithFirebase(res, { success: true, message: 'Record removed successfully!' });
    }

    else if (action === 'delete_menu') {
      const id = Number(data.id);
      db.prepare('DELETE FROM MENU_ITEM WHERE Menu_ID = ?').run(id);
      db.prepare('DELETE FROM BRANCH_MENU WHERE Menu_ID = ?').run(id);

      await firestoreDelete('menu_item', id, 'mysql_id');

      return sendWithFirebase(res, { success: true, message: 'Menu Item Deleted!' });
    }

    else {
      return sendWithFirebase(res, { success: false, message: 'Unknown Action: ' + action });
    }
  } catch (err: any) {
    return sendWithFirebase(res, { success: false, message: 'Server error: ' + err.message });
  }
});

// ============================================================
// 3. SYSTEM ADMIN API (system_admin_api.php)
// ============================================================
apiRouter.post('/system_admin_api.php', async (req, res) => {
  const user = getSessionUser(req);
  if (!user || user.role !== 'System Admin') {
    return sendWithFirebase(res, { success: false, message: 'Access Denied. System Admin role required.' });
  }

  const data = req.body || {};
  const action = data.action || '';

  try {
    if (action === 'create_branch') {
      const name = data.name;
      const street = data.street;
      const brgy = data.brgy;
      const city = data.city;
      const province = data.province;
      const radius = Number(data.radius || 5);

      const insert = db.prepare(`
        INSERT INTO BRANCH (Brnch_Name, Brnch_Street, Brnch_Brgy, Brnch_City, Brnch_Province, Brnch_Radius)
        VALUES (?, ?, ?, ?, ?, ?)
      `).run(name, street, brgy, city, province, radius);

      const branch_id = insert.lastInsertRowid;

      try {
        await firestoreAdd('branch', {
          Brnch_ID: String(branch_id),
          mysql_id: String(branch_id),
          Brnch_Name: name,
          Brnch_Street: street,
          Brnch_Brgy: brgy,
          Brnch_City: city,
          Brnch_Province: province,
          Brnch_Radius: radius,
          Brnch_Status: 'Y'
        });
      } catch (err) {
        console.error('Firebase create_branch sync error:', err);
      }

      return sendWithFirebase(res, { success: true, message: 'Branch Created successfully!' });
    }

    else if (action === 'create_manager') {
      const b_id = Number(data.branch_id);
      const fname = data.fname;
      const lname = data.lname;
      const email = data.email;
      const mobile = data.mobile;
      const rawPass = data.password || 'password';
      const hashPass = bcrypt.hashSync(rawPass, 10);

      db.prepare(`
        INSERT INTO BRANCH_MANAGER (Mgr_Brnch_ID, Mgr_FName, Mgr_LName, Mgr_Email, Mgr_MobileNum, Mgr_Pass)
        VALUES (?, ?, ?, ?, ?, ?)
      `).run(b_id, fname, lname, email, mobile, hashPass);

      return sendWithFirebase(res, { success: true, message: 'Branch Manager Created!' });
    }

    else if (action === 'get_branches_list') {
      const list = db.prepare('SELECT * FROM BRANCH').all();
      return sendWithFirebase(res, { success: true, data: list });
    }

    else if (action === 'get_managers_list') {
      const list = db.prepare(`
        SELECT m.*, b.Brnch_Name 
        FROM BRANCH_MANAGER m
        LEFT JOIN BRANCH b ON m.Mgr_Brnch_ID = b.Brnch_ID
      `).all();
      return sendWithFirebase(res, { success: true, data: list });
    }

    else if (action === 'get_global_stats') {
      const bCount = (db.prepare('SELECT COUNT(*) as count FROM BRANCH').get() as any).count;
      const mCount = (db.prepare('SELECT COUNT(*) as count FROM BRANCH_MANAGER').get() as any).count;
      const orderCount = (db.prepare('SELECT COUNT(*) as count FROM orders').get() as any).count;
      const sales = (db.prepare('SELECT SUM(Order_Total_Amount) as sum FROM orders WHERE Order_Stat = \'Completed\'').get() as any).sum || 0;

      const monthlySales = db.prepare(`
         SELECT strftime('%Y-%m', Order_Date) as month, SUM(Order_Total_Amount) as sales 
         FROM orders 
         WHERE Order_Stat = 'Completed' 
         GROUP BY month 
         ORDER BY month ASC
      `).all();

      const topProducts = db.prepare(`
         SELECT m.Menu_Name as MenuItem, SUM(oi.OItem_Quantity) as SalesCount 
         FROM ORDER_ITEM oi 
         JOIN MENU_ITEM m ON oi.OItem_Menu_ID = m.Menu_ID 
         GROUP BY m.Menu_Name 
         ORDER BY SalesCount DESC 
         LIMIT 5
      `).all();

      const branchPerformance = db.prepare(`
         SELECT b.Brnch_Name as BranchName, SUM(o.Order_Total_Amount) as Revenue 
         FROM orders o 
         JOIN BRANCH b ON o.Order_Brnch_ID = b.Brnch_ID 
         WHERE o.Order_Stat = 'Completed' 
         GROUP BY b.Brnch_Name 
         ORDER BY Revenue DESC
      `).all();

      return sendWithFirebase(res, {
        success: true,
        stats: {
          branchesCount: bCount,
          managersCount: mCount,
          ordersCount: orderCount,
          totalRevenue: sales,
          monthlySales,
          topProducts,
          branchPerformance
        }
      });
    }

    else if (action === 'update_branch') {
      const id = Number(data.id);
      const name = data.name;
      const street = data.street;
      const brgy = data.brgy;
      const city = data.city;
      const province = data.province;
      const radius = Number(data.radius || 5);

      db.prepare('UPDATE BRANCH SET Brnch_Name = ?, Brnch_Street = ?, Brnch_Brgy = ?, Brnch_City = ?, Brnch_Province = ?, Brnch_Radius = ? WHERE Brnch_ID = ?').run(name, street, brgy, city, province, radius, id);

      try {
        await firestoreUpdate('branch', id, { Brnch_Name: name, Brnch_Street: street, Brnch_Brgy: brgy, Brnch_City: city, Brnch_Province: province, Brnch_Radius: radius }, 'mysql_id');
      } catch (err) {
        console.error('Firebase update_branch sync error:', err);
      }

      return sendWithFirebase(res, { success: true, message: 'Branch details updated!' });
    }

    else if (action === 'delete_branch') {
      const id = Number(data.id);
      db.prepare('DELETE FROM BRANCH WHERE Brnch_ID = ?').run(id);

      await firestoreDelete('branch', id, 'mysql_id');

      return sendWithFirebase(res, { success: true, message: 'Branch removed!' });
    }

    else if (action === 'update_manager') {
      const id = Number(data.id);
      const b_id = data.branch_id ? Number(data.branch_id) : null;
      const fname = data.fname;
      const lname = data.lname;
      const email = data.email;
      const mobile = data.mobile;

      db.prepare('UPDATE BRANCH_MANAGER SET Mgr_Brnch_ID = ?, Mgr_FName = ?, Mgr_LName = ?, Mgr_Email = ?, Mgr_MobileNum = ? WHERE Mgr_ID = ?').run(b_id, fname, lname, email, mobile, id);

      return sendWithFirebase(res, { success: true, message: 'Manager updated!' });
    }

    else if (action === 'delete_manager') {
      const id = Number(data.id);
      db.prepare('DELETE FROM BRANCH_MANAGER WHERE Mgr_ID = ?').run(id);
      return sendWithFirebase(res, { success: true, message: 'Manager deleted!' });
    }

    else {
      return sendWithFirebase(res, { success: false, message: 'Unknown action: ' + action });
    }
  } catch (err: any) {
    return sendWithFirebase(res, { success: false, message: 'Server error: ' + err.message });
  }
});

// ============================================================
// 4. GENERAL DASHBOARD INITIALIZATION PROXIES (dashboard.php API references)
// ============================================================
apiRouter.post('/dashboard.php', async (req, res) => {
  const user = getSessionUser(req);
  if (!user) {
    return sendWithFirebase(res, { success: false, message: 'Unauthorized.' });
  }

  const data = req.body || {};
  const action = data.action || '';

  try {
    if (action === 'get_current_role') {
      return sendWithFirebase(res, { success: true, role: user.role, branch_id: user.branch_id, id: user.id });
    }
    
    // Redirect list or index request parameters
    return res.json({ success: true, user });
  } catch (err: any) {
    res.json({ success: false, message: err.message });
  }
});
