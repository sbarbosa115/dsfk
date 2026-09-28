# Deployment on cPanel

The app is a git clone on the account, updated in place by `deploy/cpanel-update.sh`: it pulls from GitHub,
installs the PHP dependencies, builds the UI, backs up and migrates the database when a migration is pending, and
ends with the `app:doctor` checklist.

| | Path |
|---|---|
| The app (git clone, outside `public_html`) | `/home/lentti/dsfk` |
| Web root (`omaha.lentti.shop` → Document Root) | `/home/lentti/dsfk/backend/public` |
| Settings (never committed) | `/home/lentti/dsfk/backend/.env.local` |
| Uploaded receipts and proofs | `/home/lentti/dsfk/backend/var/uploads` |
| Backups | `/home/lentti/backups/control-proyectos` |

Only `backend/public` is served; the code, `.env.local` and `var/` sit beside it, out of the web's reach.

---

## 0. What you need from the host

| Requirement | Where in cPanel |
|---|---|
| **SSH** or **Terminal** | *SSH Access* / *Terminal* |
| PHP **8.4** (8.4.1+) for the domain (*PHP 8.4 (ea-php84)*) | *MultiPHP Manager* |
| PHP extensions: `pdo_mysql`, `intl`, `mbstring`, `fileinfo` (the script stops without them), `ctype`, `iconv`, `openssl`, `xml`, `opcache` (recommended) | *Select PHP Version → Extensions* (CloudLinux) or ask the host |
| `upload_max_filesize` and `post_max_size` ≥ **12M** | *MultiPHP INI Editor* |
| **Node.js 20.19+ or 22.12+** (the server builds the UI) | *Setup Node.js App* (installs under `/opt/alt/alt-nodejsNN`) |
| **Composer** 2 on the command line | Usually preinstalled; otherwise put `composer.phar` in the home folder |
| A MySQL/MariaDB database, and `mysqldump` (for the backups) | *MySQL® Databases* |
| An email account for sending notifications | *Email Accounts* |
| A document root outside `public_html` for the domain | *Domains* |
| Cron jobs | *Cron Jobs* |

The script finds PHP 8.4 (`/opt/cpanel/ea-php84/root/usr/bin/php`), Composer and Node on its own. To use other
binaries, set `PHP=...`, `COMPOSER=...` or `NODE_DIR=...`. It also compares the command-line PHP with the one
cPanel gives the site (the handler line in `backend/public/.htaccess`) and warns when they differ.

---

## 1. First installation

### 1.1 Database
1. *MySQL® Databases* → create a database (e.g. `lentti_obras`) and a user (e.g. `lentti_app`) with a strong
   password, and leave the database **empty**: the migrations build it.
2. *Add User To Database* → **ALL PRIVILEGES**.
3. Note the exact server version (`mysql -V`, or `SELECT VERSION()` in phpMyAdmin), e.g. `10.6.19-MariaDB`.

### 1.2 Email account
*Email Accounts* → create `no-reply@lentti.shop`. The SMTP server and port are listed under *Connect Devices*
(usually `mail.lentti.shop`, port 465, SSL).

### 1.3 The clone
Give the server read-only access to the repository, then clone it outside `public_html`:
```bash
ssh-keygen -t ed25519 -f ~/.ssh/dsfk_deploy -N ""
cat ~/.ssh/dsfk_deploy.pub        # GitHub → repository → Settings → Deploy keys → Add (read-only)
printf 'Host github.com\n  IdentityFile ~/.ssh/dsfk_deploy\n  IdentitiesOnly yes\n' >> ~/.ssh/config
git clone git@github.com:sbarbosa115/dsfk.git ~/dsfk
```
The clone **is** the app: never edit tracked files there (the script refuses to pull over local changes).

### 1.4 Document root
*Domains* → `omaha.lentti.shop` → *Manage* → Document Root: `dsfk/backend/public`. Then *MultiPHP Manager* →
`omaha.lentti.shop` → **PHP 8.4**. cPanel writes its PHP handler into `backend/public/.htaccess`; the script adds
the app's rules below it, between `>>> dsfk front controller >>>` markers, and never touches cPanel's lines.

