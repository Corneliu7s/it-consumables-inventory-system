cat << 'EOF' > index.php
<?php
error_reporting(E_ALL & ~E_DEPRECATED);
session_start();
date_default_timezone_set('Africa/Nairobi');

$db_file = "it_consumables.db";
$db = new SQLite3($db_file);

// Ensure tables exist
$db->exec("CREATE TABLE IF NOT EXISTS users (user_id INTEGER PRIMARY KEY AUTOINCREMENT, username TEXT, email TEXT, password TEXT, role TEXT);");
$db->exec("CREATE TABLE IF NOT EXISTS categories (category_id INTEGER PRIMARY KEY AUTOINCREMENT, category_name TEXT);");
$db->exec("CREATE TABLE IF NOT EXISTS items (item_id INTEGER PRIMARY KEY AUTOINCREMENT, item_name TEXT, category_name TEXT, stock_quantity INTEGER, reorder_level INTEGER, unit_of_measure TEXT);");
$db->exec("CREATE TABLE IF NOT EXISTS staff_members (staff_id INTEGER PRIMARY KEY AUTOINCREMENT, full_name TEXT, department TEXT, email TEXT, phone_number TEXT);");
$db->exec("CREATE TABLE IF NOT EXISTS transactions (transaction_id INTEGER PRIMARY KEY AUTOINCREMENT, item_id INTEGER, staff_id INTEGER, quantity_issued INTEGER, transaction_type TEXT, transaction_date TEXT, duration_period TEXT, issued_by TEXT);");

@$db->exec("ALTER TABLE transactions ADD COLUMN duration_period TEXT;");

