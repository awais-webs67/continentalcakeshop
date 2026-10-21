# Putting the website live on cPanel

This guide takes the site from GitHub (or a zip file) to a live domain on standard cPanel hosting.
It takes about 20 minutes. You need the cPanel login and the domain already pointing at the hosting.

## What the hosting needs

- PHP 8.1 or newer (cPanel > **Select PHP Version** or **MultiPHP Manager**)
- PHP extensions: `pdo_mysql`, `gd`, `curl`, `fileinfo`, `mbstring` (all on by default on most hosts)
- MySQL 5.7+ or MariaDB 10.3+
- An SSL certificate for the domain (cPanel > **SSL/TLS Status** > Run AutoSSL)

## Step 1. Put the files on the server

Pick one way.

**A. Git (cPanel > Git Version Control)**

1. Click **Create**, turn on **Clone a Repository**.
2. Clone URL: `https://github.com/awais-webs67/continentalcakeshop.git`
3. Repository Path: `public_html` (the folder must be empty), or a folder of your choice such as `continentalcakeshop`.
4. Click **Create**. Later updates are one click: **Manage** > **Pull or Deploy** > **Update from Remote**.

**B. Zip file (cPanel > File Manager)**

1. On GitHub click **Code** > **Download ZIP**.
2. In File Manager open `public_html`, click **Upload**, choose the zip.
3. Right-click the zip > **Extract**. Move everything inside the extracted `continentalcakeshop-main` folder up into `public_html`, then delete the empty folder and the zip.

Either way, `public_html` should now contain `.htaccess`, `public/`, `src/`, `config/`, `database/` and the other folders.
The `.htaccess` in that folder sends every visitor into `public/`, so nobody can open your config or database files.

> Show hidden files: File Manager > **Settings** > tick **Show Hidden Files (dotfiles)**, otherwise `.htaccess` is invisible.

**Optional, even tidier:** if your host lets you change the domain's document root (cPanel > **Domains** > Manage), point it at `public_html/public`. Both setups work.

## Step 2. Create the database

1. cPanel > **MySQL Databases**.
2. Create a database, for example `cakes`. cPanel names it `cpuser_cakes`.
3. Create a user, for example `cakeadmin`, with a strong password. cPanel names it `cpuser_cakeadmin`.
4. Under **Add User To Database**, add the user to the database and tick **ALL PRIVILEGES**.

## Step 3. Import the data

1. cPanel > **phpMyAdmin**, click your new database on the left.
2. Click **Import** > **Choose File** > select `database/install.sql` from the project (download it from GitHub or File Manager first).
3. Click **Import** at the bottom. You should see about 20 tables, including 41 products.

Moving a database you already filled in on your computer instead?
In phpMyAdmin on your computer select the database > **Export** > **Quick** > **SQL** > **Export**, then import that file on the server instead of `install.sql`.

## Step 4. Fill in config.php

1. In File Manager open the `config` folder.
2. Right-click `config.example.php` > **Copy**, name the copy `config.php`.
3. Right-click `config.php` > **Edit** and change:

```php
'app_url' => 'https://www.yourdomain.com.au',   // exactly how people open the site, no slash at the end
'debug'   => false,

'db' => [
    'host'     => 'localhost',
    'port'     => 3306,
    'name'     => 'cpuser_cakes',
    'user'     => 'cpuser_cakeadmin',
    'password' => 'the database password',
],

'mail' => [
    'driver'       => 'smtp',
    'from_address' => 'orders@yourdomain.com.au',
    'from_name'    => 'The Continental Cake Shop',
    'smtp' => [
        'host'       => 'mail.yourdomain.com.au',
        'port'       => 465,
        'encryption' => 'ssl',
        'username'   => 'orders@yourdomain.com.au',
        'password'   => 'the email account password',
    ],
],
```

For email, first create `orders@yourdomain.com.au` in cPanel > **Email Accounts**. Its **Connect Devices** page shows the exact SMTP host and port.
If SMTP gives trouble, `'driver' => 'mail'` also works on most cPanel hosts.

You can also leave the mail part of `config.php` as it is and set up email later in **Admin > Settings > Emails** (server, port, username and password). Once saved there, those settings are used instead of `config.php`, and the password is stored encrypted with a key in `storage/app.key`.

`config.php` is never stored in GitHub, so pulling updates later will not overwrite it.

## Step 5. Folder permissions

In File Manager, right-click each folder > **Change Permissions** > `755`:

- `public/uploads` (and its sub-folders)
- `storage/uploads` (and its sub-folders)
- `storage/logs`
- `storage/backups`

If photo uploads fail later, try `775` on the same folders.

## Step 6. HTTPS and email delivery

**HTTPS.** Once the SSL certificate is active and `app_url` in `config.php` starts with `https://`, the site sends every visitor to that exact address by itself: `http://` goes to `https://`, and `continentalcakeshop.com.au` goes to `www.continentalcakeshop.com.au` (or the other way round, matching `app_url`). Nothing else to change.

**Email delivery.** So order emails land in the inbox and not in spam: cPanel > **Email Deliverability**, find the domain and click **Repair** if SPF or DKIM shows a problem. If the domain's DNS is managed somewhere else (for example the domain registrar), copy the records cPanel shows into that DNS.

