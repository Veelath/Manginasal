import Database from 'better-sqlite3';
import bcrypt from 'bcryptjs';
import * as admin from 'firebase-admin';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

// Initialize Firebase Admin SDK
export let dbFirestore: admin.firestore.Firestore | null = null;
try {
  const serviceAccountPath = path.join(process.cwd(), 'manginasaldb-firebase-adminsdk-fbsvc-683d47e41c.json');
  if (fs.existsSync(serviceAccountPath)) {
    const serviceAccount = JSON.parse(fs.readFileSync(serviceAccountPath, 'utf8'));
    admin.initializeApp({
      credential: admin.credential.cert(serviceAccount)
    });
    dbFirestore = admin.firestore();
    console.log('Firebase Admin initialized successfully.');
  } else {
    console.log('Service account file not found, running without Firestore sync.');
  }
} catch (error) {
  console.error('Failed to initialize Firebase Admin:', error);
}

// Setup SQLite local database
const dbPath = path.join(process.cwd(), 'local.db');
export const db = new Database(dbPath);
db.pragma('foreign_keys = ON');

export function initDb() {
  db.exec(`
    CREATE TABLE IF NOT EXISTS CUSTOMER (
      Cust_ID INTEGER PRIMARY KEY AUTOINCREMENT,
      Cust_FName TEXT NOT NULL,
      Cust_LName TEXT NOT NULL,
      Cust_Num TEXT NOT NULL,
      Cust_Email TEXT UNIQUE NOT NULL,
      Cust_Pass TEXT NOT NULL
    );

    CREATE TABLE IF NOT EXISTS BRANCH (
      Brnch_ID INTEGER PRIMARY KEY AUTOINCREMENT,
      Brnch_Name TEXT NOT NULL,
      Brnch_Street TEXT NOT NULL,
      Brnch_Brgy TEXT NOT NULL,
      Brnch_City TEXT NOT NULL,
      Brnch_Province TEXT NOT NULL,
      Brnch_Radius REAL NOT NULL,
      Brnch_Status TEXT DEFAULT 'Y'
    );

    CREATE TABLE IF NOT EXISTS ADDRESS (
      Add_ID INTEGER PRIMARY KEY AUTOINCREMENT,
      Add_Cust_ID INTEGER NOT NULL,
      Add_Province TEXT NOT NULL,
      Add_City TEXT NOT NULL,
      Add_Brgy TEXT NOT NULL,
      Add_Street TEXT NOT NULL,
      Add_UnitNum TEXT,
      Add_Building TEXT,
      Add_Landmark TEXT,
      Add_PostalCode TEXT,
      Add_Label TEXT NOT NULL,
      FOREIGN KEY (Add_Cust_ID) REFERENCES CUSTOMER(Cust_ID) ON DELETE CASCADE
    );

    CREATE TABLE IF NOT EXISTS SYSTEM_ADMIN (
      Admin_ID INTEGER PRIMARY KEY AUTOINCREMENT,
      Admin_FName TEXT NOT NULL,
      Admin_LName TEXT NOT NULL,
      Admin_Email TEXT UNIQUE NOT NULL,
      Admin_MobileNum TEXT NOT NULL,
      Admin_Pass TEXT NOT NULL,
      Admin_Status TEXT DEFAULT 'Active'
    );

    CREATE TABLE IF NOT EXISTS BRANCH_MANAGER (
      Mgr_ID INTEGER PRIMARY KEY AUTOINCREMENT,
      Mgr_Brnch_ID INTEGER,
      Mgr_FName TEXT NOT NULL,
      Mgr_LName TEXT NOT NULL,
      Mgr_Email TEXT UNIQUE NOT NULL,
      Mgr_MobileNum TEXT NOT NULL,
      Mgr_Pass TEXT NOT NULL,
      Mgr_Status TEXT DEFAULT 'Active',
      FOREIGN KEY (Mgr_Brnch_ID) REFERENCES BRANCH(Brnch_ID) ON DELETE SET NULL
    );

    CREATE TABLE IF NOT EXISTS STAFF (
      Staff_ID INTEGER PRIMARY KEY AUTOINCREMENT,
      Staff_Brnch_ID INTEGER,
      Staff_Mgr_ID INTEGER,
      Staff_FName TEXT NOT NULL,
      Staff_LName TEXT NOT NULL,
      Staff_Email TEXT UNIQUE NOT NULL,
      Staff_MobileNum TEXT NOT NULL,
      Staff_Role TEXT DEFAULT 'Kitchen Staff',
      Staff_Pass TEXT NOT NULL,
      Staff_Status TEXT DEFAULT 'Active',
      FOREIGN KEY (Staff_Brnch_ID) REFERENCES BRANCH(Brnch_ID) ON DELETE SET NULL,
      FOREIGN KEY (Staff_Mgr_ID) REFERENCES BRANCH_MANAGER(Mgr_ID) ON DELETE SET NULL
    );

    CREATE TABLE IF NOT EXISTS RIDER (
      Rider_ID INTEGER PRIMARY KEY AUTOINCREMENT,
      Rider_Brnch_ID INTEGER NOT NULL,
      Rider_FName TEXT NOT NULL,
      Rider_LName TEXT NOT NULL,
      Rider_Email TEXT UNIQUE NOT NULL,
      Rider_MobileNum TEXT NOT NULL,
      Rider_Pass TEXT NOT NULL,
      Rider_Status TEXT DEFAULT 'Y',
      FOREIGN KEY (Rider_Brnch_ID) REFERENCES BRANCH(Brnch_ID) ON DELETE CASCADE
    );

    CREATE TABLE IF NOT EXISTS MENU_ITEM (
      Menu_ID INTEGER PRIMARY KEY AUTOINCREMENT,
      Menu_Brnch_ID INTEGER,
      Menu_Category TEXT NOT NULL,
      Menu_Name TEXT NOT NULL,
      Menu_Size TEXT DEFAULT 'Standard',
      Menu_Description TEXT,
      Menu_Image TEXT,
      Menu_Price REAL NOT NULL,
      Menu_Status TEXT DEFAULT 'Y'
    );

    CREATE TABLE IF NOT EXISTS BRANCH_MENU (
      Brnch_ID INTEGER,
      Menu_ID INTEGER,
      Is_Available TEXT DEFAULT 'Y',
      Stock_Qty INTEGER DEFAULT 50,
      PRIMARY KEY (Brnch_ID, Menu_ID),
      FOREIGN KEY (Brnch_ID) REFERENCES BRANCH(Brnch_ID) ON DELETE CASCADE,
      FOREIGN KEY (Menu_ID) REFERENCES MENU_ITEM(Menu_ID) ON DELETE CASCADE
    );

    CREATE TABLE IF NOT EXISTS orders (
      Order_ID INTEGER PRIMARY KEY AUTOINCREMENT,
      Order_Cust_ID INTEGER NOT NULL,
      Order_Brnch_ID INTEGER NOT NULL,
      Order_Add_ID INTEGER NOT NULL,
      Order_Staff_ID INTEGER,
      Order_Code TEXT UNIQUE NOT NULL,
      Order_Type TEXT NOT NULL,
      Order_Is_Bulk TEXT DEFAULT 'N',
      Order_Recipient_Name TEXT NOT NULL,
      Order_Recipient_Num TEXT NOT NULL,
      Order_Sched_Date TEXT,
      Order_Sched_Time_Slot TEXT,
      Order_Subtotal REAL NOT NULL,
      Order_Dlvry_Fee REAL DEFAULT 0.00,
      Order_Disc_Amount REAL DEFAULT 0.00,
      Order_Total_Amount REAL NOT NULL,
      Order_Stat TEXT NOT NULL,
      Order_Date DATETIME DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (Order_Cust_ID) REFERENCES CUSTOMER(Cust_ID),
      FOREIGN KEY (Order_Brnch_ID) REFERENCES BRANCH(Brnch_ID),
      FOREIGN KEY (Order_Add_ID) REFERENCES ADDRESS(Add_ID),
      FOREIGN KEY (Order_Staff_ID) REFERENCES STAFF(Staff_ID)
    );

    CREATE TABLE IF NOT EXISTS ORDER_ITEM (
      Order_ID INTEGER NOT NULL,
      OItem_ID INTEGER NOT NULL,
      OItem_Menu_ID INTEGER NOT NULL,
      OItem_Base_Price REAL NOT NULL,
      OItem_Custom_Total REAL DEFAULT 0.00,
      OItem_Unit_Price REAL NOT NULL,
      OItem_Quantity INTEGER NOT NULL,
      OItem_Special_Instruct TEXT,
      PRIMARY KEY (Order_ID, OItem_ID),
      FOREIGN KEY (Order_ID) REFERENCES orders(Order_ID) ON DELETE CASCADE,
      FOREIGN KEY (OItem_Menu_ID) REFERENCES MENU_ITEM(Menu_ID)
    );

    CREATE TABLE IF NOT EXISTS PAYMENT (
      Pay_ID INTEGER PRIMARY KEY AUTOINCREMENT,
      Pay_Order_ID INTEGER NOT NULL,
      Pay_Amount REAL NOT NULL,
      Pay_Method TEXT NOT NULL,
      Pay_Status TEXT NOT NULL,
      Pay_Transac_RefNum TEXT,
      FOREIGN KEY (Pay_Order_ID) REFERENCES orders(Order_ID) ON DELETE CASCADE
    );

    CREATE TABLE IF NOT EXISTS DISCOUNT_VERIFICATION (
      Disc_ID INTEGER PRIMARY KEY AUTOINCREMENT,
      Order_ID INTEGER NOT NULL,
      Disc_Type TEXT NOT NULL,
      Disc_ID_Num TEXT NOT NULL,
      Disc_ID_Name TEXT NOT NULL,
      FOREIGN KEY (Order_ID) REFERENCES orders(Order_ID) ON DELETE CASCADE
    );

    CREATE TABLE IF NOT EXISTS DELIVERY (
      Dlvry_ID INTEGER PRIMARY KEY AUTOINCREMENT,
      Dlvry_Order_ID INTEGER NOT NULL,
      Dlvry_Rider_ID INTEGER NOT NULL,
      Dlvry_Pickup_Time TEXT,
      Dlvry_Arrival_Time TEXT,
      Dlvry_Current_ETA TEXT,
      FOREIGN KEY (Dlvry_Order_ID) REFERENCES orders(Order_ID) ON DELETE CASCADE,
      FOREIGN KEY (Dlvry_Rider_ID) REFERENCES RIDER(Rider_ID)
    );
  `);

  // Seeds
  const branchesCount = db.prepare('SELECT COUNT(*) as count FROM BRANCH').get() as { count: number };
  if (branchesCount.count === 0) {
    db.prepare(`
      INSERT INTO BRANCH (Brnch_Name, Brnch_Street, Brnch_Brgy, Brnch_City, Brnch_Province, Brnch_Radius, Brnch_Status)
      VALUES ('Main Branch', 'P. Gomez St', 'Brgy 1', 'Manila', 'Metro Manila', 5.00, 'Y')
    `).run();
  }

  const menuCount = db.prepare('SELECT COUNT(*) as count FROM MENU_ITEM').get() as { count: number };
  if (menuCount.count === 0) {
    db.prepare(`
      INSERT INTO MENU_ITEM (Menu_Category, Menu_Name, Menu_Description, Menu_Price) VALUES 
      ('Chicken', 'PM1 - Chicken Inasal Paa', 'Signature grilled chicken leg/thigh with rice.', 145.00),
      ('Chicken', 'PM2 - Chicken Inasal Pecho', 'Signature grilled chicken breast with rice.', 155.00),
      ('Pork', 'Sizzling Pork Sisig', 'Tasty pork sisig with egg.', 130.00),
      ('Dessert', 'Halo-Halo Small', 'Refreshing Filipino dessert.', 65.00),
      ('Sides', 'Extra Rice', 'Unlimited rice option available in-store.', 25.00)
    `).run();
  }

  const adminCount = db.prepare('SELECT COUNT(*) as count FROM SYSTEM_ADMIN').get() as { count: number };
  if (adminCount.count === 0) {
    const adminPassHash = bcrypt.hashSync('password', 10);
    db.prepare(`
      INSERT INTO SYSTEM_ADMIN (Admin_FName, Admin_LName, Admin_Email, Admin_MobileNum, Admin_Pass, Admin_Status)
      VALUES ('System', 'Admin', 'admin@inasal.com', '09123456789', ?, 'Active')
    `).run(adminPassHash);
  }

  const mgrCount = db.prepare('SELECT COUNT(*) as count FROM BRANCH_MANAGER').get() as { count: number };
  if (mgrCount.count === 0) {
    const managerPassHash = bcrypt.hashSync('password', 10);
    db.prepare(`
      INSERT INTO BRANCH_MANAGER (Mgr_Brnch_ID, Mgr_FName, Mgr_LName, Mgr_Email, Mgr_MobileNum, Mgr_Pass)
      VALUES (1, 'Manager1', 'User', 'manager1@inasal.com', '09987654321', ?)
    `).run(managerPassHash);
  }

  const staffCount = db.prepare('SELECT COUNT(*) as count FROM STAFF').get() as { count: number };
  if (staffCount.count === 0) {
    const staffPassHash = bcrypt.hashSync('password', 10);
    db.prepare(`
      INSERT INTO STAFF (Staff_Brnch_ID, Staff_Mgr_ID, Staff_FName, Staff_LName, Staff_Email, Staff_MobileNum, Staff_Role, Staff_Pass)
      VALUES (1, 1, 'Staff1', 'User', 'staff1@inasal.com', '09888777665', 'Kitchen Staff', ?)
    `).run(staffPassHash);
  }
}

