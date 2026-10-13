<?php
/**
 * Core Helper Functions & Security Utilities
 * College Event Management System (CEMS)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Escape output for safe rendering in HTML
 */
function e($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Generate or retrieve CSRF token
 */
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Output a hidden CSRF token input field
 */
function csrf_field() {
    $token = csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . e($token) . '">';
}

/**
 * Verify CSRF token from POST request
 */
function verify_csrf() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? '';
        if (empty($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
            http_response_code(403);
            die("Security validation failed (CSRF token mismatch). Please refresh the page and try again.");
        }
    }
}

/**
 * Set a session flash message
 */
function set_flash($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type, // success, danger, warning, info
        'message' => $message
    ];
}

/**
 * Display and clear session flash message
 */
function display_flash() {
    if (!empty($_SESSION['flash_message'])) {
        $flash = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        
        $type = e($flash['type']);
        $icon = match($flash['type']) {
            'success' => 'bi-check-circle-fill',
            'danger' => 'bi-exclamation-triangle-fill',
            'warning' => 'bi-exclamation-circle-fill',
            default => 'bi-info-circle-fill'
        };
        
        echo '
        <div class="alert alert-' . $type . ' alert-dismissible fade show d-flex align-items-center gap-2 mb-4 shadow-sm" role="alert">
            <i class="bi ' . $icon . ' fs-5"></i>
            <div>' . e($flash['message']) . '</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>';
    }
}

/**
 * Enforce role access control
 */
function require_role($allowed_roles) {
    if (!is_array($allowed_roles)) {
        $allowed_roles = [$allowed_roles];
    }
    
    if (empty($_SESSION['user_id']) || empty($_SESSION['role'])) {
        header("Location: login.php");
        exit;
    }
    
    if (!in_array($_SESSION['role'], $allowed_roles, true)) {
        header("Location: login.php");
        exit;
    }
}

/**
 * Execute a prepared MySQL statement safely
 *
 * @param mysqli $conn
 * @param string $sql
 * @param string $types e.g. "ssi"
 * @param array $params
 * @return mysqli_result|bool
 */
function db_prepare($conn, $sql, $types = "", $params = []) {
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        error_log("MySQL prepare error: " . mysqli_error($conn));
        return false;
    }
    
    if (!empty($types) && !empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    
    if (!mysqli_stmt_execute($stmt)) {
        error_log("MySQL execute error: " . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        return false;
    }
    
    $result = mysqli_stmt_get_result($stmt);
    if ($result === false) {
        // Query was INSERT, UPDATE, or DELETE
        $affected = mysqli_stmt_affected_rows($stmt);
        $insert_id = mysqli_stmt_insert_id($stmt);
        mysqli_stmt_close($stmt);
        return ['affected_rows' => $affected, 'insert_id' => $insert_id];
    }
    
    mysqli_stmt_close($stmt);
    return $result;
}
