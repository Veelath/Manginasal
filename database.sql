-- Mang Inasal Database Schema
DROP DATABASE IF EXISTS mang_inasal_db;
CREATE DATABASE mang_inasal_db;
USE mang_inasal_db;

-- 1. CUSTOMER TABLE
CREATE TABLE IF NOT EXISTS CUSTOMER (
    Cust_ID INT AUTO_INCREMENT PRIMARY KEY,
    Cust_FName VARCHAR(35) NOT NULL,
    Cust_LName VARCHAR(35) NOT NULL,
    Cust_Num CHAR(11) NOT NULL,
    Cust_Email VARCHAR(50) UNIQUE NOT NULL,
    Cust_Pass VARCHAR(255) NOT NULL -- For hashed passwords
);

-- 2. BRANCH TABLE
CREATE TABLE IF NOT EXISTS BRANCH (
    Brnch_ID INT AUTO_INCREMENT PRIMARY KEY,
    Brnch_Name VARCHAR(50) NOT NULL,
    Brnch_Street VARCHAR(50) NOT NULL,
    Brnch_Brgy VARCHAR(50) NOT NULL,
    Brnch_City VARCHAR(35) NOT NULL,
    Brnch_Province VARCHAR(35) NOT NULL,
    Brnch_Radius DECIMAL(5,2) NOT NULL,
    Brnch_Status CHAR(1) DEFAULT 'Y' -- Y or N
);

-- 3. ADDRESS TABLE (Links to Customer)
CREATE TABLE IF NOT EXISTS ADDRESS (
    Add_ID INT AUTO_INCREMENT PRIMARY KEY,
    Add_Cust_ID INT NOT NULL,
    Add_Province VARCHAR(35) NOT NULL,
    Add_City VARCHAR(35) NOT NULL,
    Add_UnitNum VARCHAR(20),
    Add_Building VARCHAR(50),
    Add_Street VARCHAR(50) NOT NULL,
    Add_Landmark VARCHAR(100),
    Add_Label VARCHAR(20) NOT NULL, -- e.g., 'Home', 'Office'
    FOREIGN KEY (Add_Cust_ID) REFERENCES CUSTOMER(Cust_ID) ON DELETE CASCADE
);

-- 4. STAFF TABLE (Handles Admin, Manager, Kitchen)
CREATE TABLE IF NOT EXISTS STAFF (
    Staff_ID INT AUTO_INCREMENT PRIMARY KEY,
    Staff_Brnch_ID INT, -- NULL for Global System Admin
    Staff_FName VARCHAR(35) NOT NULL,
    Staff_LName VARCHAR(35) NOT NULL,
    Staff_Email VARCHAR(50) UNIQUE NOT NULL, -- Added Email
    Staff_MobileNum CHAR(11) NOT NULL,
    Staff_Role VARCHAR(20) NOT NULL, -- 'System Admin', 'Branch Manager', 'Kitchen Staff'
    Staff_Pass VARCHAR(255) NOT NULL,
    Staff_Status CHAR(1) DEFAULT 'Y',
    FOREIGN KEY (Staff_Brnch_ID) REFERENCES BRANCH(Brnch_ID) ON DELETE SET NULL
);

-- 5. RIDER TABLE (Drivers)
CREATE TABLE IF NOT EXISTS RIDER (
    Rider_ID INT AUTO_INCREMENT PRIMARY KEY,
    Rider_Brnch_ID INT NOT NULL,
    Rider_FName VARCHAR(35) NOT NULL,
    Rider_LName VARCHAR(35) NOT NULL,
    Rider_Email VARCHAR(50) UNIQUE NOT NULL, -- Added Email
    Rider_MobileNum CHAR(11) NOT NULL,
    Rider_Pass VARCHAR(255) NOT NULL,
    Rider_Status CHAR(1) DEFAULT 'Y',
    FOREIGN KEY (Rider_Brnch_ID) REFERENCES BRANCH(Brnch_ID) ON DELETE CASCADE
);

-- 6. MENU ITEM TABLE
CREATE TABLE IF NOT EXISTS MENU_ITEM (
    Menu_ID INT AUTO_INCREMENT PRIMARY KEY,
    Menu_Category VARCHAR(35) NOT NULL,
    Menu_Name VARCHAR(50) NOT NULL,
    Menu_Description VARCHAR(200),
    Menu_Price DECIMAL(8,2) NOT NULL,
    Menu_Avail CHAR(1) DEFAULT 'Y'
);

