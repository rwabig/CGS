🧑‍💻 CGS Developer Guide

This guide covers CGS internals, including API endpoints, authentication & role checks, migrations rollback, and certificate generation logic.

🔑 Authentication & Role Management
Auth Class (src/Auth.php)

Key methods:

Method	Purpose
Auth::login($email,$password)	Verifies user credentials and starts a session.
Auth::logout()	Ends session and redirects to login page.
Auth::check()	Returns true if a user is logged in.
Auth::requireRole($roles)	Protects pages from unauthorized access. Accepts single role or array.
Auth::guestOnly()	Redirects logged-in users away from login/register pages.
Auth::user()	Returns the currently logged-in user's full DB row.
Role Slugs (Seeded in seed.sql)

student

signatory

admin

super_admin

Role-based pages call Auth::requireRole() at the top:

<?php
require_once __DIR__ . '/../src/bootstrap.php';
Auth::requireRole(['admin', 'super_admin']);
?>

🌐 API Endpoints

CGS uses a simple REST-style PHP API (JSON responses).

Authentication
Endpoint	Method	Input	Output
/api/auth_register.php	POST	email, password, name	{ "status": "ok" } or error message
/api/auth_login.php	POST	email, password	{ "status": "ok", "redirect": "/dashboard.php" }
Admin Management
Endpoint	Method	Purpose
/api/admin_create_user.php	POST	Create a user manually.
/api/admin_update_roles.php	POST	Assign or revoke roles.
/api/admin_clear_roles.php	POST	Remove selected roles.
/api/admin_toggle_active.php	POST	Activate/deactivate user.
Profile & Lookup
Endpoint	Purpose
/api/get_organizations.php	Returns all organizations
/api/get_departments.php?org_id=1	Departments for given org
/api/get_categories.php?org_id=1	Categories under given org
/api/get_programs.php?dept_id=1&cat_id=1	Programs under dept+category
/api/get_directories.php	Catcross directories & sections
🗄 Database Migrations

Migrations are stored in database/migrations/ as .sql files.

Use src/MigrationManager.php methods:

MigrationManager::getPendingMigrations()

MigrationManager::applyAllPending()

MigrationManager::getAppliedMigrations()

Rollback support is included in public/super_admin/migrations.php:

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rollback_last_batch'])) {
    // Deletes last batch from migrations_log and reverts schema changes manually
    // (you must have down-scripts prepared or reimport schema.sql fresh)
}


⚠️ Rollback Best Practice: Always write a matching _down.sql for each migration if you expect to roll it back safely.
In production, test migrations on a staging DB before applying.

🖼 Certificate Generation

Implemented in src/Certificate.php:

Uses FPDF + endroid/qr-code

Fetches:

Student details (name, reg no, program, department, organization)

Signatories (name, title, comments, date)

Student photo

Creates:

Passport photo section

Table-like signatory section (with comments wrapped)

QR code (clickable link to public/verify.php)

Footer text: "Issued through CGS on DD/MM/YYYY HH:MM"

Example Usage
require_once __DIR__ . '/../src/Certificate.php';
Certificate::generate($studentId, $clearanceId);


Generates a downloadable PDF with embedded QR code and deletes temporary PNG files after use.

🖥 Frontend

JavaScript: public/assets/js/form-wizard.js handles chained dropdowns (Organization → Department → Category → Program) and AJAX auto-saving of profiles.

CSS: public/assets/css/cgs.css is mobile-first. Tailwind utility classes used for dashboards and forms.

🧪 Development Workflow

Pull latest changes: git pull

Run migrations: super admin → Migrations page → "Run Pending"

Seed data (if needed): super admin → Seeding page

Test on fresh user accounts (student + staff)

Verify certificate generation

Commit feature branch & push PR

🔧 Troubleshooting

Login issues: Check users + user_roles tables for matching role assignment.

QR code fails: Ensure PHP GD extension is enabled in php.ini.

Schema mismatch: Re-run schema.sql + seed.sql on a clean DB.

Session timeout: Adjust SESSION_TIMEOUT in .env (default: 1800 seconds).

## 🔄 Clearance Workflow (Mermaid Diagram)

