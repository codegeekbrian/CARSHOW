# 🏎️ CARSHOW — Car Showroom Website

![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)
![HTML5](https://img.shields.io/badge/HTML5-E34F26?style=for-the-badge&logo=html5&logoColor=white)
![CSS3](https://img.shields.io/badge/CSS3-1572B6?style=for-the-badge&logo=css3&logoColor=white)
![License](https://img.shields.io/badge/license-MIT-green?style=for-the-badge)

A **PHP-based car showroom website** for showcasing vehicles, managing product pages, and handling user interactions.  
Ideal for car dealerships, auto exhibitions, or personal projects demonstrating web development skills with PHP and SQLite.

---

## 📖 Table of Contents
- [Overview](#-overview)
- [Features](#-features)
- [Technologies Used](#-technologies-used)
- [Setup Instructions](#-setup-instructions)
- [Folder Structure](#-folder-structure)
- [License](#-license)

---

## 🧩 Overview
The **CARSHOW** website provides pages for displaying car listings, company information, contact forms, and user registration.  
It can be easily customized to suit a real dealership or used as a college project.

---

## 🚀 Features
✅ **Home Page (`index.php`)** — Highlights featured cars and promotions.  
✅ **About Page (`/about`)** — Details about the car company or dealership.  
✅ **Inventory Page (`/inventory`)** — Displays available cars, prices, and specifications with advanced filtering.  
✅ **Contact Page (`/contact`)** — Lets visitors send messages or inquiries.  
✅ **Legacy Pages (`/legacy/*`)** — Dynamically generated pages for legendary vehicles.  
✅ **User Authentication** — Registration (`/register`) and login (`/login`) system.  
✅ **Admin Dashboard (`/admin`)** — Secure admin panel for managing cars, brands, and legacy details.  

---

## 🛠️ Technologies Used
- **Frontend:** HTML5, CSS3, JavaScript  
- **Backend:** PHP (Core PHP)  
- **Database:** SQLite  
- **Server:** Apache (via XAMPP, WAMP, or LAMP)

---

## ⚙️ Setup Instructions

1. **Install a Local Server Environment**
   - Download and install [XAMPP](https://www.apachefriends.org/) or [WAMP](https://www.wampserver.com/).

2. **Extract and Move the Project**
   - Copy the `CARSHOW` folder into:
     ```
     C:\xampp\htdocs\
     ```
     or for WAMP:
     ```
     C:\wamp\www\
     ```

3. **Enable mod_rewrite (Apache)**
   - Ensure `mod_rewrite` is enabled in your Apache configuration
   - Uncomment `LoadModule rewrite_module modules/mod_rewrite.so` in `httpd.conf`

4. **Start Apache**
   - Launch the control panel and start the Apache service.

5. **Access the Website**
   - Open your browser and navigate to:
     ```
     http://localhost/CARSHOW/
     ```

6. **Set Up the Database**
   - Run the following scripts in order:
     ```
     php tools/init_db.php
     php tools/seed_db.php
     php tools/seed_legacy_details.php
     ```

## 🔐 Admin Panel Access

To access the admin panel:
1. Navigate to `http://localhost/CARSHOW/admin/`
2. Login with the default credentials:
   - Username: `admin`
   - Password: `password123`

⚠️ **Important**: For production use, see `admin/SETUP.md` for instructions on securing your admin panel.

---

## 🧱 Folder Structure

```
CARSHOW/
├── admin/                 # Admin panel
│   ├── include/           # Admin includes (header, footer)
│   ├── admin_db.php       # Admin auth & session management
│   ├── config.php         # Admin credentials
│   ├── config.sample.php  # Config template
│   ├── login.php          # Admin login
│   ├── logout.php         # Admin logout
│   ├── index.php          # Dashboard
│   ├── cars.php           # Manage vehicles
│   ├── brands.php         # Manage brands
│   ├── legacy.php         # Manage legacy details
│   └── SETUP.md           # Admin setup guide
├── assets/                # Static assets
│   ├── css/               # Stylesheets (main.css, admin.css)
│   ├── js/                # JavaScript
│   └── images/            # Car images
├── data/                  # SQLite database
├── include/               # PHP includes (db, cache, csrf, nav, footer)
├── pages/                 # Page templates
├── tools/                 # Database scripts
├── .htaccess              # Apache URL rewriting
└── README.md              # This file
```

---

## 🔧 Database Schema

- **brands** — id, name, logo_url
- **vehicles** — id, brand_id, model, year, engine, power, drive, description, image_url, **is_legacy**, is_inventory
- **legacy_details** — id, vehicle_id, generation_name, years, history_text, image_url

---

## 🛡️ Security Features

- CSRF protection on all admin forms
- Prepared statements (SQL injection prevention)
- Password hashing with `password_hash()`
- HTTPS redirect enforced via `.htaccess`
- Security headers (X-Content-Type-Options, X-Frame-Options)

---

## ⚡ Performance

- File-based caching for legacy pages
- Gzip compression for text assets
- Cache headers for static assets (CSS, JS, images)
- Lazy loading on all images

---

## 📄 License

MIT License — see LICENSE file for details.