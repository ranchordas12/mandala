# DEPLOY TO ghimireaastha.com.np
## Exact steps for panel.freehosting.com EVO Panel

---

## STEP 1 — Create the MySQL Database

1. Open your EVO panel → click **Databases**
2. Click **Create Database**
3. Note the full database name shown (e.g. `epiz_12345678_mandala`)
4. Note the database **username** and set a **password**
5. Note the **host** shown (usually `sql200.epizy.com` or `localhost`)

---

## STEP 2 — Run the SQL in phpMyAdmin

1. Click **phpMyAdmin** in your panel
2. In left sidebar, click your new database name
3. Click the **SQL** tab at the top
4. Open `database.sql` file (from this package) in Notepad
5. Select ALL the text (Ctrl+A), Copy it (Ctrl+C)
6. Paste into the phpMyAdmin SQL box
7. Click **Go**
8. You should see: "5 queries executed successfully"

This creates all tables AND your admin account:
- **Email:** admin@ghimireaastha.com.np
- **Password:** Mandala@2025
- ⚠️ Change this password after first login!

---

## STEP 3 — Edit config.php

Open `api/config.php` and fill in the 4 database values from Step 1:

```php
define('DB_HOST', 'sql200.epizy.com');   // ← your host
define('DB_NAME', 'epiz_12345678_mandala'); // ← your db name
define('DB_USER', 'epiz_12345678');      // ← your db user
define('DB_PASS', 'your_password');      // ← your db password
```

Also change the JWT_SECRET to something random (go to https://www.uuidgenerator.net and paste the result):
```php
define('JWT_SECRET', 'paste-your-uuid-here-plus-some-extra-random-text');
```

Save the file.

---

## STEP 4 — Upload Files via File Manager

1. In EVO panel, click **File Manager**
2. Navigate into `htdocs` folder (this is your public_html)
3. Upload these files maintaining the folder structure:

```
htdocs/
├── index.html          ← upload here
├── dashboard.php       ← upload here
├── post.php            ← upload here
├── .htaccess           ← upload here
├── database.sql        ← upload here (then delete after use)
└── api/                ← create this folder, then upload inside it:
    ├── config.php
    ├── auth.php
    ├── api.php
    ├── image.php
    ├── thumb.php
    └── download.php
```

### How to upload:
- Click **New Folder** → name it `api` → click Create
- Click into the `api` folder
- Click **Upload Files** → select all api/*.php files
- Go back to `htdocs` root
- Click **Upload Files** → select index.html, dashboard.php, post.php, .htaccess

---

## STEP 5 — Create the uploads folder

1. Still in File Manager → `htdocs` root
2. Click **New Folder** → name it `uploads` → Create
3. Click into `uploads` → click **New Folder** → name it `thumbs` → Create
4. Right-click the `uploads` folder → **Permissions** → set to `755`

---

## STEP 6 — Test Everything

1. Open browser → go to: `https://ghimireaastha.com.np`
2. You should see the gallery homepage
3. Click **Admin Portal**
4. Login with:
   - Email: `admin@ghimireaastha.com.np`
   - Password: `Mandala@2025`
5. Go to **Settings** → change your password immediately!
6. Go to **Upload Mandala** → upload a test image
7. Go back to homepage → your mandala should appear

---

## STEP 7 — Enable SSL (HTTPS)

1. In EVO panel, click **SSL Manager**
2. Click **Install Free SSL** or **AutoSSL**
3. Wait 5-10 minutes
4. Visit `https://ghimireaastha.com.np` — it should work

Then open `.htaccess` and uncomment the HTTPS redirect lines:
```apache
RewriteCond %{HTTPS} off
RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

---

## TROUBLESHOOTING

### "Database connection failed"
→ Double-check DB_HOST, DB_NAME, DB_USER, DB_PASS in config.php
→ The host is NOT always localhost — check your panel

### "Invalid credentials" on login
→ Make sure SQL ran successfully (check phpMyAdmin → users table has 1 row)
→ Try copy-pasting the SQL again

### Images not showing
→ Make sure `uploads/` and `uploads/thumbs/` folders exist
→ Check folder permissions are 755

### White page / PHP error
→ In File Manager, check error.log file if it exists
→ Make sure all PHP files uploaded correctly (not as 0 bytes)

### .htaccess not working
→ Some free hosts disable mod_rewrite — the site still works without it
→ Just means .html extension will show in URLs

---

## FILE STRUCTURE SUMMARY

```
htdocs/ (= public_html)
├── index.html         ← Gallery homepage
├── dashboard.php      ← Admin panel (requires login)
├── post.php           ← Blog post viewer
├── .htaccess          ← Security + URL settings
├── database.sql       ← Delete this after setup!
├── uploads/           ← Images stored here
│   └── thumbs/        ← Thumbnails stored here
└── api/
    ├── config.php     ← ⚠️ Edit DB + JWT_SECRET here
    ├── auth.php       ← Token logic
    ├── api.php        ← All API endpoints
    ├── image.php      ← Serve images
    ├── thumb.php      ← Serve thumbnails
    └── download.php   ← Protected download + watermark
```

---

## SECURITY NOTES

- `api/config.php` and `api/auth.php` cannot be accessed directly (blocked by .htaccess)
- Passwords are hashed with bcrypt (cost 12) — never stored in plain text
- Tokens expire after 7 days
- Download log records every download with IP address
- Artist initials are embedded in JPEG metadata on every download
- SQL injection is prevented through PDO prepared statements throughout

---

## DEFAULT LOGIN (change immediately!)

Email:    admin@ghimireaastha.com.np
Password: Mandala@2025
