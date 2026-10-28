# SHOOT KARO - Premium Photography & Film Marketplace

**Shoot Karo** is a production-ready, full-stack photography and cinematography booking marketplace inspired by the on-demand simplicity of Ola and Uber. It connects clients with vetted local photographers, cinematic videographers, viral mobile reel makers, and certified drone pilots.

Built for local execution using **XAMPP (Apache, PHP 8.x, and MySQL/MariaDB)** with native HTML5, custom monochrome CSS, vanilla JavaScript, and secure PDO prepared statements.

---

## Key Features

1. **Monochrome Design System**: High-contrast, minimalist Uber/Ola inspired aesthetics in pure black, white, and subtle grays. Zero external CDN dependencies, 100% offline-ready, and strictly **no emojis** anywhere in the interface.
2. **Camera-Shutter SVG Brand Identity**: Custom vector aperture-blade logo used consistently across header, footer, and browser favicon.
3. **Location Search Engine**: Instant city and area-based filtering for Pune (Baner, Kothrud, Hinjawadi, Wakad, Hadapsar, Shivajinagar) pulling real database queries.
4. **5-Step Interactive Booking Engine**:
   - **Step 1: Event Details & Venue Coordinates** (with auto-prefill for logged-in clients).
   - **Step 2: Equipment Selection** (Strictly displays *only* equipment offered by the chosen creator: Camera, Mobile, Drone, or combos).
   - **Step 3: Deliverable Specifications** (Photos, Video, Both, shoot duration, editing preferences).
   - **Step 4: Itemized Price Review** (Base price, equipment add-on discounts, duration surcharges, confirmed total, 20% advance, remaining balance).
   - **Step 5: Simulated Advance Payment** (UPI QR simulator, card test checkout, or pay later; generates unique `SK-BK-YYYYMMDD-XXXX` reference).
5. **Role-Based Portals**:
   - **Customer Dashboard**: Track shoot timeline, download high-res files/cloud links, and submit verified 1-5 star reviews upon shoot completion.
   - **Creator Studio**: Incoming booking request queue (Accept/Reject), shoot progression (Start Shoot, Complete Shoot), deliverables uploader, portfolio manager, availability toggle, and net earnings tracker (after 10% platform commission).
   - **Admin Command Center**: Executive KPI metrics, creator application verification, global bookings audit, commission ledger, manual payout settlement, customer complaints desk, and dynamic platform settings.

---

## Default Demo Credentials

All seed accounts are pre-configured in `database/shoot_karo.sql`:

| Role | Email Address | Password | Profile / Permissions |
| :--- | :--- | :--- | :--- |
| **System Admin** | `admin@shootkaro.local` | `Admin@12345` | Global admin dashboard, approvals, settings |
| **Approved Creator** | `aditya@shootkaro.local` | `Creator@12345` | Aditya Visuals & Films (Camera, Mobile, Drone) |
| **Approved Creator** | `priya@shootkaro.local` | `Creator@12345` | AeroVision Drone Labs (Drone, Camera) |
| **Approved Creator** | `vikram@shootkaro.local` | `Creator@12345` | Reel Craft Productions (Mobile Reels, Camera) |
| **Pending Creator** | `amit@shootkaro.local` | `Creator@12345` | Wakad Frame Works (Awaiting Admin Approval) |
| **Verified Customer** | `customer@shootkaro.local` | `Customer@12345` | Rohan Patil (Pre-seeded bookings & reviews) |

---

## XAMPP Installation & Setup Guide (Windows)

