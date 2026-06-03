# Own It & Drive It

Own It & Drive It is a PHP car showroom app for browsing vehicles, sending inquiries, booking viewing appointments, and managing inventory from an admin panel.

![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![HTML5](https://img.shields.io/badge/HTML5-E34F26?style=for-the-badge&logo=html5&logoColor=white)
![CSS3](https://img.shields.io/badge/CSS3-1572B6?style=for-the-badge&logo=css3&logoColor=white)

## Features

- Vehicle and brand management from the admin area.
- Vehicle inquiry and contact message tracking.
- Viewing appointment requests tied to branches and vehicles.
- Customer registration, login, and profile pages.
- Admin dashboard with inventory and visitor metrics.
- Tagged file cache for repeated page data.
- CSRF checks, prepared statements, and session controls.

## Requirements

- PHP 7.4 or newer
- MySQL or MariaDB
- A web server that can run PHP, or the built-in PHP server for local development

## Local Setup

1. Create a MySQL or MariaDB database.
2. Copy `.env.example` to `.env`.
3. Update the database, site, mail, and admin values in `.env`.
4. Generate the admin password hash with:

   ```bash
   php -r "echo password_hash('your_password', PASSWORD_DEFAULT);"
   ```

5. Start the local server:

   ```bash
   php -S localhost:3000 router.php
   ```

6. Open `http://localhost:3000/`.

## Publishing Notes

- Keep `.env`, SQL dumps, local databases, logs, backups, cache files, and test output out of Git.
- Keep real credentials in the server environment or in a private `.env` file.
- Deploy the app source, public assets, `data/.htaccess`, and any runtime folders your server needs.
