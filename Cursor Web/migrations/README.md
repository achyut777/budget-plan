Migrations folder

This directory contains SQL migrations for the Budget Planner application.

- V2025_10_17__add_email_verification.sql
  Adds email verification-related columns and indexes to the `users` table.

Usage:
- Preferred: Run `sql/apply_email_verification_migration.php` which will safely add columns only if missing.
- Manual: Run the SQL file in phpMyAdmin or a MySQL client. Review the statements before running.

Notes:
- Keep migrations idempotent in your workflow; this repository keeps a PHP helper to safely apply them on local installs.