export function saveMenuImageLocal(base64Str: string, itemId?: string | number): string {
  if (!base64Str) return '';
  if (base64Str.startsWith('uploads/') || base64Str.startsWith('http://') || base64Str.startsWith('https://')) {
    return base64Str;
  }

  const uploadDir = path.join(process.cwd(), 'uploads');
  if (!fs.existsSync(uploadDir)) {
    fs.mkdirSync(uploadDir, { recursive: true });
  }

  let ext = 'jpg';
  let base64Data = '';

  if (base64Str.startsWith('data:')) {
    const parts = base64Str.split(',');
    if (parts.length >= 2) {
      const mimePart = parts[0];
      base64Data = parts[1];
      if (mimePart.includes('image/png')) {
        ext = 'png';
      } else if (mimePart.includes('image/gif')) {
        ext = 'gif';
      } else if (mimePart.includes('image/webp')) {
        ext = 'webp';
      } else if (mimePart.includes('image/svg+xml')) {
        ext = 'svg';
      }
    } else {
      return base64Str;
    }
  } else {
    base64Data = base64Str;
  }

  try {
    const rawData = Buffer.from(base64Data, 'base64');
    const prefix = itemId ? `menu_${itemId}` : `menu_item_${Date.now()}`;
    const fileName = `${prefix}_${Date.now()}.${ext}`;
    const filePath = path.join(uploadDir, fileName);
    fs.writeFileSync(filePath, rawData);
    return `uploads/${fileName}`;
  } catch (err) {
    console.error('Error saving image:', err);
    return base64Str;
  }
}

