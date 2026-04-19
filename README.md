# 📚 Library Computer Login System

[![Project Status](https://img.shields.io/badge/Status-Production--Ready-success?style=flat-square)](https://github.com/cknwyn/library-computer-login-system)
[![Platform](https://img.shields.io/badge/Platform-Windows-blue?style=flat-square&logo=windows)](https://dotnet.microsoft.com/en-us/apps/desktop/wpf)
[![Admin UI](https://img.shields.io/badge/Theme-Admin--Modernized-6366f1?style=flat-square)](https://lucide.dev/)

A professional, full-stack library computer management and session tracking system designed specifically for **nComputing vSpace** thin-client environments.  

This ecosystem features a high-performance **C# WPF/XAML Kiosk Client** for terminal lockdown and a modernized **PHP/MySQL Admin Dashboard** for real-time monitoring and advanced analytics.

---

## ✨ Key Features

- **🔐 Robust Kiosk Lockdown**: Native Windows integration (WPF) with low-level keyboard hooks to block OS shortcuts (Alt+F4, WinKey, etc.).
- **💂 Native Watchdog**: A background `KioskGuard` service that ensures the kiosk is always running and automatically recovers from crashes.
- **🎨 Modern Admin UI**: A premium, "SaaS-style" light theme dashboard featuring **Lucide** icons and responsive design.
- **📊 Advanced Analytics**: Real-time session feed, usage heatmaps, CSV exports, and detailed user/terminal management.
- **🛡️ Security First**: PDO-secured API, Bcrypt-hashed credentials, and cryptographically secure session tokens.

---

## 🛠️ Technology Stack

| Component | Technology | Description |
|---|---|---|
| **Kiosk Client** | .NET 10.0 (WPF) | High-performance C# Windows Shell |
| **Watchdog** | Console Service | Lightweight process monitor |
| **Admin Panel** | PHP 8.2+ | Modernized Dashboard with Vanilla CSS |
| **Database** | MySQL 8.0+ | Relational schema with optimized indexing |
| **Icons** | Lucide | Professional SVG Vector Icons |
| **Typography** | Inter | High-legibility modern typeface |

---

## 📂 Project Structure

```text
library-computer-login-system/
├── client-wpf/          # Native Windows Infrastructure
│   ├── LibraryKiosk/    # Primary WPF Application (C#/XAML)
│   ├── KioskGuard/      # C# Watchdog Monitor
│   └── assets/          # Embedded Branding (Logo & Background)
│
├── server/              # PHP Web Infrastructure
│   ├── admin/           # Premium Modern Dashboard (Themed)
│   ├── api/             # RESTful API Endpoints
│   ├── includes/        # Shared DB & Auth Core
│   └── assets/          # Clean CSS & ES6 JS Modules
│
└── database/
    ├── schema.sql       # Optimized DB Structure
    └── seed.sql         # Default credentials & Sample Data
```

---

## 🚀 Deployment Guide

### 1. Server-Side (XAMPP/Direct)

1. **Database**: Import `database/schema.sql` and `database/seed.sql` via phpMyAdmin or MySQL CLI.
2. **Web Content**: Copy the contents of the `server/` directory to your web root (e.g., `C:\xampp\htdocs\server\`).
3. **Configuration**: Edit `server/config.php` to match your environment.

### 2. WPF Kiosk Configuration

1. **Setup**: Navigate to `client-wpf/LibraryKiosk/`.
2. **Secrets**: Update `appsettings.json` with your Server API URL:
   ```json
   {
     "ApiBaseUrl": "http://your-server-ip/server/api",
     "TerminalCode": "PC-01"
   }
   ```
3. **Build**: Build the solution using Visual Studio 2022/2026 or `dotnet build`.
4. **Deploy**: Copy the `bin/Release` output to the target terminal.

---

## 🔑 Access Credentials

> [!CAUTION]
> **Production Security**: Immediately change administrator passwords upon deployment.

| Role | Username / ID | Default Password |
|---|---|---|
| **System Administrator** | `admin` | `Admin@1234` |
| **Student (Sample)** | `2021-00001` | `Password@123` |
| **Staff (Sample)** | `STAFF-001` | `Password@123` |

---

## 🛡️ OS-Level Lockdown

The WPF client implements advanced Windows-native security:
- **Registry Interaction**: Can be configured to override the Explorer shell.
- **Hooking**: Utilizes `SetWindowsHookEx` for deep keyboard interception.
- **Watchdog Sync**: The `KioskGuard` monitors the `LibraryKiosk` process every 5 seconds and restarts it if it is terminated.

---

## 📈 Data Insight & Reports

The system tracks **Live Session Metadata** including:
- Real-time heartbeat monitoring.
- Automated "Abandoned Session" detection (>2 min silence).
- User role classification (Student/Staff differentiation).
- CSV Exporting for institutional auditing and spreadsheet integration.

---

Developed for **AUF Library Systems**.
Modernized by **Antigravity AI**.