// Fix legacy items table if it still uses category_id instead of category_name
$res = $db->query("PRAGMA table_info(items)");
$has_cat_id = false;
$has_cat_name = false;
while ($col = $res->fetchArray(SQLITE3_ASSOC)) {
    if ($col['name'] === 'category_id') $has_cat_id = true;
    if ($col['name'] === 'category_name') $has_cat_name = true;
}
if ($has_cat_id && !$has_cat_name) {
    $db->exec("CREATE TABLE items_new (item_id INTEGER PRIMARY KEY AUTOINCREMENT, item_name TEXT, category_name TEXT, stock_quantity INTEGER, reorder_level INTEGER, unit_of_measure TEXT);");
    $db->exec("INSERT INTO items_new (item_id, item_name, category_name, stock_quantity, reorder_level, unit_of_measure) 
               SELECT i.item_id, i.item_name, COALESCE(c.category_name, 'General'), i.stock_quantity, i.reorder_level, i.unit_of_measure 
               FROM items i LEFT JOIN categories c ON i.category_id = c.category_id;");
    $db->exec("DROP TABLE items;");
    $db->exec("ALTER TABLE items_new RENAME TO items;");
} elseif (!$has_cat_name) {
    $db->exec("ALTER TABLE items ADD COLUMN category_name TEXT;");
}

// Set up or update your permanent admin credentials automatically
$admin_pass = password_hash("uto.con.58", PASSWORD_DEFAULT);
$user_check = $db->querySingle("SELECT COUNT(*) FROM users WHERE username = 'Cornelius' OR email = 'corneliusmunyao8@gmail.com'");
if ($user_check == 0) {
    $db->exec("INSERT INTO users (username, email, password, role) VALUES ('Cornelius', 'corneliusmunyao8@gmail.com', '$admin_pass', 'Admin');");
} else {
    $db->exec("UPDATE users SET username = 'Cornelius', email = 'corneliusmunyao8@gmail.com', password = '$admin_pass', role = 'Admin' WHERE username = 'Cornelius' OR email = 'corneliusmunyao8@gmail.com'");
}

// Seed 15 Categories if empty
$cat_check = $db->querySingle("SELECT COUNT(*) FROM categories");
if ($cat_check == 0) {
    $categories = [
        'Storage Devices', 'Input Devices', 'Display & Monitors', 'Networking Equipment',
        'Power & UPS', 'Printing Supplies', 'Memory & RAM', 'Processors & CPUs',
        'Motherboards', 'Cables & Adapters', 'Audio & Video', 'Server Hardware',
        'Tools & Maintenance', 'Security & Surveillance', 'Software Licenses'
    ];
    foreach ($categories as $cat) {
        $db->exec("INSERT INTO categories (category_name) VALUES ('$cat');");
    }
}

// Seed 15 Items if empty
$item_check = $db->querySingle("SELECT COUNT(*) FROM items");
if ($item_check == 0) {
    $items = [
        ['Kingston 1TB NVMe SSD', 'Storage Devices', 25, 5, 'Pieces'],
        ['Seagate 2TB External HDD', 'Storage Devices', 15, 3, 'Pieces'],
        ['Logitech Wireless Keyboard & Mouse', 'Input Devices', 30, 6, 'Sets'],
        ['Dell 24-inch IPS Monitor', 'Display & Monitors', 12, 2, 'Units'],
        ['TP-Link Gigabit Switch 24-Port', 'Networking Equipment', 8, 2, 'Units'],
        ['APC Smart-UPS 1500VA', 'Power & UPS', 5, 1, 'Units'],
        ['HP LaserJet Pro Toner Cartridge', 'Printing Supplies', 40, 10, 'Cartridges'],
        ['Corsair 16GB DDR4 RAM', 'Memory & RAM', 20, 4, 'Pieces'],
        ['Intel Core i7 12th Gen CPU', 'Processors & CPUs', 10, 2, 'Pieces'],
        ['ASUS ROG Strix Motherboard', 'Motherboards', 6, 2, 'Pieces'],
        ['Cat6 Ethernet Cable (305m Box)', 'Cables & Adapters', 10, 3, 'Boxes'],
        ['HDMI to VGA Active Adapter', 'Cables & Adapters', 25, 5, 'Pieces'],
        ['Logitech HD USB Webcam', 'Audio & Video', 18, 4, 'Units'],
        ['Precision Toolkit for Electronics', 'Tools & Maintenance', 14, 3, 'Kits'],
        ['Hikvision IP Security Camera', 'Security & Surveillance', 12, 3, 'Units']
    ];
    foreach ($items as $itm) {
        $db->exec("INSERT INTO items (item_name, category_name, stock_quantity, reorder_level, unit_of_measure) VALUES ('{$itm[0]}', '{$itm[1]}', {$itm[2]}, {$itm[3]}, '{$itm[4]}');");
    }
}

// Seed 15 Staff Members if empty
$staff_check = $db->querySingle("SELECT COUNT(*) FROM staff_members");
if ($staff_check == 0) {
    $staff = [
        ['Justin Kleins', 'IT Support', 'justin.kleins@ict.ac.ke', '+254712345678'],
        ['Jane Munyao', 'Networking', 'jane.munyao@ict.ac.ke', '0722334455'],
        ['Brian Omondi', 'Software Development', 'brian.omondi@ict.ac.ke', '+254733445566'],
        ['Mercy Wanjiku', 'Administration', 'mercy.wanjiku@ict.ac.ke', '0744556677'],
        ['David Kipkorir', 'Hardware Maintenance', 'david.kipkorir@ict.ac.ke', '+254755667788'],
        ['Amina Mohamed', 'Library Services', 'amina.mohamed@ict.ac.ke', '0766778899'],
        ['Kevin Otieno', 'Security Operations', 'kevin.otieno@ict.ac.ke', '+254777889900'],
        ['Faith Chepkoech', 'Academics', 'faith.chepkoech@ict.ac.ke', '0788990011'],
        ['Samuel Njoroge', 'Finance', 'samuel.njoroge@ict.ac.ke', '+254799001122'],
        ['Esther Akinyi', 'Procurement', 'esther.akinyi@ict.ac.ke', '0711223344'],
        ['Victor Mutua', 'Technical Training', 'victor.mutua@ict.ac.ke', '+254722114477'],
        ['Hassan Juma', 'Maintenance', 'hassan.juma@ict.ac.ke', '0733552288'],
        ['Grace Nyambura', 'Quality Assurance', 'grace.nyambura@ict.ac.ke', '+254744881155'],
        ['Peter Kamau', 'Networking', 'peter.kamau@ict.ac.ke', '0755992266'],
        ['Cynthia Chebet', 'ICT Helpdesk', 'cynthia.chebet@ict.ac.ke', '+254766337744']
    ];
    foreach ($staff as $st) {
        $db->exec("INSERT INTO staff_members (full_name, department, email, phone_number) VALUES ('{$st[0]}', '{$st[1]}', '{$st[2]}', '{$st[3]}');");
    }
}

$message = "";
$error = "";

// Handle Login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $identity = trim($_POST['identity']);
    $pass = $_POST['password'];

    $stmt = $db->prepare("SELECT * FROM users WHERE username = :id OR email = :id");
    $stmt->bindValue(':id', $identity, SQLITE3_TEXT);
    $result = $stmt->execute();
    $user = $result->fetchArray(SQLITE3_ASSOC);

    if ($user && password_verify($pass, $user['password'])) {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = trim($user['username']);
        $_SESSION['role'] = $user['role'];
        header("Location: index.php");
        exit;
    } else {
        $error = "Invalid username/email or password.";
    }
}

// Handle Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit;
}

$is_logged_in = isset($_SESSION['user_id']);
$is_admin = $is_logged_in && $_SESSION['role'] === 'Admin';
$active_tab = $_GET['tab'] ?? 'dashboard';
$current_user = $_SESSION['username'] ?? 'Cornelius';

