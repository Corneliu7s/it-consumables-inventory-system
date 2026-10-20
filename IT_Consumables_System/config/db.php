<?php
/**
 * db.php
 * Secure SQLite PDO Database Connection Script for the
 * IT Consumables Inventory and Stock Tracking System.
 *
 * - Opens (and creates, on first run) the SQLite database file.
 * - Applies the relational schema idempotently.
 * - Seeds demonstration data the first time the system runs,
 *   so the application is ready to present immediately.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$storageDir = __DIR__ . '/../storage';
if (!is_dir($storageDir)) {
    mkdir($storageDir, 0777, true);
}

$db_file = $storageDir . '/it_consumables.db';

try {
    $pdo = new PDO('sqlite:' . $db_file);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA foreign_keys = ON;');
} catch (PDOException $e) {
    die('Database Connection Failure: ' . $e->getMessage());
}

// ------------------------------------------------------------------
// Apply schema (idempotent - safe to run on every request)
// ------------------------------------------------------------------
$schemaPath = __DIR__ . '/../db/schema.sql';
if (file_exists($schemaPath)) {
    $pdo->exec(file_get_contents($schemaPath));
}

// ------------------------------------------------------------------
// Seed demonstration data on first run only (tables empty)
// ------------------------------------------------------------------
$userCount = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();

if ($userCount === 0) {
    $pdo->beginTransaction();

    // --- Default administrator account ---
    $adminHash = password_hash('Admin@2026', PASSWORD_DEFAULT);
    $pdo->prepare('INSERT INTO users (email, password_hash, full_name, role) VALUES (?, ?, ?, ?)')
        ->execute(['corneliusmunyao8@gmail.com', $adminHash, 'Cornelius Kithuka Munyao', 'Administrator']);

    // --- Categories ---
    $categories = [
        ['Network Cables',       'Ethernet patch cords, crimping accessories and connectors'],
        ['Input Peripherals',    'Keyboards, mice and other user input devices'],
        ['Storage Media',        'Flash drives, external drives and memory cards'],
        ['Printer Consumables',  'Toner cartridges, ink and printer maintenance items'],
    ];
    $catStmt = $pdo->prepare('INSERT INTO categories (category_name, description) VALUES (?, ?)');
    $catIds = [];
    foreach ($categories as $cat) {
        $catStmt->execute($cat);
        $catIds[$cat[0]] = (int) $pdo->lastInsertId();
    }

    // --- Items ---
    $items = [
        // name, category, qty, threshold, unit
        ['Cat6 Ethernet Cable (3m)',       'Network Cables',      42, 15, 'pieces'],
        ['RJ45 Connectors (Pack of 50)',   'Network Cables',       6, 10, 'packs'],
        ['USB Wired Keyboard',             'Input Peripherals',   18,  8, 'pieces'],
        ['Optical USB Mouse',              'Input Peripherals',    5,  8, 'pieces'],
        ['16GB Flash Drive',               'Storage Media',       25, 12, 'pieces'],
        ['1TB External Hard Drive',        'Storage Media',        3,  5, 'pieces'],
        ['HP LaserJet Toner Cartridge',    'Printer Consumables',  4, 10, 'cartridges'],
        ['Canon Ink Cartridge (Black)',    'Printer Consumables', 14, 10, 'cartridges'],
    ];
    $itemStmt = $pdo->prepare('INSERT INTO items (item_name, category_id, quantity_in_stock, minimum_threshold, unit_measure) VALUES (?, ?, ?, ?, ?)');
    $itemIds = [];
    foreach ($items as $item) {
        $itemStmt->execute([$item[0], $catIds[$item[1]], $item[2], $item[3], $item[4]]);
        $itemIds[$item[0]] = (int) $pdo->lastInsertId();
    }

    // --- Staff members ---
    $staff = [
        ['Faith Wanjiru',   'ICT Department',        'faith.wanjiru@ectech.ac.ke',   '0712345001'],
        ['Brian Otieno',    'Finance Department',    'brian.otieno@ectech.ac.ke',    '0712345002'],
        ['Grace Mueni',     'Registrar Office',      'grace.mueni@ectech.ac.ke',     '0712345003'],
        ['Samuel Kiptoo',   'ICT Department',        'samuel.kiptoo@ectech.ac.ke',   '0712345004'],
    ];
    $staffStmt = $pdo->prepare('INSERT INTO staff_members (full_name, department, email, phone_number) VALUES (?, ?, ?, ?)');
    $staffIds = [];
    foreach ($staff as $s) {
        $staffStmt->execute($s);
        $staffIds[$s[0]] = (int) $pdo->lastInsertId();
    }

    // --- Sample transaction history (audit trail) ---
    // Stock quantities above already reflect the outcome of this history.
    $transStmt = $pdo->prepare('INSERT INTO transactions (item_id, staff_id, quantity_issued, transaction_type, transaction_date, issued_by) VALUES (?, ?, ?, ?, ?, ?)');
    $sampleTransactions = [
        ['Cat6 Ethernet Cable (3m)',    'Samuel Kiptoo', 8, 'ISSUE',   '-6 days'],
        ['RJ45 Connectors (Pack of 50)','Faith Wanjiru', 4, 'ISSUE',   '-5 days'],
        ['USB Wired Keyboard',          'Grace Mueni',   2, 'ISSUE',   '-4 days'],
        ['Optical USB Mouse',           'Brian Otieno',  3, 'ISSUE',   '-4 days'],
        ['16GB Flash Drive',            'Grace Mueni',   5, 'ISSUE',   '-3 days'],
        ['HP LaserJet Toner Cartridge', 'Faith Wanjiru', 2, 'ISSUE',   '-2 days'],
        ['1TB External Hard Drive',     'Samuel Kiptoo', 1, 'ISSUE',   '-2 days'],
        ['Canon Ink Cartridge (Black)', 'Brian Otieno',  6, 'ISSUE',   '-1 days'],
        ['USB Wired Keyboard',          'Samuel Kiptoo', 1, 'RETURN',  '-1 days'],
        ['16GB Flash Drive',            'Faith Wanjiru', 10,'RESTOCK', 'today'],
    ];
    foreach ($sampleTransactions as $t) {
        $date = date('Y-m-d H:i:s', strtotime($t[4]));
        $transStmt->execute([$itemIds[$t[0]], $staffIds[$t[1]], $t[2], $t[3], $date, 'Cornelius Kithuka Munyao']);
    }

    $pdo->commit();
}
