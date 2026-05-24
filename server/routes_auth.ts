import { Router } from 'express';
import bcrypt from 'bcryptjs';
import { db, dbFirestore, syncFirebaseCustomersToMySql } from './db_sqlite.js';

export const authRouter = Router();

// Helper to wrap responses with Firebase health status
function sendWithFirebaseStatus(res: any, data: any) {
  let isConnected = false;
  if (dbFirestore) {
    isConnected = true;
  }
  res.json({
    ...data,
    firebase_status: {
      connected: isConnected,
      error: isConnected ? '' : 'Firebase connection uninitialized or credential missing'
    }
  });
}

authRouter.post('/auth.php', async (req, res) => {
  const data = req.body || {};
  const email = String(data.email || '').trim();
  const password = String(data.password || '').trim();
  const action = data.action || '';

  if (!email) {
    return sendWithFirebaseStatus(res, { success: false, message: 'Email is required.' });
  }

  if (action !== 'reset_password' && !password) {
    return sendWithFirebaseStatus(res, { success: false, message: 'Password is required.' });
  }

  try {
    if (action === 'reset_password') {
      const newPass = 'password';
      const hashed = bcrypt.hashSync(newPass, 10);

      // Try Staff first
      let result = db.prepare('UPDATE STAFF SET Staff_Pass = ? WHERE Staff_Email = ?').run(hashed, email);
      if (result.changes > 0) {
        return sendWithFirebaseStatus(res, { success: true, message: `Password reset for staff! New password: ${newPass}` });
      }

      // Try Customer
      result = db.prepare('UPDATE CUSTOMER SET Cust_Pass = ? WHERE Cust_Email = ?').run(hashed, email);
      if (result.changes > 0) {
        return sendWithFirebaseStatus(res, { success: true, message: `Password reset for customer! New password: ${newPass}` });
      }

      // Try Admin
      result = db.prepare('UPDATE SYSTEM_ADMIN SET Admin_Pass = ? WHERE Admin_Email = ?').run(hashed, email);
      if (result.changes > 0) {
        return sendWithFirebaseStatus(res, { success: true, message: `Password reset for admin! New password: ${newPass}` });
      }

      return sendWithFirebaseStatus(res, { success: false, message: 'Email not found in our records.' });
    }

    else if (action === 'signup') {
      const fname = String(data.fname || '').trim();
      const lname = String(data.lname || '').trim();
      const mobile = String(data.mobile || '').trim();

      if (!fname || !lname || !mobile) {
        return sendWithFirebaseStatus(res, { success: false, message: 'All fields are required for signup.' });
      }

      if (!email.includes('@')) {
        return sendWithFirebaseStatus(res, { success: false, message: 'Account creation requires a valid email with @ symbol.' });
      }

      if (!/^[0-9]{11}$/.test(mobile)) {
        return sendWithFirebaseStatus(res, { success: false, message: 'Mobile number must be exactly 11 numeric digits.' });
      }

      // Sync Customers first
      await syncFirebaseCustomersToMySql();

      const exists = db.prepare('SELECT * FROM CUSTOMER WHERE Cust_Email = ?').get(email);
      if (exists) {
        return sendWithFirebaseStatus(res, { success: false, message: 'Account already exists.' });
      }

      const hashedPassword = bcrypt.hashSync(password, 10);
      const insert = db.prepare('INSERT INTO CUSTOMER (Cust_FName, Cust_LName, Cust_Num, Cust_Email, Cust_Pass) VALUES (?, ?, ?, ?, ?)').run(fname, lname, mobile, email, hashedPassword);
      const newId = insert.lastInsertRowid;

      // Sync to Firebase
      try {
        if (dbFirestore) {
          await dbFirestore.collection('customer').add({
            Cust_ID: String(newId),
            mysql_id: String(newId),
            Cust_FName: fname,
            Cust_LName: lname,
            Cust_Num: mobile,
            Cust_Email: email,
            Cust_Pass: hashedPassword
          });
        }
      } catch (err) {
        console.error('Firebase signup sync error:', err);
      }

      return sendWithFirebaseStatus(res, { success: true, message: 'Customer account created!' });
    }

    else if (action === 'login') {
      // Sync first
      await syncFirebaseCustomersToMySql();

      let user: any = null;

      // 1. Check CUSTOMER
      const customer = db.prepare(`
        SELECT Cust_ID as id, Cust_FName as fname, Cust_LName as lname, Cust_Email as email, Cust_Pass as pass, 'Customer' as role, NULL as branch_id
        FROM CUSTOMER WHERE Cust_Email = ?
      `).get(email) as any;
      if (customer) {
        user = customer;
      }

      // 2. Check SYSTEM_ADMIN
      if (!user) {
        const admin = db.prepare(`
          SELECT Admin_ID as id, Admin_FName as fname, Admin_LName as lname, Admin_Pass as pass, 'System Admin' as role, NULL as branch_id
          FROM SYSTEM_ADMIN WHERE Admin_Email = ?
        `).get(email) as any;
        if (admin) {
          user = admin;
        }
      }

      // 3. Check BRANCH_MANAGER
      if (!user) {
        const manager = db.prepare(`
          SELECT Mgr_ID as id, Mgr_FName as fname, Mgr_LName as lname, Mgr_Pass as pass, 'Branch Manager' as role, Mgr_Brnch_ID as branch_id
          FROM BRANCH_MANAGER WHERE Mgr_Email = ?
        `).get(email) as any;
        if (manager) {
          user = manager;
        }
      }

      // 4. Check STAFF
      if (!user) {
        const staff = db.prepare(`
          SELECT Staff_ID as id, Staff_FName as fname, Staff_LName as lname, Staff_Pass as pass, Staff_Role as role, Staff_Brnch_ID as branch_id
          FROM STAFF WHERE Staff_Email = ?
        `).get(email) as any;
        if (staff) {
          user = staff;
        }
      }

      // 5. Check RIDER
      if (!user) {
        const rider = db.prepare(`
          SELECT Rider_ID as id, Rider_FName as fname, Rider_LName as lname, Rider_Pass as pass, 'Driver' as role, Rider_Brnch_ID as branch_id
          FROM RIDER WHERE Rider_Email = ?
        `).get(email) as any;
        if (rider) {
          user = { ...rider, name: `${rider.fname} ${rider.lname}` };
        }
      }

      if (!user) {
        return sendWithFirebaseStatus(res, { success: false, message: 'Invalid credentials. Please ensure you typed your email and password correctly.' });
      }

      // Reconciles password formats - both system BCrypt seeds ($2y$) and node hashes ($2a$)
      let userHash = user.pass;
      if (userHash.startsWith('$2y$')) {
        userHash = userHash.replace(/^\$2y\$/, '$2a$');
      }

      const isMatch = bcrypt.compareSync(password, userHash);
      if (!isMatch) {
         return sendWithFirebaseStatus(res, { success: false, message: 'Invalid credentials. Please ensure you typed your email and password correctly.' });
      }

      // Put info on session
      req.session.user_id = user.id;
      req.session.role = user.role;
      req.session.branch_id = user.branch_id;
      req.session.email = email;
      req.session.name = user.fname ? `${user.fname} ${user.lname}` : user.name;

      return sendWithFirebaseStatus(res, { success: true, message: `Welcome! Logged in as ${user.role}` });
    }
  } catch (err: any) {
    return sendWithFirebaseStatus(res, { success: false, message: 'Server error: ' + err.message });
  }
});

authRouter.all('/logout.php', (req, res) => {
  req.session.destroy(() => {
    res.redirect('/index.php');
  });
});
