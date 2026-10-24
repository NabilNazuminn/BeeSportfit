# BeeSportFit Website (Plain PHP + MySQL)

Import gym equipment store for **BeeSportFit**. Dark theme with a luxury-gold accent. Public store + admin panel.
No framework, no Composer, no build step: just PHP files you can edit in any text editor.

## 1. Requirements
- PHP 8.1 or newer (extensions: pdo_mysql, fileinfo, mbstring, **gd** (shrinks big photos), exif – all standard on cPanel/Laragon)
- MySQL 5.7+ / MariaDB 10.3+
- Apache (the `.htaccess` files assume it). Nginx also works: see "Nginx" at the end.

## 2. Run it locally (XAMPP / Laragon)
1. Copy this whole folder to `htdocs/fitness-store/`.
2. Open phpMyAdmin → create an empty database named `fitness_store` (collation `utf8mb4_unicode_ci`) → **select it** →
   **Import** → choose `database/schema.sql`. (Optional) then import `database/seed_demo.sql` for sample products.
3. Copy `.env.example` to `.env` and set `DB_HOST`, `DB_NAME`, `DB_USER`, and `DB_PASS` for this computer's MySQL server
  (XAMPP/Laragon commonly use `localhost`, `root`, and an empty password). Keep `.env` private; it is excluded from Git.
  The `.env` file only contains connection settings, not database contents: on another computer, create/import the database
  there too, then make its own `.env` from `.env.example`.
4. Create the first admin (see section 4).
5. Visit `http://localhost/fitness-store/public/` – admin is at `/fitness-store/public/admin/`.

## 3. Deploy on shared hosting (cPanel)
Recommended layout so `app/` is NOT reachable from the web:
```
/home/USER/
├── app/              <- upload the app folder here (outside public_html)
├── database/         <- keep it here too (or don't upload; only needed for import)
└── public_html/      <- upload the CONTENTS of the public folder here
```
1. In cPanel create a MySQL database + user (add the user to the database with ALL privileges). Open phpMyAdmin,
   **click that database in the left list**, then Import `database/schema.sql` (it no longer creates the database itself,
   so it works with prefixed names like `youruser_fitness`).
2. Copy `.env.example` to `.env` and set `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` to the hosting provider's database
  credentials. Alternatively, configure these as server environment variables. Error details are hidden automatically on a real
   domain (`DEBUG` is detected: only `localhost` / `*.test` shows them).
3. Make `public_html/uploads/products/` and `uploads/brands/` writable (permission 755, or 775 if uploads fail).
4. Turn on HTTPS and uncomment the redirect lines in `public/.htaccess`.
5. Change the WhatsApp number, address and **email** in **Admin → Pengaturan** (the email receives new-order / lead / chat alerts).
6. Open the site once: it updates the database by itself if you upgraded from an older version (see `app/migrate.php`).

If you cannot use that layout and must upload everything into `public_html`, keep the root
`.htaccess` – it blocks browser access to `app/`, `database/` and `storage/`.
**Upgrading an existing site:** upload the new files over the old ones (keep your own `app/config.php` and `public/uploads/`).

## 4. Create the admin account
Option A (terminal): `php database/create_admin.php you@email.com "StrongPassword" "Your Name"`

Option B (no terminal, e.g. cPanel): open the site → **Register** a normal account, then in
phpMyAdmin open table `users` and change that row's `role` from `customer` to `admin`.
Log in at `/admin/login.php`.

## 5. Where to edit what
| I want to change...                         | Edit this                                   |
|---------------------------------------------|---------------------------------------------|
| Colors (gold!), fonts, spacing              | `public/assets/css/style.css` (top `:root`) |
| Logo                                        | replace `public/assets/img/logo.png` (keep the name; transparent PNG, ~4:1 wide) |
| Favicon / tab icon                          | `public/assets/img/favicon.png`, `apple-touch-icon.png` |
| Tagline under the logo                      | `SITE_TAGLINE` in `app/config.php`          |
| Header / menu / footer                      | `app/views/header.php`, `footer.php`        |
| How a product card looks                    | `app/views/product_card.php`                |
| Home page text & sections                   | `public/index.php`                          |
| Database login                             | `.env` (copy from `.env.example`)           |
| Items per page, upload size                | `app/config.php`                            |
| WhatsApp, address, email, hero numbers      | Admin → Pengaturan (no code needed)         |
| Products, categories, brands, testimonials, pages | Admin panel (no code needed)          |
| Hero background photo                       | Instructions in `style.css` (`.hero-bg`)    |
| A database query                            | `app/functions/*.php`                       |

