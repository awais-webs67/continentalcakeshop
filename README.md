# The Continental Cake Shop Canberra

Online ordering website for The Continental Cake Shop, Shop 4, 38 Reed St N, Greenway ACT 2900.
Plain PHP 8.1+ and MySQL/MariaDB, with no framework and no Composer packages, so it runs on standard Australian shared hosting.

**Working on the code?** Read [FEATURES.md](FEATURES.md) first. It explains every feature, how it works and where its code is.

## What the customer side does

- Home, Shop (category filters, search, sort), product pages, Custom Cakes quote form, About, Contact
- Cart with free cake message, quantity and size per line
- Checkout with **pickup or delivery**. Pickup: the customer books a date and time slot. Delivery: the customer chooses a date, a preferred time of day and their address (checked against the delivery postcodes); the shop then sets the exact delivery time in the admin panel and the customer is emailed. Both respect opening hours, minimum notice, closed dates, how far ahead people can book and optional daily limits
- Standard cakes in two sizes: **Midi (serves 8)** and **Large (serves 14)**. Specials can be set to "Price on request" so they show an Enquire button instead of Add to Cart
- Delivery fee set by the shop (0 = free), with optional free delivery over an amount and a minimum order. The preferred delivery time windows are edited in Settings, and the shop confirms an exact time or a range such as 1pm to 3pm
- **Coupons**: the shop creates codes (percentage or fixed amount) with an optional minimum order, number of uses, start and expiry date. Customers click "Have a coupon code?" at checkout; the discount is checked again when the order is placed and also applied on the Stripe payment page
- **Promo popup**: a designed card shown when the site opens, edited in Admin > Promo popup (title, text, picture, button, linked coupon, pages, delay, how often)
- Custom cake requests with occasion, date, number of people, pickup or delivery, description and a reference photo
- Payment screenshots and custom cake photos upload as soon as they are chosen, with a progress bar in percent, and the form cannot be sent until the upload is complete
- Payment options: **Stripe card payment**, **PayID** and **bank transfer** (both with a payment screenshot upload)
- Private order page for the customer, order emails to the customer and the shop
- FAQ page and editable Privacy Policy, Terms, Refund Policy and Allergen pages (stored in the `pages` and `faqs` tables; review the wording with the shop before launch)
- Optional customer accounts: register, log in, password reset, order history with live order status, and account creation during checkout
- SEO: clean URLs, unique titles and descriptions, canonical tags, Open Graph, `Bakery`, `Product` and `BreadcrumbList` structured data, `sitemap.xml` and `robots.txt`
- Responsive layout in the shop's green colours (#1E9B82 and #58DCBF), with a mobile slide-in menu (close button, tap outside or Esc to close)

## What the admin panel does

Open `/admin`. The first visit creates the owner account. Everything below is managed without touching code:

- **Dashboard**: today's pickups, new orders, payments waiting for a check, custom requests and messages
- **Orders**: filter by status, payment and pickup date; view the payment screenshot; confirm payment, change status, reschedule pickup, refund Stripe payments, private notes, a timeline of every change, and a printable kitchen ticket. Status changes can email the customer
- **Pickup schedule**: the next days of pickups at a glance
- **Custom requests**: read the request and reference photo, send a branded quote email with a price, track the status
- **Messages**: contact form messages, reply by email, mark read or replied
- **Customers**: accounts with their orders and spend
- **Products, Categories, Gallery, Reviews**: add, edit, hide, sort, upload photos (resized to WebP automatically), choose the homepage photo
- **Policy pages and FAQs**: edit the wording with simple formatting
- **Coupons** and **Promo popup**: discount codes and the campaign card shown on the website
- **Homepage**: switch each homepage section on or off, change the order and edit its text and buttons
- **Text editor** for policy pages, FAQs, product and category descriptions and homepage text: headings, bold, italic, underline, alignment and justify, lists, quotes, links, pictures (uploaded and resized) and an HTML view
- **Email settings** (Settings > Emails): SMTP server, port, security, username and password (stored encrypted, never shown), sender name and address, with a test email that shows the server's error message
- **Duplicate** a product to add a similar cake quickly, or **import** many from Excel/CSV (template download, preview, then import; also used to update prices in bulk)
- **Messages**: customers write from the contact page, their order page or the "Reply to us" button in every email; staff answer from one inbox and the customer is emailed
- **Sales reports**: sales, paid, discounts, delivery fees, refunds, net and GST, by day, payment method, category and cake, with CSV exports for Excel
- **Staff access**: the owner chooses which parts of the panel each staff account can open
- **Morning email** before the shop opens, sent by `cron/run.php` (see DEPLOY.md)
- **Security**: payment screenshots and custom cake photos are stored in `storage/` and can only be opened by logged-in staff; private folders are blocked even if mod_rewrite is off; spam protection on public forms; admin password reset by email
- **Settings**: shop details, opening hours, ordering rules and closed dates, payment methods on or off with their details (Stripe keys, PayID, bank), email alerts and a test email, homepage text, Google rating, social links and SEO
- **Staff accounts** (owner only): add staff, change roles, reset passwords, disable logins
- **System check** (owner only): server requirements, folder permissions, live-site warnings, and one-click database updates

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
   Already imported an older version? Run `php database/migrate.php` (XAMPP: `C:\xampp\php\php.exe database\migrate.php`), or use **Update database** in Admin > System check. It is safe to run again.
3. Copy `config/config.example.php` to `config/config.php` and enter the site URL, database and email details.
4. Make `public/uploads`, `storage/uploads` and `storage/logs` writable by PHP.
5. Open `/admin` and create the owner account.

Local development, either way works:

- **XAMPP (Apache):** put the project in `htdocs/continentalcakeshop`, set `'app_url' => 'http://localhost/continentalcakeshop'` in `config/config.php`, and open that address. The root `.htaccess` passes every request to `public/`.
- **PHP built-in server:** set `'app_url' => 'http://localhost:8000'` and run
  ```
  php -d upload_max_filesize=12M -d post_max_size=16M -S localhost:8000 -t public public/router.php
  ```

Always open the site at exactly the address in `app_url`, otherwise the session (cart and forms) will not work.

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
