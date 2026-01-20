# Database Schema & ER Diagram
## Qttenzy - MySQL Database Design

---

## 📊 ER DIAGRAM (Text Format)

```
┌─────────────┐         ┌──────────────┐         ┌─────────────┐
│    users    │◄──┐     │   sessions   │◄──┐     │ attendances │
├─────────────┤   │     ├──────────────┤   │     ├─────────────┤
│ id (PK)     │   │     │ id (PK)      │   │     │ id (PK)     │
│ name        │   │     │ title        │   │     │ user_id(FK) │
│ email       │   │     │ description  │   │     │ session_id  │
│ password    │   │     │ start_time   │   │     │   (FK)      │
│ role        │   │     │ end_time     │   │     │ qr_code_id  │
│ phone       │   │     │ location_lat │   │     │   (FK)      │
│ avatar      │   │     │ location_lng │   │     │ verified_at │
│ is_active   │   │     │ radius_m     │   │     │ face_match  │
│ created_at  │   │     │ session_type │   │     │ gps_valid   │
│ updated_at  │   │     │ status       │   │     │ location_lat │
└─────────────┘   │     │ created_by   │   │     │ location_lng │
                  │     │   (FK)       │   │     │ ip_address   │
┌─────────────┐   │     │ created_at   │   │     │ device_info  │
│ face_       │   │     │ updated_at   │   │     │ created_at   │
│ enrollments │   │     └──────────────┘   │     │ updated_at   │
├─────────────┤   │                        │     └─────────────┘
│ id (PK)     │   │     ┌──────────────┐   │
│ user_id(FK) │───┘     │   qr_codes   │───┘
│ face_data   │        ├──────────────┤
│ created_at  │        │ id (PK)      │
│ updated_at  │        │ session_id   │
└─────────────┘        │   (FK)       │
                        │ code         │
┌─────────────┐         │ expires_at   │
│ payments    │         │ is_active    │
├─────────────┤         │ created_at   │
│ id (PK)     │         │ updated_at   │
│ user_id(FK) │         └──────────────┘
│ session_id  │
│   (FK)      │         ┌──────────────┐
│ amount      │         │ location_    │
│ currency    │         │    logs      │
│ status      │         ├──────────────┤
│ gateway     │         │ id (PK)      │
│ transaction │         │ user_id(FK)  │
│   _id       │         │ session_id   │
│ paid_at     │         │   (FK)       │
│ created_at  │         │ latitude     │
│ updated_at  │         │ longitude    │
└─────────────┘         │ accuracy     │
                        │ timestamp    │
┌─────────────┐         │ created_at   │
│ registrations│        └──────────────┘
├─────────────┤
│ id (PK)     │
│ user_id(FK) │
│ session_id  │
│   (FK)      │
│ payment_id  │
│   (FK)      │
│ status      │
│ registered_at│
│ created_at  │
│ updated_at  │
└─────────────┘
```

---

## 📋 TABLE SCHEMAS

### 1. users
```sql
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    email_verified_at TIMESTAMP NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NULL,
    avatar VARCHAR(500) NULL,
    role ENUM('admin', 'student', 'employee', 'session_manager') DEFAULT 'student',
    is_active BOOLEAN DEFAULT TRUE,
    webauthn_enabled BOOLEAN DEFAULT FALSE,
    webauthn_credential_id VARCHAR(500) NULL,
    last_login_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 2. sessions
```sql
CREATE TABLE sessions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    start_time DATETIME NOT NULL,
    end_time DATETIME NOT NULL,
    location_lat DECIMAL(10, 8) NOT NULL,
    location_lng DECIMAL(11, 8) NOT NULL,
    location_name VARCHAR(255) NULL,
    radius_meters INT DEFAULT 100,
    session_type ENUM('admin_approved', 'pre_registered', 'open') DEFAULT 'open',
    status ENUM('draft', 'active', 'completed', 'cancelled') DEFAULT 'draft',
    requires_payment BOOLEAN DEFAULT FALSE,
    payment_amount DECIMAL(10, 2) NULL,
    max_attendees INT NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_status (status),
    INDEX idx_start_time (start_time),
    INDEX idx_created_by (created_by),
    INDEX idx_location (location_lat, location_lng)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 3. qr_codes
