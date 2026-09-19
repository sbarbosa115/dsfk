# Deployment on cPanel

The server pulls the code from GitHub and builds it itself with `deploy/cpanel-update.sh`.
There is no other way to deploy.

| | Path |
|---|---|
| App folder | `/home/lentti/public_html/dsfk` |
| Web root (`omaha.lentti.shop` → Document Root) | `/home/lentti/public_html/dsfk/public` |
| Build clone (outside `public_html`) | `/home/lentti/dsfk-src` |
| Build workspace (staging, Composer) | `/home/lentti/dsfk-build` |
| Backups | `/home/lentti/backups/control-proyectos` |

The app folder is inside `public_html`, but its `.htaccess` refuses all web access. Only `public/`
is served.

---

## 0. What you need from the host

| Requirement | Where in cPanel |
|---|---|
| **SSH** or **Terminal** | *SSH Access* / *Terminal* |
| PHP **8.4** (8.4.1+) for the domain (*PHP 8.4 (ea-php84)*) | *MultiPHP Manager* |
| PHP extensions: `pdo_mysql`, `intl`, `mbstring`, `fileinfo`, `ctype`, `iconv`, `openssl`, `xml`, `opcache` (recommended) | *Select PHP Version → Extensions* (CloudLinux) or ask the host |
| `upload_max_filesize` and `post_max_size` ≥ **12M** | *MultiPHP INI Editor* |
| **Node.js 20.19+ or 22.12+** (for building the frontend) | *Setup Node.js App* (installs under `/opt/alt/alt-nodejsNN`) |
| A MySQL/MariaDB database | *MySQL® Databases* |
| An email account for sending notifications | *Email Accounts* |
| Cron jobs (every minute is best; every 5 minutes also works) | *Cron Jobs* |

The script finds PHP 8.4 (`/opt/cpanel/ea-php84/root/usr/bin/php`) and Node on its own. It also
downloads Composer. To use other binaries, set `PHP=...`, `NODE_DIR=...` or `COMPOSER=...`. In the
cron jobs below, if `php -v` shows a version older than 8.4, use the full PHP path.

---

## 1. First installation

### 1.1 Database
1. *MySQL® Databases* → create a database (e.g. `lentti_obras`) and a user (e.g. `lentti_app`)
   with a strong password.
2. *Add User To Database* → **ALL PRIVILEGES**.
3. Note the server version shown in cPanel (e.g. MariaDB 10.6.x or MySQL 8.0.x).

### 1.2 Email account
*Email Accounts* → create `no-reply@lentti.shop`. The SMTP server and port are listed under
*Connect Devices* (usually `mail.lentti.shop`, port 465, SSL).

### 1.3 GitHub access
Give the server read-only access to the repository:
```bash
ssh-keygen -t ed25519 -f ~/.ssh/dsfk_deploy -N ""
cat ~/.ssh/dsfk_deploy.pub        # GitHub → repository → Settings → Deploy keys → Add (read-only)
printf 'Host github.com\n  IdentityFile ~/.ssh/dsfk_deploy\n  IdentitiesOnly yes\n' >> ~/.ssh/config
git clone git@github.com:sbarbosa115/dsfk.git ~/dsfk-src
```
The clone is only a build workspace. Every deployment resets it, so never edit files there.

### 1.4 Configuration
```bash
~/dsfk-src/deploy/cpanel-update.sh
```
The first run creates `public_html/dsfk/.env.local` (permissions 600) and stops. Edit it:

| Variable | Value |
|---|---|
| `APP_SECRET` | 32 random characters: `php -r 'echo bin2hex(random_bytes(16));'` |
| `DATABASE_URL` | user, password and database from 1.1. Use `serverVersion=mariadb-10.6.0` or `serverVersion=8.0` to match your server |
| `MAILER_DSN` | the account from 1.2. URL-encode `@` as `%40` and other special characters in the password |
| `MAILER_FROM` | `Control de Proyectos <no-reply@lentti.shop>` |
| `APP_URL` / `DEFAULT_URI` | `https://omaha.lentti.shop` |

### 1.5 Install
```bash
~/dsfk-src/deploy/cpanel-update.sh
```
It ends with the checklist from `app:doctor`, where everything must be **OK**. The OPcache line is
informational only.

