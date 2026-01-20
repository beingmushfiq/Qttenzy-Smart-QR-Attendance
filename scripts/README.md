# Setup Scripts
## Qttenzy - Platform-Specific Setup Scripts

---

## 📋 Available Scripts

### Windows Scripts

1. **setup.ps1** - PowerShell script (Recommended for Windows)
   ```powershell
   .\scripts\setup.ps1
   ```

2. **setup.bat** - Command Prompt script
   ```cmd
   scripts\setup.bat
   ```

### Unix/Linux/Mac Scripts

3. **setup.sh** - Bash script (Linux/Mac/Git Bash)
   ```bash
   chmod +x scripts/setup.sh
   ./scripts/setup.sh
   # OR (no chmod needed on Windows Git Bash)
   bash scripts/setup.sh
   ```

---

## 🪟 Windows Users

**You don't need `chmod` on Windows!**

- Use `setup.ps1` for PowerShell
- Use `setup.bat` for Command Prompt
- Use `setup.sh` with Git Bash (no chmod needed)

---

## 🐧 Linux/Mac Users

Use the `.sh` script:
```bash
chmod +x scripts/setup.sh
./scripts/setup.sh
```

---

## 📝 What the Scripts Do

1. Check prerequisites (Node.js, PHP, Composer, MySQL)
2. Install frontend dependencies (`npm install`)
3. Install backend dependencies (`composer install`)
4. Create `.env` files from `.env.example`
5. Generate Laravel application key
6. Generate JWT secret
7. Create necessary directories
8. Display next steps

---

## ⚠️ Important Notes

- **Database setup is manual** - You need to:
  1. Create MySQL database
  2. Update `backend/.env` with database credentials
  3. Run `php artisan migrate`
  4. Run `php artisan db:seed`

- **Environment files** - Edit `.env` files after setup:
  - `frontend/.env` - API URL
  - `backend/.env` - Database credentials, JWT secret, etc.

---

## 🔧 Troubleshooting

### Windows PowerShell: "Script execution is disabled"
```powershell
Set-ExecutionPolicy -ExecutionPolicy RemoteSigned -Scope CurrentUser
```

### Windows: "Command not found"
- Make sure Node.js, PHP, and Composer are in your PATH
- Restart terminal after installing

### Linux/Mac: "Permission denied"
```bash
chmod +x scripts/setup.sh
```

---

## 📚 See Also

- [WINDOWS_SETUP.md](../WINDOWS_SETUP.md) - Windows-specific guide
- [QUICK_START.md](../QUICK_START.md) - Quick start guide
- [README.md](../README.md) - Main documentation

