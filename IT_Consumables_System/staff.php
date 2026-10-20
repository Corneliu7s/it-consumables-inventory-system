<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Staff Directory';

$search = trim($_GET['q'] ?? '');
$sql = 'SELECT * FROM staff_members WHERE 1=1';
$params = [];
if ($search !== '') {
    $sql .= ' AND (full_name LIKE ? OR department LIKE ? OR email LIKE ?)';
    $params = ["%$search%", "%$search%", "%$search%"];
}
$sql .= ' ORDER BY full_name';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$staff = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>
<p class="kicker">Staff and technicians eligible to check out or return IT consumables.</p>

<div class="filter-bar">
    <form method="get" action="staff.php" class="filter-bar" style="margin:0;">
        <div class="form-group">
            <label class="form-label" for="q">Search</label>
            <input type="text" id="q" name="q" placeholder="Name, department or email" value="<?= h($search) ?>">
        </div>
        <div class="form-group">
            <button type="submit" class="btn btn-secondary">Search</button>
        </div>
    </form>
    <div style="margin-left:auto;">
        <a href="staff_form.php" class="btn btn-primary">Add staff member</a>
    </div>
</div>

<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Full name</th>
                <th>Department</th>
                <th>Email</th>
                <th>Phone</th>
                <th class="text-right">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!$staff): ?>
                <tr><td colspan="5" class="table-empty">No staff members found.</td></tr>
            <?php endif; ?>
            <?php foreach ($staff as $s): ?>
                <tr>
                    <td><?= h($s['full_name']) ?></td>
                    <td><?= h($s['department']) ?></td>
                    <td><?= h($s['email']) ?></td>
                    <td><?= h($s['phone_number']) ?></td>
                    <td class="text-right">
                        <a href="staff_form.php?id=<?= (int) $s['staff_id'] ?>" class="btn btn-sm btn-secondary">Edit</a>
                        <form action="staff_delete.php" method="post" class="btn-link-form" data-confirm="Delete '<?= h($s['full_name']) ?>'? Their transaction history will also be removed.">
                            <input type="hidden" name="id" value="<?= (int) $s['staff_id'] ?>">
                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
