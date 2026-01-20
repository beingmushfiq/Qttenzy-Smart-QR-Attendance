# 🚀 Qttenzy - Quick Start Guide

## Start in 3 Steps

### 1. Start Backend (Terminal 1)
```bash
cd backend
php -S 127.0.0.1:8000 -t public
```
✅ Backend running at: http://localhost:8000

### 2. Start Frontend (Terminal 2)
```bash
cd frontend
npm run dev
```
✅ Frontend running at: http://localhost:5173

### 3. Login
- **URL**: http://localhost:5173
- **Admin**: admin@qttenzy.com / password
- **Student**: student1@qttenzy.com / password

---

## Demo Accounts

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@qttenzy.com | password |
| Teacher | teacher1@qttenzy.com | password |
| Student | student1@qttenzy.com | password |

---

## Database Stats
- **30 Users** (3 admins, 5 teachers, 22 students)
- **11 Sessions** (various statuses)
- **27 Attendance Records**

---

## Quick Reset
```bash
cd backend
php artisan migrate:fresh --seed
```

---

## Need Help?
See `ACADEMIC_READY.md` in `.gemini/antigravity/brain/` folder for complete guide.

**Status**: ✅ 100% Ready for Academic Defense!
