# Deployment on cPanel shared hosting

The app is one folder, `control-proyectos/`, placed **outside** `public_html`. Only its `public/`
subfolder is exposed to the web. Everything below uses menu names from the cPanel interface.

Tested setups: PHP 8.2 and 8.3; MySQL 8.0 and MariaDB 10.6; with or without SSH; with the domain
pointing to `public/`, or with a fixed `public_html`.

---

## 0. What you need from the host

| Requirement | Where to check in cPanel |
|---|---|
| PHP **8.2 or newer** for the domain | *MultiPHP Manager* |
| PHP extensions: `pdo_mysql`, `intl`, `mbstring`, `fileinfo`, `ctype`, `iconv`, `openssl`, `xml`, `opcache` (recommended) | *Select PHP Version → Extensions* (CloudLinux) or ask the host |
| A MySQL/MariaDB database | *MySQL® Databases* |
| An email account for sending notifications | *Email Accounts* |
| Cron jobs (every minute is best; every 5 minutes also works) | *Cron Jobs* |
| A way to run commands: **SSH**, **Terminal**, or a **one-time cron job** | *Terminal* / *SSH Access* |

> **PHP on the command line.** The `php` command in Terminal or cron may be a different version
> from the one the website uses. Check it with `php -v`. If it's older than 8.2, use the full
> path instead, usually `/opt/cpanel/ea-php82/root/usr/bin/php` (or `ea-php83`). The scripts
> read it from the `PHP` variable: `PHP=/opt/cpanel/ea-php82/root/usr/bin/php deploy/update.sh`.

---

## 1. Build the release (on your computer)

```bash
deploy/build-release.sh 1.0.0
# → build/control-proyectos-1.0.0.zip
```

The script builds the React app, installs the PHP dependencies without development tools, and
checks that they run on PHP 8.2. It also writes a `MANIFEST` listing every file it ships, which
`update.sh` uses later to delete files a newer version no longer has.

---

## 2. First installation

### 2.1 Database
1. *MySQL® Databases* → create a database (e.g. `cpuser_obras`) and a user (e.g. `cpuser_app`)
   with a strong password.
2. *Add User To Database* → **ALL PRIVILEGES**.
3. Note the server version shown in cPanel (e.g. MariaDB 10.6.x or MySQL 8.0.x).

### 2.2 Email account
*Email Accounts* → create `no-reply@yourdomain.com`. The SMTP server and port are listed under
*Connect Devices* (usually `mail.yourdomain.com`, port 465, SSL).

### 2.3 Upload the files
1. *File Manager* → your home folder (`/home/cpuser`, **not** `public_html`) → *Upload*
   `control-proyectos-1.0.0.zip`.
2. Right-click → *Extract*. You get `/home/cpuser/control-proyectos/`.

### 2.4 Configuration
In `control-proyectos/deploy/` copy `env.local.example` to **`control-proyectos/.env.local`**
(the app folder, not `deploy/`), then edit it:

| Variable | Value |
|---|---|
| `APP_SECRET` | 32 random characters |
| `DATABASE_URL` | user, password and database from 2.1. Use `serverVersion=mariadb-10.6.0` or `serverVersion=8.0` to match your server |
| `MAILER_DSN` | the account from 2.2. URL-encode `@` as `%40` and other special characters in the password |
| `MAILER_FROM` | e.g. `Control de Proyectos <no-reply@yourdomain.com>` |
| `APP_URL` / `DEFAULT_URI` | the public address, e.g. `https://obras.yourdomain.com` |

Set the file's permissions to **600** (*File Manager → Permissions*).

### 2.5 Point the web address to the app

**Option A (recommended): a subdomain or addon domain**
*Domains* → *Create A New Domain* (e.g. `obras.yourdomain.com`), and set its **Document Root** to
`control-proyectos/public`.

**Option B: the domain must use `public_html`**
Leave `public_html` alone. In step 2.6, run the script with `PUBLIC_DIR=$HOME/public_html`. It
copies the web files there, plus a small `index.php` that forwards to the app. Other files in
`public_html` are not touched, but `index.php`, `.htaccess` and `app/` are replaced.

### 2.6 Install: database tables, cache and checks
Run **one** of the following.