## 6. How a page works (read this first)
Every page starts with `require_once __DIR__ . '/../app/config.php';`, which loads the database,
helpers, login, CSRF and cart code. Then the page: (1) handles any form submission,
(2) loads data with a function like `get_products()`, (3) includes `header.php`, prints HTML,
includes `footer.php`.

Golden rules used throughout:
- Print database/user text with `e()` (HTML-escape). Build links with `url()` / `asset()`.
- Every POST form has `<?= csrf_field() ?>` and the handler calls `csrf_verify()`.
- Admin pages begin with `require_admin();`.
- All SQL uses prepared statements (`db_all`, `db_one`, `db_run`) – never put variables inside SQL strings.

## 7. Features
**New in this version:** order stock (reduces on order, "Habis" at 0, returns on cancel) · admin sets shipping cost and final/quote prices ·
order status rules + customer can cancel a new order · e-mails (to the owner and customers) · forgot/reset/change password + profile page ·
admin "Pelanggan" page (reset password, deactivate) · product search in the header · login/spam throttling by IP · photos auto-resized ·
robots.txt, sitemap, Open Graph + product structured data · pagination in admin lists · friendly error page.

Light/dark mode · animations · Live chat widget + admin inbox · Catalog with search/filter/sort/pagination · product pages with gallery & specs · session cart
(saved to the database for logged-in users) · checkout as **order** or **quote request** ·
customer accounts and order history · free-consultation form saved as leads · WhatsApp buttons ·
admin: dashboard, product CRUD with multi-photo upload, categories, order status workflow with
history, leads, testimonials, editable warranty/terms pages, site settings.

