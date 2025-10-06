BEGIN;

-- 🧑‍💻 SUPER ADMIN PROFILES
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.tables
        WHERE table_name = 'super_admin_profiles'
    ) THEN
        CREATE TABLE super_admin_profiles (
            id SERIAL PRIMARY KEY,
            user_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
            full_name VARCHAR(120),
            phone VARCHAR(30),
            office_location VARCHAR(120),
            cheque_number VARCHAR(50),
            avatar VARCHAR(255) DEFAULT 'default-avatar.png',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
    ELSE
        IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name='super_admin_profiles' AND column_name='cheque_number') THEN
            ALTER TABLE super_admin_profiles ADD COLUMN cheque_number VARCHAR(50);
        END IF;
        IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name='super_admin_profiles' AND column_name='avatar') THEN
            ALTER TABLE super_admin_profiles ADD COLUMN avatar VARCHAR(255) DEFAULT 'default-avatar.png';
        END IF;
    END IF;
END $$;

-- 👨‍💼 ADMIN PROFILES
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.tables
        WHERE table_name = 'admin_profiles'
    ) THEN
        CREATE TABLE admin_profiles (
            id SERIAL PRIMARY KEY,
            user_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
            full_name VARCHAR(120),
            phone VARCHAR(30),
            position_title VARCHAR(100),
            organization_id INT REFERENCES organizations(id) ON DELETE SET NULL,
            department_id INT REFERENCES departments(id) ON DELETE SET NULL,
            cheque_number VARCHAR(50),
            avatar VARCHAR(255) DEFAULT 'default-avatar.png',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
    ELSE
        IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name='admin_profiles' AND column_name='cheque_number') THEN
            ALTER TABLE admin_profiles ADD COLUMN cheque_number VARCHAR(50);
        END IF;
        IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name='admin_profiles' AND column_name='avatar') THEN
            ALTER TABLE admin_profiles ADD COLUMN avatar VARCHAR(255) DEFAULT 'default-avatar.png';
        END IF;
    END IF;
END $$;

-- 👥 STAFF PROFILES
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.tables
        WHERE table_name = 'staff_profiles'
    ) THEN
        CREATE TABLE staff_profiles (
            id SERIAL PRIMARY KEY,
            user_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
            full_name VARCHAR(120),
            phone VARCHAR(30),
            position_title VARCHAR(100),
            organization_id INT REFERENCES organizations(id) ON DELETE SET NULL,
            department_id INT REFERENCES departments(id) ON DELETE SET NULL,
            cheque_number VARCHAR(50),
            avatar VARCHAR(255) DEFAULT 'default-avatar.png',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
    ELSE
        IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name='staff_profiles' AND column_name='cheque_number') THEN
            ALTER TABLE staff_profiles ADD COLUMN cheque_number VARCHAR(50);
        END IF;
        IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name='staff_profiles' AND column_name='avatar') THEN
            ALTER TABLE staff_profiles ADD COLUMN avatar VARCHAR(255) DEFAULT 'default-avatar.png';
        END IF;
    END IF;
END $$;

-- 🎓 STUDENT PROFILES (with passport photo)
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.tables
        WHERE table_name = 'student_profiles'
    ) THEN
        CREATE TABLE student_profiles (
            id SERIAL PRIMARY KEY,
            user_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
            full_name VARCHAR(120),
            reg_number VARCHAR(50),
            phone VARCHAR(30),
            program_id INT REFERENCES programs(id) ON DELETE SET NULL,
            department_id INT REFERENCES departments(id) ON DELETE SET NULL,
            category_id INT REFERENCES categories(id) ON DELETE SET NULL,
            graduation_year INT,
            residential_status VARCHAR(30),
            hall_name VARCHAR(100),
            current_address TEXT,
            photo VARCHAR(255) DEFAULT 'default-student.png',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
    ELSE
        IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name='student_profiles' AND column_name='photo') THEN
            ALTER TABLE student_profiles ADD COLUMN photo VARCHAR(255) DEFAULT 'default-student.png';
        END IF;
    END IF;
END $$;

-- 🆕 CLEARANCE CERTIFICATES (with logo support)
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.tables WHERE table_name = 'clearance_certificates'
    ) THEN
        CREATE TABLE clearance_certificates (
            id SERIAL PRIMARY KEY,
            student_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
            university_name VARCHAR(120) DEFAULT 'Ardhi University',
            university_logo VARCHAR(255) DEFAULT 'aru-logo.png',
            title VARCHAR(255) DEFAULT 'Clearance Certificate Form for Graduating Student',
            description TEXT DEFAULT 'Ensure safe return of all university/property equipment, books etc. entrusted during the period of studies. Clearance must be done before collection of the result slip, academic certificate and transcripts.',
            student_photo VARCHAR(255) DEFAULT 'default-student.png',
            completion_year INT,
            graduation_date DATE,
            issued_at TIMESTAMP NULL,
            qr_code TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
    ELSE
        IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name='clearance_certificates' AND column_name='university_logo') THEN
            ALTER TABLE clearance_certificates ADD COLUMN university_logo VARCHAR(255) DEFAULT 'aru-logo.png';
        END IF;
    END IF;
END $$;

-- 🆕 CLEARANCE SIGNATORY STEPS
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.tables WHERE table_name = 'clearance_signatories'
    ) THEN
        CREATE TABLE clearance_signatories (
            id SERIAL PRIMARY KEY,
            certificate_id INT NOT NULL REFERENCES clearance_certificates(id) ON DELETE CASCADE,
            step_order INT NOT NULL,
            signatory_name VARCHAR(120),
            signatory_title VARCHAR(120),
            status VARCHAR(20) CHECK (status IN ('pending','approved','rejected')) DEFAULT 'pending',
            comments TEXT,
            signed_at TIMESTAMP NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
    END IF;
END $$;

COMMIT;