// Background firestore sync helpers
export async function firestoreAdd(collectionName: string, data: any) {
  if (!dbFirestore) return null;
  try {
    const docRef = await dbFirestore.collection(collectionName).add({
      ...data,
      created_at: new Date().toISOString().slice(0, 19).replace('T', ' ')
    });
    return docRef.id;
  } catch (err) {
    console.error(`Firebase add error (${collectionName}):`, err);
    return null;
  }
}

export async function firestoreUpdate(collectionName: string, id: string | number, updatedData: any, idField: string) {
  if (!dbFirestore) return;
  try {
    const snapshot = await dbFirestore.collection(collectionName).get();
    for (const doc of snapshot.docs) {
      const data = doc.data();
      const matchId = data.mysql_id || data[idField] || '';
      if (String(matchId) === String(id) || doc.id === String(id)) {
        await dbFirestore.collection(collectionName).doc(doc.id).update({
          ...updatedData,
          updated_at: new Date().toISOString().slice(0, 19).replace('T', ' ')
        });
        break;
      }
    }
  } catch (err) {
    console.error(`Firebase update sync error (${collectionName}):`, err);
  }
}

export async function firestoreDelete(collectionName: string, id: string | number, idField: string) {
  if (!dbFirestore) return;
  try {
    const snapshot = await dbFirestore.collection(collectionName).get();
    for (const doc of snapshot.docs) {
      const data = doc.data();
      const matchId = data.mysql_id || data[idField] || '';
      if (String(matchId) === String(id) || doc.id === String(id)) {
        await dbFirestore.collection(collectionName).doc(doc.id).delete();
        break;
      }
    }
  } catch (err) {
    console.error(`Firebase delete sync error (${collectionName}):`, err);
  }
}

