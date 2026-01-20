# REST API Documentation
## Qttenzy - Complete API Reference

**Base URL**: `https://api.qttenzy.com/api/v1`  
**Authentication**: Bearer Token (JWT)

---

## 🔐 AUTHENTICATION API

### POST /auth/register
Register a new user.

**Request Body:**
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "SecurePass123!",
  "password_confirmation": "SecurePass123!",
  "phone": "+8801712345678",
  "role": "student"
}
```

**Response (201):**
```json
{
  "success": true,
  "message": "User registered successfully",
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "role": "student"
    },
    "token": "eyJ0eXAiOiJKV1QiLCJhbGc..."
  }
}
```

---

### POST /auth/login
Authenticate user and get JWT token.

**Request Body:**
```json
{
  "email": "john@example.com",
  "password": "SecurePass123!"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "role": "student",
      "avatar": "https://..."
    },
    "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "expires_in": 3600
  }
}
```

---

### POST /auth/logout
Logout user (invalidate token).

**Headers:**
```
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Logged out successfully"
}
```

---

### POST /auth/refresh
Refresh JWT token.

**Headers:**
```
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "expires_in": 3600
  }
}
```

---

### POST /auth/forgot-password
Request password reset.

**Request Body:**
```json
{
  "email": "john@example.com"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Password reset link sent to your email"
}
```

---

### POST /auth/reset-password
Reset password with token.

**Request Body:**
```json
{
  "email": "john@example.com",
  "token": "reset_token_here",
  "password": "NewSecurePass123!",
  "password_confirmation": "NewSecurePass123!"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Password reset successfully"
}
```

---

## 👤 USER API

### GET /user/profile
Get authenticated user profile.

**Headers:**
```
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "phone": "+8801712345678",
    "avatar": "https://...",
    "role": "student",
    "is_active": true,
    "webauthn_enabled": false,
    "face_enrolled": true,
    "created_at": "2024-01-01T00:00:00.000000Z"
  }
}
```

---

### PUT /user/profile
Update user profile.

**Headers:**
```
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "name": "John Updated",
  "phone": "+8801712345679",
  "avatar": "base64_image_data"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Profile updated successfully",
  "data": { ... }
}
```

---

### POST /user/face-enroll
Enroll user face for verification.

**Headers:**
```
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "face_descriptor": [0.123, -0.456, ...], // Array of 128 numbers
  "image": "base64_image_string"
}
```

**Response (201):**
```json
{
  "success": true,
  "message": "Face enrolled successfully"
}
```

---

### POST /user/webauthn/register
Register WebAuthn credential.

**Headers:**
```
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "credential_id": "credential_id_string",
  "public_key": "public_key_string"
}
```

**Response (201):**
```json
{
  "success": true,
  "message": "WebAuthn credential registered"
}
```

---

## 📅 SESSION API

### GET /sessions
Get all sessions (with filters).

**Query Parameters:**
- `status`: draft|active|completed|cancelled
- `type`: admin_approved|pre_registered|open
- `page`: Page number (default: 1)
- `per_page`: Items per page (default: 15)
- `search`: Search in title/description

**Response (200):**
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "title": "Workshop on AI",
        "description": "...",
        "start_time": "2024-01-15T10:00:00Z",
        "end_time": "2024-01-15T12:00:00Z",
        "location": {
          "lat": 23.8103,
          "lng": 90.4125,
          "name": "Dhaka University"
        },
        "radius_meters": 100,
        "session_type": "pre_registered",
        "status": "active",
        "requires_payment": true,
        "payment_amount": 500.00,
        "max_attendees": 50,
        "current_attendees": 25,
        "created_by": {
          "id": 2,
          "name": "Admin User"
        },
        "created_at": "2024-01-01T00:00:00Z"
      }
    ],
    "total": 50,
    "per_page": 15,
    "last_page": 4
  }
}
```

---

### GET /sessions/{id}
Get single session details.

