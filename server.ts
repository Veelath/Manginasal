import express from 'express';
import session from 'express-session';
import cookieParser from 'cookie-parser';
import path from 'path';
import fs from 'fs';
import { fileURLToPath } from 'url';

import { initDb, syncFirebaseCustomersToMySql, syncFirebaseMenuToMySql, syncFirebaseBranchesToMySql } from './server/db_sqlite.js';
import { authRouter } from './server/routes_auth.js';
import { apiRouter } from './server/routes_api.js';
import { htmlRouter } from './server/routes_html.js';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

async function startServer() {
  const app = express();
  const PORT = 3000;

  // Initialize SQLite database, schemas, and seeds
  initDb();

  // Run backend synchronizations immediately in the background
  (async () => {
    try {
      console.log('Initiating background Firebase bidirectional synconization...');
      await syncFirebaseCustomersToMySql();
      await syncFirebaseMenuToMySql();
      await syncFirebaseBranchesToMySql();
      console.log('Initial Firebase bidirectional sync complete.');
    } catch (err) {
      console.error('Initial background Firebase sync failed:', err);
    }
  })();

  // Periodic alignment interval (every 5 minutes)
  setInterval(async () => {
    try {
      await syncFirebaseCustomersToMySql();
      await syncFirebaseMenuToMySql();
      await syncFirebaseBranchesToMySql();
      console.log('Periodic background Firestore alignment completed.');
    } catch (err) {
      console.error('Periodic Firestore alignment error:', err);
    }
  }, 5 * 60 * 1000);

  // Body Parsing and Sessions
  app.use(express.json({ limit: '50mb' }));
  app.use(express.urlencoded({ extended: true, limit: '50mb' }));
  app.use(cookieParser());
  app.use(session({
    secret: 'mang-inasal-security-cookie-secret-12345',
    resave: false,
    saveUninitialized: false,
    cookie: {
      secure: false, // Local HTTP serving
      maxAge: 24 * 60 * 60 * 1000 // 24 hours
    }
  }));

  // Route API and Auth requests FIRST
  app.use(authRouter);
  app.use(apiRouter);

  // Serve static folders and compiled pages
  app.use('/uploads', express.static(path.join(process.cwd(), 'uploads')));
  
  // Serve everything from html router
  app.use(htmlRouter);

  // Serve other assets if present
  app.use(express.static(process.cwd()));

  app.listen(PORT, '0.0.0.0', () => {
    console.log(`Express custom server running on http://0.0.0.0:${PORT}`);
  });
}

startServer().catch(err => {
  console.error('Panic: Failed to start server:', err);
  process.exit(1);
});
