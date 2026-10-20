<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Return / Restock Item';
$errors = [];

$items = $pdo->query('SELECT * FROM items ORDER BY item_name')->fetchAll();
$staffList = $pdo->query('SELECT * FROM staff_members ORDER BY full_name')->fetchAll();

$selectedItemId = '';
$selectedStaffId = '';
$quantity = '';
$type = 'RETURN';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedItemId  = (int) ($_POST['item_id'] ?? 0);
    $selectedStaffId = (int) ($_POST['staff_id'] ?? 0);
    $quantity        = (int) ($_POST['quantity'] ?? 0);
    $type            = $_POST['transaction_type'] ?? 'RETURN';

    if (!in_array($type, ['RETURN', 'RESTOCK'], true)) $errors[] = 'Invalid transaction type.';
    if (!$selectedItemId)  $errors[] = 'Please select an item.';
    if (!$selectedStaffId) $errors[] = 'Please select the staff member handling this transaction.';
    if ($quantity <= 0)    $errors[] = 'Quantity must be greater than zero.';

    if (!$errors) {
        $stmt = $pdo->prepare('SELECT * FROM items WHERE item_id = ?');
        $stmt->execute([$selectedItemId]);
        $item = $stmt->fetch();

        if (!$item) {
            $errors[] = 'The selected item no longer exists.';
        } else {
            try {
                $pdo->beginTransaction();

                $update = $pdo->prepare('UPDATE items SET quantity_in_stock = quantity_in_stock + ? WHERE item_id = ?');
                $update->execute([$quantity, $selectedItemId]);

                $insert = $pdo->prepare('
                    INSERT INTO transactions (item_id, staff_id, quantity_issued, transaction_type, issued_by)
                    VALUES (?, ?, ?, ?, ?)
                ');
                $insert->execute([$selectedItemId, $selectedStaffId, $quantity, $type, current_user_name()]);

                $pdo->commit();
                $verb = $type === 'RESTOCK' ? 'Restocked' : 'Recorded return of';
                set_flash('success', $verb . ' ' . $quantity . ' ' . $item['unit_measure'] . ' of "' . $item['item_name'] . '".');
                header('Location: transactions.php');
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                $errors[] = 'Transaction failed: ' . $e->getMessage();
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>
<p class="kicker">Record a staff return or a new stock shipment. Stock is incremented automatically.</p>

<div class="card" style="max-width:600px;">
    <div class="card-body">
        <form method="post" action="return_stock.php">
            <div class="form-grid single">
                <div class="form-group">
                    <label class="form-label">Transaction type</label>
                    <div class="radio-row">
                        <label><input type="radio" name="transaction_type" value="RETURN" <?= $type === 'RETURN' ? 'checked' : '' ?>> Staff return</label>
                        <label><input type="radio" name="transaction_type" value="RESTOCK" <?= $type === 'RESTOCK' ? 'checked' : '' ?>> New stock shipment (restock)</label>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label" for="item_id">Item</label>
                    <select id="item_id" name="item_id" required>
                        <option value="">Select an item&hellip;</option>
                        <?php foreach ($items as $item): ?>
                            <option value="<?= (int) $item['item_id'] ?>"
                                data-stock="<?= (int) $item['quantity_in_stock'] ?>"
                                data-unit="<?= h($item['unit_measure']) ?>"
                                <?= $selectedItemId === (int) $item['item_id'] ? 'selected' : '' ?>>
                                <?= h($item['item_name']) ?> (currently <?= (int) $item['quantity_in_stock'] ?> <?= h($item['unit_measure']) ?>)
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
                    <span class="form-hint">For a restock, select the officer receiving the shipment.</span>
                </div>
                <div class="form-group">
                    <label class="form-label" for="quantity">Quantity</label>
                    <input type="number" id="quantity" name="quantity" min="1" value="<?= h($quantity) ?>" required>
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Record transaction</button>
                <a href="items.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