export async function syncFirebaseCustomersToMySql() {
  if (!dbFirestore) return;
  try {
    const snapshot = await dbFirestore.collection('customer').get();
    const fbEmails: { [key: string]: string } = {};
    const fbMysqlIds: { [key: string]: string } = {};

    for (const doc of snapshot.docs) {
      const data = doc.data();
      const email = data.Cust_Email || data.email || '';
      if (email) {
        fbEmails[email.toLowerCase()] = doc.id;
      }
      const mysqlId = data.mysql_id || data.Cust_ID || '';
      if (mysqlId) {
        fbMysqlIds[String(mysqlId)] = doc.id;
      }

      if (email) {
        const custExist = db.prepare('SELECT Cust_ID FROM CUSTOMER WHERE Cust_Email = ?').get(email) as any;
        if (!custExist) {
          const fname = data.Cust_FName || data.first_name || data.fname || 'New';
          const lname = data.Cust_LName || data.last_name || data.lname || 'Customer';
          const num = data.Cust_Num || data.mobile || data.mobile_num || '09000000000';
          const pass = data.Cust_Pass || data.password || bcrypt.hashSync('password', 10);

          const result = db.prepare(`
            INSERT INTO CUSTOMER (Cust_FName, Cust_LName, Cust_Num, Cust_Email, Cust_Pass)
            VALUES (?, ?, ?, ?, ?)
          `).run(fname, lname, num, email, pass);
          const newId = result.lastInsertRowid;

          await dbFirestore.collection('customer').doc(doc.id).update({
            Cust_ID: String(newId),
            mysql_id: String(newId)
          });
          fbMysqlIds[String(newId)] = doc.id;
        } else {
          const mysqlId_ = custExist.Cust_ID;
          if (!data.Cust_ID || data.Cust_ID != mysqlId_) {
            await dbFirestore.collection('customer').doc(doc.id).update({
              Cust_ID: String(mysqlId_),
              mysql_id: String(mysqlId_)
            });
          }
          fbMysqlIds[String(mysqlId_)] = doc.id;
        }
      }
    }

    // Push local customers to Firestore
    const localCusts = db.prepare('SELECT * FROM CUSTOMER').all() as any[];
    for (const cust of localCusts) {
      const mysqlId = String(cust.Cust_ID);
      const email = cust.Cust_Email.toLowerCase();
      if (!fbMysqlIds[mysqlId] && !fbEmails[email]) {
        await dbFirestore.collection('customer').add({
          Cust_ID: mysqlId,
          mysql_id: mysqlId,
          Cust_FName: cust.Cust_FName,
          Cust_LName: cust.Cust_LName,
          Cust_Num: cust.Cust_Num,
          Cust_Email: cust.Cust_Email,
          Cust_Pass: cust.Cust_Pass
        });
      }
    }
  } catch (err) {
    console.error('Error syncing Firebase customers:', err);
  }
}