**Errors.** If a page ever shows "Sorry, something went wrong", the details are saved in `storage/logs/php-errors.log` (File Manager). Customers never see them.

## Step 7. Create the owner account

1. Open `https://www.yourdomain.com.au/admin`.
2. The first visit shows **Set up**. Enter the owner's name, email and a password of at least 10 characters.
3. Go to **System check** in the menu. Everything should show **OK**. Anything marked **Check** tells you what to fix.

## Step 8. Shop settings

In the admin panel, **Settings**:

- **Payments**: add the PayID and/or bank details, then switch each method on. For cards, see Step 9.
- **Emails**: set where new order alerts go, then click **Send me a test email**.
- **Opening hours** and **Ordering & pickup**: check the hours, notice time and closed dates, and the delivery fee, delivery area text and delivery postcodes.
- **Homepage**, **Social & SEO**: check the text and links.

Then place a small test order on the website to see the full flow and the emails.

## Step 9. Scheduled jobs (cron job)

One cron job runs everything that happens by itself:

- the **morning email** before the shop opens (pickups, deliveries, payments to check, messages), which also shows straight away if email stops working
- **reminders** to customers the day before their pickup or delivery
- **Google review requests** a while after an order is marked Completed
- a **database backup** every night, kept for 14 days in `storage/backups`

1. cPanel > **Cron Jobs** > **Add New Cron Job**.
2. Common settings: **Once Per Fifteen Minutes** (`*/15 * * * *`).
3. Command (change `cpaneluser` to your cPanel username, and the folder if the site is not in `public_html`):

```
/usr/local/bin/php /home/cpaneluser/public_html/cron/run.php >/dev/null 2>&1
```

4. In **Admin > Settings > Emails** choose who receives the morning email and how many minutes before opening, and switch reminders and review requests on or off. **Send today's morning email now** sends one straight away to try it.

**Admin > System check** shows whether the cron job is running.

## Step 10. Stripe card payments

1. Log in at [dashboard.stripe.com](https://dashboard.stripe.com) and finish the account activation.
2. **Developers** > **API keys**: copy the **Secret key** (`sk_live_...`) into Admin > Settings > Payments.
3. **Developers** > **Webhooks** > **Add endpoint**:
   - Endpoint URL: `https://www.yourdomain.com.au/stripe-webhook`
   - Events: `checkout.session.completed`, `checkout.session.async_payment_succeeded`, `checkout.session.async_payment_failed`, `charge.refunded`
4. Open the new webhook, reveal the **Signing secret** (`whsec_...`) and paste it into the same settings page.
5. Switch **Accept card payments** on, save, and click **Test Stripe connection**.

To try it first without real money, use the `sk_test_...` key and the test card `4242 4242 4242 4242`, then swap in the live key.

## Step 11. Google (keeping the old website's rankings)

The old Wix site ranks well in Canberra, mostly because of the domain's age, links to it and the Google Business Profile. All three carry over as long as the new site uses **the same address** and the old pages redirect. Everything else is ready in **Admin > SEO**: a launch checklist, a check of every old address, and a list of addresses that were not found after launch with one-click redirects.

1. In [Google Search Console](https://search.google.com/search-console) add the domain, choose **HTML tag**, and paste the code into **Admin > Settings > Social & SEO**. Then submit `https://www.yourdomain.com.au/sitemap.xml` under **Sitemaps**.
2. Optional: create a Google Analytics 4 property and paste the `G-...` ID in the same place.
3. Keep `app_url` as the same address the old site used (for example `https://www.continentalcakeshop.com.au`). All 16 pages of the old site redirect permanently (301) to their new page, for example `/allcakes` to the shop, `/mud-cakes` to Mud Cakes, `/gallery-06` to Birthday Cakes Canberra and `/about-us` to About.
4. In your **Google Business Profile**, check the website link and add the **Order online** link to `/shop`.
5. Publish the landing pages in **Admin > Pages** after reading them (birthday, wedding, baby shower and Halal cakes in Canberra).
6. Open **Admin > SEO**, press **Suggest keywords**, then improve the cakes with the lowest SEO score first using **Fill in for me** in each cake.
7. For the first weeks after launch, check **Admin > SEO > Addresses not found** once a week and redirect anything listed.

## Updating the site later

1. Pull the update (Git Version Control > **Update from Remote**) or upload the new files over the old ones. Do not upload a different `config/config.php` or replace `public/uploads`.
2. Open the website once. Database changes that came with the update are applied by themselves on the first visit (a backup is made every night, and you can press **Back up now** in **Admin > System check** before updating). If **System check** still says **The database needs an update**, click **Update database**.

With SSH access you can run the same update with `php database/migrate.php`.

## Backups

- Database: backed up every night to `storage/backups` (kept 14 days). Download one from **Admin > System check > Backups** now and then and keep it on your computer. To restore, import the file in phpMyAdmin. phpMyAdmin > Export and cPanel > **Backup** also work.
- Files that change on the live site: `public/uploads` (product, gallery and page photos) and `storage/uploads` (payment screenshots and custom cake photos).
- `storage/app.key`: keep it with the database backup. Without it the saved email password has to be typed in again.
