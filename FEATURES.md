# Feature guide for developers

This file explains every feature of the website: what it does, how it works and where its code lives.
Read it before changing anything. For putting the site live, see [DEPLOY.md](DEPLOY.md). For a short overview, see [README.md](README.md).

## Contents

1. [How the code is organised](#1-how-the-code-is-organised)
2. [Shop and products](#2-shop-and-products)
3. [Cart](#3-cart)
4. [Pickup, delivery and booking rules](#4-pickup-delivery-and-booking-rules)
5. [Checkout and orders](#5-checkout-and-orders)
6. [Payments (Stripe, PayID, bank)](#6-payments-stripe-payid-bank)
7. [Coupons](#7-coupons)
8. [Promo popup](#8-promo-popup)
9. [Custom cake requests](#9-custom-cake-requests)
10. [File uploads and private files](#10-file-uploads-and-private-files)
11. [Customer accounts](#11-customer-accounts)
12. [Messages between customers and the shop](#12-messages-between-customers-and-the-shop)
13. [Emails](#13-emails)
14. [Scheduled jobs: morning email, reminders, reviews, backups](#14-scheduled-jobs-morning-email-reminders-reviews-backups)
15. [Content: homepage, pages, FAQs, text editor](#15-content-homepage-pages-faqs-text-editor)
16. [SEO and moving from the old website](#16-seo-and-moving-from-the-old-website)
17. [Admin panel](#17-admin-panel)
18. [Staff accounts and permissions](#18-staff-accounts-and-permissions)
19. [Sales reports and CSV exports](#19-sales-reports-and-csv-exports)
20. [Product import from Excel or CSV](#20-product-import-from-excel-or-csv)
21. [Settings](#21-settings)
22. [Security](#22-security)
23. [Database and migrations](#23-database-and-migrations)
24. [Design and front-end assets](#24-design-and-front-end-assets)
25. [How to add things](#25-how-to-add-things)
26. [Testing a change](#26-testing-a-change)
27. [Tax invoices](#27-tax-invoices)

## 1. How the code is organised

Plain PHP 8.1+ with MySQL or MariaDB. There is no framework and no Composer, so it runs on any cPanel host.

```
public/        Web root. One PHP file per page (shop.php, product.php, checkout.php...).
public/admin/  index.php is the single entry point of the admin panel.
public/api/    Small JSON endpoints: slots, upload, coupon.
src/           Shared functions, grouped by topic. Loaded by src/bootstrap.php.
templates/     Website header, footer, partials, homepage sections and email templates.
admin/         Admin controllers (logic) and views (HTML). Not web accessible.
database/      schema.sql + seed.sql, install.sql (one-file import), migrations/.
cron/run.php   Scheduled tasks, run every 15 minutes by cPanel cron.
storage/       Private uploads, logs, backups and the encryption key. Never web accessible.
tests/         run.php: automatic checks (php tests/run.php).
config/        config.php (not in git), copied from config.example.php.
```

**Request flow on the website**

1. Apache sends the request to `public/` (root `.htaccess`), and `public/.htaccess` maps the clean URL to a PHP file, for example `/cakes/black-forest-torte` goes to `product.php?slug=black-forest-torte`.
2. Every page starts with `require dirname(__DIR__) . '/src/bootstrap.php';`. This loads the config, every file in `src/`, starts the session and handles HTTPS redirects.
3. The page gets its data, sets `$page` (title, description, canonical) and includes `templates/header.php` and `templates/footer.php`.

**Request flow in the admin panel**

1. `/admin/<route>` goes to `public/admin/index.php?route=<route>`.
2. `ADMIN_ROUTES` maps the route to a controller in `admin/controllers/`.
3. The front controller checks login, staff permissions and the CSRF token on every POST.
4. The controller does the work and calls `admin_view('name', [...])`, which renders `admin/views/name.php` inside `admin/views/layout.php`.

**Helpers used everywhere** (`src/helpers.php`, `src/db.php`)

| Function | What it does |
|---|---|
| `e($text)` | HTML escape. Use it for every value printed in HTML. |
| `url('shop')`, `asset('css/style.css')` | Build links from `app_url`, so the site also works inside a sub-folder. |
| `db_all`, `db_one`, `db_value`, `db_run` | PDO with prepared statements. Never put user input into SQL text. |
| `csrf_field()`, `verify_csrf()` | CSRF token for every form. |
| `flash()`, `take_flashes()` | One-time messages after a redirect. |
| `input('name')` | Trimmed value from GET or POST. |
| `setting('key')`, `setting_save()` | Read and save shop settings (see section 21). |
| `icon('name')` | Inline SVG icon from `src/icons.php`. |

## 2. Shop and products

**What it does.** The shop page lists cakes with category filters, search and sorting. Each cake has its own page with photo, sizes, price, description, a cake message box and related cakes.

**Sizes.** Standard cakes come in **Midi (serves 8)** and **Large (serves 14)**. Sizes live in the `product_sizes` table (label, serves, price). A product with no sizes uses its own price.

**Specials ("price on request").** When a product's enquiry flag is on, the page shows an **Enquire** button that opens the custom cake form with the cake name filled in, instead of Add to Cart.

**Options.** Choices on one cake that change its price, such as "Eggless +$5" (`product_options` table, edited in the product editor). Customers tick them on the cake page; the price updates live (`main.js`). The chosen names are saved on the order line (`order_items.options`) and shown in emails, the kitchen ticket, the morning email and exports.

**Photos.** The main photo is `products.image` (with `image_alt`); extra photos are in `product_images`. The cake page shows a gallery with thumbnails. `save_product_photos()` in `admin/bootstrap.php` handles uploads (several at once), alt text, order, removing and "make main". A photo file is only deleted when nothing uses it any more (`delete_public_image()`).

**Sold out.** `products.sold_out_until` is the last day a cake is sold out (`9999-12-31` = until switched back). `product_sold_out()` and `product_back_label()` in `src/catalog.php`. Sold out cakes show a badge, cannot be added to the cart and are `OutOfStock` in the structured data.

**Code**
- Pages: `public/shop.php`, `public/product.php`, card: `templates/partials/product-card.php`
- Functions: `src/catalog.php` (`get_products`, `get_product_by_slug`, `get_product_sizes`, `get_product_options`, `price_label`, `enquiry_url`, `product_image_url`, `product_sold_out`), `src/seo.php` (`get_product_images`, `product_image_alt`)
- Admin: Products, Categories, Gallery, Reviews (`admin/controllers/product-edit.php` and others)
- Tables: `products`, `product_sizes`, `product_options`, `product_images`, `categories`, `gallery_images`, `reviews`

Product photos are resized and saved as WebP in `public/uploads/`. A photo is only deleted when no other product, category or gallery item still uses it.

## 3. Cart

**What it does.** Customers add cakes with a size, quantity and an optional free message on the cake. The cart is kept in the PHP session.

**Code:** `public/cart.php`, `src/cart.php` (`cart_add`, `cart_add_error`, `cart_items`, `cart_subtotal`, `cart_lead_hours`, `cart_reorder`). Limits: `CART_MAX_QTY`, `CAKE_MESSAGE_MAX`. A cart line is one size with its options and message (`cart_line_key`).

**Extras.** Candles, cake knife, topper, gift card: rows in the `addons` table, managed in **Admin > Extras** and switched on with the `addons_enabled` setting. The customer picks quantities in the cart (`$_SESSION['cart_addons']`). `cart_order_lines()` returns the cakes followed by the extras, in the same shape, and everything that totals or saves an order uses it, so extras appear in totals, Stripe, emails and reports (as category "Extras"). Extras are saved as order lines with `addon_id`.

**Order again.** In the customer account, `cart_reorder()` puts a past order back in the cart at today's prices and lists what is no longer available.

**Pausing orders.** Settings > Ordering can pause online orders, optionally until a date (`ordering_paused`, `ordering_paused_until`, `ordering_paused_message`). While paused the top bar shows the notice, Add to Cart and checkout are blocked, and custom cake requests still work. `ordering_paused()` in `src/catalog.php`.

`cart_lead_hours()` returns the longest notice time of the cakes in the cart. That notice decides the first date the customer can choose.

## 4. Pickup, delivery and booking rules

**Pickup.** The customer chooses a date and a time slot. Slots come from the opening hours, the notice time, closed dates and an optional limit of orders per slot.

**Delivery.** The customer chooses a date, a preferred time window (for example "Morning") and an address. The postcode is checked against the delivery postcode list. Staff then set the exact time, or a range such as 1pm to 3pm, in the admin. The customer gets an email when they do. Until then `orders.pickup_time` is NULL.

**Rules the shop controls in Settings:** opening hours, minimum notice, how many days ahead people can book, closed dates (`blocked_dates` table), orders per slot, delivery fee (0 means free), free delivery over an amount, minimum delivery order, delivery postcodes and the delivery time windows.

**Code**
- `src/booking.php`: `pickup_slots`, `earliest_pickup`, `first_delivery_date`, `delivery_date_error`, `delivery_postcode_allowed`, `is_blocked_date`
- `public/api/slots.php`: returns free slots for a date (used by the checkout JavaScript)
- `src/orders.php`: `delivery_windows`, `order_when`, `order_time_label`
- Admin: Pickups & deliveries page (`pickups`) and the "Deliveries to schedule" card on the dashboard

## 5. Checkout and orders

**What it does.** One checkout page with contact details, pickup or delivery, coupon, payment method, and account creation if the customer wants one. After the order the customer sees a private order page and gets an email.

**Order lifecycle**

| Status (`orders.order_status`) | Shown as |
|---|---|
| `new` | Received |
| `confirmed` | Confirmed |
| `in_progress` | Being prepared |
| `ready` | Ready |
| `completed` | Completed |
| `cancelled` | Cancelled |

| Payment (`orders.payment_status`) | Shown as |
|---|---|
| `pending` | Awaiting payment (Stripe not finished) |
| `awaiting_verification` | Payment being checked (PayID or bank screenshot uploaded) |
| `partially_paid` | Part paid (a deposit or part of the total) |
| `paid` | Paid |
| `failed` | Payment failed |
| `refunded` | Refunded |

Every change is written to `order_events`. That table is the timeline on the admin order page.

**Where orders come from** (`orders.channel`): `online` (checkout), `phone` and `in_store` (typed by staff in **Admin > Orders > New order**, `admin/controllers/order-new.php`) and `quote` (a custom cake quote accepted online, section 9). Staff orders can use any cake size, extra or a custom line with its own price (`admin_order_lines_from_post()` and the `line-adder` partial), any date and time, a delivery fee and a discount.

**Changing an order.** On the admin order page staff can change quantities and prices, remove lines, add lines, and change the delivery fee and discount. `recalculate_order_total()` puts the total back together and the payment status follows (section 6).

**Code**
- `public/checkout.php`, `public/order.php` (private order page at `/order/<number>`)
- `src/orders.php`: `create_order` (one database transaction, coupon redeemed inside it), `insert_order_lines`, `mark_order_paid`, `record_payment`, `record_refund`, `order_balance`, `recalculate_order_total`, `log_order_event`, `get_order`, `order_item_detail`
- Admin: `orders` (list, filters, search), `orders/new` (phone and shop orders), `orders/view` (status, payments, items, customer details, delivery time, refunds, notes, messages), `orders/print` (kitchen ticket)
- Tables: `orders`, `order_items`, `order_events`, `payments`

Order numbers look like `CCS-261019-F714F` (date plus random part), made by `generate_order_number()`.

## 6. Payments (Stripe, PayID, bank)

A payment method only shows at checkout when it is switched on **and** its details are filled in (Admin > Settings > Payments). The admin warns when no method is available.

**Stripe card payments**
1. `stripe_create_checkout()` creates a Stripe Checkout session and sends the customer to Stripe. A coupon discount is passed as a one-off Stripe coupon (`amount_off`).
2. When the customer comes back, `public/order.php` calls `stripe_confirm_session()`.
3. Stripe also calls `/stripe-webhook` (`public/stripe-webhook.php`). The signature is checked with `stripe_verify_webhook()`, then `mark_order_paid()` runs. Both paths are safe to run twice, because the payment is recorded with the Stripe payment id as its reference.
4. Part payments: when something is already paid (or a quote deposit is paid), `stripe_create_checkout()` charges one line with the amount owing instead of the items.
5. Refunds: the admin order page calls `stripe_refund($order, $amount)`, which refunds the newest card payments first. Refunds made in the Stripe dashboard arrive as `charge.refunded` and are copied with `stripe_sync_refunds()`.

Stripe is called with plain cURL in `stripe_request()`. There is no Stripe library.

**PayID and bank transfer.** The customer sees the details, pays from their bank app and uploads a screenshot. The order becomes "Payment being checked". Staff open the screenshot in the admin and record the payment.

**Payment history.** Every payment and refund is a row in the `payments` table (amount, method, reference, note, staff member). `record_payment()` and `record_refund()` add a row (a reference already recorded is ignored), then `refresh_order_payment()` works out `orders.amount_paid`, `orders.amount_refunded` and the payment status: part paid, paid or refunded. `order_balance()` is what is still owing. Staff can record cash and EFTPOS too (`IN_STORE_PAYMENT_METHODS`). A partial refund also lowers the order total, so nothing shows as owing; a full refund marks the order refunded. Reports and customer spend use the money kept (`amount_paid - amount_refunded`).

**Code:** `src/payments.php` (`payment_method_definitions`, `available_payment_methods`, `staff_payment_methods`, Stripe functions), `src/orders.php` (payment history), `public/stripe-webhook.php`.

## 7. Coupons

**What it does.** The shop creates codes with a percentage or fixed amount, an optional minimum order, a total number of uses, a start date, an expiry date and an on/off switch. The admin can generate a random code. At checkout a "Have a coupon code?" link opens a box; the code is checked by `/api/coupon` and checked again when the order is placed.

**Rules:** the discount applies to cakes only, not to the delivery fee. `order_totals()` works out subtotal, discount, delivery and total in one place, so the checkout, the order and Stripe always agree.

**Code:** `src/coupons.php` (`coupon_error`, `coupon_discount`, `apply_coupon`, `redeem_coupon`, `order_totals`, `delivery_fee_for`), `public/api/coupon.php`, admin `coupons`. Table: `coupons`. `redeem_coupon()` uses one atomic UPDATE, so a coupon cannot go over its use limit when two people order at the same time. If it runs out in between, `CouponUnavailable` is thrown and the customer is told.

## 8. Promo popup

**What it does.** A designed card shown when someone opens the website: picture, title, text, button and an optional linked coupon code with a copy button. The admin sets which pages it shows on, the delay and how often it repeats.

**Code:** `src/content.php` (`promo_popup`, `popup_link`, `POPUP_SKIP_PATHS`), `templates/partials/promo-popup.php`, admin `popup` (with a live preview). Settings keys start with `popup_`. The checkout, cart and account pages never show it.

## 9. Custom cake requests

**What it does.** A form for custom cakes: occasion, date, number of people (typed by the customer), pickup or delivery, description and a reference photo. Staff see it in the admin, send a branded quote email with a price, and track the status (`new`, `quoted`, `confirmed`, `closed`). Each request has a message thread.

**Quotes paid online.** With a price, staff can set a deposit and switch on "Let the customer accept and pay online". The quote email then has an **Accept and pay** button to `/quote/<token>` (`public/quote.php`). The customer chooses deposit or full price, adds a delivery address if needed and pays by card, PayID or bank. `order_for_quote()` makes an order (channel `quote`) with one line for the custom cake, links it to the request (`custom_requests.order_id`) and its conversation, and sets the request to Confirmed. The rest is paid like any order.

**Code:** `public/custom-cakes.php`, `public/quote.php`, `src/quotes.php`, `src/mail.php` (`send_custom_request_emails`, `send_custom_quote_email`), admin `custom-requests` and `custom-requests/view`. Table: `custom_requests`.

## 10. File uploads and private files

**Where files go**

| What | Folder | Who can open it |
|---|---|---|
| Product, category, gallery, page and popup photos | `public/uploads/` | Everyone |
| Payment screenshots and custom cake photos | `storage/uploads/payments/`, `storage/uploads/custom/` | Logged-in staff only, through `/admin/file` |

**Upload with progress.** The photo starts uploading as soon as the customer picks it (`/api/upload`). A progress bar shows the percentage, and the form cannot be sent until the upload is done. The server returns a token that is saved in the session (`store_async_upload`, `async_upload`). The form sends the token, not the file.

**Limits:** 10 MB (`UPLOAD_MAX_BYTES`). Images (JPG, PNG, WebP), plus PDF for payment receipts. The real file type is checked, not only the extension.

**Code:** `src/uploads.php`, `public/api/upload.php`, `templates/partials/upload-field.php`, `public/assets/js/main.js`, admin `file` controller. `public/.user.ini` raises the PHP upload limit on cPanel.

## 11. Customer accounts

Optional. Customers can register, log in, reset their password and see their orders with live status. They can also create an account during checkout. Login is locked for 15 minutes after 5 wrong passwords.

**Code:** `src/auth.php`, `public/login.php`, `register.php`, `account.php`, `forgot-password.php`, `reset-password.php`. Tables: `users`, `password_resets`.

## 12. Messages between customers and the shop

**What it does.** Every order, custom request and contact form message can have a conversation (a "thread"). Customers open it with a private link `/messages/<token>`, which is in their emails as a **Reply to us** button. Staff answer from **Admin > Messages**, and the customer is emailed each reply. The shop is emailed each customer message.

**Code:** `src/messages.php` (`order_thread`, `custom_request_thread`, `customer_message`, `shop_message`), `public/messages.php`, admin `messages` and `messages/view`. Tables: `threads`, `thread_messages`. Unread counts show as badges in the admin menu.

## 13. Emails

All emails use one branded layout with the logo: `templates/emails/layout.php`. Each email is a small template in `templates/emails/` and is rendered with `render_email('name', [...])`. Pass `reply_url` to add the **Reply to us** button.

| Template | When it is sent |
|---|---|
| `order-confirmation` | Customer placed an order |
| `order-new-admin` | Shop alert for a new order |
| `order-update` | Staff changed the status, delivery time or payment |
| `custom-request-customer`, `custom-request-admin` | Custom cake request sent |
| `custom-quote` | Staff sent a quote |
| `contact-customer`, `contact-admin` | Contact form |
| `message-customer`, `message-admin` | New message in a conversation |
| `password-reset` | Customer or staff password reset |
| `order-reminder` | The day before pickup or delivery |
| `review-request` | A while after the order is completed |
| `daily-summary` | Morning email |
| `test` | Test button in Settings |

**Sending.** `send_mail()` in `src/mail.php` uses one of three drivers: `smtp` (own SMTP client, no library), `mail` (PHP mail) or `log` (saves to `storage/logs/mail` for testing). Settings saved in **Admin > Settings > Emails** win over `config.php`. The SMTP password is encrypted with AES-256-GCM using the key in `storage/app.key` (`secret_encrypt`, `secret_decrypt` in `src/settings.php`).

## 14. Scheduled jobs: morning email, reminders, reviews, backups

cPanel runs `cron/run.php` every 15 minutes. It calls `run_scheduled_tasks()` in `src/daily.php`, which saves `cron_last_run` and runs each job on its own (one failing job never stops the others). **Admin > System check** shows if the cron job has stopped.

| Job | What it does | Settings |
|---|---|---|
| `morning_email_task` | On open days, a set time before opening: pickups, deliveries, deliveries without a time, payments to check, new requests, unread messages. Also proves that email works. Once a day (`daily_email_last_sent`). | `daily_email_enabled`, `daily_email_to`, `daily_email_minutes_before` |
| `reminder_email_task` | From a set hour the day before: reminder with the time, address, anything still to pay and the order link (`order-reminder` email). Once per order (`orders.reminder_sent_at`, cleared when staff move the date). | `reminder_email_enabled`, `reminder_send_hour` |
| `review_request_task` | A set number of hours after an order is marked Completed (`orders.completed_at`): asks for a Google review (`review-request` email). Each customer at most once every 120 days; never for refunded orders or orders older than 14 days. | `review_email_enabled`, `review_delay_hours`, `google_reviews_url` |
| `backup_task` | After 2am, once a night: full database backup (`src/backup.php`). | `backup_enabled`, `backup_keep_days` |
| `cleanup_task` | Once a day: removes "not found" entries older than 90 days and expired password links. | |

New scheduled jobs: add a function `something_task(DateTimeImmutable $now): string` and add its name to the list in `run_scheduled_tasks()`.

## 15. Content: homepage, pages, FAQs, text editor

**Homepage sections.** Hero, highlights, categories, featured cakes, custom cakes, free text, reviews and visit us. Staff switch each one on or off, change the order and edit its text in **Admin > Homepage**. The order and on/off state are a JSON list in the `home_sections` setting. Each section is a file in `templates/home/`. Code: `home_sections()` and `HOME_SECTIONS` in `src/content.php`, `public/index.php`.

**Pages and FAQs.** Privacy Policy, Terms, Refund Policy and Allergens are in the `pages` table (`/privacy-policy` and so on, served by `public/page.php`). FAQs are in the `faqs` table.

**Text editor.** Quill 2.0.3 is bundled in `public/assets/vendor/quill/` and only loads on pages that need it. It gives headings, bold, italic, underline, alignment and justify, lists, quotes, links, images and an HTML view. Images go through `/admin/upload-image`. Saved HTML is cleaned by `clean_html()` (safe tags only), and shown with `rich_text()`, which also handles older plain text.

## 16. SEO

- Clean URLs, unique titles and descriptions, canonical tags and Open Graph (`page_meta()` in `src/seo.php`).
- Structured data: `Bakery` (with address, hours, ABN, areas served), `Product` (one offer per size with availability, every photo, servings) and `BreadcrumbList` (`json_ld`, `local_business_schema`, `product_schema`).
- `/sitemap.xml` with every product photo and the landing pages (`public/sitemap.php`) and `/robots.txt` (`public/robots.php`).
- **Homepage.** The title starts with "Cake Shop Canberra", like the old site. The hero's small line is part of the `h1`, so the main heading reads "Cake shop in Greenway, Canberra since 2015 / Fresh cakes for every celebration".
- **Old website links.** All 16 pages of the old Wix site (`/allcakes`, `/mud-cakes`, `/gallery-06`, `/about-us`...) are sent with a 301 to their new page. Built-in list: `LEGACY_REDIRECTS`. The old gallery pages go to the landing pages (or Custom Cakes when a landing page is off). Staff can add more in Settings (`seo_redirects`) or from Admin > SEO. Applied in `public/404.php` through `legacy_redirect_target()`.
- **Automatic redirects.** Changing the web address of a cake, category or landing page adds a redirect from the old address (`add_seo_redirect()`), so rankings and old links keep working.
- **Landing pages** (`pages.type = 'landing'`): Birthday, Wedding, Baby shower and Halal cakes in Canberra, shown by `public/page.php` with text, cake photos and a quote button that fills in the occasion. Any simple address (`/something`) is checked against the pages table before the 404 page. Staff add more in Admin > Pages; `RESERVED_SLUGS` lists addresses a page can never take.
- **Product SEO checklist.** `product_seo_checks()` (11 checks: focus keyword in title, description, address and text, lengths, local words, enough text, photos) runs in the product editor while staff type (the same rules in `admin.js`), and each cake's score shows in the product list. "Fill in for me" writes a title, description and photo text. Automatic cake titles always fit in 60 characters (`product_default_title()`).
- **Admin > SEO** (`admin/controllers/seo.php`): launch checklist, a check of every old address, addresses not found (logged by `log_not_found()` in the `not_found_log` table) with one-click redirects, the redirects list, and "Suggest keywords" for cakes without one.
- Google Analytics 4 and Search Console verification from Settings (`analytics_tags()`).
- Filtered and search pages are `noindex`.
- On the live site every request goes to one address: HTTPS and the same www or non-www host as `app_url` (`src/bootstrap.php`).

## 17. Admin panel

**Layout.** `admin/views/layout.php`: sidebar menu with badges, a top bar with search (press `/`), a **New** menu, notifications bell and account menu, and a bottom bar on phones. Styles in `public/assets/css/admin.css`, behaviour in `public/assets/js/admin.js`.

**Pages**

| Route | What it is |
|---|---|
| `/admin` | Dashboard: today, payments to check, deliveries to schedule, sales chart, recent orders |
| `orders`, `orders/new`, `orders/view`, `orders/print` | Orders, phone and shop orders, order detail, kitchen ticket |
| `pickups` | Pickups and deliveries by day |
| `custom-requests` | Custom cake requests and quotes |
| `messages` | Inbox of customer conversations |
| `customers` | Customer accounts with orders and spend |
| `reports`, `reports/export` | Sales reports and CSV exports |
| `coupons`, `popup` | Discount codes, promo popup |
| `products`, `products/edit`, `products/import` | Cakes (photos, options, sold out, SEO checklist), duplicate, Excel/CSV import |
| `categories`, `addons`, `gallery`, `reviews` | Catalogue content, extras for the cart |
| `homepage`, `pages`, `pages/edit`, `faqs` | Website content, landing and policy pages |
| `seo` | Launch checklist, old addresses, addresses not found, redirects |
| `settings` | Every shop setting (section 21) |
| `admins` (owner only) | Staff accounts and their access |
| `system` (owner only) | Server checks, cron status, database updates, backups to make and download |
| `account` | Own name, email and password |

First visit to `/admin` with no accounts opens `setup`, which creates the owner.

**Tables on phones.** `admin.js` copies each column name into `data-label` on the cells, so tables turn into cards on small screens. Wide tables (6+ columns) also turn into cards on tablets.

## 18. Staff accounts and permissions

Two roles: **owner** (everything) and **staff**. For staff, the owner ticks which sections they can open. The list is in `ADMIN_SECTIONS` in `admin/bootstrap.php` and is saved as JSON in `admins.permissions` (NULL means every section). `admin_can($section)` hides menu items and the front controller blocks the routes. `admins` and `system` are owner only (`OWNER_ONLY_ROUTES`). Staff can reset a forgotten password by email (`admin_password_resets` table).

## 19. Sales reports and CSV exports

For any date range (presets: today, yesterday, this week, this month, last month, this year, or any custom dates): number of orders, sales, paid, discounts, delivery fees, refunds, net and the GST part. Breakdowns by day (chart), payment method, category and top cakes. Exports to CSV for Excel: orders, order items, by day and by cake.

**Code:** `admin/reports.php` (`report_range`, `report_summary`, `report_by_day`...), `send_csv()` (adds a BOM for Excel and blocks spreadsheet formula injection), admin `reports` and `reports/export`. Cancelled orders are left out of sales.

## 20. Product import from Excel or CSV

**What it does.** Staff download a template (or the current products), edit it in Excel, save as CSV and upload it. A preview shows what will be added, updated or skipped, then they confirm. Existing products are matched by their web address (the `slug` column, or the name turned into a slug when it is empty), so the same file can update prices in bulk. Sizes are matched by label (Midi, Large). A photo can be given as a URL and is downloaded.

**Code:** `admin/product-import.php` (`IMPORT_COLUMNS`, `read_import_csv`, `plan_import_row`, `apply_import_row`, `download_import_image`), admin `products/import`. `download_import_image()` refuses private network addresses so it cannot be used to reach the server's own network.

## 21. Settings

Nearly everything a shop owner may want to change is a row in the `settings` table (key and value), not code. Read with `setting('key', 'default')`, save with `setting_save()`. Values are cached per request.

Settings tabs in the admin: shop details (including the WhatsApp number for the chat button, `whatsapp_url()`), opening hours, ordering and pickup (including pausing online orders), delivery, payments, emails (including reminders and review requests), homepage, social and SEO. A new setting needs a default row in a migration (section 23) and a field in `admin/views/settings.php` plus saving code in `admin/controllers/settings.php`.

`config/config.php` only holds what cannot live in the database: `app_url`, `debug`, timezone, database login and the default mail settings.

## 22. Security

- Prepared statements for all SQL, `e()` for all output, CSRF token on every form.
- Passwords hashed with `password_hash()`. Login lockout after 5 tries. Session cookie is HttpOnly, SameSite=Lax and Secure on HTTPS.
- Private folders (`storage`, `config`, `database`, `src`, `templates`, `cron`, `tests`, `.git`) are blocked in the root `.htaccess`, even if mod_rewrite is off. `storage/.htaccess` denies everything. `public/uploads/.htaccess` stops scripts from running there.
- Payment screenshots and custom photos are only served to logged-in staff with the right section access. Order pages, invoices, quotes and conversations are opened with long random tokens.
- Database backups are stored in `storage/backups` and can only be downloaded by the owner.
- Public forms have spam protection: a hidden honeypot field, a minimum fill time of 3 seconds and a rate limit (`spam_trap_fields`, `is_spam_submission`).
- Security headers in `public/.htaccess`. HSTS is sent on HTTPS.
- With `debug => false`, errors are hidden from visitors, saved to `storage/logs/php-errors.log`, and a friendly error page is shown.

## 23. Database and migrations

- `database/schema.sql` + `database/seed.sql` are the source. `database/install.sql` is the one-file import for a new site.
- Changes to an existing database go in a new numbered file in `database/migrations/` (next number after the last one, for example `012_something.sql`). Never edit a migration that has already shipped.
- They run by themselves on the first visit after new code arrives (`auto_migrate()` in `src/migrations.php`, called from `src/bootstrap.php`): the newest migration name is kept in the `schema_version` setting, and a database lock stops two visitors running them at once. Set `'auto_migrate' => false` in `config.php` to run them only by hand with `php database/migrate.php` or **Admin > System check > Update database**. Applied files are stored in `schema_migrations`. A migration must be safe to run twice.
- Settings and other default data go between `-- @data-start` and `-- @data-end` in the migration, using `INSERT IGNORE` so existing values are never overwritten.
- After adding a migration, also update `schema.sql`/`seed.sql` and rebuild `install.sql`: import the old `install.sql` into an empty database, run the migrations, export with `mysqldump --skip-comments --skip-dump-date` and keep the four comment lines at the top. Check that a database built from `schema.sql` + `seed.sql` has the same columns, indexes and rows.

**Tables:** `settings`, `categories`, `products`, `product_sizes`, `product_options`, `product_images`, `addons`, `gallery_images`, `reviews`, `orders`, `order_items`, `order_events`, `payments`, `coupons`, `blocked_dates`, `custom_requests`, `threads`, `thread_messages`, `pages`, `faqs`, `users`, `password_resets`, `admins`, `admin_password_resets`, `not_found_log`, `schema_migrations`. `contact_messages` is no longer used: migration 010 copied its messages into `threads`, and it is kept only so older databases keep their history.

**Backups.** `src/backup.php` writes every table to `storage/backups/db-YYYY-MM-DD-HHMMSS.sql.gz` (dates in UTC), every night through the cron job and on demand from Admin > System check. Old backups are removed after `backup_keep_days` (at least 3 stay). Restore by importing the file in phpMyAdmin.

## 24. Design and front-end assets

- Brand colours: dark green `#1E9B82` (main), mint `#58DCBF` (accents), deep green `#0E5447`. They are CSS variables at the top of `public/assets/css/style.css` (website) and `admin.css` (admin).
- Fonts: Outfit (headings) and DM Sans (text).
- `public/assets/js/main.js`: website behaviour (menu, checkout, slots, uploads, coupon box, popup, product options price, photo gallery, quote amount).
- **Visit us on phones.** Under 900px the homepage "Visit us" section shows a card with today's date, a live Open now / Closed now badge, today's hours and when the shop next opens (`today_status()` in `src/settings.php`, holiday closures count as closed), and a map card. Desktop keeps the week table.
- `public/assets/js/admin.js`: admin behaviour (menus, dropdowns, editor, previews, repeaters, mobile tables).
- No build step. Edit the files directly. CSS and JS links include the file time, so browsers load the new version after a change.

## 25. How to add things

**A new text page** (no code): Admin > Pages > Add a landing page.

**A new website page with its own code**
1. Create `public/your-page.php` starting with the bootstrap require, set `$page`, include the header and footer.
2. Add a rewrite rule in `public/.htaccess` (and in `public/router.php` for the PHP built-in server).
3. Add it to `public/sitemap.php` if Google should find it.

**A new admin page**
1. Add the route to `ADMIN_ROUTES` in `public/admin/index.php`.
2. Create `admin/controllers/name.php` and `admin/views/name.php`.
3. Add the route to a section in `ADMIN_SECTIONS` (so staff permissions apply) and a link in the `$nav` list in `admin/views/layout.php`.

**A new email**
1. Create `templates/emails/name.php` (copy a small one such as `contact-customer.php`).
2. Send it with `send_mail($to, $subject, render_email('name', [...]))`.

**A new setting:** see section 21.
**A new homepage section:** add a file in `templates/home/`, add it to `HOME_SECTIONS` and to the default `home_sections` list in a migration, and add its fields in `admin/views/homepage.php`.
**A new scheduled job:** add a `name_task(DateTimeImmutable $now): string` function and add it to the list in `run_scheduled_tasks()` in `src/daily.php`.

## 26. Testing a change

Before pushing:

1. `php -l` on every PHP file you changed, and `php tests/run.php` (automatic checks for prices, coupons, delivery areas, pickup times, safe HTML, Stripe signatures, SEO and the payment history; add a check when you change one of these).
2. Run the site locally (README > Local development) with `'mail' => ['driver' => 'log']`, so emails are saved in `storage/logs/mail` instead of being sent.
3. Walk through the affected flow by hand: add to cart, checkout with pickup and with delivery, each payment method, a coupon, and the matching admin page.
4. Check the page on a phone width (390px), a tablet (768px) and a laptop (1280px).
5. If you added a migration, run it on a copy of an older database and on a fresh install.
6. Open **Admin > System check** and make sure everything is OK.

## 27. Tax invoices

**What it does.** Every order has a printable page at `/order/<number>/invoice` (`public/invoice.php`): a **tax invoice** with the shop's ABN, the date of issue, the items with their options, discount, delivery, the GST included (total / 11) and every payment and refund with the balance due. When the shop is not registered for GST or has no ABN, the same page is a **receipt**. Customers open it from their order page (the private link, `invoice_url()`), staff from the admin order page. It prints cleanly or saves as a PDF from the browser.

## 28. Live updates

**What it does.** Every open admin page checks in every 10 seconds (once a minute when the tab is in the background) at `admin/live` (`admin/controllers/live.php`, `admin_live_state()` in `admin/bootstrap.php`). New orders, customer messages and custom requests pop up with a short chime and an Open button, the badges in the menu and the bell update, and parts of pages marked `data-live="name"` refresh themselves (dashboard, orders, pickups, messages, custom requests). A refresh waits while someone is typing in that part of the page. Notices are shared between tabs through `localStorage`, so each one pops up once.

**Sounds** (`public/assets/js/sounds.js`, made in the browser, no audio files): a three-note bell for a new order (and a vibration on phones), a chat "pop" for a customer message (in the inbox notice and inside the open conversation) and for a shop reply on the customer's chat page, a soft tick when a message is sent, and a two-note bell for a custom request. The speaker button in the admin top bar turns sounds on or off, tests them, and can **keep ringing** for a new order every 15 seconds until someone taps the screen (up to 5 minutes). Choices are saved per device. Browsers only allow sound after one tap on the page, so a "Tap to turn on order sounds" button shows until then.

**Chat.** The customer's conversation page (`public/messages.php`) and the admin conversation (`admin/controllers/message-view.php`) check for new messages every few seconds and send without reloading (`?poll=1&after=<id>` returns new bubbles as JSON, built by `chat_bubble_html()`). `threads.admin_seen_at` and `threads.customer_seen_at` record who has the chat open; no email is sent for a message the other side is reading live (`thread_seen_live()`, `CHAT_LIVE_SECONDS`).

## 29. Orders, pickups and kitchen tickets

**Orders** (`admin/controllers/orders.php`): quick views with counts (Needs action, Today, Tomorrow, Coming up, Money owing, Completed, Cancelled, All), search and a Filters panel. Every order is a card (`admin/views/partials/order-row.php`): the whole card opens the order and the next step (Confirm, Mark ready, Collected or Delivered) is one tap; the `back` field returns staff to the same list.

**Pickups & deliveries** (`admin/controllers/pickups.php`): **Today** (with totals), **Advance orders** (every later day, grouped by date, or one day with the date picker) and **Past** (last 30 days, "Only not done" shows orders never marked collected). **Add advance order** opens the phone order form with the date filled in (`orders/new?date=`).

**Kitchen ticket** (`admin/controllers/order-print.php`): the slip the baker works from and tapes to the box. Big date and time, what to write on each cake, options such as Eggless, extras to pack, notes, tick boxes and how much to collect. `?id=` prints one order, `?date=` prints every order that day (one per page); `&size=80` formats it for an 80 mm receipt printer.

## 30. Homepage categories and product photos

**Categories layout** (Admin > Homepage > Categories, setting `home_categories_style`): **Photo tiles** (the first category large; `templates/home/categories.php` works out the spans so every row is full for any number of categories) or **Round photos, scrolling** (round photos with a theme-coloured ring that glide past like a news ticker, pause on hover or touch, can be swiped, and end with "View all categories").

**Product photos.** Cards show up to four photos (`with_card_photos()` loads them in one query): moving the mouse across the photo shows the next ones, phones swipe, and dots show which one is on screen. The product page has a swipe gallery with arrows, thumbnails and a large view, the Google rating, badges, and on phones an Add to Cart bar while the real button is off screen.