## 8. Before going live (checklist)
- [ ] Replace demo products/testimonials (or don't import `seed_demo.sql`)
- [ ] Set the real WhatsApp number, email, address (Admin → Pengaturan)
- [ ] Strong admin password; HTTPS enabled (the HTTPS redirect in `public/.htaccess` uncommented)
- [ ] Read and edit the warranty & terms text (Admin → Halaman). The text shipped is a **first draft**, have the owner/a lawyer check it.
- [ ] Send a test: place an order and check the owner email arrives (if not, ask the host to enable `mail()`, or use their SMTP; see "E-mail")
- [ ] After going live, put your domain in `public/robots.txt` (Sitemap line) and submit `https://YOUR-DOMAIN/sitemap.php` in Google Search Console
- [ ] Remove the sample photos in `public/uploads/` that you do not use
- [ ] Optional: replace `public/assets/img/hero.jpg` (keep it under ~400 KB) and the favicon

## Live chat (built in, no WhatsApp)
The round gold button at the bottom-right of every public page opens a chat panel. Visitors (guests or
logged-in customers) write to the admin; replies appear in the visitor's chat within ~4 seconds.
- **Admin → Live Chat**: inbox with unread counters, reply, mark finished, delete. The sidebar shows the unread number.
- **Admin → Pengaturan**: switch chat on/off and edit the welcome message.
- Works by short polling (plain PHP, no websocket server), so it runs on any normal shared hosting.
- Guests are recognised by a random cookie token (`bsf_chat`); the chat has a honeypot field and per-session rate limits.
- Any button can open the chat: add `data-chat-open` (and optionally `data-chat-message="text"`).
- **WhatsApp is only used for ordering**: "Pesan via WhatsApp" on product and cart pages, "Konfirmasi pesanan" on an order page,
  and the contact section. Set the number in Admin → Pengaturan.
- Existing database? Run `database/update_chat.sql` once to add the chat tables.

## Brand logos (Insight, Force USA, BRTW, DHZ)
Manage them in **Admin → Brand**: name, logo upload (PNG with transparent background), and the checkbox
"Logo hitam → tampilkan putih" for black logos (Force USA). A brand with a logo automatically appears in:
the dark **brand strip** under the hero (home), the **logo chips** above the catalog (click = filter by brand),
a **badge** on the product page, and a small **footer row**. Brands without a logo simply don't show in those places.
Logo files live in `public/uploads/brands/`. Existing database? Run `database/update_brand_logos.sql` once and copy that folder.

## Changing the logo
Three files in `public/assets/img/` (keep these exact names, transparent PNG):
`logo.png` = full logo (used in the dark footer), `logo-emblem.png` = emblem only (header, About, admin),
`favicon.png` / `apple-touch-icon.png` = browser tab icon. The site now adds `?v=<file time>` to every asset URL,
so a replaced image shows up right away. If it still doesn't change: check the file name is exactly `logo.png`,
you replaced it inside the folder the website actually runs from (e.g. `htdocs/fitness-store/public/assets/img/`),
and press Ctrl+F5 once (the favicon is cached the longest).

## Light / dark mode & animations
- The sun/moon button in the header (and "Mode Terang / Gelap" in the admin sidebar) switches the theme; the choice is
  remembered in the browser (`localStorage`, key `bsf_theme`). Default is **dark**; the loader in `app/views/theme_init.php`
  has a commented line if you prefer to follow the visitor's device setting.
- Dark colors live in `:root` at the top of `style.css`; light colors in the `:root[data-theme="light"]` block right below it.
  The hero and footer intentionally stay dark in light mode.
- Animations are in section 23 of `style.css` (hero entrance, shining title, button effects, scroll reveal).
  Add `class="reveal"` to any block to make it fade in on scroll. Visitors with "reduce motion" enabled get no animations.

## Dummy data to replace (BeeSportFit)
Everything below is placeholder text until the client supplies the real details:
- Admin → **Pengaturan**: WhatsApp number, email, address, Instagram, the 3 hero numbers + labels
  (only keep numbers that are TRUE; e.g. do not claim "500+ products" unless it is real).
- Admin → **Brand**: "Brand A–D" are dummies. Admin → **Produk**: sample products have no photos.
- Admin → **Testimoni**: sample testimonials are made up. Admin → **Halaman**: warranty & terms text.
- Already imported an older version of the database? Run `database/update_beesportfit.sql` once.

## Stock
- **Stock = 0 means "not tracked"**: the product can be ordered normally (use this for made-to-order / pre-order items).
- **Stock > 0 = tracked**: the cart is capped to the stock, each order reduces it, at 0 the product turns **Habis** automatically,
  and cancelling the order gives the units back. Quote requests never change stock.

## E-mail
The site sends e-mail with PHP's `mail()` (works on most cPanel hosts): to the shop email on every new order, quote, consultation and first chat message,
and to customers when they order or the status/price changes. On localhost nothing is sent: the text is written to `storage/mail.log` so you can read it.
If mail does not arrive on your host: check spam, ask the host to enable `mail()`, or tell me and I will add SMTP (PHPMailer).
Customers who cannot receive e-mail can be helped from **Admin → Pelanggan → Reset password** (gives a temporary password).

## Nginx
Apache reads `.htaccess`; Nginx does not. Add equivalent rules (the PHP code already sends the security headers):
```
location ~ ^/(app|database|storage)/ { deny all; }
location ^~ /uploads/ { location ~ \.php$ { deny all; } }
```
and point `root` to the `public` folder.

## Notes
- Prices use IDR.
- **Ordering happens on WhatsApp.** When a customer presses *Kirim* at checkout, the order is saved (so it shows in Admin → Pesanan with a code)
  and WhatsApp opens with the order already written (code, items, subtotal, address). Shipping cost and payment are agreed in that chat.
  The admin can then record the agreed shipping/prices in Admin → Pesanan → detail. No free-shipping or installation rules are built in
  (none were provided); if the client has such rules, tell the developer to add them.
- The cart page also has a direct "Pesan via WhatsApp" button that needs no account. Checkout itself requires a customer account.
- Every product photo is uploaded by the admin
  (a placeholder is shown until then).
- Payment gateway and automatic shipping rates are intentionally not included.
