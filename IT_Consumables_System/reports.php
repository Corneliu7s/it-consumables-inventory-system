<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Inventory Reports';

$lowStock = $pdo->query('
    SELECT i.*, c.category_name
    FROM items i
    LEFT JOIN categories c ON i.category_id = c.category_id
    WHERE i.quantity_in_stock <= i.minimum_threshold
    ORDER BY i.quantity_in_stock ASC
')->fetchAll();

$allItems = $pdo->query('
    SELECT i.*, c.category_name
    FROM items i
    LEFT JOIN categories c ON i.category_id = c.category_id
    ORDER BY c.category_name, i.item_name
')->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>
<div class="flex-between no-print" style="margin-top:-10px;margin-bottom:16px;">
    <p class="kicker" style="margin:0;">Low-stock warnings and a full inventory summary, ready to print for procurement.</p>
    <button type="button" class="btn btn-secondary btn-sm" onclick="window.print()">Print report</button>
</div>

<div class="section-block">
    <h2>Low-stock alert</h2>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Category</th>
                    <th>Quantity in stock</th>
                    <th>Minimum threshold</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$lowStock): ?>
                    <tr><td colspan="5" class="table-empty">All items are currently above their minimum threshold.</td></tr>
                <?php endif; ?>
                <?php foreach ($lowStock as $item): $status = stock_status($item); ?>
                    <tr class="<?= $item['quantity_in_stock'] <= 0 ? 'row-out-of-stock' : 'row-low-stock' ?>">
                        <td><?= h($item['item_name']) ?></td>
                        <td><?= h($item['category_name'] ?: 'Uncategorised') ?></td>
                        <td><?= (int) $item['quantity_in_stock'] ?> <?= h($item['unit_measure']) ?></td>
                        <td><?= (int) $item['minimum_threshold'] ?></td>
                        <td><span class="badge <?= $status['class'] ?>"><?= $status['label'] ?></span></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="section-block">
    <h2>Full inventory summary</h2>
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
                </tr>
            </thead>
            <tbody>
                <?php foreach ($allItems as $item): $status = stock_status($item); ?>
                    <tr>
                        <td><?= h($item['item_name']) ?></td>
                        <td><?= h($item['category_name'] ?: 'Uncategorised') ?></td>
                        <td><?= (int) $item['quantity_in_stock'] ?></td>
                        <td><?= (int) $item['minimum_threshold'] ?></td>
                        <td><?= h($item['unit_measure']) ?></td>
                        <td><span class="badge <?= $status['class'] ?>"><?= $status['label'] ?></span></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