## Clearance Workflow (Visual)
```mermaid
sequenceDiagram
    participant Student
    participant System
    participant Signatory1 as Signatory (Step 1)
    participant Signatory2 as Signatory (Step 2)
    participant Admin
    participant Certificate

    Student->>System: Submit Clearance Request
    System-->>Student: Confirmation + "In Progress" Status

    loop Clearance Steps
        System->>Signatory1: Notify pending step
        Signatory1->>System: Approve or Comment
        System-->>Student: Update progress status

        System->>Signatory2: Notify next step (after step 1 done)
        Signatory2->>System: Approve or Comment
        System-->>Student: Update progress status
    end

    System->>Admin: Notify final step ready
    Admin->>System: Approve Final Clearance
    System-->>Student: Mark clearance "Completed"

    Student->>Certificate: Request Download
    Certificate-->>Student: Generate PDF (with photo, signatories, QR)
sequenceDiagram
    participant Student
    participant System
    participant Signatory1 as Signatory (Step 1)
    participant Signatory2 as Signatory (Step 2)
    participant Admin
    participant Certificate

    Student->>System: Submit Clearance Request
    System-->>Student: Confirmation + "In Progress" Status

    loop Clearance Steps
        System->>Signatory1: Notify pending step
        Signatory1->>System: Approve or Comment
        System-->>Student: Update progress status

        System->>Signatory2: Notify next step (after step 1 done)
        Signatory2->>System: Approve or Comment
        System-->>Student: Update progress status
    end

    System->>Admin: Notify final step ready
    Admin->>System: Approve Final Clearance
    System-->>Student: Mark clearance "Completed"

    Student->>Certificate: Request Download
    Certificate-->>Student: Generate PDF (with photo, signatories, QR)

## 🗄 Database ERD (Mermaid)
```mermaid
erDiagram
    USERS {
        int id PK
        varchar name
        varchar email
        varchar password_hash
        varchar reg_no
        tinyint is_active
        varchar phone
        enum status
        timestamp created_at
    }

    ROLES {
        int id PK
        varchar name
        varchar slug
    }

    USER_ROLES {
        int user_id FK
        int role_id FK
    }

    STUDENT_PROFILES {
        int id PK
        int user_id FK
        int organization_id FK
        int department_id FK
        int category_id FK
        int program_id FK
        varchar residence_name
        varchar address
        year completion_year
        date graduation_date
        varchar photo
    }

    STAFF_PROFILES {
        int id PK
        int user_id FK
        int organization_id FK
        int department_id FK
        int staff_title_id FK
        varchar check_number
        varchar photo
    }

    ORGANIZATIONS {
        int id PK
        varchar name
    }

    DEPARTMENTS {
        int id PK
        int organization_id FK
        varchar name
    }

    CATEGORIES {
        int id PK
        int department_id FK
        varchar name
    }

    PROGRAMS {
        int id PK
        int department_id FK
        int category_id FK
        varchar name
    }

    STAFF_TITLES {
        int id PK
        varchar name
    }

    CLEARANCE_REQUESTS {
        int id PK
        int user_id FK
        enum status
        timestamp created_at
    }

    CLEARANCE_STEPS {
        int id PK
        int request_id FK
        int signatory_id FK
        int step_order
        enum status
        text comments
        timestamp signed_at
    }

    DIRECTORIES {
        int id PK
        varchar name
        int organization_id FK
    }

    SECTIONS {
        int id PK
        int directory_id FK
        varchar name
    }

    USERS ||--o{ USER_ROLES : "has"
    ROLES ||--o{ USER_ROLES : "assigned to"
    USERS ||--o{ STUDENT_PROFILES : "may have"
    USERS ||--o{ STAFF_PROFILES : "may have"
    ORGANIZATIONS ||--o{ DEPARTMENTS : "contains"
    DEPARTMENTS ||--o{ CATEGORIES : "has"
    CATEGORIES ||--o{ PROGRAMS : "contains"
    STAFF_TITLES ||--o{ STAFF_PROFILES : "used in"
    USERS ||--o{ CLEARANCE_REQUESTS : "submits"
    CLEARANCE_REQUESTS ||--o{ CLEARANCE_STEPS : "contains"
    DIRECTORIES ||--o{ SECTIONS : "contains"

## ⚙️ Installation & Seeding Process

```mermaid
flowchart TD
    A[Start Installation] --> B{Choose Method}
    B -->|Web Installer| C[Run install/install.php]
    B -->|CLI Installer| D[Run install/install-cli.php]

    C --> E[Create Database if not exists]
    D --> E[Create Database if not exists]

    E --> F[Run schema.sql (creates tables)]
    F --> G[Run seed.sql (insert roles, super admin, staff titles)]
    G --> H[Installation Complete]

    H --> I[Open Login Page]
    I --> J[Enter Super Admin Credentials]
    J --> K[Login Successful → Redirect to Super Admin Dashboard]
    K --> L[Configure Organizations, Departments, Categories, Programs, etc.]
    L --> M[System Ready for Students & Staff]

## 🔄 Clearance Request Lifecycle

```mermaid
stateDiagram-v2
    [*] --> NotStarted

    NotStarted --> InProgress: Student submits request
    InProgress --> PartiallySigned: First signatory signs (some steps remain)
    PartiallySigned --> PartiallySigned: Additional signatories sign (but not all)
    PartiallySigned --> FullyApproved: Last signatory approves
    FullyApproved --> CertificateIssued: Admin finalizes clearance
    CertificateIssued --> [*]: Student downloads certificate

    %% Optional edge cases
    InProgress --> Cancelled: Student withdraws request
    PartiallySigned --> Cancelled: Admin cancels clearance
## 🏃 Clearance Approval Process (Step-by-Step)

```mermaid
flowchart TD
    A([Start]) --> B[Student submits clearance request]
    B --> C{Steps assigned?}
    C -->|Yes| D[System creates clearance steps (ordered)]
    D --> E[Notify first signatory]

    E --> F{Signatory signs?}
    F -->|Yes| G[Mark step as signed + add comments/date]
    G --> H{More steps remaining?}
    H -->|Yes| I[Notify next signatory in order]
    I --> F
    H -->|No| J[All steps signed → Notify Admin]

    J --> K{Admin approves?}
    K -->|Yes| L[Mark clearance as fully approved]
    L --> M[Generate certificate record]
    M --> N[Student sees "Download Certificate" button]
    N --> O([End])

    %% Optional paths
    F -->|Reject| X[Mark step as rejected]
    X --> Y[Notify student & admin]
    Y --> O
