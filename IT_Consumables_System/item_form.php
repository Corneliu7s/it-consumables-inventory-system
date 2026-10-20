<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$item = [
    'item_name' => '', 'category_id' => '', 'quantity_in_stock' => 0,
    'minimum_threshold' => 5, 'unit_measure' => '',
];
$pageTitle = $id ? 'Edit Item' : 'Add Item';
$errors = [];

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM items WHERE item_id = ?');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if ($found) {
        $item = $found;
    } else {
        set_flash('danger', 'Item not found.');
        header('Location: items.php');
        exit;
    }
}

$categories = $pdo->query('SELECT * FROM categories ORDER BY category_name')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item['item_name']         = trim($_POST['item_name'] ?? '');
    $item['category_id']       = $_POST['category_id'] !== '' ? (int) $_POST['category_id'] : null;
    $item['quantity_in_stock'] = (int) ($_POST['quantity_in_stock'] ?? 0);
    $item['minimum_threshold'] = (int) ($_POST['minimum_threshold'] ?? 0);
    $item['unit_measure']      = trim($_POST['unit_measure'] ?? '');

    if ($item['item_name'] === '') $errors[] = 'Item name is required.';
    if ($item['unit_measure'] === '') $errors[] = 'Unit of measure is required (e.g. pieces, packs, cartridges).';
    if ($item['quantity_in_stock'] < 0) $errors[] = 'Quantity in stock cannot be negative.';
    if ($item['minimum_threshold'] < 0) $errors[] = 'Minimum threshold cannot be negative.';

    if (!$errors) {
        if ($id) {
            $stmt = $pdo->prepare('UPDATE items SET item_name = ?, category_id = ?, quantity_in_stock = ?, minimum_threshold = ?, unit_measure = ? WHERE item_id = ?');
            $stmt->execute([$item['item_name'], $item['category_id'], $item['quantity_in_stock'], $item['minimum_threshold'], $item['unit_measure'], $id]);
            set_flash('success', 'Item updated successfully.');
        } else {
            $stmt = $pdo->prepare('INSERT INTO items (item_name, category_id, quantity_in_stock, minimum_threshold, unit_measure) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$item['item_name'], $item['category_id'], $item['quantity_in_stock'], $item['minimum_threshold'], $item['unit_measure']]);
            set_flash('success', 'Item added successfully.');
        }
        header('Location: items.php');
        exit;
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="card" style="max-width:700px;">
    <div class="card-body">
        <form method="post" action="<?= $id ? 'item_form.php?id=' . $id : 'item_form.php' ?>">
            <div class="form-grid">
                <div class="form-group full">
                    <label class="form-label" for="item_name">Item name</label>
                    <input type="text" id="item_name" name="item_name" value="<?= h($item['item_name']) ?>" required autofocus>
                </div>
                <div class="form-group">
                    <label class="form-label" for="category_id">Category</label>
                    <select id="category_id" name="category_id">
                        <option value="">Uncategorised</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= (int) $cat['category_id'] ?>" <?= (int) $item['category_id'] === (int) $cat['category_id'] ? 'selected' : '' ?>>
                                <?= h($cat['category_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="unit_measure">Unit of measure</label>
                    <input type="text" id="unit_measure" name="unit_measure" placeholder="e.g. pieces, packs, cartridges" value="<?= h($item['unit_measure']) ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="quantity_in_stock">Quantity in stock</label>
                    <input type="number" id="quantity_in_stock" name="quantity_in_stock" min="0" value="<?= h($item['quantity_in_stock']) ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="minimum_threshold">Minimum threshold</label>
                    <input type="number" id="minimum_threshold" name="minimum_threshold" min="0" value="<?= h($item['minimum_threshold']) ?>" required>
                    <span class="form-hint">Dashboard and reports flag the item as low stock at or below this level.</span>
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><?= $id ? 'Save changes' : 'Add item' ?></button>
                <a href="items.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
