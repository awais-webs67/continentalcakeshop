# The Continental Cake Shop Canberra

Online ordering website for The Continental Cake Shop, Shop 4, 38 Reed St N, Greenway ACT 2900.
Plain PHP 8.1+ and MySQL/MariaDB, with no framework and no Composer packages, so it runs on standard Australian shared hosting.

## What the customer side does

- Home, Shop (category filters, search, sort), product pages, Custom Cakes quote form, About, Contact
- Cart with free cake message, quantity and size per line
- Checkout with **pickup date and time booking** (respects opening hours, minimum notice, blocked dates and how far ahead people can book)
- Payment options: **Stripe card payment**, **PayID** and **bank transfer** (both with a payment screenshot upload)
- Private order page for the customer, order emails to the customer and the shop
- SEO: clean URLs, unique titles and descriptions, canonical tags, Open Graph, `Bakery`, `Product` and `BreadcrumbList` structured data, `sitemap.xml` and `robots.txt`
- Responsive layout with a mobile slide-in menu (close button, tap outside or Esc to close)

## Folder structure

```
config/       config.example.php (copy to config.php, which git ignores)
database/     schema.sql (tables) and seed.sql (current menu, prices and settings)
public/       web root: pages, assets, .htaccess, uploads/products for product photos
src/          shared PHP functions (catalog, cart, booking, payments, orders, settings, SEO)
templates/    header, footer and reusable partials
storage/      private uploads (payment screenshots, custom cake photos), never web accessible
```

Everything the shop will change later is stored in the database, not in the code:
products, sizes and prices, categories, homepage text, opening hours, pickup rules, payment switches and reviews.
The admin panel only needs to read and write these tables, using the same functions in `src/`.

## Setup

1. Create a MySQL database and user.
2. Import the tables and starter data:
   ```
   mysql -u USER -p DATABASE < database/schema.sql
   mysql -u USER -p DATABASE < database/seed.sql
   ```
3. Copy `config/config.example.php` to `config/config.php` and enter the site URL and database details.
4. Point the domain's document root at the `public/` folder (Apache with `mod_rewrite`).
5. Make `storage/uploads` and `public/uploads` writable by PHP.

Local development:

```
php -S 127.0.0.1:8000 -t public public/router.php
```

## Settings to fill in before going live

These live in the `settings` table (the admin panel will edit them):

| Setting | Purpose |
|---|---|
| `payid_identifier`, `payid_account_name` | PayID shown at checkout. PayID stays hidden until this is filled in |
| `bank_account_name`, `bank_bsb`, `bank_account_number` | Bank transfer details. Hidden until BSB and account number are filled in |
| `payment_stripe_enabled`, `stripe_secret_key`, `stripe_publishable_key`, `stripe_webhook_secret` | Card payments. Add a webhook in Stripe for `checkout.session.completed` pointing to `https://your-domain/stripe-webhook` |
| `payment_payid_enabled`, `payment_bank_enabled` | Switch each method on or off |
| `min_lead_hours`, `custom_lead_days`, `max_advance_days`, `slot_interval_minutes` | Pickup booking rules |
| `opening_hours` | JSON per weekday, e.g. `{"mon":["08:00","19:00"], ...}`. Use `null` for a closed day |
| `delivery_enabled`, `delivery_fee` | Optional delivery at checkout (off by default) |
| `social_facebook`, `social_instagram` | Shown in the footer and in structured data |

Closed days such as public holidays go in the `blocked_dates` table.
Product photos go in `public/uploads/products/` and the file name in `products.image` (e.g. `products/red-velvet.jpg`).
Size servings (e.g. "Serves 10 to 12") go in `product_sizes.servings`.
