<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Checkout Item';
$errors = [];

$items = $pdo->query('SELECT * FROM items WHERE quantity_in_stock > 0 ORDER BY item_name')->fetchAll();
$staffList = $pdo->query('SELECT * FROM staff_members ORDER BY full_name')->fetchAll();

$selectedItemId = '';
$selectedStaffId = '';
$quantity = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedItemId  = (int) ($_POST['item_id'] ?? 0);
    $selectedStaffId = (int) ($_POST['staff_id'] ?? 0);
    $quantity        = (int) ($_POST['quantity'] ?? 0);

    if (!$selectedItemId)  $errors[] = 'Please select an item.';
    if (!$selectedStaffId) $errors[] = 'Please select the staff member receiving the item.';
    if ($quantity <= 0)    $errors[] = 'Quantity must be greater than zero.';

    if (!$errors) {
        $stmt = $pdo->prepare('SELECT * FROM items WHERE item_id = ?');
        $stmt->execute([$selectedItemId]);
        $item = $stmt->fetch();

        if (!$item) {
            $errors[] = 'The selected item no longer exists.';
        } elseif ($quantity > (int) $item['quantity_in_stock']) {
            $errors[] = 'Insufficient stock: only ' . $item['quantity_in_stock'] . ' ' . $item['unit_measure'] . ' available for "' . $item['item_name'] . '".';
        } else {
            try {
                $pdo->beginTransaction();

                // Atomic, conditional decrement: guarantees stock can never go negative
                // even under concurrent checkouts.
                $update = $pdo->prepare('
                    UPDATE items
                    SET quantity_in_stock = quantity_in_stock - ?
                    WHERE item_id = ? AND quantity_in_stock >= ?
                ');
                $update->execute([$quantity, $selectedItemId, $quantity]);

                if ($update->rowCount() === 0) {
                    throw new RuntimeException('Stock level changed before the checkout could complete. Please try again.');
                }

                $insert = $pdo->prepare('
                    INSERT INTO transactions (item_id, staff_id, quantity_issued, transaction_type, issued_by)
                    VALUES (?, ?, ?, ?, ?)
                ');
                $insert->execute([$selectedItemId, $selectedStaffId, $quantity, 'ISSUE', current_user_name()]);

                $pdo->commit();
                set_flash('success', 'Checked out ' . $quantity . ' ' . $item['unit_measure'] . ' of "' . $item['item_name'] . '".');
                header('Location: transactions.php');
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                $errors[] = 'Checkout failed: ' . $e->getMessage();
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>
<p class="kicker">Issue an item to a staff member. Stock is deducted automatically and atomically.</p>

<div class="card" style="max-width:600px;">
    <div class="card-body">
        <form method="post" action="checkout.php">
            <div class="form-grid single">
                <div class="form-group">
                    <label class="form-label" for="item_id">Item</label>
                    <select id="item_id" name="item_id" required>
                        <option value="">Select an item&hellip;</option>
                        <?php foreach ($items as $item): ?>
                            <option value="<?= (int) $item['item_id'] ?>"
                                data-stock="<?= (int) $item['quantity_in_stock'] ?>"
                                data-unit="<?= h($item['unit_measure']) ?>"
                                <?= $selectedItemId === (int) $item['item_id'] ? 'selected' : '' ?>>
                                <?= h($item['item_name']) ?> (<?= (int) $item['quantity_in_stock'] ?> <?= h($item['unit_measure']) ?> available)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <span class="form-hint" id="stockHint"></span>
                </div>
                <div class="form-group">
                    <label class="form-label" for="staff_id">Staff member</label>
                    <select id="staff_id" name="staff_id" required>
                        <option value="">Select staff member&hellip;</option>
                        <?php foreach ($staffList as $s): ?>
                            <option value="<?= (int) $s['staff_id'] ?>" <?= $selectedStaffId === (int) $s['staff_id'] ? 'selected' : '' ?>>
                                <?= h($s['full_name']) ?> &middot; <?= h($s['department']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="quantity">Quantity to issue</label>
                    <input type="number" id="quantity" name="quantity" min="1" value="<?= h($quantity) ?>" required>
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Checkout item</button>
                <a href="items.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
