# The Continental Cake Shop Canberra

Online ordering website for The Continental Cake Shop, Shop 4, 38 Reed St N, Greenway ACT 2900.
Plain PHP 8.1+ and MySQL/MariaDB, with no framework and no Composer packages, so it runs on standard Australian shared hosting.

**Working on the code?** Read [FEATURES.md](FEATURES.md) first. It explains every feature, how it works and where its code is.

## What the customer side does

- Home, Shop (category filters, search, sort), product pages, Custom Cakes quote form, About, Contact
- Cart with free cake message, quantity and size per line, product **options** such as "Eggless +$5", and **extras** (candles, cake knife, topper, gift card) chosen in the cart
- **Sold out** cakes (today, until a date or until switched back) and a switch to **pause online orders** for holidays, with a notice at the top of every page
- **WhatsApp** chat button (when a number is set) and **Order again** in the customer account
- **Custom cake quotes paid online**: the quote email has an "Accept and pay" button; the customer pays a deposit or the full price by card, PayID or bank, and the request becomes an order
- **Tax invoice** (or receipt when not registered for GST) for every order, to print or save as PDF
- Reminder email the day before pickup or delivery, and a Google review request after the order is completed
- Checkout with **pickup or delivery**. Pickup: the customer books a date and time slot. Delivery: the customer chooses a date, a preferred time of day and their address (checked against the delivery postcodes); the shop then sets the exact delivery time in the admin panel and the customer is emailed. Both respect opening hours, minimum notice, closed dates, how far ahead people can book and optional daily limits
- Standard cakes in two sizes: **Midi (serves 8)** and **Large (serves 14)**. Specials can be set to "Price on request" so they show an Enquire button instead of Add to Cart
- Delivery fee set by the shop (0 = free), with optional free delivery over an amount and a minimum order. The preferred delivery time windows are edited in Settings, and the shop confirms an exact time or a range such as 1pm to 3pm
- **Coupons**: the shop creates codes (percentage or fixed amount) with an optional minimum order, number of uses, start and expiry date. Customers click "Have a coupon code?" at checkout; the discount is checked again when the order is placed and also applied on the Stripe payment page
- **Promo popup**: a designed card shown when the site opens, edited in Admin > Promo popup (title, text, picture, button, linked coupon, pages, delay, how often)
- Custom cake requests with occasion, date, number of people, pickup or delivery, description and a reference photo
- Payment screenshots and custom cake photos upload as soon as they are chosen, with a progress bar in percent, and the form cannot be sent until the upload is complete
- Payment options: **Stripe card payment**, **PayID** and **bank transfer** (both with a payment screenshot upload)
- Private order page for the customer (with paid so far and balance), order emails to the customer and the shop
- FAQ page and editable Privacy Policy, Terms, Refund Policy and Allergen pages (stored in the `pages` and `faqs` tables; review the wording with the shop before launch)
- Optional customer accounts: register, log in, password reset, order history with live order status, and account creation during checkout
- SEO: clean URLs, unique titles and descriptions, canonical tags, Open Graph, `Bakery`, `Product` (one offer per size) and `BreadcrumbList` structured data, `sitemap.xml` with every photo and `robots.txt`
- Landing pages for "birthday cakes canberra", "wedding cakes canberra", "baby shower cakes canberra" and "halal cakes canberra"; every address of the old Wix site redirects to its new page
- On phones the "Visit us" section shows today's date, a live Open now / Closed now badge, today's hours and a map card
- Responsive layout in the shop's green colours (#1E9B82 and #58DCBF), with a mobile slide-in menu (close button, tap outside or Esc to close)

## What the admin panel does

Open `/admin`. The first visit creates the owner account. Everything below is managed without touching code:

