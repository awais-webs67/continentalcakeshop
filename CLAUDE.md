# Notes for Claude

- **Live site:** https://continentalcakeshop.com.au, on cPanel hosting (account `contnlga`), code in `~/public_html`, cloned from GitHub over SSH with a read-only deploy key.
- **Updates reach the live site** from the `main` branch: the cPanel cron job runs `cron/deploy.sh` every 5 minutes (also database changes, via `cron/migrate.php`); each update is listed in `storage/logs/deploy.log`. Push finished work to `main`.
- **Old test site:** https://cake.skillsnexuspro.com on Hostinger (auto-pulls `main` too); it should send visitors to the live site (its `app_url` set to the live address).
- Never put passwords, keys or `config/config.php` in the repository. Photos added in the admin panel, `storage/` and `config/config.php` exist only on the server (see `.gitignore`); products, categories and settings live in the database, not in GitHub.
- The owner reads Roman Urdu and likes simple step-by-step answers. Explain before changing things they only asked about.
- Details: `DEPLOY.md` (hosting), `FEATURES.md` (how everything works).