### 1.5 Configuration
```bash
cd ~/dsfk && cp deploy/env.local.example backend/.env.local && chmod 600 backend/.env.local
```
Edit `backend/.env.local`:

| Variable | Value |
|---|---|
| `APP_SECRET` | 32 random characters: `php -r 'echo bin2hex(random_bytes(16));'` |
| `DATABASE_URL` | user, password and database from 1.1, and `serverVersion=` the version you noted (e.g. `10.6.19-MariaDB`) |
| `MAILER_DSN` | the account from 1.2. URL-encode `@` as `%40` and other special characters in the password |
| `MAILER_FROM` | `Control de Proyectos <no-reply@lentti.shop>` |
| `APP_URL` / `DEFAULT_URI` | `https://omaha.lentti.shop` |

### 1.6 Install
```bash
cd ~/dsfk && ./deploy/cpanel-update.sh
```
It ends with the `app:doctor` checklist, where everything must be **OK** (the OPcache line is informational), and
prints the cron jobs that are still missing (section 2).

### 1.7 First admin user
```bash
cd ~/dsfk/backend && /opt/cpanel/ea-php84/root/usr/bin/php bin/console app:create-admin you@lentti.shop "Your Name"
```
Log in and change the password (*Cambiar contraseña*, in the sidebar).

### 1.8 HTTPS
*SSL/TLS Status* → run AutoSSL for `omaha.lentti.shop`. Once `https://` works, uncomment the two "Force HTTPS"
lines in `deploy/htaccess-symfony.conf`, commit, and on the server remove the block from
`backend/public/.htaccess` (from `>>> dsfk front controller >>>` to `<<< dsfk front controller <<<`) before the
next deploy, which appends the new one.

---

## 2. Cron jobs (permanent)

The update script checks the crontab and prints any of these that is missing, with this account's PHP path filled
in. Add them in *Cron Jobs*:

| Schedule | Command | Purpose |
|---|---|---|
| `* * * * *` | `flock -n $HOME/.dsfk-worker.lock /opt/cpanel/ea-php84/root/usr/bin/php $HOME/dsfk/backend/bin/console messenger:consume async --time-limit=55 --memory-limit=128M --env=prod --no-debug >> $HOME/dsfk/backend/var/log/worker.log 2>&1` | Sends queued emails, one worker at a time |
| `0 7 * * *` | `/opt/cpanel/ea-php84/root/usr/bin/php $HOME/dsfk/backend/bin/console app:alerts:daily --env=prod --no-debug -q` | Daily 7am summary |
| `30 2 * * *` | `PHP=/opt/cpanel/ea-php84/root/usr/bin/php $HOME/dsfk/deploy/backup.sh >/dev/null 2>&1` | Database and uploads backup |

If the host doesn't allow cron every minute, use `*/5 * * * *` for the first one: emails just arrive up to 5
minutes later. Each deploy asks running workers to stop (`messenger:stop-workers`), so the next cron run starts one
on the new code.

---

## 3. Deploying a new version

Push to GitHub, then on the server:
```bash
cd ~/dsfk && ./deploy/cpanel-update.sh
SKIP_PULL=1 ./deploy/cpanel-update.sh                                  # rebuild without pulling
PHP=/opt/cpanel/ea-php84/root/usr/bin/php ./deploy/cpanel-update.sh    # if it picks the wrong PHP
```
It does, in order, and every step is safe to run again:
1. checks the account: `backend/.env.local` filled in, PHP 8.4 with its extensions (on the command line and the
   one Apache uses), Composer, Node; refuses to run over local changes to tracked files;
2. `git pull --ff-only` (when the pull changes the script itself, it restarts on the new copy);
3. `composer install --no-dev`, then a fresh production cache;
4. builds the UI with Webpack Encore into `backend/public/build` (`npm ci` only when `package-lock.json` or
   `composer.lock` moved since the last deploy);
5. checks `serverVersion` against the real database server and stops when they disagree (a MySQL version on
   MariaDB makes Doctrine see its migration table out of sync forever);
6. **when a migration is pending**, dumps the database to
   `~/backups/control-proyectos/pre-migration-<db>-<date>.sql.gz`, checks the dump is complete, and only then
   migrates. If the dump fails, nothing is migrated. The password goes through a 0600 file, never an argument.
   `BACKUP_ALWAYS=1` dumps even when nothing is pending;
