<?php
/**
 * auth.php - Require an authenticated session for protected pages.
 * Must be included after config/db.php (which starts the session).
 */

if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
