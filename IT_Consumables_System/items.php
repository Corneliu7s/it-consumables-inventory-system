<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Inventory Items';

$search = trim($_GET['q'] ?? '');
$categoryFilter = isset($_GET['category_id']) && $_GET['category_id'] !== '' ? (int) $_GET['category_id'] : null;

$sql = '
    SELECT i.*, c.category_name
    FROM items i
    LEFT JOIN categories c ON i.category_id = c.category_id
    WHERE 1=1
';
$params = [];

if ($search !== '') {
    $sql .= ' AND i.item_name LIKE ?';
    $params[] = "%$search%";
}
if ($categoryFilter) {
    $sql .= ' AND i.category_id = ?';
    $params[] = $categoryFilter;
}
$sql .= ' ORDER BY i.item_name';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll();

$categories = $pdo->query('SELECT * FROM categories ORDER BY category_name')->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>
<p class="kicker">Catalogue of all IT consumable items, with live stock levels.</p>

<div class="filter-bar">
    <form method="get" action="items.php" class="filter-bar" style="margin:0;">
        <div class="form-group">
            <label class="form-label" for="q">Search</label>
            <input type="text" id="q" name="q" placeholder="Search by item name" value="<?= h($search) ?>">
        </div>
        <div class="form-group">
            <label class="form-label" for="category_id">Category</label>
            <select id="category_id" name="category_id">
                <option value="">All categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= (int) $cat['category_id'] ?>" <?= $categoryFilter === (int) $cat['category_id'] ? 'selected' : '' ?>>
                        <?= h($cat['category_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <button type="submit" class="btn btn-secondary">Filter</button>
        </div>
    </form>
    <div style="margin-left:auto;">
        <a href="item_form.php" class="btn btn-primary">Add item</a>
    </div>
</div>

<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Item</th>
                <th>Category</th>
                <th>Quantity in stock</th>
                <th>Minimum threshold</th>
                <th>Unit</th>
                <th>Status</th>
                <th class="text-right">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!$items): ?>
                <tr><td colspan="7" class="table-empty">No items match your search.</td></tr>
            <?php endif; ?>
            <?php foreach ($items as $item): $status = stock_status($item); ?>
                <tr class="<?= $item['quantity_in_stock'] <= 0 ? 'row-out-of-stock' : ($item['quantity_in_stock'] <= $item['minimum_threshold'] ? 'row-low-stock' : '') ?>">
                    <td><?= h($item['item_name']) ?></td>
                    <td><?= h($item['category_name'] ?: 'Uncategorised') ?></td>
                    <td><?= (int) $item['quantity_in_stock'] ?></td>
                    <td><?= (int) $item['minimum_threshold'] ?></td>
                    <td><?= h($item['unit_measure']) ?></td>
                    <td><span class="badge <?= $status['class'] ?>"><?= $status['label'] ?></span></td>
                    <td class="text-right">
                        <a href="item_form.php?id=<?= (int) $item['item_id'] ?>" class="btn btn-sm btn-secondary">Edit</a>
                        <form action="item_delete.php" method="post" class="btn-link-form" data-confirm="Delete '<?= h($item['item_name']) ?>'? This cannot be undone.">
                            <input type="hidden" name="id" value="<?= (int) $item['item_id'] ?>">
                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
