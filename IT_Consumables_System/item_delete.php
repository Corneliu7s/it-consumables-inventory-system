<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['id'])) {
    $stmt = $pdo->prepare('DELETE FROM items WHERE item_id = ?');
    $stmt->execute([(int) $_POST['id']]);
    set_flash('success', 'Item deleted.');
}

header('Location: items.php');
exit;