*With SSH or cPanel Terminal:*
```bash
cd ~/control-proyectos
deploy/update.sh                                   # option A
PUBLIC_DIR=$HOME/public_html deploy/update.sh      # option B
```

*Without a terminal:* go to *Cron Jobs* and add a job scheduled for the next minute:
```
cd $HOME/control-proyectos && deploy/update.sh > $HOME/update.log 2>&1
```
Wait two minutes, check `update.log` in *File Manager*, then **delete the cron job**.

The last step prints a checklist (`app:doctor`). Everything must be **OK**. The OPcache line is
informational only.

### 2.7 First admin user
```bash
php bin/console app:create-admin you@yourdomain.com "Your Name"
```
Without a terminal, use a one-time cron job again, with `--generate-password`:
```
cd $HOME/control-proyectos && php bin/console app:create-admin you@yourdomain.com "Your Name" --generate-password > $HOME/admin.log 2>&1
```
The temporary password is in `admin.log`. **Delete that file** after logging in, and change the
password with the key icon in the top bar (*Cambiar contraseña*).

### 2.8 HTTPS
*SSL/TLS Status* → run AutoSSL for the domain. Once `https://` works, uncomment the two
"Force HTTPS" lines in `public/.htaccess` (and in `public_html/.htaccess` for option B).

---

## 3. Cron jobs (permanent)

*Cron Jobs* → add these three. Replace `php` with the full path if needed (see section 0).

| Schedule | Command | Purpose |
|---|---|---|
| `* * * * *` | `cd $HOME/control-proyectos && php bin/console messenger:consume async --time-limit=50 --memory-limit=128M -q >/dev/null 2>&1` | Sends queued emails |
| `0 7 * * *` | `cd $HOME/control-proyectos && php bin/console app:alerts:daily -q >/dev/null 2>&1` | Daily 7am summary |
| `30 2 * * *` | `$HOME/control-proyectos/deploy/backup.sh >/dev/null 2>&1` | Database and files backup |

If the host doesn't allow cron every minute, use `*/5 * * * *` for the first one. Emails will just
arrive up to 5 minutes later.

---

## 4. Updating to a new version

1. Build it: `deploy/build-release.sh 1.1.0`.
2. Upload the zip to your home folder and *Extract* it over the existing `control-proyectos`
   (overwrite files). Your `.env.local`, uploaded files and logs are not in the zip, so they stay.
3. Run `deploy/update.sh` (with `PUBLIC_DIR=...` for option B), by terminal or a one-time cron job
   as in 2.6. It does, in order:
   1. backs up the database and uploads;
   2. deletes files the new version no longer has;
   3. rebuilds the cache;
   4. runs database migrations;
   5. runs the checks.

The site keeps running during the update. For large changes, update outside working hours.

---

## 5. Backups

- `deploy/backup.sh` writes `db-<date>.sql.gz` and `uploads-<date>.tar.gz` to
  `~/backups/control-proyectos/` and keeps 14 days. Change this with `BACKUP_DIR` and `KEEP_DAYS`.
- **Uploaded receipts and proofs live in `control-proyectos/var/uploads/`.** They are as important
  as the database.
- Once a month, copy a backup off the server: download it from *File Manager*, or use cPanel's own
  *Backup* feature.
- **Restore:** in *phpMyAdmin*, import the `.sql.gz` into an empty database. Extract
  `uploads-*.tar.gz` into `control-proyectos/var/`.

---

## 6. Troubleshooting

| Symptom | Check |
|---|---|
| Blank page or error 500 | `control-proyectos/var/log/prod-<date>.log`, and the domain's PHP version in *MultiPHP Manager* |
| "Frontend not built" | `public/app/index.html` is missing: re-extract the zip |
| 403 on every page | The Document Root must be `control-proyectos/public` (option A). Never the app folder itself |
| Emails never arrive | Is the `messenger:consume` cron running? Is `MAILER_DSN` correct? Also check `php bin/console messenger:failed:show` |
| Login works, then you're logged out immediately | `var/sessions` must be writable. Run `php bin/console app:doctor` |
| Upload rejected as too large | The PHP limits `upload_max_filesize` and `post_max_size` must be at least 12M (*MultiPHP INI Editor*) |
