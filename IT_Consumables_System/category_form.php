<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$category = ['category_name' => '', 'description' => ''];
$pageTitle = $id ? 'Edit Category' : 'Add Category';
$errors = [];

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM categories WHERE category_id = ?');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if ($found) {
        $category = $found;
    } else {
        set_flash('danger', 'Category not found.');
        header('Location: categories.php');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category['category_name'] = trim($_POST['category_name'] ?? '');
    $category['description']   = trim($_POST['description'] ?? '');

    if ($category['category_name'] === '') {
        $errors[] = 'Category name is required.';
    }

    if (!$errors) {
        if ($id) {
            $stmt = $pdo->prepare('UPDATE categories SET category_name = ?, description = ? WHERE category_id = ?');
            $stmt->execute([$category['category_name'], $category['description'], $id]);
            set_flash('success', 'Category updated successfully.');
        } else {
            $stmt = $pdo->prepare('INSERT INTO categories (category_name, description) VALUES (?, ?)');
            $stmt->execute([$category['category_name'], $category['description']]);
            set_flash('success', 'Category added successfully.');
        }
        header('Location: categories.php');
        exit;
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="card" style="max-width:600px;">
    <div class="card-body">
        <form method="post" action="<?= $id ? 'category_form.php?id=' . $id : 'category_form.php' ?>">
            <div class="form-grid single">
                <div class="form-group">
                    <label class="form-label" for="category_name">Category name</label>
                    <input type="text" id="category_name" name="category_name" value="<?= h($category['category_name']) ?>" required autofocus>
                </div>
                <div class="form-group">
                    <label class="form-label" for="description">Description</label>
                    <textarea id="description" name="description" rows="3"><?= h($category['description']) ?></textarea>
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><?= $id ? 'Save changes' : 'Add category' ?></button>
                <a href="categories.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