```sql
CREATE TABLE qr_codes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    session_id BIGINT UNSIGNED NOT NULL,
    code VARCHAR(255) UNIQUE NOT NULL,
    expires_at DATETIME NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    rotation_interval INT DEFAULT 300, -- seconds
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (session_id) REFERENCES sessions(id) ON DELETE CASCADE,
    INDEX idx_session_id (session_id),
    INDEX idx_code (code),
    INDEX idx_expires_at (expires_at),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 4. attendances
```sql
CREATE TABLE attendances (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    session_id BIGINT UNSIGNED NOT NULL,
    qr_code_id BIGINT UNSIGNED NOT NULL,
    verified_at DATETIME NOT NULL,
    face_match_score DECIMAL(5, 2) NULL,
    face_match BOOLEAN DEFAULT FALSE,
    gps_valid BOOLEAN DEFAULT FALSE,
    location_lat DECIMAL(10, 8) NULL,
    location_lng DECIMAL(11, 8) NULL,
    distance_from_venue DECIMAL(10, 2) NULL, -- meters
    ip_address VARCHAR(45) NULL,
    device_info TEXT NULL,
    webauthn_used BOOLEAN DEFAULT FALSE,
    verification_method ENUM('qr_only', 'qr_face', 'qr_face_gps', 'qr_face_gps_webauthn') DEFAULT 'qr_face_gps',
    status ENUM('pending', 'verified', 'rejected', 'flagged') DEFAULT 'pending',
    rejection_reason TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (session_id) REFERENCES sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (qr_code_id) REFERENCES qr_codes(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_session (user_id, session_id),
    INDEX idx_user_id (user_id),
    INDEX idx_session_id (session_id),
    INDEX idx_verified_at (verified_at),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 5. face_enrollments
```sql
CREATE TABLE face_enrollments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    face_descriptor TEXT NOT NULL, -- JSON array of face descriptor
    image_url VARCHAR(500) NULL,
    enrollment_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_enrollment_status (enrollment_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 6. location_logs
```sql
CREATE TABLE location_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    session_id BIGINT UNSIGNED NULL,
    latitude DECIMAL(10, 8) NOT NULL,
    longitude DECIMAL(11, 8) NOT NULL,
    accuracy DECIMAL(10, 2) NULL, -- meters
    altitude DECIMAL(10, 2) NULL,
    heading DECIMAL(5, 2) NULL,
    speed DECIMAL(5, 2) NULL,
    timestamp DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (session_id) REFERENCES sessions(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_session_id (session_id),
    INDEX idx_timestamp (timestamp)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 7. payments
```sql
CREATE TABLE payments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    session_id BIGINT UNSIGNED NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'BDT',
    status ENUM('pending', 'processing', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    gateway ENUM('sslcommerz', 'stripe') NOT NULL,
    transaction_id VARCHAR(255) UNIQUE NULL,
    gateway_response TEXT NULL, -- JSON
    paid_at TIMESTAMP NULL,
    refunded_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (session_id) REFERENCES sessions(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_session_id (session_id),
    INDEX idx_status (status),
    INDEX idx_transaction_id (transaction_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 8. registrations
```sql
CREATE TABLE registrations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    session_id BIGINT UNSIGNED NOT NULL,
    payment_id BIGINT UNSIGNED NULL,
    status ENUM('pending', 'confirmed', 'cancelled', 'waitlisted') DEFAULT 'pending',
    registered_at TIMESTAMP NULL,
    cancelled_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (session_id) REFERENCES sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE SET NULL,
    UNIQUE KEY unique_user_session (user_id, session_id),
    INDEX idx_user_id (user_id),
    INDEX idx_session_id (session_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 9. password_resets (Laravel Default)
```sql
CREATE TABLE password_resets (
    email VARCHAR(255) NOT NULL,
    token VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NULL,
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 10. failed_jobs (Laravel Default)
```sql
CREATE TABLE failed_jobs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid VARCHAR(255) UNIQUE NOT NULL,
    connection TEXT NOT NULL,
    queue TEXT NOT NULL,
    payload LONGTEXT NOT NULL,
    exception LONGTEXT NOT NULL,
    failed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## 🔑 INDEXES & OPTIMIZATION

### Composite Indexes
```sql
-- For attendance queries
CREATE INDEX idx_attendance_user_session ON attendances(user_id, session_id, verified_at);
CREATE INDEX idx_attendance_session_date ON attendances(session_id, DATE(verified_at));

-- For session queries
CREATE INDEX idx_session_time_status ON sessions(start_time, end_time, status);

-- For payment queries
CREATE INDEX idx_payment_user_status ON payments(user_id, status, created_at);
```

### Full-Text Indexes
```sql
ALTER TABLE sessions ADD FULLTEXT idx_session_search (title, description);
```

---

## 🔄 RELATIONSHIPS SUMMARY

1. **users** → **sessions** (1:N) - One user can create many sessions
2. **users** → **attendances** (1:N) - One user can have many attendance records
3. **users** → **face_enrollments** (1:N) - One user can have multiple face enrollments
4. **users** → **payments** (1:N) - One user can make many payments
5. **users** → **registrations** (1:N) - One user can register for many sessions
6. **sessions** → **qr_codes** (1:N) - One session can have multiple QR codes (rotation)
7. **sessions** → **attendances** (1:N) - One session can have many attendance records
8. **sessions** → **payments** (1:N) - One session can have many payments
9. **sessions** → **registrations** (1:N) - One session can have many registrations
10. **qr_codes** → **attendances** (1:N) - One QR code can be used for many attendances
11. **payments** → **registrations** (1:1) - One payment can be linked to one registration

---

## 📊 DATA TYPES EXPLANATION

- **DECIMAL(10, 8)**: Latitude (range: -90 to 90)
- **DECIMAL(11, 8)**: Longitude (range: -180 to 180)
- **DECIMAL(10, 2)**: Money amounts
- **DECIMAL(5, 2)**: Face match scores (0-100)
- **TEXT**: JSON data, descriptors, device info
- **ENUM**: Fixed value sets for status, roles, types
- **TIMESTAMP**: Automatic timestamp management

---

## 🔐 SECURITY CONSIDERATIONS

1. **Password Hashing**: Laravel's bcrypt (default)
2. **Face Descriptors**: Encrypted storage (AES-256)
3. **Sensitive Data**: Payment gateway responses encrypted
4. **Indexes**: Optimized for query performance
5. **Foreign Keys**: Cascade deletes where appropriate
6. **Unique Constraints**: Prevent duplicate attendances

