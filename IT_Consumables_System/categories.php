<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Categories';

$categories = $pdo->query('
    SELECT c.*, (SELECT COUNT(*) FROM items i WHERE i.category_id = c.category_id) AS item_count
    FROM categories c
    ORDER BY c.category_name
')->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>
<p class="kicker">Group consumable items into categories for easier cataloguing and reporting.</p>

<div class="page-head" style="margin-top:-10px;">
    <span></span>
    <div class="actions">
        <a href="category_form.php" class="btn btn-primary">Add category</a>
    </div>
</div>

<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Category</th>
                <th>Description</th>
                <th>Items</th>
                <th class="text-right">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!$categories): ?>
                <tr><td colspan="4" class="table-empty">No categories yet. Add your first category to get started.</td></tr>
            <?php endif; ?>
            <?php foreach ($categories as $cat): ?>
                <tr>
                    <td><?= h($cat['category_name']) ?></td>
                    <td class="text-muted"><?= h($cat['description'] ?: '—') ?></td>
                    <td><?= (int) $cat['item_count'] ?></td>
                    <td class="text-right">
                        <a href="category_form.php?id=<?= (int) $cat['category_id'] ?>" class="btn btn-sm btn-secondary">Edit</a>
                        <form action="category_delete.php" method="post" class="btn-link-form" data-confirm="Delete '<?= h($cat['category_name']) ?>'? Items in this category will be kept but unassigned.">
                            <input type="hidden" name="id" value="<?= (int) $cat['category_id'] ?>">
                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
