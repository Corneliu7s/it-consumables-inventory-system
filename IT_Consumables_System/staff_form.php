<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$staff = ['full_name' => '', 'department' => '', 'email' => '', 'phone_number' => ''];
$pageTitle = $id ? 'Edit Staff Member' : 'Add Staff Member';
$errors = [];

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM staff_members WHERE staff_id = ?');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if ($found) {
        $staff = $found;
    } else {
        set_flash('danger', 'Staff member not found.');
        header('Location: staff.php');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $staff['full_name']    = trim($_POST['full_name'] ?? '');
    $staff['department']   = trim($_POST['department'] ?? '');
    $staff['email']        = trim($_POST['email'] ?? '');
    $staff['phone_number'] = trim($_POST['phone_number'] ?? '');

    if ($staff['full_name'] === '') $errors[] = 'Full name is required.';
    if ($staff['department'] === '') $errors[] = 'Department is required.';
    if ($staff['email'] === '' || !filter_var($staff['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email address is required.';
    if ($staff['phone_number'] === '') $errors[] = 'Phone number is required.';

    if (!$errors) {
        try {
            if ($id) {
                $stmt = $pdo->prepare('UPDATE staff_members SET full_name = ?, department = ?, email = ?, phone_number = ? WHERE staff_id = ?');
                $stmt->execute([$staff['full_name'], $staff['department'], $staff['email'], $staff['phone_number'], $id]);
                set_flash('success', 'Staff member updated successfully.');
            } else {
                $stmt = $pdo->prepare('INSERT INTO staff_members (full_name, department, email, phone_number) VALUES (?, ?, ?, ?)');
                $stmt->execute([$staff['full_name'], $staff['department'], $staff['email'], $staff['phone_number']]);
                set_flash('success', 'Staff member added successfully.');
            }
            header('Location: staff.php');
            exit;
        } catch (PDOException $e) {
            $errors[] = str_contains($e->getMessage(), 'UNIQUE')
                ? 'That email address is already registered to another staff member.'
                : 'Could not save staff member: ' . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="card" style="max-width:600px;">
    <div class="card-body">
        <form method="post" action="<?= $id ? 'staff_form.php?id=' . $id : 'staff_form.php' ?>">
            <div class="form-grid single">
                <div class="form-group">
                    <label class="form-label" for="full_name">Full name</label>
                    <input type="text" id="full_name" name="full_name" value="<?= h($staff['full_name']) ?>" required autofocus>
                </div>
                <div class="form-group">
                    <label class="form-label" for="department">Department</label>
                    <input type="text" id="department" name="department" value="<?= h($staff['department']) ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?= h($staff['email']) ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="phone_number">Phone number</label>
                    <input type="tel" id="phone_number" name="phone_number" value="<?= h($staff['phone_number']) ?>" required>
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><?= $id ? 'Save changes' : 'Add staff member' ?></button>
                <a href="staff.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