### Step 1: Install & Launch XAMPP
1. Download **XAMPP for Windows** (with PHP 8.0 or higher) from [apachefriends.org](https://www.apachefriends.org/).
2. Run the XAMPP installer and install to the standard default path: `C:\xampp`.
3. Open the **XAMPP Control Panel** (`C:\xampp\xampp-control.exe`).

### Step 2: Start Apache and MySQL
1. In the XAMPP Control Panel, click the **Start** button next to **Apache**.
2. Click the **Start** button next to **MySQL**.
3. Both modules should show green indicators with port numbers (typically `80, 443` for Apache, and `3306` for MySQL).

### Step 3: Project Location
Ensure the project files reside in your XAMPP `htdocs` directory:
```
C:\xampp\htdocs\ShootKaro\
```
*(A directory junction has also been created so both `http://localhost/ShootKaro/` and `http://localhost/shoot-karo/` work interchangeably).*

### Step 4: Import the Database in phpMyAdmin
1. Open your browser and navigate to:
   ```
   http://localhost/phpmyadmin/
   ```
2. Click **New** on the left navigation panel.
3. Enter database name: `shoot_karo_db` and select collation `utf8mb4_unicode_ci`, then click **Create**.
4. Select `shoot_karo_db` from the left sidebar, and click on the **Import** tab at the top.
5. Click **Choose File** and select:
   ```
   C:\xampp\htdocs\ShootKaro\database\shoot_karo.sql
   ```
6. Click **Go** (or **Import**) at the bottom. The 17 tables and seed dataset will be imported successfully.

*Alternatively, import via Command Prompt:*
```cmd
cmd /c "C:\xampp\mysql\bin\mysql.exe -u root < C:\xampp\htdocs\ShootKaro\database\shoot_karo.sql"
```

### Step 5: Configure Database Connection (Optional)
The default configuration in `config/database.php` uses standard XAMPP local settings:
- **Host**: `localhost`
- **Database**: `shoot_karo_db`
- **User**: `root`
- **Password**: `""` (empty by default in XAMPP)
- **Port**: `3306`

If your MySQL root user has a password, open `config/database.php` and update `DB_PASS`.

### Step 6: Verify Upload Folder Permissions
Ensure the following directories exist and are writable by Apache:
- `uploads/profiles/`
- `uploads/portfolio/`
- `uploads/deliverables/`

Each upload subfolder contains an `.htaccess` script prevention guard to block unauthorized execution of script files.

### Step 7: Launch the Website
Open your browser and visit:
```
http://localhost/ShootKaro/
```
or
```
http://localhost/shoot-karo/
```

---

## Troubleshooting Common Errors

### 1. "Can't connect to MySQL server on 'localhost' (10061)"
- **Cause**: MySQL service is not running in XAMPP.
- **Fix**: Open XAMPP Control Panel and click **Start** on MySQL, or run `C:\xampp\mysql_start.bat`. Check if another service (like an existing MariaDB or MySQL 8 Windows Service) is occupying port 3306.

### 2. Apache Port 80 / 443 Conflict
- **Cause**: Skype, VMware, or IIS might be bound to port 80.
- **Fix**: In XAMPP Control Panel, click **Config** on Apache -> `httpd.conf` and change `Listen 80` to `Listen 8080`. Then access the site at `http://localhost:8080/ShootKaro/`.

### 3. "Access denied for user 'root'@'localhost'"
- **Cause**: Your XAMPP MySQL installation has a non-empty password configured for `root`.
- **Fix**: Open `config/database.php` and set `DB_PASS` to your MySQL root password.

### 4. Upload Size Limitations
- **Cause**: Large ZIP files or video deliverables exceed PHP's default upload limit.
- **Fix**: In `C:\xampp\php\php.ini`, adjust:
  ```ini
  upload_max_filesize = 64M
  post_max_size = 64M
  ```
  Restart Apache after editing `php.ini`.

---

## Security Architecture

- **Prepared SQL Statements**: 100% of queries use PDO parameter binding, preventing SQL injection.
- **Password Protection**: Modern Bcrypt hashing via PHP `password_hash()` and `password_verify()`.
- **Session Protection**: CSRF tokens validated on all mutating POST requests; session fixation prevention with `session_regenerate_id()`.
- **Brute-Force Rate Limiting**: Built-in login rate limiter locks repeated failed attempts.
- **Strict File Upload Validation**: Files checked for extension whitelisting, file size limits, MIME type verification via `finfo`, and storage with cryptographically randomized filenames.
- **Server Recalculation**: Pricing, equipment add-ons, advance percentages, and commission earnings are recalculated on the server, never blindly trusted from client-side JavaScript.