**Response (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "title": "Workshop on AI",
    "description": "...",
    "start_time": "2024-01-15T10:00:00Z",
    "end_time": "2024-01-15T12:00:00Z",
    "location": { ... },
    "qr_code": {
      "code": "SESSION_123_QR_CODE",
      "expires_at": "2024-01-15T10:05:00Z"
    },
    "registration_status": "registered", // null|registered|pending|waitlisted
    "attendance_status": null, // null|present|absent
    ...
  }
}
```

---

### POST /sessions
Create a new session (Admin/Manager only).

**Headers:**
```
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "title": "Workshop on AI",
  "description": "Learn AI fundamentals",
  "start_time": "2024-01-15T10:00:00Z",
  "end_time": "2024-01-15T12:00:00Z",
  "location_lat": 23.8103,
  "location_lng": 90.4125,
  "location_name": "Dhaka University",
  "radius_meters": 100,
  "session_type": "pre_registered",
  "requires_payment": true,
  "payment_amount": 500.00,
  "max_attendees": 50
}
```

**Response (201):**
```json
{
  "success": true,
  "message": "Session created successfully",
  "data": { ... }
}
```

---

### PUT /sessions/{id}
Update session (Admin/Manager only).

**Headers:**
```
Authorization: Bearer {token}
```

**Request Body:** (Same as POST, all fields optional)

**Response (200):**
```json
{
  "success": true,
  "message": "Session updated successfully",
  "data": { ... }
}
```

---

### DELETE /sessions/{id}
Delete session (Admin only).

**Headers:**
```
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Session deleted successfully"
}
```

---

### GET /sessions/{id}/qr
Get QR code for session (Manager/Admin only).

**Headers:**
```
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "qr_code": "SESSION_123_QR_CODE",
    "qr_image": "data:image/png;base64,iVBORw0KG...",
    "expires_at": "2024-01-15T10:05:00Z",
    "session_id": 1
  }
}
```

---

## ✅ ATTENDANCE API

### POST /attendance/verify
Verify and mark attendance.

**Headers:**
```
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "session_id": 1,
  "qr_code": "SESSION_123_QR_CODE",
  "face_descriptor": [0.123, -0.456, ...], // Current face descriptor
  "location": {
    "lat": 23.8103,
    "lng": 90.4125,
    "accuracy": 10.5
  },
  "webauthn_credential_id": "optional_credential_id"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Attendance verified successfully",
  "data": {
    "attendance_id": 123,
    "verified_at": "2024-01-15T10:02:30Z",
    "verification_method": "qr_face_gps",
    "face_match_score": 95.5,
    "gps_valid": true,
    "distance_from_venue": 45.2
  }
}
```

**Error Responses:**

**400 - Invalid QR Code:**
```json
{
  "success": false,
  "message": "Invalid or expired QR code"
}
```

**400 - Face Mismatch:**
```json
{
  "success": false,
  "message": "Face verification failed",
  "data": {
    "face_match_score": 45.2,
    "threshold": 70.0
  }
}
```

**400 - Location Invalid:**
```json
{
  "success": false,
  "message": "Location verification failed",
  "data": {
    "distance_from_venue": 250.5,
    "allowed_radius": 100
  }
}
```

**409 - Duplicate Attendance:**
```json
{
  "success": false,
  "message": "Attendance already recorded for this session"
}
```

---

### GET /attendance/history
Get user's attendance history.

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters:**
- `session_id`: Filter by session
- `start_date`: Start date (YYYY-MM-DD)
- `end_date`: End date (YYYY-MM-DD)
- `page`: Page number
- `per_page`: Items per page

**Response (200):**
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 123,
        "session": {
          "id": 1,
          "title": "Workshop on AI",
          "start_time": "2024-01-15T10:00:00Z"
        },
        "verified_at": "2024-01-15T10:02:30Z",
        "status": "verified",
        "verification_method": "qr_face_gps",
        "face_match_score": 95.5,
        "gps_valid": true
      }
    ],
    "total": 25,
    "per_page": 15
  }
}
```

---

