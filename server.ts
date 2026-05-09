import express from "express";
import { createServer as createViteServer } from "vite";
import path from "path";
import { fileURLToPath } from "url";
import Database from "better-sqlite3";
import bcrypt from "bcryptjs";
import mysql from "mysql2/promise"; // Added MySQL support

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

// --- DATABASE CONFIGURATION ---
// SET THIS TO 'mysql' if you want to use your XAMPP MySQL database
// SET THIS TO 'sqlite' to use the local file database.db
const DB_TYPE: 'sqlite' | 'mysql' = 'sqlite'; 

let sqliteDb: any;
let mysqlPool: any;

if (DB_TYPE === 'sqlite') {
  sqliteDb = new Database("database.db");
  sqliteDb.exec(`
    CREATE TABLE IF NOT EXISTS users (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      email TEXT UNIQUE,
      password TEXT,
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )
  `);
} else {
  // CONFIG FOR XAMPP MYSQL
  mysqlPool = mysql.createPool({
    host: 'localhost',
    user: 'root',      // Default XAMPP user
    password: '',      // Default XAMPP password is empty
    database: 'mang_inasal_db' 
  });
}

async function startServer() {
  const app = express();
  const PORT = 3000;

  app.use(express.json());

  // Real Auth API
  app.post("/api/auth", async (req, res) => {
    const { email, password, action } = req.body;

    if (!email || !password) {
      return res.status(400).json({ success: false, message: "Mangyaring punan ang lahat ng mga field." });
    }

    try {
      if (action === "signup") {
        let userExists;
        if (DB_TYPE === 'sqlite') {
          userExists = sqliteDb.prepare("SELECT * FROM users WHERE email = ?").get(email);
        } else {
          const [rows]: any = await mysqlPool.execute("SELECT * FROM users WHERE email = ?", [email]);
          userExists = rows[0];
        }

        if (userExists) {
          return res.status(400).json({ success: false, message: "May account na gamit ang email na ito." });
        }

        const hashedPassword = await bcrypt.hash(password, 10);

        if (DB_TYPE === 'sqlite') {
          sqliteDb.prepare("INSERT INTO users (email, password) VALUES (?, ?)").run(email, hashedPassword);
        } else {
          await mysqlPool.execute("INSERT INTO users (email, password) VALUES (?, ?)", [email, hashedPassword]);
        }

        return res.json({ success: true, message: "Account created successfully! Maaari ka na ngayong mag-login." });
      } 
      
      if (action === "login") {
        let user: any;
        if (DB_TYPE === 'sqlite') {
          user = sqliteDb.prepare("SELECT * FROM users WHERE email = ?").get(email);
        } else {
          const [rows]: any = await mysqlPool.execute("SELECT * FROM users WHERE email = ?", [email]);
          user = rows[0];
        }
        
        if (!user) {
          return res.status(401).json({ success: false, message: "Maling email o password." });
        }

        const validPassword = await bcrypt.compare(password, user.password);
        if (!validPassword) {
          return res.status(401).json({ success: false, message: "Maling email o password." });
        }

        return res.json({ success: true, message: `Salamat! Logging in as ${email}...` });
      }

      res.status(400).json({ success: false, message: "Invalid action." });
    } catch (error) {
      console.error("Auth error:", error);
      res.status(500).json({ success: false, message: "May naganap na error sa server." });
    }
  });

  // Vite middleware for development
  if (process.env.NODE_ENV !== "production") {
    const vite = await createViteServer({
      server: { middlewareMode: true },
      appType: "spa",
    });
    app.use(vite.middlewares);
  } else {
    const distPath = path.join(process.cwd(), 'dist');
    app.use(express.static(distPath));
    app.get('*', (req, res) => {
      res.sendFile(path.join(distPath, 'index.html'));
    });
  }

  app.listen(PORT, "0.0.0.0", () => {
    console.log(`Server running on http://localhost:${PORT}`);
  });
}

startServer();
