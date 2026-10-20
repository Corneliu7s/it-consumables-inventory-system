<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Dashboard';

$totalItems      = (int) $pdo->query('SELECT COUNT(*) FROM items')->fetchColumn();
$totalCategories = (int) $pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn();
$totalStaff      = (int) $pdo->query('SELECT COUNT(*) FROM staff_members')->fetchColumn();

$lowStockItems = $pdo->query('
    SELECT * FROM items
    WHERE quantity_in_stock <= minimum_threshold
    ORDER BY quantity_in_stock ASC
')->fetchAll();
$lowStockCount = count($lowStockItems);

$issuesToday = (int) $pdo->query("
    SELECT COUNT(*) FROM transactions
    WHERE transaction_type = 'ISSUE' AND date(transaction_date) = date('now')
")->fetchColumn();

$recentTransactions = $pdo->query('
    SELECT t.*, i.item_name, i.unit_measure, s.full_name AS staff_name
    FROM transactions t
    JOIN items i ON t.item_id = i.item_id
    JOIN staff_members s ON t.staff_id = s.staff_id
    ORDER BY t.transaction_date DESC, t.transaction_id DESC
    LIMIT 8
')->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>
<p class="kicker">Welcome back, <?= h(current_user_name()) ?>. Here is today's storeroom overview.</p>

<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-label">Catalogued items</div>
        <div class="stat-value"><?= $totalItems ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Categories</div>
        <div class="stat-value"><?= $totalCategories ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Registered staff</div>
        <div class="stat-value"><?= $totalStaff ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Items issued today</div>
        <div class="stat-value"><?= $issuesToday ?></div>
    </div>
    <div class="stat-card <?= $lowStockCount > 0 ? 'warn' : '' ?>">
        <div class="stat-label">Low-stock alerts</div>
        <div class="stat-value"><?= $lowStockCount ?></div>
    </div>
</div>

<div class="section-block">
    <div class="flex-between">
        <h2>Low-stock alerts</h2>
        <a href="reports.php" class="btn btn-sm btn-secondary">View full report</a>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Quantity in stock</th>
                    <th>Minimum threshold</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$lowStockItems): ?>
                    <tr><td colspan="4" class="table-empty">No items are currently below their minimum threshold.</td></tr>
                <?php endif; ?>
                <?php foreach ($lowStockItems as $item): $status = stock_status($item); ?>
                    <tr class="<?= $item['quantity_in_stock'] <= 0 ? 'row-out-of-stock' : 'row-low-stock' ?>">
                        <td><?= h($item['item_name']) ?></td>
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
    <div class="flex-between">
        <h2>Recent activity</h2>
        <a href="transactions.php" class="btn btn-sm btn-secondary">View all transactions</a>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Item</th>
                    <th>Staff member</th>
                    <th>Type</th>
                    <th>Quantity</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$recentTransactions): ?>
                    <tr><td colspan="5" class="table-empty">No transactions recorded yet.</td></tr>
                <?php endif; ?>
                <?php foreach ($recentTransactions as $t): ?>
                    <tr>
                        <td><?= h(format_datetime($t['transaction_date'])) ?></td>
                        <td><?= h($t['item_name']) ?></td>
                        <td><?= h($t['staff_name']) ?></td>
                        <td><span class="badge <?= transaction_badge_class($t['transaction_type']) ?>"><?= h($t['transaction_type']) ?></span></td>
                        <td><?= (int) $t['quantity_issued'] ?> <?= h($t['unit_measure']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