-- 7. ORDERS TABLE (Using 'orders' because 'ORDER' is reserved)
CREATE TABLE IF NOT EXISTS orders (
    Order_ID INT AUTO_INCREMENT PRIMARY KEY,
    Order_Cust_ID INT NOT NULL,
    Order_Brnch_ID INT NOT NULL,
    Order_Add_ID INT NOT NULL,
    Order_Staff_ID INT, -- Staff who processed it
    Order_Code VARCHAR(20) UNIQUE NOT NULL,
    Order_Type VARCHAR(20) NOT NULL, -- 'Dine-in', 'Take-out', 'Delivery'
    Order_Is_Bulk CHAR(1) DEFAULT 'N',
    Order_Recipient_Name VARCHAR(70) NOT NULL,
    Order_Recipient_Num CHAR(11) NOT NULL,
    Order_Sched_Date DATE,
    Order_Sched_Time_Slot VARCHAR(20),
    Order_Subtotal DECIMAL(10,2) NOT NULL,
    Order_Dlvry_Fee DECIMAL(6,2) DEFAULT 0.00,
    Order_Disc_Amount DECIMAL(8,2) DEFAULT 0.00,
    Order_Total_Amount DECIMAL(10,2) NOT NULL,
    Order_Stat VARCHAR(20) NOT NULL, -- 'Pending', 'Preparing', 'Ready', 'Delivering', 'Completed'
    FOREIGN KEY (Order_Cust_ID) REFERENCES CUSTOMER(Cust_ID),
    FOREIGN KEY (Order_Brnch_ID) REFERENCES BRANCH(Brnch_ID),
    FOREIGN KEY (Order_Add_ID) REFERENCES ADDRESS(Add_ID),
    FOREIGN KEY (Order_Staff_ID) REFERENCES STAFF(Staff_ID)
);

-- 8. ORDER ITEM TABLE
CREATE TABLE IF NOT EXISTS ORDER_ITEM (
    Order_ID INT NOT NULL,
    OItem_ID INT NOT NULL, -- Item seq # per order
    OItem_Menu_ID INT NOT NULL,
    OItem_Base_Price DECIMAL(8,2) NOT NULL,
    OItem_Custom_Total DECIMAL(8,2) DEFAULT 0.00,
    OItem_Unit_Price DECIMAL(8,2) NOT NULL,
    OItem_Quantity INT NOT NULL,
    OItem_Special_Instruct VARCHAR(200),
    PRIMARY KEY (Order_ID, OItem_ID),
    FOREIGN KEY (Order_ID) REFERENCES orders(Order_ID) ON DELETE CASCADE,
    FOREIGN KEY (OItem_Menu_ID) REFERENCES MENU_ITEM(Menu_ID)
);

-- 9. PAYMENT TABLE
CREATE TABLE IF NOT EXISTS PAYMENT (
    Pay_ID INT AUTO_INCREMENT PRIMARY KEY,
    Pay_Order_ID INT NOT NULL,
    Pay_Amount DECIMAL(10,2) NOT NULL,
    Pay_Method VARCHAR(20) NOT NULL, -- 'Cash', 'G-Cash', 'Card'
    Pay_Status VARCHAR(20) NOT NULL, -- 'Pending', 'Paid', 'Failed'
    Pay_Transac_RefNum VARCHAR(50),
    FOREIGN KEY (Pay_Order_ID) REFERENCES orders(Order_ID) ON DELETE CASCADE
);

-- 10. DISCOUNT VERIFICATION TABLE
CREATE TABLE IF NOT EXISTS DISCOUNT_VERIFICATION (
    Disc_ID INT AUTO_INCREMENT PRIMARY KEY,
    Order_ID INT NOT NULL,
    Disc_Type VARCHAR(35) NOT NULL,
    Disc_ID_Num VARCHAR(20) NOT NULL,
    Disc_ID_Name VARCHAR(70) NOT NULL,
    FOREIGN KEY (Order_ID) REFERENCES orders(Order_ID) ON DELETE CASCADE
);

-- 11. DELIVERY TABLE
CREATE TABLE IF NOT EXISTS DELIVERY (
    Dlvry_ID INT AUTO_INCREMENT PRIMARY KEY,
    Dlvry_Order_ID INT NOT NULL,
    Dlvry_Rider_ID INT NOT NULL,
    Dlvry_Pickup_Time DATETIME,
    Dlvry_Arrival_Time DATETIME,
    Dlvry_Current_ETA DATETIME,
    FOREIGN KEY (Dlvry_Order_ID) REFERENCES orders(Order_ID) ON DELETE CASCADE,
    FOREIGN KEY (Dlvry_Rider_ID) REFERENCES RIDER(Rider_ID)
);

-- SEED DATA: Example System Admin and Branch
INSERT INTO BRANCH (Brnch_Name, Brnch_Street, Brnch_Brgy, Brnch_City, Brnch_Province, Brnch_Radius, Brnch_Status)
VALUES ('Main Branch', 'P. Gomez St', 'Brgy 1', 'Manila', 'Metro Manila', 5.00, 'Y');

-- Seed Admin (User: admin@inasal.com / Pass: password)
INSERT INTO STAFF (Staff_Brnch_ID, Staff_FName, Staff_LName, Staff_Email, Staff_MobileNum, Staff_Role, Staff_Pass)
VALUES (1, 'Admin', 'User', 'admin@inasal.com', '09123456789', 'System Admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Seed Branch Manager (User: manager1@inasal.com / Pass: password)
INSERT INTO STAFF (Staff_Brnch_ID, Staff_FName, Staff_LName, Staff_Email, Staff_MobileNum, Staff_Role, Staff_Pass)
VALUES (1, 'Manager1', 'User', 'manager1@inasal.com', '09987654321', 'Branch Manager', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');