// Handle Bulk Actions (Admin Only)
if ($is_admin && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action'])) {
    $bulk_action = $_POST['bulk_action'];
    $target_table = $_POST['target_table'] ?? '';
    $selected_ids = $_POST['selected_ids'] ?? [];

    $pk_map = [
        'categories' => 'category_id',
        'items' => 'item_id',
        'staff_members' => 'staff_id',
        'transactions' => 'transaction_id'
    ];

    if (!empty($selected_ids) && isset($pk_map[$target_table])) {
        $pk = $pk_map[$target_table];
        // Sanitize IDs to integers
        $ids_sanitized = array_map('intval', $selected_ids);
        $id_list = implode(',', $ids_sanitized);

        if ($bulk_action === 'delete') {
            $db->exec("DELETE FROM `$target_table` WHERE `$pk` IN ($id_list)");
            $message = "Selected record(s) deleted successfully from " . ucwords(str_replace('_', ' ', $target_table));
        } elseif ($bulk_action === 'copy') {
            // Duplicate records
            $cols_res = $db->query("PRAGMA table_info(`$target_table`)");
            $col_names = [];
            while ($c = $cols_res->fetchArray(SQLITE3_ASSOC)) {
                if ($c['name'] !== $pk) {
                    $col_names[] = $c['name'];
                }
            }
            if (!empty($col_names)) {
                $cols_str = '`' . implode('`,`', $col_names) . '`';
                foreach ($ids_sanitized as $copy_id) {
                    $db->exec("INSERT INTO `$target_table` ($cols_str) SELECT $cols_str FROM `$target_table` WHERE `$pk` = $copy_id");
                }
                $message = "Selected record(s) duplicated/copied successfully!";
            }
        } elseif ($bulk_action === 'export') {
            // Quick CSV export headers
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=' . $target_table . '_export.csv');
            $output = fopen('php://output', 'w');
            
            $res = $db->query("SELECT * FROM `$target_table` WHERE `$pk` IN ($id_list)");
            $header_written = false;
            while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
                if (!$header_written) {
                    fputcsv($output, array_keys($row));
                    $header_written = true;
                }
                fputcsv($output, $row);
            }
            fclose($output);
            exit;
        }
        header("Location: index.php?tab=" . $active_tab);
        exit;
    } else {
        $error = "Please select at least one record and a valid bulk action.";
    }
}

// Handle Form Submissions (Add / Edit)
if ($is_logged_in && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $table = $_POST['table_name'] ?? '';

    if ($is_admin) {
        if ($action === 'add') {
            if ($table === 'categories') {
                $name = SQLite3::escapeString($_POST['category_name']);
                $db->exec("INSERT INTO categories (category_name) VALUES ('$name')");
                $message = "Category added successfully!";
            } elseif ($table === 'items') {
                $name = SQLite3::escapeString($_POST['item_name']);
                $cat_name = SQLite3::escapeString($_POST['category_name']);
                $qty = (int)$_POST['stock_quantity'];
                $reorder = (int)$_POST['reorder_level'];
                $uom = SQLite3::escapeString($_POST['unit_of_measure']);
                $db->exec("INSERT INTO items (item_name, category_name, stock_quantity, reorder_level, unit_of_measure) VALUES ('$name', '$cat_name', $qty, $reorder, '$uom')");
                $message = "Item added successfully!";
            } elseif ($table === 'staff_members') {
                $fname = SQLite3::escapeString($_POST['full_name']);
                $dept = SQLite3::escapeString($_POST['department']);
                $email = SQLite3::escapeString($_POST['email']);
                $phone = SQLite3::escapeString($_POST['phone_number']);

                if (!preg_match('/^(\+254|254|0)[17]\d{8}$/', $phone)) {
                    $error = "Invalid phone number! Must be a valid Kenyan format (e.g., +254712345678 or 0712345678).";
                } else {
                    $db->exec("INSERT INTO staff_members (full_name, department, email, phone_number) VALUES ('$fname', '$dept', '$email', '$phone')");
                    $message = "Staff member added successfully!";
                }
            }
        } elseif ($action === 'edit') {
            $edit_id = (int)$_POST['edit_id'];
            if ($table === 'categories') {
                $name = SQLite3::escapeString($_POST['category_name']);
                $db->exec("UPDATE categories SET category_name = '$name' WHERE category_id = $edit_id");
                $message = "Category updated successfully!";
            } elseif ($table === 'items') {
                $name = SQLite3::escapeString($_POST['item_name']);
                $cat_name = SQLite3::escapeString($_POST['category_name']);
                $qty = (int)$_POST['stock_quantity'];
                $reorder = (int)$_POST['reorder_level'];
                $uom = SQLite3::escapeString($_POST['unit_of_measure']);
                $db->exec("UPDATE items SET item_name = '$name', category_name = '$cat_name', stock_quantity = $qty, reorder_level = $reorder, unit_of_measure = '$uom' WHERE item_id = $edit_id");
                $message = "Item updated successfully!";
            } elseif ($table === 'staff_members') {
                $fname = SQLite3::escapeString($_POST['full_name']);
                $dept = SQLite3::escapeString($_POST['department']);
                $email = SQLite3::escapeString($_POST['email']);
                $phone = SQLite3::escapeString($_POST['phone_number']);

                if (!preg_match('/^(\+254|254|0)[17]\d{8}$/', $phone)) {
                    $error = "Invalid phone number format!";
                } else {
                    $db->exec("UPDATE staff_members SET full_name = '$fname', department = '$dept', email = '$email', phone_number = '$phone' WHERE staff_id = $edit_id");
                    $message = "Staff member updated successfully!";
                }
            }
        } elseif ($action === 'transaction') {
            $itemId = (int)$_POST['item_id'];
            $staffId = (int)$_POST['staff_id'];
            $qty = (int)$_POST['quantity_issued'];
            $type = SQLite3::escapeString($_POST['transaction_type']);
            $duration = SQLite3::escapeString($_POST['duration_period'] ?? '1 Year');

            $stockCheck = $db->prepare("SELECT item_name, stock_quantity FROM items WHERE item_id = :id");
            $stockCheck->bindValue(':id', $itemId, SQLITE3_INTEGER);
            $itemData = $stockCheck->execute()->fetchArray(SQLITE3_ASSOC);

            if ($type === 'Issue' && $itemData && $qty > (int)$itemData['stock_quantity']) {
                $error = "Error: Not enough stock! Only " . $itemData['stock_quantity'] . " left for " . $itemData['item_name'];
            } else {
                $date = date('Y-m-d H:i:s');
                $db->exec("BEGIN TRANSACTION;");
                $db->exec("INSERT INTO transactions (item_id, staff_id, quantity_issued, transaction_type, transaction_date, duration_period, issued_by) VALUES ($itemId, $staffId, $qty, '$type', '$date', '$duration', '$current_user')");
                
                if ($type === 'Issue') {
                    $db->exec("UPDATE items SET stock_quantity = stock_quantity - $qty WHERE item_id = $itemId");
                } else {
                    $db->exec("UPDATE items SET stock_quantity = stock_quantity + $qty WHERE item_id = $itemId");
                }
                $db->exec("COMMIT;");
                $message = "Transaction recorded successfully!";
            }
        }
    }
}

