# IndiaPetInsurance.com website

Static site (HTML, CSS, JS). No build step.

- `index.html` – page structure and content
- `assets/style.css` – all styling
- `assets/app.js` – ID builder, card download, orders, insurance quote, walking dog and cat
- `.htaccess` – HTTPS redirect and caching (Hostinger / Apache)

Deployed to Hostinger via hPanel → Advanced → Git. Every push to `main` goes live automatically.

**Before public launch:** remove the `noindex` meta tag in `index.html`, fill in the insurer and IRDAI details in the footer disclosure, and connect the forms to a backend (nothing is stored yet).
