# IndiaPetInsurance.com

Website + backend for IndiaPetInsurance.com (Scram Technologies Pvt. Ltd.). Plain HTML/CSS/JS front end and a PHP + MySQL backend that runs on Hostinger shared hosting. No build step.

## What's where

| Path | What it is |
|---|---|
| `index.html`, `assets/` | Public website, ID builder, card download, orders, insurance quote |
| `api/` | JSON API used by the website (`pet.php`, `order.php`, `quote.php`, `auth.php`) |
| `api/lib/` | Shared code and database schema (blocked from the web) |
| `admin/` | Admin panel: superadmin, admins and staff |
| `account/` | Owner portal: owners log in to see their IDs and change privacy |
| `p.php` | Public "found pet" page opened by the QR code: `/p/IPI-JK-26-123456` |
| `install.php` | One-time setup wizard (locks itself after setup) |
| `robots.txt`, `sitemap.xml`, `site.webmanifest` | SEO and install-to-home-screen |

## First-time setup on Hostinger

1. hPanel → **Databases → Management**: create a MySQL database and user. Note the database name, user and password.
2. Open `https://indiapetinsurance.com/install.php`, fill in the database details and create the superadmin (you).
3. Log in at `https://indiapetinsurance.com/admin/`.

The config file (`ipi-config.php`) and uploaded documents (`ipi-uploads/`) are created **one folder above `public_html`**, so they are never public and never overwritten by a Git deploy.

## Roles

- **superadmin**: everything, including adding and removing team users
- **admin**: everything except team management
- **staff**: view records, update order and quote status
- **owner**: dog/cat owners, created automatically when they make an ID; log in at `/account/`

## Notifications

Every new ID, owner signup, order, quote request and QR scan is written to the `notifications` table and shown in the admin panel. The table already has `channel` (`admin`, `email`, `whatsapp`, `sms`), `recipient`, `status` and `attempts` columns so email/WhatsApp/SMS sending can be added later as a worker without changing the site.

## Updating CSS/JS

Bump the `?v=` number on `assets/style.css` and `assets/app.js` in `index.html` so browsers fetch the new version.
