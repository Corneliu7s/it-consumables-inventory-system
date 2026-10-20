<?php
/**
 * functions.php - Shared helper functions used across the system.
 */

// Escape output safely for HTML context.
function h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// Store a one-time flash message in the session.
function set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

// Retrieve and clear the flash message, if any.
function get_flash(): ?array
{
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// The signed-in user's display name.
function current_user_name(): string
{
    return $_SESSION['full_name'] ?? 'Guest';
}

// The signed-in user's role.
function current_user_role(): string
{
    return $_SESSION['role'] ?? '';
}

// Format a stored DATETIME value for display.
function format_datetime(string $value): string
{
    $timestamp = strtotime($value);
    return $timestamp ? date('d M Y, g:i A', $timestamp) : $value;
}

// Whether the current request path matches a given script name (for nav highlighting).
function is_active(string $script): bool
{
    return basename($_SERVER['SCRIPT_NAME']) === $script;
}

// Badge classes for a transaction type.
function transaction_badge_class(string $type): string
{
    return match ($type) {
        'ISSUE'   => 'badge-warning',
        'RETURN'  => 'badge-info',
        'RESTOCK' => 'badge-success',
        default   => 'badge-secondary',
    };
}

// Stock status label + badge class for an item.
function stock_status(array $item): array
{
    $qty = (int) $item['quantity_in_stock'];
    $min = (int) $item['minimum_threshold'];

    if ($qty <= 0) {
        return ['label' => 'Out of stock', 'class' => 'badge-danger'];
    }
    if ($qty <= $min) {
        return ['label' => 'Low stock', 'class' => 'badge-warning'];
    }
    return ['label' => 'In stock', 'class' => 'badge-success'];
}
