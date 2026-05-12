# 🏎️ CARSHOW — Car Showroom Website

![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)
![HTML5](https://img.shields.io/badge/HTML5-E34F26?style=for-the-badge&logo=html5&logoColor=white)
![CSS3](https://img.shields.io/badge/CSS3-1572B6?style=for-the-badge&logo=css3&logoColor=white)
![License](https://img.shields.io/badge/license-MIT-green?style=for-the-badge)

A simple **PHP-based car showroom website** for showcasing vehicles, managing product pages, and handling user interactions.  
It’s ideal for car dealerships, auto exhibitions, or personal projects demonstrating web development skills with PHP and MySQL.

---

## 📖 Table of Contents
- [Overview](#-overview)
- [Features](#-features)
- [Technologies Used](#-technologies-used)
- [Setup Instructions](#-setup-instructions)
- [Folder Structure](#-folder-structure)
- [Screenshots](#-screenshots)
- [Future Improvements](#-future-improvements)
- [License](#-license)
- [Author](#-author)

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

4. **Start Apache & MySQL**
   - Launch the control panel and start both services.

5. **Access the Website**
   - Open your browser and navigate to:
     ```
     http://localhost/CARSHOW/
     ```

6. **(Optional) Set Up the Database**
   - Open **phpMyAdmin**.
   - Create a new database (e.g. `carshow_db`).
   - Import the SQL file if provided in your project.

## 🔐 Admin Panel Access

To access the admin panel:
1. Navigate to `http://localhost/CARSHOW/admin/`
2. Login with the default credentials:
   - Username: `admin`
   - Password: `password123`
   
⚠️ **Important**: For production use, see `admin/SETUP.md` for instructions on securing your admin panel.

---


## 🧱 Folder Structure