### 1.6 First admin user
```bash
cd ~/public_html/dsfk && php bin/console app:create-admin you@lentti.shop "Your Name"
```
Log in and change the password with the key icon in the top bar (*Cambiar contraseña*).

### 1.7 HTTPS
*SSL/TLS Status* → run AutoSSL for `omaha.lentti.shop`. Once `https://` works, uncomment the two
"Force HTTPS" lines in `backend/public/.htaccess` and deploy. Commit that change to the repository, so
the next deployment doesn't undo it.

---

## 2. Cron jobs (permanent)

*Cron Jobs* → add these three. Replace `php` with the full path if needed (see section 0).

| Schedule | Command | Purpose |
|---|---|---|
| `* * * * *` | `cd $HOME/public_html/dsfk && php bin/console messenger:consume async --time-limit=50 --memory-limit=128M -q >/dev/null 2>&1` | Sends queued emails |
| `0 7 * * *` | `cd $HOME/public_html/dsfk && php bin/console app:alerts:daily -q >/dev/null 2>&1` | Daily 7am summary |
| `30 2 * * *` | `$HOME/public_html/dsfk/deploy/backup.sh >/dev/null 2>&1` | Database and files backup |

If the host doesn't allow cron every minute, use `*/5 * * * *` for the first one. Emails will just
arrive up to 5 minutes later.

---

## 3. Deploying a new version

Push to GitHub, then on the server:
```bash
~/dsfk-src/deploy/cpanel-update.sh           # latest main
~/dsfk-src/deploy/cpanel-update.sh v1.2.0    # a tag, branch or commit
```
It does, in order:
1. fetches the ref and builds the frontend (`npm ci`, `tsc`, `vite build`);
2. installs PHP dependencies without dev tools in `~/dsfk-build/stage`, checks they load on PHP 8.4,
   and writes a `MANIFEST` of every shipped file;
3. copies the release into the app folder. `.env.local` and `var/` (uploads, logs, sessions) are
   never touched;
4. runs `deploy/update.sh`: backs up the database and uploads, deletes code files not in the
   `MANIFEST`, rebuilds the cache, runs migrations, and runs the checks.

To roll back, deploy the previous tag. Migrations are not reverted, so restore the backup taken in
step 4 if a migration must be undone. The site keeps running during a deployment. For large changes,
deploy outside working hours. Only one deployment can run at a time.

---

## 4. Backups

- `deploy/backup.sh` writes `db-<date>.sql.gz` and `uploads-<date>.tar.gz` to
  `~/backups/control-proyectos/` and keeps 14 days. Change this with `BACKUP_DIR` and `KEEP_DAYS`.
- **Uploaded receipts and proofs live in `public_html/dsfk/var/uploads/`.** They are as important
  as the database.
- Once a month, copy a backup off the server: download it from *File Manager*, or use cPanel's own
  *Backup* feature.
- **Restore:** in *phpMyAdmin*, import the `.sql.gz` into an empty database. Extract
  `uploads-*.tar.gz` into `public_html/dsfk/var/`.

---

## 5. Troubleshooting

| Symptom | Check |
|---|---|
| Deployment stops at "Node.js … not found" | Install Node in *Setup Node.js App*, or set `NODE_DIR` to the folder with `node` |
| Frontend build is killed (out of memory) | The host's per-account memory limit is too low: ask the host to raise it |
| `git fetch` asks for a password or is denied | The deploy key (1.3) is missing on GitHub, or not in `~/.ssh/config` |
| Blank page or error 500 | `public_html/dsfk/var/log/prod-<date>.log`, and the domain's PHP version in *MultiPHP Manager* |
| "Frontend not built" | `public/app/index.html` is missing: run the deployment again |
| 403 on every page | The Document Root of `omaha.lentti.shop` must be `public_html/dsfk/public`, not `public_html/dsfk` |
| Emails never arrive | Is the `messenger:consume` cron running? Is `MAILER_DSN` correct? Also check `php bin/console messenger:failed:show` |
| Login works, then you're logged out immediately | `var/sessions` must be writable. Run `php bin/console app:doctor` |
| Upload rejected as too large | The PHP limits `upload_max_filesize` and `post_max_size` must be at least 12M (*MultiPHP INI Editor*) |
