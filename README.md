# 📚 Library Computer Login System

A full-stack library computer management system for **nComputing vSpace** thin-client environments.  
Includes a kiosk Electron client and a PHP/MySQL admin panel (XAMPP).

---

## Tech Stack

| Component | Technology |
|---|---|
| Kiosk Client | Electron.js (Node.js) |
| Admin Panel | PHP 8+ |
| Database | MySQL (via XAMPP) |
| Fonts | Inter (Google Fonts) |

---

## Project Structure

```
library-computer-login-system/
├── client/              # Electron kiosk app
│   ├── src/
│   │   ├── main.js      # Main process
│   │   ├── preload.js   # Secure IPC bridge
│   │   └── renderer/    # Login + Dashboard UI
│   ├── .env.example     # Copy to .env and configure
│   └── package.json
│
├── server/              # PHP app → copy to htdocs/library-system/
│   ├── api/             # REST API (used by Electron)
│   ├── admin/           # Admin web panel
│   ├── includes/        # Shared PHP helpers
│   ├── assets/          # CSS + JS for admin panel
│   └── config.php       # Configuration
│
└── database/
    ├── schema.sql       # Full DB schema
    └── seed.sql         # Default admin + sample data
```

---

## 🚀 Local Setup (for testing)

### Step 1 — Database

1. Start **XAMPP** → Apache + MySQL
2. Open **phpMyAdmin** at `http://localhost/phpmyadmin`
3. Import `database/schema.sql` first
4. Import `database/seed.sql` next

**Default admin login:** `admin` / `Admin@1234`  
**Default user password:** `Password@123`

### Step 2 — PHP Server

1. Copy the entire `server/` folder to `C:\xampp\htdocs\library-system\`
2. Open `server/config.php` and confirm settings (defaults work for XAMPP)
3. Visit `http://localhost/library-system/admin/` to access the admin panel

### Step 3 — Electron Client

```powershell
cd client

# Copy and configure environment
copy .env.example .env

# Install dependencies
npm install

# Run in dev mode (no kiosk lockdown, window is resizable)
npm run dev
```

The `.env` defaults are set for local testing:
```
API_BASE_URL=http://localhost/library-system/api
TERMINAL_CODE=LOCAL-TEST
KIOSK_MODE=false
```

---

## 🖥️ nComputing Deployment

### Server-side setup

1. Install XAMPP on the nComputing server
2. Copy `server/` to `htdocs/library-system/`
3. Import the database via phpMyAdmin
4. Update `client/.env`:
   ```
   API_BASE_URL=http://localhost/library-system/api
   TERMINAL_CODE=PC-01        ← Match terminal in admin panel
   KIOSK_MODE=true
   ```
5. Build the Electron app:
   ```powershell
   cd client
   npm run build
   ```
6. Install the generated `.exe` from `client/dist/`

### Auto-start on user session login (nComputing)

Add a registry key to auto-launch for each user session:
- Open `regedit`
- Go to `HKCU\Software\Microsoft\Windows\CurrentVersion\Run`
- Add: `LibraryKiosk` → path to the installed `Library Kiosk.exe`

> **Note:** Because nComputing sessions are per-user on a shared server, the `HKCU` run key means each user's session auto-starts the kiosk. The `TERMINAL_CODE` in `.env` must be set to match the terminal's code in the admin panel.

---

## 📊 Admin Panel Pages

| Page | URL | Description |
|---|---|---|
| Login | `/admin/index.php` | Admin authentication |
| Dashboard | `/admin/dashboard.php` | Live stats + active sessions |
| Users | `/admin/users.php` | Add/edit/suspend users |
| Sessions | `/admin/sessions.php` | History + force-end sessions |
| App Requests | `/admin/apps.php` | Approve/deny requests |
| Terminals | `/admin/terminals.php` | Manage nComputing stations |
| Reports | `/admin/reports.php` | Usage stats + CSV export |

---

## 🔑 Default Credentials

> **Change all passwords immediately in production!**

| Account | ID | Password |
|---|---|---|
| Admin (panel) | `admin` | `Admin@1234` |
| Sample student | `2021-00001` | `Password@123` |
| Sample staff | `STAFF-001` | `Password@123` |

---

## 📡 REST API (Electron → PHP)

All endpoints are under `/library-system/api/`.  
The Electron app sends `X-Session-Token: <token>` in all authenticated requests.

| Method | Endpoint | Auth | Description |
|---|---|---|---|
| POST | `/login.php` | No | Authenticate + create session |
| POST | `/logout.php` | Token | End session |
| POST | `/heartbeat.php` | Token | Keep session alive |
| GET | `/session.php` | Token | Get session info |
| GET | `/apps.php` | Token | List available apps |
| POST | `/apps.php` | Token | Submit app request |

---

## 🔒 Security Notes

- All passwords hashed with **bcrypt** (cost 12)
- Session tokens are **cryptographically random** (256-bit)
- API uses **PDO prepared statements** (SQL injection protected)
- Kiosk mode blocks: Alt+F4, Alt+Tab, Ctrl+W, Ctrl+Shift+I, F5, Windows key
- Admin panel uses PHP sessions with **inactivity timeout** (1 hour)
- Set `DEBUG_MODE = false` in `config.php` for production

---

## 📈 Session Data Collected

| Field | Description |
|---|---|
| `login_time` | When the user logged in |
| `logout_time` | When the user logged out (or was force-ended) |
| `duration_seconds` | Total session duration |
| `last_heartbeat` | Last ping from kiosk client |
| `status` | active / completed / force_ended / abandoned |
| Terminal info | Which PC was used |

Sessions with no heartbeat for >2 minutes are automatically marked **abandoned** on the next admin panel page load.

---

## 📋 Data Reports

The **Reports** page supports:
- Filter by **date range**
- Group by **day**, **user**, or **terminal**
- **Bar chart** visualization (Chart.js)
- **CSV export** for spreadsheet analysis
