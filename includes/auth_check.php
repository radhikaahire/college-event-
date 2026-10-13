<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Active session verification
$user_check_id = (int)$_SESSION['user_id'];
$status_stmt = mysqli_prepare($conn, "SELECT status, role FROM users WHERE id = ?");
if ($status_stmt) {
    mysqli_stmt_bind_param($status_stmt, "i", $user_check_id);
    mysqli_stmt_execute($status_stmt);
    $res = mysqli_stmt_get_result($status_stmt);
    if ($user = mysqli_fetch_assoc($res)) {
        if ($user['status'] !== 'active') {
            $_SESSION = [];
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000,
                    $params["path"], $params["domain"],
                    $params["secure"], $params["httponly"]
                );
            }
            session_destroy();
            header("Location: login.php?error=deactivated");
            exit;
        }
        $_SESSION['role'] = $user['role'];
    } else {
        $_SESSION = [];
        session_destroy();
        header("Location: login.php");
        exit;
    }
    mysqli_stmt_close($status_stmt);
}
