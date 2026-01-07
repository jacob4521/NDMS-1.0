# NDMS 1.0 - Sri Lankan eID System (PHP + MySQL)

## 🚀 Quick Setup Guide

### Prerequisites
- WAMP Server installed and running
- PHP QR Code library (download separately)

### Installation Steps

1. **Start WAMP Server**
   - Make sure Apache and MySQL are running

2. **Setup Database**
   - Open phpMyAdmin (http://localhost/phpmyadmin)
   - Import `database_setup.sql` to create the database and tables
   - Or copy-paste the SQL commands

3. **Install PHP QR Code Library**
   - Download from: https://sourceforge.net/projects/phpqrcode/
   - Extract to `includes/phpqrcode/` folder
   - Ensure `qrlib.php` exists in `includes/phpqrcode/qrlib.php`

4. **Place Project Files**
   - Copy all files to `C:\wamp64\www\ndms\` (or your WAMP www directory)

5. **Access the System**
   - Visit: http://localhost/ndms/login.php

### Default Login Credentials

| Role | Username | Password |
|------|----------|----------|
| Admin | admin | admin123 |
| Medical Officer | doctor1 | doc123 |
| Education Officer | teacher1 | edu123 |
| Employer | employer1 | emp123 |

### Features Included

✅ **User Authentication System**
- Role-based login (Admin, Medical, Education, Employer)
- Session management
- Secure password hashing

✅ **Citizen Registration**
- Personal information capture
- QR code generation for each citizen
- Unique citizen ID assignment

✅ **Role-Based Dashboard**
- Different access levels per role
- Expandable for future features

✅ **Database Structure**
- Citizens table
- Medical records
- Education records
- Employment records
- Users for authentication

### Next Steps for Your Group

1. **Enhance UI/UX**
   - Add CSS/Bootstrap styling
   - Improve form layouts
   - Add responsive design

2. **Expand Functionality**
   - Medical record management
   - Education certificate tracking
   - Employment verification
   - QR code scanning

3. **Add Security Features**
   - Input validation
   - SQL injection protection
   - Password strength requirements
   - Session timeout

4. **Integration Features**
   - Email notifications
   - PDF report generation
   - Data export/import
   - API endpoints

### Troubleshooting

**Database Connection Issues:**
- Check WAMP is running
- Verify database name is 'ndms'
- Ensure MySQL credentials in config.php

**QR Code Not Generating:**
- Download PHP QR Code library
- Check folder permissions for 'qr/' directory
- Verify qrlib.php path

**Access Denied:**
- Clear browser cache
- Check session settings
- Verify user credentials

### File Structure
```
NDMS/
├── config.php          # Database connection
├── login.php           # User authentication
├── register.php        # Citizen registration
├── dashboard.php       # Role-based dashboard
├── database_setup.sql  # Database schema
├── qr/                 # QR code storage
└── includes/
    ├── auth.php        # Authentication helpers
    └── phpqrcode/      # QR code library (download separately)
```

---
**Good luck with your Final Project! 🇱🇰**
