**#Team Transport — Transport Management System (PHP/MySQL)**

Team Transport is a web-based transport management system built with PHP, MySQL, and Bootstrap to simplify and digitize logistics operations. The platform enables administrators to manage users, customers, and transport records efficiently through a secure login system and intuitive dashboard. Features include user role management (admin, driver, customer, dispatcher), trip and delivery tracking, and integrated database operations for real-time data access. Designed with scalability and maintainability in mind, the project demonstrates clean backend logic, organized SQL schema design, and modular front-end integration for future expansion.

**Features:**

**1. User Management**

✔ Admin panel for managing users
✔ Create / edit / delete users
✔ Admin & dispatcher roles
✔ Driver mobile interface
✔ Login / session security
✔ Password hashing

**2. Customer Management**

✔ Create customers
✔ Edit customer details
✔ Delete customers
✔ Customer company name
✔ Customer internal handler name
✔ Contact info, address, emails

**3. Load Management (Full TMS Core)**
✔ Create Load (with all fields)
- Customer
- Reference number
- Pickup details
- Delivery details
- Weight
- Rate
- Currency
- Assigned driver
- Status
- Notes
- Document uploads
  
✔ Edit Load
- Update all fields
- Add more documents
- View & delete documents

✔ Load View (Full details)

✔ Loads List Page

- Status filters
- Date range filters
- Search filters
- Customer filters
- Driver filters
- Pagination
- Column sorting
- Saved user-specific views
- Quick export (CSV)

✔ Load Documents
- POD uploads
- BOL uploads
- Load Summary PDFs
- Delete documents
- Stored in dedicated folders: /uploads/pod/, /uploads/bol/, /uploads/summary/

**4. Driver Mobile View**
✔ Driver can see assigned loads
✔ Update load status
✔ Upload POD
✔ Mobile-friendly layout

**5. PDF Generation**
✔ Generate Bill of Lading
✔ Generate Load Summary
✔ TCPDF engine installed & configured

**6. Security & Infrastructure**
✔ Session-based auth
✔ Role-based permissions
✔ Admin-only pages
✔ Validations
✔ Config settings in /services/config.php
✔ File uploads stored securely
✔ MariaDB structure with foreign keys
✔ Raspberry Pi + Cloudflare Deployment
✔ GitHub Action deployment (CI/CD)

🌍**Features In The Works Right Now**
✔ Fully modular load creation
✔ Fully modular load editing
✔ Branded header + UI
✔ Unified include structure
✔ Partials for forms/scripts
✔ Cleaner TMS layout
✔ Driver assignment workflow



**Hosted on Rasberry Pi Web Server:**

🚀 **Server Features Include:**
🔥 **Bulletproof Monitoring Features**

- Auto-fixes nginx
- Auto-fixes Cloudflare Tunnel
- Hourly system health summary
- CPU temp alerts
- Throttling alerts
- Disk space alerts
- RAM & load monitoring
- IPv4-only socket check (no more false alarms)
- Only meaningful alerts (no spam)

🛡 **Production-Safe**

- No reboot loops
- No duplicate alerts
- No mismatched state errors
- Zero false negatives
- Zero false positives

📡 **Real-Time Discord Dashboard**

**Rasberry Pi now reports like a real cloud server.  AWS ;)**
✔ Auto-backs up SQL
✔ Auto-backs up website files
✔ Uploads all backups securely to Google Drive
✔ Has Cloudflare Tunnel, NGINX, monitoring, alerts, system checks — EVERYTHING fully automated.