7. appends the front-controller rules to `backend/public/.htaccess` if they are not there yet;
8. makes `var/` writable, restarts the queue workers, checks the cron jobs, runs `app:doctor`.

It ends with the rollback command for this deploy: `git reset --hard <previous> && SKIP_PULL=1
./deploy/cpanel-update.sh`. Migrations are not reverted: restore the pre-migration dump if one ran. If a change does
not show up, restart PHP from cPanel (OPcache).

### Moving a server from the old layout (once)

Until this version the app was copied into `~/public_html/dsfk` from a build clone in `~/dsfk-src`. To move to
the layout above without losing data (the database stays as it is; there is no pending migration):
```bash
git clone git@github.com:sbarbosa115/dsfk.git ~/dsfk                 # the deploy key from 1.3 still works
cp -p ~/public_html/dsfk/.env.local ~/dsfk/backend/.env.local
mkdir -p ~/dsfk/backend/var && cp -a ~/public_html/dsfk/var/uploads ~/dsfk/backend/var/
cd ~/dsfk && ./deploy/cpanel-update.sh      # fix serverVersion in backend/.env.local if it asks
```
Then, in cPanel:
1. *Domains* → `omaha.lentti.shop` → Document Root `dsfk/backend/public`; *MultiPHP Manager* → PHP 8.4 for it.
2. Copy the receipts uploaded meanwhile: `cp -a -n ~/public_html/dsfk/var/uploads ~/dsfk/backend/var/`.
3. *Cron Jobs*: replace the three old lines (they point at `public_html/dsfk`) with those of section 2.
4. Run `./deploy/cpanel-update.sh` once more: it now finds cPanel's handler in the new `.htaccess` and checks it.

Everyone signs in again (sessions stay behind). Once the site works, keep `~/public_html/dsfk` for a few days, then
delete it with `~/dsfk-src` and `~/dsfk-build`.

---

## 4. Backups

- `deploy/backup.sh` writes `db-<date>.sql.gz` and `uploads-<date>.tar.gz` to `~/backups/control-proyectos/` and
  keeps 14 days. Change this with `BACKUP_DIR` and `KEEP_DAYS`. The update script's `pre-migration-*` dumps go to
  the same folder, so they are pruned after 14 days too.
- **Uploaded receipts and proofs live in `~/dsfk/backend/var/uploads/`.** They are as important as the database.
- Once a month, copy a backup off the server: download it from *File Manager*, or use cPanel's own *Backup*.
- **Restore:** in *phpMyAdmin*, import the `.sql.gz` into an empty database. Extract `uploads-*.tar.gz` into
  `~/dsfk/backend/var/`.

---

## 5. Troubleshooting

| Symptom | Check |
|---|---|
| "Tracked files have local changes" | Something edited the clone. `git status`; settings belong in `backend/.env.local`, never in `backend/.env` |
| "serverVersion does not match the database server" | Put the value the message suggests in `DATABASE_URL` in `backend/.env.local` |
| "Composer not found" / "Node.js … not found" | Set `COMPOSER=/path/to/composer` or `NODE_DIR=/folder/with/node`; install Node in *Setup Node.js App* |
| Frontend build is killed (out of memory) | The host's per-account memory limit is too low: ask the host to raise it |
| `git pull` asks for a password or is denied | The deploy key (1.3) is missing on GitHub, or not in `~/.ssh/config` |
| Blank page or error 500 | `~/dsfk/backend/var/log/prod-<date>.log`, and the domain's PHP version in *MultiPHP Manager* |
| 404 on every page but `/` | The front-controller block is missing from `backend/public/.htaccess`: run the script (it appends it) |
| 403, or the site shows a folder listing | The Document Root must be `dsfk/backend/public` |
| Emails never arrive | Is the `messenger:consume` cron there (the script warns if not)? Is `MAILER_DSN` correct? Check `messenger:failed:show` and `backend/var/log/worker.log` |
| Login works, then you're logged out immediately | `backend/var/sessions` must be writable: run the script, then `bin/console app:doctor` |
| Upload rejected as too large | `upload_max_filesize` and `post_max_size` must be at least 12M (*MultiPHP INI Editor*) |
