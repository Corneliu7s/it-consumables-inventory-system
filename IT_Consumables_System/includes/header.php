<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h($pageTitle ?? 'Dashboard') ?> &middot; IT Consumables Inventory System</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<nav class="navbar">
    <div class="navbar-inner">
        <a class="navbar-brand" href="index.php">
            <span class="brand-mark">IT</span>
            <span class="brand-text">Consumables Inventory</span>
        </a>
        <button class="navbar-toggle" type="button" aria-label="Toggle navigation" onclick="document.getElementById('primaryNav').classList.toggle('open')">
            &#9776;
        </button>
        <div class="navbar-links" id="primaryNav">
            <a href="index.php" class="nav-link <?= is_active('index.php') ? 'active' : '' ?>">Dashboard</a>
            <a href="items.php" class="nav-link <?= is_active('items.php') || is_active('item_form.php') ? 'active' : '' ?>">Items</a>
            <a href="categories.php" class="nav-link <?= is_active('categories.php') || is_active('category_form.php') ? 'active' : '' ?>">Categories</a>
            <a href="staff.php" class="nav-link <?= is_active('staff.php') || is_active('staff_form.php') ? 'active' : '' ?>">Staff</a>
            <a href="checkout.php" class="nav-link <?= is_active('checkout.php') ? 'active' : '' ?>">Checkout</a>
            <a href="return_stock.php" class="nav-link <?= is_active('return_stock.php') ? 'active' : '' ?>">Return / Restock</a>
            <a href="transactions.php" class="nav-link <?= is_active('transactions.php') ? 'active' : '' ?>">Transactions</a>
            <a href="reports.php" class="nav-link <?= is_active('reports.php') ? 'active' : '' ?>">Reports</a>
        </div>
        <div class="navbar-user">
            <span class="user-name"><?= h(current_user_name()) ?></span>
            <span class="user-role"><?= h(current_user_role()) ?></span>
            <a href="logout.php" class="btn btn-outline btn-sm">Log out</a>
        </div>
    </div>
</nav>

<main class="container page">
    <div class="page-head">
        <h1><?= h($pageTitle ?? '') ?></h1>
    </div>

    <?php $flash = get_flash(); ?>
    <?php if ($flash): ?>
        <div class="alert alert-<?= h($flash['type']) ?>"><?= h($flash['message']) ?></div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="alert-list">
                <?php foreach ($errors as $err): ?>
                    <li><?= h($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
