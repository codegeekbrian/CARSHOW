# Admin Panel Setup Guide

## Credentials (required)

Secrets are **not** stored in source files. Set them as server environment variables in production. For local development only, you may use a `.env` file at the repo root.

1. Copy the example file:
   ```powershell
   copy .env.example .env
   ```
2. Generate a password hash:
   ```powershell
   php -r "echo password_hash('your_strong_password', PASSWORD_DEFAULT);"
   ```
3. Paste the hash into your environment variables or local `.env`:
   ```
   ADMIN_USERNAME=admin
   ADMIN_PASSWORD_HASH=<paste hash here>
   ```

`.env` is gitignored and must not be deployed. Never commit real credentials.

## Security features (Phase 2)

- CSRF on admin login and message delete
- `session_regenerate_id()` on successful admin login
- Session idle timeout (`SESSION_TIMEOUT` in `.env`, default 7200 seconds)
- Secure logout clears session cookie

## Access

Navigate to: `http://yourdomain.com/admin/`

## Troubleshooting

| Problem | Fix |
|---------|-----|
| "Admin not configured" on login | Set `ADMIN_PASSWORD_HASH` in the server environment or local `.env` |
| Session expired | Log in again; adjust `SESSION_TIMEOUT` if needed |
| Cannot log in | Verify hash matches password; clear browser cookies |

See `.env.example` for supported environment variables.