### GET /attendance/session/{session_id}
Get attendance list for a session (Manager/Admin only).

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters:**
- `status`: pending|verified|rejected|flagged
- `export`: csv|pdf (for export)

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 123,
      "user": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com"
      },
      "verified_at": "2024-01-15T10:02:30Z",
      "status": "verified",
      "face_match_score": 95.5,
      "gps_valid": true,
      "location": {
        "lat": 23.8103,
        "lng": 90.4125
      }
    }
  ]
}
```

---

## 💳 PAYMENT API

### POST /payment/initiate
Initiate payment for session registration.

**Headers:**
```
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "session_id": 1,
  "gateway": "sslcommerz" // or "stripe"
}
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "payment_id": 456,
    "amount": 500.00,
    "currency": "BDT",
    "gateway": "sslcommerz",
    "payment_url": "https://sandbox.sslcommerz.com/...",
    "transaction_id": "TXN123456789"
  }
}
```

---

### POST /payment/webhook/{gateway}
Payment webhook endpoint (SSLCommerz/Stripe).

**Request Body:** (Gateway-specific format)

**Response (200):**
```json
{
  "success": true,
  "message": "Webhook processed"
}
```

---

### GET /payment/status/{payment_id}
Get payment status.

**Headers:**
```
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "id": 456,
    "status": "completed",
    "amount": 500.00,
    "transaction_id": "TXN123456789",
    "paid_at": "2024-01-10T14:30:00Z"
  }
}
```

---

## 📊 ADMIN API

### GET /admin/dashboard
Get admin dashboard statistics.

**Headers:**
```
Authorization: Bearer {token}
Role: admin
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "total_users": 1250,
    "total_sessions": 45,
    "total_attendances": 3250,
    "today_attendances": 125,
    "active_sessions": 5,
    "pending_approvals": 3,
    "revenue": {
      "today": 12500.00,
      "month": 125000.00,
      "total": 500000.00
    }
  }
}
```

---

### GET /admin/users
Get all users (with filters).

**Query Parameters:**
- `role`: Filter by role
- `status`: active|inactive
- `search`: Search name/email
- `page`: Page number
- `per_page`: Items per page

**Response (200):**
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [ ... ],
    "total": 1250,
    "per_page": 15
  }
}
```

---

### PUT /admin/users/{id}/status
Update user status.

**Request Body:**
```json
{
  "is_active": false
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "User status updated"
}
```

---

### GET /admin/reports/attendance
Generate attendance reports.

**Query Parameters:**
- `session_id`: Filter by session
- `start_date`: Start date
- `end_date`: End date
- `format`: json|csv|pdf
- `group_by`: day|month|session

**Response (200):**
```json
{
  "success": true,
  "data": {
    "summary": {
      "total_attendances": 3250,
      "unique_users": 850,
      "average_attendance_rate": 75.5
    },
    "by_date": [
      {
        "date": "2024-01-15",
        "attendances": 125,
        "sessions": 5
      }
    ],
    "by_session": [ ... ]
  }
}
```

---

## 🔍 QR CODE API

### GET /qr/generate/{session_id}
Generate QR code for session (Manager/Admin only).

**Headers:**
```
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "qr_code": "SESSION_123_QR_CODE",
    "qr_image": "data:image/png;base64,iVBORw0KG...",
    "expires_at": "2024-01-15T10:05:00Z",
    "rotation_interval": 300
  }
}
```

---

### POST /qr/validate
Validate QR code (used internally).

**Request Body:**
```json
{
  "qr_code": "SESSION_123_QR_CODE",
  "session_id": 1
}
```

**Response (200):**
```json
{
  "success": true,
  "valid": true,
  "data": {
    "session_id": 1,
    "expires_at": "2024-01-15T10:05:00Z"
  }
}
```

---

## 📝 ERROR RESPONSES

All errors follow this format:

```json
{
  "success": false,
  "message": "Error message",
  "errors": {
    "field_name": ["Validation error message"]
  }
}
```

**HTTP Status Codes:**
- `200`: Success
- `201`: Created
- `400`: Bad Request / Validation Error
- `401`: Unauthorized
- `403`: Forbidden
- `404`: Not Found
- `409`: Conflict (Duplicate)
- `422`: Unprocessable Entity
- `500`: Server Error

---

## 🔒 AUTHENTICATION

All protected endpoints require:
```
Authorization: Bearer {jwt_token}
```

Token expires in 1 hour. Use `/auth/refresh` to get a new token.

---

## 📌 RATE LIMITING

- **Public endpoints**: 60 requests/minute
- **Authenticated endpoints**: 120 requests/minute
- **Admin endpoints**: 200 requests/minute

Rate limit headers:
```
X-RateLimit-Limit: 120
X-RateLimit-Remaining: 95
X-RateLimit-Reset: 1640000000
```

