import express from "express";
import { createServer as createViteServer } from "vite";
import path from "path";
import { fileURLToPath } from "url";

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

async function startServer() {
  const app = express();
  const PORT = 3000;

  app.use(express.json());

  // Simple API routes to mimic the user's "PHP logic"
  app.post("/api/auth", (req, res) => {
    const { email, password, action } = req.body;
    
    if (action === "login") {
      if (email && password) {
        res.json({ success: true, message: `Salamat! Logging in as ${email}...` });
      } else {
        res.status(400).json({ success: false, message: "Mangyaring punan ang lahat ng mga field." });
      }
    } else if (action === "signup") {
      res.json({ success: true, message: "Account created successfully! Please login." });
    } else {
      res.status(400).json({ success: false, message: "Invalid action." });
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