export async function syncFirebaseMenuToMySql() {
  if (!dbFirestore) return;
  try {
    const snapshot = await dbFirestore.collection('menu_item').get();
    const fbNames: { [key: string]: string } = {};
    const fbMysqlIds: { [key: string]: string } = {};

    for (const doc of snapshot.docs) {
      const data = doc.data();
      const name = data.Menu_Name || data.name || '';
      if (name) {
        fbNames[name.toLowerCase()] = doc.id;
      }
      const mysqlId = data.mysql_id || data.Menu_ID || '';
      if (mysqlId) {
        fbMysqlIds[String(mysqlId)] = doc.id;
      }

      if (name) {
        const itemExist = db.prepare('SELECT Menu_ID FROM MENU_ITEM WHERE Menu_Name = ?').get(name) as any;
        
        const category = data.Menu_Category || data.category || 'Chicken';
        const price = Number(data.Menu_Price || data.price || 0);
        const desc = data.Menu_Description || data.description || '';
        const image = data.Menu_Image || data.image || '';
        const size = data.Menu_Size || data.size || 'Standard';
        const status = data.Menu_Status || data.status || 'Y';
        let branchId = data.Menu_Brnch_ID || data.branch_id || null;
        if (branchId === 'NULL' || branchId === '') branchId = null;

        if (!itemExist) {
          const result = db.prepare(`
            INSERT INTO MENU_ITEM (Menu_Category, Menu_Name, Menu_Description, Menu_Image, Menu_Price, Menu_Status, Menu_Size, Menu_Brnch_ID)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
          `).run(category, name, desc, image, price, status, size, branchId);
          const newId = result.lastInsertRowid;

          await dbFirestore.collection('menu_item').doc(doc.id).update({
            Menu_ID: String(newId),
            mysql_id: String(newId)
          });
          fbMysqlIds[String(newId)] = doc.id;
        } else {
          const mysqlId_ = itemExist.Menu_ID;
          if (!data.Menu_ID || data.Menu_ID != mysqlId_) {
            await dbFirestore.collection('menu_item').doc(doc.id).update({
              Menu_ID: String(mysqlId_),
              mysql_id: String(mysqlId_)
            });
          }
          fbMysqlIds[String(mysqlId_)] = doc.id;
        }
      }
    }

    // Push local menu to Firestore
    const localItems = db.prepare('SELECT * FROM MENU_ITEM').all() as any[];
    for (const item of localItems) {
      const mysqlId = String(item.Menu_ID);
      const name = item.Menu_Name.toLowerCase();
      if (!fbMysqlIds[mysqlId] && !fbNames[name]) {
        await dbFirestore.collection('menu_item').add({
          Menu_ID: mysqlId,
          mysql_id: mysqlId,
          Menu_Category: item.Menu_Category,
          Menu_Name: item.Menu_Name,
          Menu_Description: item.Menu_Description,
          Menu_Image: item.Menu_Image,
          Menu_Price: item.Menu_Price,
          Menu_Status: item.Menu_Status,
          Menu_Size: item.Menu_Size,
          Menu_Brnch_ID: item.Menu_Brnch_ID
        });
      }
    }
  } catch (err) {
    console.error('Error syncing Firebase menu items:', err);
  }
}

