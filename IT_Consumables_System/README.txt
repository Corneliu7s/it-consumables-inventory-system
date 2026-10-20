================================================================
 AUTOMATED IT CONSUMABLES INVENTORY AND STOCK TRACKING SYSTEM
 Author: Cornelius Kithuka Munyao
 Institution: Eastlands College of Technology 
================================================================

This is a complete, working implementation of the system described: PHP 8 + SQLite (via PDO) + a Bootstrap-5-
style responsive interface. It runs entirely offline once installed,
which makes it ideal for a live assessment where internet
access is not guaranteed.

Note on Bootstrap: the report specifies Bootstrap 5 for the
frontend. Because Bootstrap's own files are normally pulled from an
online CDN, and this system must run fully offline, the interface
uses a small bundled stylesheet (assets/css/style.css) built with
the same component conventions (navbar, cards, badges, tables,
forms, buttons) so the system needs no internet connection at all,
on the machine.


----------------------------------------------------------------
1. REQUIREMENTS
----------------------------------------------------------------
- XAMPP (Windows, macOS or Linux) with PHP 8.0 or later.
- The PHP "pdo_sqlite" and "sqlite3" extensions, which are enabled
  by default in XAMPP - no extra setup needed.
- No internet connection is required to run or demonstrate the
  system.


----------------------------------------------------------------
2. INSTALLATION (XAMPP)
----------------------------------------------------------------
1. Install and open XAMPP.
2. Copy the whole "IT_Consumables_System" folder into your XAMPP
   htdocs directory, e.g.:
       Windows : C:\xampp\htdocs\IT_Consumables_System
       macOS   : /Applications/XAMPP/htdocs/IT_Consumables_System
       Linux   : /opt/lampp/htdocs/IT_Consumables_System
3. Open the XAMPP Control Panel and click "Start" next to Apache.
   (You do NOT need to start MySQL - this system uses SQLite, a
   self-contained file database, exactly as documented in the
   report.)
4. Open a web browser and visit:
       http://localhost/IT_Consumables_System/
5. You will land on the login page.


----------------------------------------------------------------
3. LOGGING IN
----------------------------------------------------------------
The very first time the app runs, it automatically creates the
SQLite database file (storage/it_consumables.db), builds all five
tables from db/schema.sql, and loads demonstration data (sample
categories, items, staff and transaction history) so the system is
immediately ready to present.

Demo storekeeper account:
    Email:    corneliusmunyao8@gmail.com
    Password: Admin@2026

(This matches the "corneliusmunyao8@gmail.com" account referenced
in Chapter 4 / Test Case TC-01 of the project report.)


----------------------------------------------------------------
4. WHAT IT SHOWS 
----------------------------------------------------------------
- Dashboard        : live counts, low-stock alerts, recent activity.
- Items            : full catalogue, add/edit/delete, category
                      filter and search, colour-coded stock status.
- Categories       : add/edit/delete categories.
- Staff            : staff directory (add/edit/delete).
- Checkout         : issue an item to a staff member - stock is
                      deducted atomically and cannot go negative
                      (try checking out more than is available to
                      see the validation in action - this maps to
                      Test Case TC-03 in the report).
- Return / Restock : record a staff return or a new shipment -
                      stock is incremented automatically.
- Transactions     : full searchable audit trail of every ISSUE,
                      RETURN and RESTOCK.
- Reports          : low-stock warning list plus a full inventory
                      summary, with a "Print report" button.


----------------------------------------------------------------
5. RESETTING THE DEMO DATA
----------------------------------------------------------------
To start over with a clean database, simply delete the database file and refresh the
page - it will be recreated and reseeded automatically:
    IT_Consumables_System/storage/it_consumables.db


----------------------------------------------------------------
6. PROJECT / FOLDER STRUCTURE
----------------------------------------------------------------
IT_Consumables_System/
|-- login.php, logout.php          Authentication
|-- index.php                      Dashboard
|-- categories.php / category_form.php / category_delete.php
|-- items.php / item_form.php / item_delete.php
|-- staff.php / staff_form.php / staff_delete.php
|-- checkout.php                   Issue items (stock decrement)
|-- return_stock.php               Returns & restocks (stock increment)
|-- transactions.php               Audit trail / transaction log
|-- reports.php                    Low-stock & summary reports
|-- config/db.php                  PDO SQLite connection, schema
|                                   bootstrap and demo-data seeding
|-- db/schema.sql                  Relational schema (5 tables)
|-- includes/                      Shared auth guard, header,
|                                   footer and helper functions
|-- assets/css/style.css           Offline stylesheet
|-- assets/js/app.js               Small client-side helpers
|-- storage/it_consumables.db      The SQLite database (auto-created,
                                    protected from direct web access)


----------------------------------------------------------------
7. TECHNOLOGY STACK (matches Chapter 2.5 of the report)
----------------------------------------------------------------
- Database : SQLite 3 (via PHP PDO), foreign keys enforced,
             atomic transactions for stock updates.
- Backend  : PHP 8, PDO prepared statements throughout, sessions
             for authentication, password_hash()/password_verify()
             for secure credential storage.
- Frontend : HTML5, CSS3, responsive layout (mobile and desktop),
             Bootstrap-5-equivalent component styling, bundled
             locally for offline use.


----------------------------------------------------------------
8. SECURITY NOTES
----------------------------------------------------------------
- All database queries use PDO prepared statements (no raw string
  concatenation), preventing SQL injection.
- Passwords are stored using PHP's password_hash() (bcrypt) and
  verified with password_verify() - never stored in plain text.
- Every page except login.php requires an authenticated session.
- The config/, includes/, db/ and storage/ folders each contain an
  .htaccess file that blocks direct browser access, so the database
  file and internal PHP includes cannot be downloaded or executed
  directly.