// Check if editing a record via single click/edit
$edit_record = null;
if ($is_admin && isset($_GET['edit_id']) && isset($_GET['table'])) {
    $e_table = $_GET['table'];
    $e_id = (int)$_GET['edit_id'];
    $pk_map = ['categories' => 'category_id', 'items' => 'item_id', 'staff_members' => 'staff_id'];
    if (isset($pk_map[$e_table])) {
        $pk = $pk_map[$e_table];
        $res = $db->query("SELECT * FROM `$e_table` WHERE `$pk` = $e_id");
        $edit_record = $res->fetchArray(SQLITE3_ASSOC);
    }
}

$tables_result = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' AND name NOT LIKE 'users'");
$tables_data = [];
$total_records = 0;

while ($row = $tables_result->fetchArray(SQLITE3_ASSOC)) {
    $t_name = $row['name'];
    
    if ($t_name === 'transactions') {
        $cols = ['ID', 'Item Name', 'Staff Member', 'Qty', 'Type', 'Issue Date', 'Allocated Period', 'Time In Use', 'Issued By'];
        $data_result = $db->query("SELECT t.transaction_id, i.item_name, s.full_name, t.quantity_issued, t.transaction_type, t.transaction_date, t.duration_period, t.issued_by FROM transactions t LEFT JOIN items i ON t.item_id = i.item_id LEFT JOIN staff_members s ON t.staff_id = s.staff_id");
        
        $rows = [];
        while ($data_row = $data_result->fetchArray(SQLITE3_ASSOC)) {
            $issue_time = new DateTime($data_row['transaction_date']);
            $now = new DateTime();
            $diff = $issue_time->diff($now);
            
            if ($diff->y > 0) {
                $time_in_use = $diff->y . " yr" . ($diff->y > 1 ? "s " : " ") . $diff->m . " mo";
            } elseif ($diff->m > 0) {
                $time_in_use = $diff->m . " mo " . $diff->d . " days";
            } elseif ($diff->d > 0) {
                $time_in_use = $diff->d . " day" . ($diff->d > 1 ? "s" : "");
            } else {
                $time_in_use = $diff->h . " hrs " . $diff->i . " mins";
            }

            $rows[] = [
                'id' => $data_row['transaction_id'],
                'vals' => [
                    $data_row['transaction_id'],
                    $data_row['item_name'] ?? 'N/A',
                    $data_row['full_name'] ?? 'N/A',
                    $data_row['quantity_issued'],
                    $data_row['transaction_type'],
                    $data_row['transaction_date'],
                    $data_row['duration_period'] ?? '1 Year',
                    $time_in_use,
                    $data_row['issued_by']
                ]
            ];
        }
    } else {
        $cols_result = $db->query("PRAGMA table_info(`$t_name`)");
        $cols = [];
        while ($col = $cols_result->fetchArray(SQLITE3_ASSOC)) {
            $cols[] = $col['name'];
        }
        $data_result = $db->query("SELECT * FROM `$t_name`");
        $rows = [];
        while ($data_row = $data_result->fetchArray(SQLITE3_NUM)) {
            $rows[] = [
                'id' => $data_row[0],
                'vals' => $data_row
            ];
        }
    }

    $total_records += count($rows);
    $tables_data[$t_name] = ['cols' => $cols, 'rows' => $rows];
}

