<?php
/**
 * Toast Helper
 * Enables quick session-based toast messages across your PHP app.
 *
 * Usage example:
 *   require_once __DIR__ . '/toast_helper.php';
 *   toast('success', 'Customer created successfully!');
 *   header('Location: ../dashboard.php');
 *   exit();
 */

/**
 * Create a toast notification.
 *
 * @param string $type    Toast type: 'success', 'error', 'info'
 * @param string $message Message to display
 */
function toast(string $type, string $message): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $allowedTypes = ['success', 'error', 'info'];
    if (!in_array($type, $allowedTypes)) {
        $type = 'info'; // fallback to neutral
    }

    $_SESSION[$type] = $message;
}
