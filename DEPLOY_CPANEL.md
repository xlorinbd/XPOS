# Khan Gadget POS - cPanel shared hosting checklist

Written for the developer who installs it. Keep the client's real values (company address, phones, SMTP, WhatsApp) out of the repository; enter them in the admin screens after install.

## 1. Hosting requirements
- PHP 8.2 (cPanel > MultiPHP Manager) with: `gd`, `mbstring`, `pdo_mysql`, `zip`, `openssl`, `curl`, `fileinfo`, `bcmath`, `intl` (PDF and invoices need `gd`).
- MySQL 5.7+/MariaDB 10.4+, one database and user.
- `memory_limit` 256M, `max_execution_time` 120 (PDF, big reports).
- Cron access (any cPanel plan has it).

## 2. Files
1. Upload the project **outside** `public_html` (for example `/home/USER/khangadget`) and point the domain / sub-domain document root to `/home/USER/khangadget/public`.
   If the host cannot change the document root, put the contents of `public/` in `public_html` and edit `index.php` so the two `require` lines point to `../khangadget/...`.
2. `vendor/` must be uploaded (or run `composer install --no-dev --optimize-autoloader` if SSH is available).
3. Writable folders: `storage/` (all sub folders) and `bootstrap/cache/` -> permission 775.
4. `public/logo/` must be writable (company logo upload).

## 3. `.env` (production values)
```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain
APP_KEY=            # php artisan key:generate --force (only once; never change it later, one-time codes depend on it)
DB_HOST=localhost
DB_DATABASE=...
DB_USERNAME=...
DB_PASSWORD=...
CACHE_DRIVER=file
SESSION_DRIVER=file
USER_VERIFIED=1     # turns the vendor demo mode off
# ALLOW_DEMO_DB_RESET must NOT be set. That command drops every table.
```

## 4. First install
```
php artisan migrate --force
php artisan db:seed --class=KhanGadgetSetupSeeder --force   # roles, 12 branches, accounts, lists, currency (BDT), company placeholders. Dummy users are created only when APP_ENV=local.
php artisan storage:link
php artisan config:cache && php artisan route:cache && php artisan view:cache
```
The seeder can be run again later; it does not overwrite what the shop already changed (roles that were edited, accounts, prices).
After every code update run `php artisan migrate --force` and `php artisan cache:clear`.

## 5. Cron (day-end Daily Account PDF, scheduled tasks)
cPanel > Cron Jobs, every minute:
```
* * * * * /usr/local/bin/php /home/USER/khangadget/artisan schedule:run >> /dev/null 2>&1
```
The day-end job runs at 23:55 and saves one PDF per branch into `storage/app/daily-accounts/YYYY-MM-DD/`. It can also be run by hand: `php artisan kg:daily-account --date=2026-10-01`.
The vendor demo commands `reset:db` and `quote:daily` are no longer scheduled. Do not add them back.

## 6. Settings to fill in after install (Admin)
| Where | What |
|---|---|
| Settings > General Setting | company name, logo, timezone |
| Product > Warehouse | real address, phone, e-mail of every branch (shown in the invoice header) |
| Accounting > Account List | opening balances of the branch / main accounts |
| Settings > Mail Setting | the company SMTP account (staff loan one-time codes are sent by e-mail) |
| WhatsApp > Settings | phone number id and access token (notifications also go to WhatsApp) |
| HRM > Employee | e-mail and mobile of every staff member, basic salary, link to the login user |
| HRM > Holiday | company / public holidays (used for working days in the salary sheet) |
| People > Roles | tick the permissions of each role; `view-cost-profit` = who may see purchase price and profit |
| Settings > POS Setting | payment methods, default warehouse |

## 7. Backups and safety
- Daily MySQL dump from cPanel Backup Wizard or a cron `mysqldump`, and a weekly copy of `storage/app` and `public/logo` off the server.
- Change every seeded password; delete demo users if any exist.
- Keep `APP_DEBUG=false`. Errors are in `storage/logs/laravel.log`.

## 8. What "offline" means here
Shared hosting cannot run the POS without internet. The POS page keeps a sale that fails only because the connection dropped, shows "N offline sale(s) waiting", and sends it again when the connection returns (each sale carries a token, so it can never be saved twice). The browser must not be closed or its data cleared meanwhile. Printing of that invoice happens after it is sent.

## 9. Tests
`php artisan test --filter KhanGadget` (uses the `.env` database, every test is rolled back). Run it before each release; it covers cash transfers, gateway confirmation, pre-order advance, the daily account chain, salary rules and the border-price rule.