- **Dashboard**: today's pickups, new orders, payments waiting for a check, custom requests and messages
- **Orders**: filter by status, payment and pickup date; view the payment screenshot; record payments (full, deposit or part, by cash, EFTPOS, PayID, bank or card) with a payment history; change items, prices, delivery fee and discount; full or partial refunds (also through Stripe); change status, reschedule pickup, private notes, a timeline of every change, a printable kitchen ticket and tax invoice. Status changes can email the customer
- **New order**: phone and in-shop orders with any cake, extra or custom line, any date and time, and payment taken now or later
- **Pickup schedule**: the next days of pickups at a glance
- **Custom requests**: read the request and reference photo, send a branded quote email with a price and an optional deposit the customer can pay online, track the status and the order it became
- **Messages**: contact form messages, reply by email, mark read or replied
- **Customers**: accounts with their orders and spend
- **Products, Categories, Gallery, Reviews**: add, edit, hide, sort, upload photos (resized to WebP automatically), choose the homepage photo
- **Product editor**: several photos with alt text, sizes, options (e.g. Eggless), sold out, a focus keyword, a live Google preview, "Fill in for me" and an 11 point SEO checklist; changing a web address keeps the old one working with a redirect
- **Extras**: candles, knife, toppers and gift cards offered in the cart
- **SEO**: launch checklist, a check of every old website address, addresses not found with one-click redirects, and the redirects list
- **Pages**: landing pages and policy pages
- **FAQs**: edit the wording with simple formatting
- **Coupons** and **Promo popup**: discount codes and the campaign card shown on the website
- **Homepage**: switch each homepage section on or off, change the order and edit its text and buttons
- **Text editor** for policy pages, FAQs, product and category descriptions and homepage text: headings, bold, italic, underline, alignment and justify, lists, quotes, links, pictures (uploaded and resized) and an HTML view
- **Email settings** (Settings > Emails): SMTP server, port, security, username and password (stored encrypted, never shown), sender name and address, with a test email that shows the server's error message
- **Duplicate** a product to add a similar cake quickly, or **import** many from Excel/CSV (template download, preview, then import; also used to update prices in bulk)
- **Messages**: customers write from the contact page, their order page or the "Reply to us" button in every email; staff answer from one inbox and the customer is emailed
- **Sales reports**: sales, paid, discounts, delivery fees, refunds, net and GST, by day, payment method, category and cake, with CSV exports for Excel
- **Staff access**: the owner chooses which parts of the panel each staff account can open
- **Scheduled jobs** in `cron/run.php` (see DEPLOY.md): the morning email before the shop opens, reminders the day before, Google review requests and a nightly database backup
- **Security**: payment screenshots and custom cake photos are stored in `storage/` and can only be opened by logged-in staff; private folders are blocked even if mod_rewrite is off; spam protection on public forms; admin password reset by email
- **Settings**: shop details, opening hours, ordering rules and closed dates, payment methods on or off with their details (Stripe keys, PayID, bank), email alerts and a test email, homepage text, Google rating, social links and SEO
- **Staff accounts** (owner only): add staff, change roles, reset passwords, disable logins
- **System check** (owner only): server requirements, folder permissions, live-site warnings, database updates (they also run by themselves after an update), and backups to make and download

All emails (order confirmation, new order alert, order updates, custom quote, password reset, contact alert) use one branded template with the logo, in `templates/emails/`.

## Folder structure

```
admin/        admin panel controllers and views (not web accessible)
config/       config.example.php (copy to config.php, which git ignores)
database/     install.sql (one-file import), schema.sql + seed.sql (source), migrations/ and migrate.php
public/       web root: pages, admin/index.php, assets, .htaccess, uploads/ for photos
src/          shared PHP functions (catalog, cart, booking, payments, orders, mail, settings, SEO)
templates/    header, footer, partials and emails/
storage/      private uploads (payment screenshots, custom cake photos) and logs, never web accessible
```

## Setup

**Live on cPanel:** follow [DEPLOY.md](DEPLOY.md), step by step.

Short version:

1. Create a MySQL database and user.
2. Import `database/install.sql` (phpMyAdmin > Import, or `mysql -u USER -p DATABASE < database/install.sql`).
   Already imported an older version? The database updates itself on the first visit after you pull new code. You can also run `php database/migrate.php` (XAMPP: `C:\xampp\php\php.exe database\migrate.php`) or use **Update database** in Admin > System check. It is safe to run again.
3. Copy `config/config.example.php` to `config/config.php` and enter the site URL, database and email details.
4. Make `public/uploads`, `storage/uploads`, `storage/logs` and `storage/backups` writable by PHP.
5. Open `/admin` and create the owner account.

Local development, either way works:

- **XAMPP (Apache):** put the project in `htdocs/continentalcakeshop`, set `'app_url' => 'http://localhost/continentalcakeshop'` in `config/config.php`, and open that address. The root `.htaccess` passes every request to `public/`.
- **PHP built-in server:** set `'app_url' => 'http://localhost:8000'` and run
  ```
  php -d upload_max_filesize=12M -d post_max_size=16M -S localhost:8000 -t public public/router.php
  ```

Always open the site at exactly the address in `app_url`, otherwise the session (cart and forms) will not work.

Checks: `php tests/run.php` runs the automatic tests (prices, coupons, delivery areas, pickup times, safe HTML, Stripe signatures, SEO and payments).

## Where uploaded files are stored

Everything is stored on your own hosting, inside the website folder. Nothing goes to an outside service.

| What | Folder | Who can open it |
|---|---|---|
| Product, category, gallery and homepage photos | `public/uploads/` | Everyone (they are shown on the website) |
| Payment screenshots and custom cake reference photos | `storage/uploads/payments/` and `storage/uploads/custom/` | Only logged-in admin staff, through the admin panel |

Include both folders in your backups.

## Before going live

Fill these in from **Admin > Settings**:

- **Payments**: PayID and/or bank details, Stripe secret key and webhook secret. A method only appears at checkout when it is switched on and filled in
- **Emails**: the address for new order alerts, then send a test email
- **Opening hours** and **Ordering & pickup**: notice time, closed dates
- Check the wording of the policy pages with the shop

Then open **Admin > System check** and fix anything it marks.

`database/install.sql` is generated from `schema.sql` + `seed.sql`. After changing either, rebuild it by importing both into an empty database and exporting with `mysqldump --skip-comments`.
