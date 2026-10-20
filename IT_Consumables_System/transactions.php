<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Transaction History';

$filterType = $_GET['type'] ?? '';
$search = trim($_GET['q'] ?? '');

$sql = '
    SELECT t.*, i.item_name, i.unit_measure, s.full_name AS staff_name, s.department
    FROM transactions t
    JOIN items i ON t.item_id = i.item_id
    JOIN staff_members s ON t.staff_id = s.staff_id
    WHERE 1=1
';
$params = [];

if (in_array($filterType, ['ISSUE', 'RETURN', 'RESTOCK'], true)) {
    $sql .= ' AND t.transaction_type = ?';
    $params[] = $filterType;
}
if ($search !== '') {
    $sql .= ' AND (i.item_name LIKE ? OR s.full_name LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
}
$sql .= ' ORDER BY t.transaction_date DESC, t.transaction_id DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$transactions = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>
<p class="kicker">A complete, searchable audit trail of every checkout, return and restock.</p>

<form method="get" action="transactions.php" class="filter-bar">
    <div class="form-group">
        <label class="form-label" for="q">Search</label>
        <input type="text" id="q" name="q" placeholder="Item or staff name" value="<?= h($search) ?>">
    </div>
    <div class="form-group">
        <label class="form-label" for="type">Type</label>
        <select id="type" name="type">
            <option value="">All types</option>
            <option value="ISSUE" <?= $filterType === 'ISSUE' ? 'selected' : '' ?>>Issue</option>
            <option value="RETURN" <?= $filterType === 'RETURN' ? 'selected' : '' ?>>Return</option>
            <option value="RESTOCK" <?= $filterType === 'RESTOCK' ? 'selected' : '' ?>>Restock</option>
        </select>
    </div>
    <div class="form-group">
        <button type="submit" class="btn btn-secondary">Filter</button>
    </div>
</form>

<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Date &amp; time</th>
                <th>Item</th>
                <th>Staff member</th>
                <th>Department</th>
                <th>Type</th>
                <th>Quantity</th>
                <th>Logged by</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!$transactions): ?>
                <tr><td colspan="7" class="table-empty">No transactions match your filters.</td></tr>
            <?php endif; ?>
            <?php foreach ($transactions as $t): ?>
                <tr>
                    <td><?= h(format_datetime($t['transaction_date'])) ?></td>
                    <td><?= h($t['item_name']) ?></td>
                    <td><?= h($t['staff_name']) ?></td>
                    <td class="text-muted"><?= h($t['department']) ?></td>
                    <td><span class="badge <?= transaction_badge_class($t['transaction_type']) ?>"><?= h($t['transaction_type']) ?></span></td>
                    <td><?= (int) $t['quantity_issued'] ?> <?= h($t['unit_measure']) ?></td>
                    <td class="text-muted"><?= h($t['issued_by']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