export async function syncFirebaseBranchesToMySql() {
  if (!dbFirestore) return;
  try {
    const snapshot = await dbFirestore.collection('branch').get();
    const fbNames: { [key: string]: string } = {};
    const fbMysqlIds: { [key: string]: string } = {};

    for (const doc of snapshot.docs) {
      const data = doc.data();
      const name = data.Brnch_Name || data.name || '';
      if (name) {
        fbNames[name.toLowerCase()] = doc.id;
      }
      const mysqlId = data.mysql_id || data.Brnch_ID || '';
      if (mysqlId) {
        fbMysqlIds[String(mysqlId)] = doc.id;
      }

      if (name) {
        const branchExist = db.prepare('SELECT Brnch_ID FROM BRANCH WHERE Brnch_Name = ?').get(name) as any;
        
        const street = data.Brnch_Street || data.street || '';
        const brgy = data.Brnch_Brgy || data.brgy || '';
        const city = data.Brnch_City || data.city || '';
        const province = data.Brnch_Province || data.province || '';
        const radius = Number(data.Brnch_Radius || data.radius || 5);
        const status = data.Brnch_Status || data.status || 'Y';

        if (!branchExist) {
          const result = db.prepare(`
            INSERT INTO BRANCH (Brnch_Name, Brnch_Street, Brnch_Brgy, Brnch_City, Brnch_Province, Brnch_Radius, Brnch_Status)
            VALUES (?, ?, ?, ?, ?, ?, ?)
          `).run(name, street, brgy, city, province, radius, status);
          const newId = result.lastInsertRowid;

          await dbFirestore.collection('branch').doc(doc.id).update({
            Brnch_ID: String(newId),
            mysql_id: String(newId)
          });
          fbMysqlIds[String(newId)] = doc.id;
        } else {
          const mysqlId_ = branchExist.Brnch_ID;
          if (!data.Brnch_ID || data.Brnch_ID != mysqlId_) {
            await dbFirestore.collection('branch').doc(doc.id).update({
              Brnch_ID: String(mysqlId_),
              mysql_id: String(mysqlId_)
            });
          }
          fbMysqlIds[String(mysqlId_)] = doc.id;
        }
      }
    }

    // Push local branches to Firestore
    const localBranches = db.prepare('SELECT * FROM BRANCH').all() as any[];
    for (const branch of localBranches) {
      const mysqlId = String(branch.Brnch_ID);
      const name = branch.Brnch_Name.toLowerCase();
      if (!fbMysqlIds[mysqlId] && !fbNames[name]) {
        await dbFirestore.collection('branch').add({
          Brnch_ID: mysqlId,
          mysql_id: mysqlId,
          Brnch_Name: branch.Brnch_Name,
          Brnch_Street: branch.Brnch_Street,
          Brnch_Brgy: branch.Brnch_Brgy,
          Brnch_City: branch.Brnch_City,
          Brnch_Province: branch.Brnch_Province,
          Brnch_Radius: branch.Brnch_Radius,
          Brnch_Status: branch.Brnch_Status
        });
      }
    }
  } catch (err) {
    console.error('Error syncing Firebase branches:', err);
  }
}
