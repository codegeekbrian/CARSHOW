# Admin Panel Setup Guide

## Initial Setup

1. The admin panel is pre-configured with default credentials:
   - Username: `admin`
   - Password: `password123`

2. For production use, change these credentials in `config.php`

## Changing Admin Credentials

To change the admin credentials:

1. Open `admin/config.php` in a text editor
2. Change the `ADMIN_USERNAME` value to your desired username
3. Generate a new password hash by running:

```php
<?php
echo password_hash('your_new_password', PASSWORD_DEFAULT);
?>
```

4. Update `ADMIN_PASSWORD_HASH` with the generated hash

## Security Recommendations

1. Always change the default credentials in production
2. Use a strong password (at least 12 characters with mixed case, numbers, and symbols)
3. Consider changing the password regularly
4. Restrict access to the admin panel using IP whitelisting if possible
5. Enable HTTPS on your server for secure transmission of credentials

## Accessing the Admin Panel

Navigate to: `http://yourdomain.com/admin/`

Login with your admin credentials to access the dashboard.

## Troubleshooting

If you cannot log in:
1. Verify that `config.php` contains the correct username and password hash
2. Check that the database connection is working
3. Clear your browser cache and cookies
4. Ensure sessions are working correctly on your server

For further assistance, refer to the main project README.md file.