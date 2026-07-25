# Production Deployment & Synchronization Workflow (Standard Operating Procedure)

This document establishes the official standard operating procedure (SOP) for pushing code updates to GitHub and deploying to the CloudPanel production environment for **HIGST101**.

---

## Architecture Overview

- **Repository**: `https://github.com/rasheedaljadi/HIGST101.git`
- **Production Server IP**: `76.13.79.242`
- **Server User**: `highest-ye`
- **CloudPanel Project Root**: `/home/highest-ye/htdocs/highest-ye.store`
- **PHP Version**: `PHP 8.3` (`php8.3` binary)

---

## 1. Quick One-Click Deployment Commands

### Command 1: Deploy & Sync Everything (Local -> GitHub -> Production)
From PowerShell in the project root directory:
```powershell
.\deploy_prod.ps1
```
**What this command does automatically:**
1. Staging and committing all local changes with descriptive message.
2. Pushing commits to GitHub `origin main`.
3. Connecting via SSH to production server `highest-ye@76.13.79.242`.
4. Performing an atomic code sync via `git fetch origin main` and `git reset --hard origin/main`.
5. Re-linking storage symlink (`public/storage` -> `storage/app/public/`).
6. Setting proper directory permissions (`chmod -R 775 storage public/storage`).
7. Clearing and rebuilding all application caches (`config:clear`, `cache:clear`, `route:clear`, `view:clear`).
8. Running full production readiness audit (`php8.3 artisan fulfillment:production-check`).

---

### Command 2: Push to GitHub Only
```powershell
.\push_to_github.ps1
```
Pushes local code commits to `https://github.com/rasheedaljadi/HIGST101.git`.

---

## 2. Server Background Services & Crontab Setup

### Laravel Scheduler (Crontab)
Registered in server crontab (`crontab -l`):
```bash
* * * * * cd /home/highest-ye/htdocs/highest-ye.store && php8.3 artisan schedule:run >> /dev/null 2>&1
```

### Automatic Sync Configuration in Database
To enable/check automatic sync in database:
```bash
php8.3 artisan tinker --execute="$s = \App\Models\AliExpressSetting::current(); $s->sync_enabled = true; $s->sync_schedule = 'hourly'; $s->save();"
```

---

## 3. Production Health Check Command

To verify database connection, provider health, polling, queue status, and dead letters at any time:
```bash
php8.3 artisan fulfillment:production-check
```

---

## 4. Immediate Manual Synchronization Trigger

To run product price and inventory sync immediately without waiting for the scheduled hour:
```bash
php8.3 artisan aliexpress:sync-products --all
```
