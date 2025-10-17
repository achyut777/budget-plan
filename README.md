# Budget Planner

A complete personal finance web application (PHP + MySQL) for tracking income, expenses, budgets, and goals. This repository contains the application code (under `Cursor Web/`), APIs, SQL schemas and supporting documentation.

---

## Quick summary

- Project: Budget Planner
- Primary language: PHP (7.4+)
- Database: MySQL / MariaDB
- UI: Bootstrap
- Main application folder: `Cursor Web/`

This README covers how to get started locally (Windows/XAMPP), how to configure the app, where to find key scripts, and troubleshooting pointers. More detailed end-user and developer docs live in `documentation/README.md` — use that as the canonical guide for features and advanced usage.

## Contents of this repo

- `Cursor Web/` — main PHP application (public-facing pages, admin panel, install scripts)
- `api/` — REST-like API endpoints used by the frontend
- `assets/` — CSS, JS and image assets
- `config/` — application configuration files (database, email, init scripts)
- `sql/` — SQL schemas and helpers (e.g. `remember_tokens.sql`)
- `documentation/` — detailed documentation and guides
- `logs/` — runtime logs (created at runtime, ensure webserver write access)
- `LICENSE` — project license (MIT)

## Minimum requirements

- PHP 7.4 or later (mysqli, json, openssl recommended)
- MySQL 8.0+ or compatible MariaDB
- Apache or Nginx (or a local dev server such as XAMPP/WAMP)
- Composer is optional (project doesn't require it by default)

## Quick start (Windows + XAMPP)

1. Install XAMPP (or similar) and start Apache + MySQL.
2. Copy or clone the repository into your web root (for XAMPP, typically `C:\xampp\htdocs\`). Example in PowerShell:

```powershell
# clone into your workspace (example)
git clone https://github.com/achyut777/budget-plan.git

# copy to XAMPP htdocs (adjust paths as needed)
Copy-Item -Path .\budget-plan\* -Destination 'C:\xampp\htdocs\budget-plan' -Recurse
```

3. Run the installer in a browser:

Open: http://localhost/budget-plan/Cursor%20Web/install.php

The installer will:

- Check server requirements
- Save DB configuration to `Cursor Web/config/database.php`
- Initialize the database tables and (optionally) sample data

Alternatively, for a minimal database creation you can run (PowerShell in repository root):

```powershell
# Run PHP script that creates the database and tables (may output to terminal)
php "Cursor Web/create_database.php"
```

Note: The installer is intentionally restricted to localhost for security. After installation remove `Cursor Web/install.php`.

## Configuration

- Database: `Cursor Web/config/database.php` — set host, username, password, database name and port.
- Email: `Cursor Web/config/email_config.php` — configure SMTP or from-address for production.
- Init DB: `Cursor Web/config/init_db.php` — contains table creation and seed logic used by the installer.

Make sure the webserver user can write to `Cursor Web/logs/` so email logs and app logs can be stored during development.

## Default / demo accounts

- Admin: `admin@budgetplanner.com` / `admin123` (created by the initializer)
- Demo user: `demo@example.com` / `demo12345`

Change default passwords immediately on production and remove the installer file.

## Running and testing the app

- Development: open the site in a browser on localhost.
- Email testing: on dev, emails are saved to `Cursor Web/logs/emails/` and a simple viewer exists at `Cursor Web/email_viewer.php`.
- Reset DB: run `Cursor Web/config/init_db.php` (via the installer interface or directly) to recreate tables + sample data.

## Important scripts & files

- `Cursor Web/install.php` — installation wizard (interactive)
- `Cursor Web/create_database.php` — script to create the database and tables from CLI or browser
- `sql/remember_tokens.sql` — example SQL for persistent login tokens
- `Cursor Web/reset_database.php` — reset script (if present)

## Security notes (deployment checklist)

- Remove or secure `Cursor Web/install.php` after installing.
- Set strong, unique passwords for DB and admin accounts.
- Use HTTPS in production and configure your server for secure headers.
- Configure proper file permissions (logs writable by the webserver but code files not world-writable).
- Regular backups of the MySQL database (use `mysqldump` or managed backups).

Refer to `documentation/DEPLOYMENT-CHECKLIST-CURSOR.md` for a detailed checklist.

## Troubleshooting (common issues)

- Database connection failed: verify credentials in `Cursor Web/config/database.php` and ensure MySQL service is running.
- Emails appear not to send: check `Cursor Web/logs/emails/` (development) and `Cursor Web/config/email_config.php` for production SMTP settings.
- Permission errors: grant appropriate write permissions for the `logs/` directory.

## Development & contributing

We welcome contributions. Please follow the guidelines in `documentation/README.md` and:

1. Fork the repo
2. Create a feature branch
3. Run and test locally
4. Submit a pull request with a clear description and tests (if applicable)

Code style & safety

- Use prepared statements and input validation for DB queries.
- Escape output in templates to prevent XSS.
- Add migrations or update `Cursor Web/config/init_db.php` when changing schema.

## Where to find more documentation

- Full user & developer documentation: `documentation/README.md`
- Deployment checklist: `documentation/DEPLOYMENT-CHECKLIST-CURSOR.md`
- Export guide: `documentation/EXPORT-DOCUMENTATION.md`

## License

This project is licensed under the MIT License. See the `LICENSE` file for details.

---

If you'd like, I can also:

- Copy parts of the `documentation/README.md` into this top-level README or keep it as a short landing with links to `documentation/` (recommended).
- Add a short `CONTRIBUTING.md` and `CODE_OF_CONDUCT.md`.

Made with care — enjoy exploring and improving your finances.