$items_list = $db->query("SELECT item_id, item_name, stock_quantity FROM items WHERE stock_quantity > 0");
$staff_list = $db->query("SELECT staff_id, full_name, department FROM staff_members");
$categories_list = $db->query("SELECT category_name FROM categories");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IT Consumables Dashboard</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #0f172a; color: #f8fafc; display: flex; flex-direction: column; min-height: 100vh; }
        @media(min-width: 768px) {
            body { flex-direction: row; }
            sidebar { width: 240px; min-height: 100vh; position: fixed; }
            .main-content { margin-left: 240px; flex: 1; }
        }
        sidebar { background: #1e293b; border-bottom: 1px solid #334155; display: flex; flex-direction: column; width: 100%; }
        @media(min-width: 768px) { sidebar { border-right: 1px solid #334155; border-bottom: none; } }
        .sidebar-brand { padding: 15px 20px; font-size: 1rem; font-weight: bold; color: #38bdf8; border-bottom: 1px solid #334155; text-transform: uppercase; letter-spacing: 1px; }
        .nav-links { display: flex; overflow-x: auto; padding: 10px; gap: 5px; list-style: none; }
        @media(min-width: 768px) { .nav-links { flex-direction: column; overflow-x: visible; padding: 15px; gap: 8px; } }
        .nav-links li { flex-shrink: 0; }
        .nav-links a { display: block; padding: 10px 14px; color: #94a3b8; text-decoration: none; border-radius: 8px; font-weight: 500; font-size: 0.9rem; transition: 0.2s; white-space: nowrap; }
        .nav-links a:hover, .nav-links a.active { background: #0284c7; color: #fff; }
        .main-content { display: flex; flex-direction: column; width: 100%; }
        header { background: #1e293b; padding: 15px 20px; border-bottom: 1px solid #334155; font-size: 1.1rem; font-weight: 600; color: #f1f5f9; display: flex; justify-content: space-between; align-items: center; }
        .container { padding: 15px; }
        @media(min-width: 768px) { .container { padding: 30px; } }
        .alert { background: #22c55e22; border: 1px solid #22c55e; color: #4ade80; padding: 10px 15px; border-radius: 8px; margin-bottom: 20px; font-size: 0.9rem; }
        .error { background: #ef444422; border: 1px solid #ef4444; color: #f87171; padding: 10px 15px; border-radius: 8px; margin-bottom: 20px; font-size: 0.9rem; }
        .login-wrapper { display: flex; justify-content: center; align-items: center; min-height: 80vh; width: 100%; }
        .login-card { background: #1e293b; border: 1px solid #334155; padding: 30px; border-radius: 12px; width: 100%; max-width: 400px; }
        .login-card h2 { color: #38bdf8; margin-bottom: 20px; font-size: 1.4rem; text-align: center; }
        .cards-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 15px; margin-bottom: 20px; }
        @media(min-width: 768px) { .cards-grid { grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 30px; } }
        .card { background: #1e293b; border: 1px solid #334155; padding: 15px; border-radius: 12px; }
        .card h3 { font-size: 0.8rem; color: #94a3b8; text-transform: uppercase; margin-bottom: 6px; }
        .card .value { font-size: 1.5rem; font-weight: bold; color: #38bdf8; }
        .form-card, .table-card { background: #1e293b; border: 1px solid #334155; border-radius: 12px; overflow: hidden; margin-bottom: 20px; }
        .form-header, .table-header { padding: 15px; font-size: 1rem; font-weight: 600; border-bottom: 1px solid #334155; color: #e2e8f0; background: #243044; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; }
        .form-body { padding: 15px; }
        .form-group { margin-bottom: 12px; }
        .form-group label { display: block; font-size: 0.8rem; color: #94a3b8; margin-bottom: 5px; text-transform: uppercase; }
        .form-group input, .form-group select { width: 100%; padding: 10px; background: #0f172a; border: 1px solid #334155; color: #fff; border-radius: 6px; font-size: 0.9rem; }
        .btn { background: #0284c7; color: white; border: none; padding: 10px 15px; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 0.9rem; width: 100%; }
        .btn:hover { background: #0369a1; }
        .bulk-action-bar { display: flex; align-items: center; gap: 10px; background: #1a2332; padding: 10px 15px; border-bottom: 1px solid #334155; }
        .bulk-action-bar select, .bulk-action-bar button { padding: 8px 12px; background: #0f172a; border: 1px solid #334155; color: #fff; border-radius: 6px; font-size: 0.85rem; }
        .bulk-action-bar button { background: #0284c7; font-weight: 600; cursor: pointer; border: none; }
        .bulk-action-bar button:hover { background: #0369a1; }
        .logout-btn { background: #ef4444; color: white; padding: 6px 12px; border-radius: 6px; text-decoration: none; font-size: 0.8rem; font-weight: 600; }
        .logout-btn:hover { background: #dc2626; }
        .table-responsive { width: 100%; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; text-align: left; white-space: nowrap; }
        th, td { padding: 12px 15px; border-bottom: 1px solid #334155; font-size: 0.85rem; }
        th { background: #1a2332; color: #38bdf8; font-weight: 600; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.5px; }
        tr:hover { background: rgba(255, 255, 255, 0.02); }
        tr:last-child td { border-bottom: none; }
        input[type="checkbox"] { width: 16px; height: 16px; accent-color: #0284c7; cursor: pointer; }
    </style>
    <script>
        function toggleSelectAll(source, tableName) {
            checkboxes = document.querySelectorAll('.checkbox-' + tableName);
            for(var i=0, n=checkboxes.length;i<n;i++) {
                checkboxes[i].checked = source.checked;
            }
        }
    </script>
</head>
<body>
    <?php if (!$is_logged_in): ?>
        <div class="login-wrapper">
            <div class="login-card">
                <h2>IT Inventory Login</h2>
                <?php if (!empty($error)): ?>
                    <div class="error"><?= $error ?></div>
                <?php endif; ?>
                <form method="POST">
                    <input type="hidden" name="action" value="login">
                    <div class="form-group">
                        <label>Username or Email</label>
                        <input type="text" name="identity" required placeholder="corneliusmunyao8@gmail.com">
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" required placeholder="••••••••">
                    </div>
                    <button type="submit" class="btn">Login</button>
                </form>
            </div>
        </div>
    <?php else: ?>
        <sidebar>
            <div class="sidebar-brand">IT Inventory Hub</div>
            <ul class="nav-links">
                <li><a href="?tab=dashboard" class="<?= $active_tab == 'dashboard' ? 'active' : '' ?>">📊 Dashboard</a></li>
                <?php foreach ($tables_data as $t_name => $t_info): ?>
                    <li><a href="?tab=<?= $t_name ?>" class="<?= $active_tab == $t_name ? 'active' : '' ?>">📁 <?= ucwords(str_replace('_', ' ', $t_name)) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </sidebar>
        <div class="main-content">
            <header>
                <span><?= $active_tab == 'dashboard' ? 'Overview Dashboard' : 'Management / ' . ucwords(str_replace('_', ' ', $active_tab)) ?></span>
                <div style="display: flex; align-items: center; gap: 15px;">
                    <span style="font-size: 0.85rem; color: #94a3b8;">User: <strong><?= htmlspecialchars($_SESSION['username']) ?></strong> (<?= $_SESSION['role'] ?>)</span>
                    <a href="?logout=1" class="logout-btn">Logout</a>
                </div>
            </header>
            <div class="container">
                <?php if (!empty($message)): ?>
                    <div class="alert"><?= $message ?></div>
                <?php endif; ?>
                <?php if (!empty($error)): ?>
                    <div class="error"><?= $error ?></div>
                <?php endif; ?>

                <?php if ($active_tab == 'dashboard'): ?>
                    <div class="cards-grid">
                        <div class="card"><h3>Total Tables</h3><div class="value"><?= count($tables_data) ?></div></div>
                        <div class="card"><h3>Total Records</h3><div class="value"><?= $total_records ?></div></div>
                        <div class="card"><h3>Engine</h3><div class="value" style="color: #22c55e; font-size: 1.2rem; margin-top: 5px;">SQLite</div></div>
                    </div>
                    <?php foreach ($tables_data as $t_name => $t_info): ?>
                        <div class="table-card">
                            <div class="table-header">Table: <?= strtoupper($t_name) ?></div>
                            <div class="table-responsive">
                                <table>
                                    <tr>
                                        <?php foreach ($t_info['cols'] as $col): ?>
                                            <th><?= htmlspecialchars($col) ?></th>
                                        <?php endforeach; ?>
                                    </tr>
                                    <?php if (empty($t_info['rows'])): ?>
                                        <tr><td colspan="<?= count($t_info['cols']) ?>" style="text-align: center; color: #94a3b8;">No records found.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($t_info['rows'] as $row_item): ?>
                                            <tr>
                                                <?php foreach ($row_item['vals'] as $val): ?>
                                                    <td><?= htmlspecialchars($val) ?></td>
                                                <?php endforeach; ?>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </table>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <?php if ($is_admin): ?>
                        <div class="form-card">
                            <div class="form-header">
                                <?php if ($edit_record): ?>
                                    Editing Record in <?= strtoupper($active_tab) ?> (ID: <?= $_GET['edit_id'] ?>)
                                <?php else: ?>
                                    <?= $active_tab == 'transactions' ? 'Process New Transaction / Issue Item' : 'Add New Record to ' . strtoupper($active_tab) ?>
                                <?php endif; ?>
                            </div>
                            <div class="form-body">
                                <form method="POST">
                                    <?php if ($active_tab == 'transactions'): ?>
                                        <input type="hidden" name="action" value="transaction">
                                        <div class="form-group">
                                            <label>Select Item (Only In-Stock Items Shown)</label>
                                            <select name="item_id" required>
                                                <?php 
                                                $has_stock = false;
                                                while ($item = $items_list->fetchArray(SQLITE3_ASSOC)): 
                                                    $has_stock = true;
                                                ?>
                                                    <option value="<?= $item['item_id'] ?>"><?= htmlspecialchars($item['item_name']) ?> (Stock: <?= $item['stock_quantity'] ?>)</option>
                                                <?php endwhile; ?>
                                                <?php if (!$has_stock): ?>
                                                    <option value="" disabled selected>No items currently in stock!</option>
                                                <?php endif; ?>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label>Select Staff Member</label>
                                            <select name="staff_id" required>
                                                <?php while ($staff = $staff_list->fetchArray(SQLITE3_ASSOC)): ?>
                                                    <option value="<?= $staff['staff_id'] ?>"><?= htmlspecialchars($staff['full_name']) ?> - <?= htmlspecialchars($staff['department']) ?></option>
                                                <?php endwhile; ?>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label>Quantity</label>
                                            <input type="number" name="quantity_issued" required value="1" min="1">
                                        </div>
                                        <div class="form-group">
                                            <label>Allocated Usage Period</label>
                                            <input type="text" name="duration_period" required value="1 Year" placeholder="e.g. 1 Year">
                                        </div>
                                        <div class="form-group">
                                            <label>Transaction Type</label>
                                            <select name="transaction_type">
                                                <option value="Issue">Issue Item</option>
                                                <option value="Return">Return Item</option>
                                            </select>
                                        </div>
                                        <button type="submit" class="btn" style="width: auto;">Submit Transaction</button>
                                    <?php else: ?>
                                        <input type="hidden" name="action" value="<?= $edit_record ? 'edit' : 'add' ?>">
                                        <input type="hidden" name="table_name" value="<?= $active_tab ?>">
                                        <?php if ($edit_record): ?>
                                            <input type="hidden" name="edit_id" value="<?= $_GET['edit_id'] ?>">
                                        <?php endif; ?>
                                        
                                        <?php if ($active_tab == 'categories'): ?>
                                            <div class="form-group">
                                                <label>Category Name</label>
                                                <input type="text" name="category_name" required value="<?= htmlspecialchars($edit_record['category_name'] ?? '') ?>" placeholder="e.g. Storage Devices">
                                            </div>
                                        <?php elseif ($active_tab == 'items'): ?>
                                            <div class="form-group">
                                                <label>Item Name</label>
                                                <input type="text" name="item_name" required value="<?= htmlspecialchars($edit_record['item_name'] ?? '') ?>" placeholder="e.g. Kingston 1TB SSD">
                                            </div>
                                            <div class="form-group">
                                                <label>Category Name</label>
                                                <select name="category_name" required>
                                                    <option value="" disabled>-- Select Category --</option>
                                                    <?php while ($cat = $categories_list->fetchArray(SQLITE3_ASSOC)): ?>
                                                        <option value="<?= htmlspecialchars($cat['category_name']) ?>" <?= ($edit_record && $edit_record['category_name'] == $cat['category_name']) ? 'selected' : '' ?>><?= htmlspecialchars($cat['category_name']) ?></option>
                                                    <?php endwhile; ?>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label>Stock Quantity</label>
                                                <input type="number" name="stock_quantity" required value="<?= $edit_record['stock_quantity'] ?? 10 ?>">
                                            </div>
                                            <div class="form-group">
                                                <label>Reorder Level</label>
                                                <input type="number" name="reorder_level" required value="<?= $edit_record['reorder_level'] ?? 2 ?>">
                                            </div>
                                            <div class="form-group">
                                                <label>Unit of Measure</label>
                                                <input type="text" name="unit_of_measure" required value="<?= htmlspecialchars($edit_record['unit_of_measure'] ?? 'Pieces') ?>">
                                            </div>
                                        <?php elseif ($active_tab == 'staff_members'): ?>
                                            <div class="form-group">
                                                <label>Full Name</label>
                                                <input type="text" name="full_name" required value="<?= htmlspecialchars($edit_record['full_name'] ?? '') ?>" placeholder="e.g. John Doe">
                                            </div>
                                            <div class="form-group">
                                                <label>Department</label>
                                                <input type="text" name="department" required value="<?= htmlspecialchars($edit_record['department'] ?? '') ?>" placeholder="e.g. Networking">
                                            </div>
                                            <div class="form-group">
                                                <label>Email</label>
                                                <input type="email" name="email" required value="<?= htmlspecialchars($edit_record['email'] ?? '') ?>" placeholder="john@example.com">
                                            </div>
                                            <div class="form-group">
                                                <label>Phone Number</label>
                                                <input type="text" name="phone_number" required 
                                                       value="<?= htmlspecialchars($edit_record['phone_number'] ?? '') ?>"
                                                       pattern="^(\+254|254|0)[17]\d{8}$" 
                                                       placeholder="e.g. +254712345678 or 0712345678">
                                            </div>
                                        <?php endif; ?>
                                        <div style="display: flex; gap: 10px;">
                                            <button type="submit" class="btn" style="width: auto;"><?= $edit_record ? 'Update Record' : 'Save Record' ?></button>
                                            <?php if ($edit_record): ?>
                                                <a href="?tab=<?= $active_tab ?>" class="btn" style="background: #64748b; text-decoration: none; display: inline-block; text-align: center; line-height: normal; padding: 10px 15px;">Cancel</a>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </form>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="form-card" style="padding: 20px; color: #94a3b8; font-size: 0.9rem;">
                            🔒 Read-Only Mode: Only the Admin can manage or modify records.
                        </div>
                    <?php endif; ?>

                    <?php if (isset($tables_data[$active_tab])): $t_info = $tables_data[$active_tab]; ?>
                        <form method="POST">
                            <input type="hidden" name="target_table" value="<?= $active_tab ?>">
                            <div class="table-card">
                                <div class="table-header">
                                    <span>Records for <?= strtoupper($active_tab) ?></span>
                                    <?php if ($is_admin && $active_tab !== 'transactions' && !empty($t_info['rows'])): ?>
                                        <div style="display: flex; align-items: center; gap: 8px;">
                                            <select name="bulk_action" style="padding: 6px 10px; background: #0f172a; border: 1px solid #334155; color: #fff; border-radius: 6px; font-size: 0.85rem;">
                                                <option value="" disabled selected>-- Select Action --</option>
                                                <option value="delete">Delete Selected</option>
                                                <option value="copy">Duplicate / Copy Selected</option>
                                                <option value="export">Export Selected (CSV)</option>
                                            </select>
                                            <button type="submit" class="btn" style="padding: 6px 12px; font-size: 0.85rem; width: auto;" onclick="return confirm('Apply selected action to chosen records?');">Apply</button>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="table-responsive">
                                    <table>
                                        <tr>
                                            <?php if ($is_admin && $active_tab !== 'transactions' && !empty($t_info['rows'])): ?>
                                                <th style="width: 40px; text-align: center;"><input type="checkbox" onclick="toggleSelectAll(this, '<?= $active_tab ?>')"></th>
                                                <th>Quick Action</th>
                                            <?php endif; ?>
                                            <?php foreach ($t_info['cols'] as $col): ?>
                                                <th><?= htmlspecialchars($col) ?></th>
                                            <?php endforeach; ?>
                                        </tr>
                                        <?php if (empty($t_info['rows'])): ?>
                                            <tr><td colspan="<?= count($t_info['cols']) + 2 ?>" style="text-align: center; color: #94a3b8;">No records found.</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($t_info['rows'] as $row_item): ?>
                                                <tr>
                                                    <?php if ($is_admin && $active_tab !== 'transactions'): ?>
                                                        <td style="text-align: center;"><input type="checkbox" name="selected_ids[]" value="<?= $row_item['id'] ?>" class="checkbox-<?= $active_tab ?>"></td>
                                                        <td>
                                                            <a href="?tab=<?= $active_tab ?>&edit_id=<?= $row_item['id'] ?>&table=<?= $active_tab ?>" style="color: #38bdf8; text-decoration: none; font-weight: 600; font-size: 0.8rem;">Edit</a>
                                                        </td>
                                                    <?php endif; ?>
                                                    <?php foreach ($row_item['vals'] as $val): ?>
                                                        <td><?= htmlspecialchars($val) ?></td>
                                                    <?php endforeach; ?>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </table>
                                </div>
                            </div>
                        </form>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</body>
</html>
EOF
