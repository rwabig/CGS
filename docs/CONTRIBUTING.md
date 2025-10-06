👨‍💻 Contributing to CGS

Thank you for considering contributing to Clearance for Graduating Students (CGS)!
This guide will help you set up your local development environment, follow coding conventions, and submit meaningful contributions.

🛠 Development Setup
1️⃣ Clone the Repository
git clone https://github.com/rwabig/CGS.git
cd CGS

2️⃣ Install Dependencies

Make sure Composer
 is installed, then run:

composer install

3️⃣ Configure Environment

Copy .env.example to .env and adjust DB credentials:

cp .env.example .env


Edit .env to match your local database setup:

DB_HOST=127.0.0.1
DB_NAME=cgs
DB_USER=root
DB_PASS=
APP_ENV=local
SESSION_TIMEOUT=1800

4️⃣ Install the Application

You can use either method:

Web Installer:

http://localhost/CGS/install/install.php


CLI Installer:

php install/install-cli.php


Both methods will:

Create the database if not present

Run schema.sql to set up tables

Run seed.sql to populate roles, a default super admin, and sample data

📂 Recommended Tools

PHP 8.0+ with PDO, GD enabled

MariaDB/MySQL 10+

Composer

XAMPP / Laragon / WAMP for local dev

A good editor: VS Code / PhpStorm

🧑‍🎨 Coding Guidelines

PHP: Follow PSR-12 coding standards.

JavaScript: Use ES6+ syntax. Keep code modular and well-commented.

CSS: Use public/assets/css/cgs.css (mobile-first) or Tailwind for components.

Database: Write new migrations as database/migrations/YYYYMMDD_description.sql.

🧪 Testing & QA

Run the installer on a fresh database to ensure schema and seed files work.

Test registration (student & staff) and verify role assignments.

Check dashboards (student, staff, admin, super admin) for correct role access.

Generate a clearance certificate and confirm QR code works.

🔀 Contribution Workflow

Fork the repository.

Create a branch for your feature/fix:

git checkout -b feature/my-new-feature


Commit changes with clear messages:

git commit -m "feat: add bulk role assignment for admin users"


Push to your fork:

git push origin feature/my-new-feature


Submit a Pull Request to main branch.

🧑‍💼 Super Admin Access

The default super admin account is created automatically by seed.sql:

Email: rwabig@gmail.com

Password: ChangeMe123

Change this password after installation for security.

📝 Reporting Issues

Open an issue in GitHub with a clear description.

Include reproduction steps, error logs, and screenshots if possible.

💡 Suggestions

Have ideas for improving CGS?
Open a discussion or propose a feature request in GitHub Issues.
