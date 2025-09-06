# CGS
CGS –Clearance for Graduating Students: is a centralized digital platform designed to streamline and manage the clearance process for graduating students. It enables final-year undergraduate and postgraduate students to complete academic, financial, and administrative clearance requirements electronically before graduation.

Digital clearance for UG/PG students. Built with PHP, MySQL, HTML5/CSS3/JS. Mobile‑first.

## Features
- Self‑registration; admin assigns roles (Admin, Student, Signatory, Academic Admin)
- UG & PG workflows mapped to real signatories/departments
- Progress tracker & audit logging
- Web & CLI interactive installer (no manual config edits)
- **Certificate generation (PDF with QR code) once clearance is complete**

---

## Requirements
- PHP 8+ (bundled with XAMPP)
- MySQL / MariaDB (via XAMPP)
- [Composer](https://getcomposer.org/download/) (for PHP package management)

---

## Local Dev Setup (XAMPP + Composer)

### 1. Install XAMPP
- Download and install from [https://www.apachefriends.org](https://www.apachefriends.org)
- Place repo into `C:\xampp\htdocs\CGS`

### 2. Install Composer (Windows)
- Download Composer-Setup.exe from [https://getcomposer.org/download/](https://getcomposer.org/download/)
- During install, point it to `C:\xampp\php\php.exe`
- Verify: open **Command Prompt** → run `composer -V`

### 3. Install Dependencies
From your project folder:
```bash
cd C:\xampp\htdocs\CGS
composer install
```

This installs FPDF (used for PDF + QR certificates).

### 4. Start Services
- Launch XAMPP Control Panel → Start **Apache** & **MySQL**

### 5. Run Installer
Visit:
```
http://localhost/CGS/install/install.php
```
Fill DB + Admin details. This creates schema, seeds roles/departments, and provisions first Admin.

### 6. Log In
Visit:
```
http://localhost/CGS/public/login.php
```
Use the admin credentials you just created.

---

## Certificate Workflow
- Once a student’s clearance is fully approved, they will see a **Download Certificate** button.
- CGS generates a **PDF certificate with a QR code** for verification.
- QR codes point to `verify.php`, allowing external parties to confirm authenticity.

---

## GitHub Workflow
```bash
# one‑time repo setup
mkdir CGS && cd CGS
git init
git add .
git commit -m "chore: bootstrap CGS"
git branch -M main
git remote add origin https://github.com/rwabig/CGS.git
git push -u origin main
```

> Do **not** commit `.env`. It holds secrets and DB credentials.

---

## Notes
- You can manage roles, departments, workflows from Admin pages.
- `database/migrations/` holds incremental schema updates (example: `certificates` table).
- Certificates are stored in `/public/certificates/`.

---

## Next Steps
- Add email notifications when certificates are generated.
- Assign department officers as signatories.
- Add search & pagination to Admin tables.
- Improve front-end UI/UX with modern JS framework if needed.
