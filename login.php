<?php
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

// If already authenticated, redirect to appropriate role dashboard
if (!empty($_SESSION['user_id']) && !empty($_SESSION['role'])) {
    header("Location: " . $_SESSION['role'] . "_dashboard.php");
    exit;
}

$error = "";

if (isset($_GET['error']) && $_GET['error'] === 'deactivated') {
    $error = "Your account has been deactivated. Please contact the administrator.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password.";
    } else {
        // Safe prepared statement query
        $stmt = mysqli_prepare($conn, "SELECT id, role, password, status FROM users WHERE username = ? LIMIT 1");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $username);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if ($user = mysqli_fetch_assoc($result)) {
                $is_password_valid = false;

                // 1. Try Bcrypt verification
                if (password_verify($password, $user['password'])) {
                    $is_password_valid = true;
                }
                // 2. Fallback to legacy plaintext check & automatic transparent migration
                elseif ($user['password'] === $password) {
                    $is_password_valid = true;
                    // Automatically rehash and update in DB
                    $new_hash = password_hash($password, PASSWORD_BCRYPT);
                    $up_stmt = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE id = ?");
                    if ($up_stmt) {
                        mysqli_stmt_bind_param($up_stmt, "si", $new_hash, $user['id']);
                        mysqli_stmt_execute($up_stmt);
                        mysqli_stmt_close($up_stmt);
                    }
                }

                if ($is_password_valid) {
                    if ($user['status'] !== 'active') {
                        $error = "Your account has been deactivated. Please contact the administrator.";
                    } else {
                        // Prevent session fixation
                        session_regenerate_id(true);

                        $_SESSION['user_id'] = (int)$user['id'];
                        $_SESSION['role']    = $user['role'];
                        $_SESSION['username'] = $username;

                        // Redirect to role dashboard
                        $redirect = match($user['role']) {
                            'admin' => 'admin_dashboard.php',
                            'organizer' => 'organizer_dashboard.php',
                            default => 'student_dashboard.php'
                        };

                        header("Location: " . $redirect);
                        exit;
                    }
                } else {
                    $error = "Invalid username or password.";
                }
            } else {
                $error = "Invalid username or password.";
            }
            mysqli_stmt_close($stmt);
        } else {
            $error = "A system error occurred. Please try again later.";
        }
    }
}

$page_title = "Sign In";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($page_title); ?> | College Event System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="assets/css/theme.css" rel="stylesheet">
    <style>
      body {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        background: radial-gradient(circle at 10% 20%, rgba(99, 102, 241, 0.08), transparent 40%),
                    radial-gradient(circle at 90% 80%, rgba(14, 165, 233, 0.08), transparent 40%),
                    var(--c-bg);
        padding: 1.5rem;
      }
      .login-box {
        width: 100%;
        max-width: 440px;
        background: var(--c-surface);
        border: 1px solid var(--c-border);
        border-radius: var(--radius-xl);
        padding: 2.5rem;
        box-shadow: var(--shadow-card);
      }
    </style>
</head>
<body>

<div class="login-box anim-fade-in">
    <div class="text-center mb-4">
        <a href="index.php" class="text-decoration-none">
            <span class="fs-1 d-block mb-1 text-primary"><i class="bi bi-mortarboard-fill"></i></span>
            <h3 class="fw-bold text-dark tracking-tight mb-1">College Event Portal</h3>
        </a>
        <p class="text-muted small">Sign in to your campus account to continue</p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger d-flex align-items-center gap-2 mb-4 py-2 px-3 small border-0" role="alert">
            <i class="bi bi-exclamation-circle-fill fs-6"></i>
            <div><?php echo e($error); ?></div>
        </div>
    <?php endif; ?>

    <form method="post" action="login.php" novalidate>
        <?php echo csrf_field(); ?>

        <div class="mb-3">
            <label class="form-label fw-semibold small text-muted">Username</label>
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-person"></i></span>
                <input type="text" name="username" class="form-control border-start-0 ps-0"
                       placeholder="e.g. admin or student1" required autofocus
                       value="<?php echo e($_POST['username'] ?? ''); ?>">
            </div>
        </div>

        <div class="mb-4">
            <div class="d-flex justify-content-between align-items-center">
                <label class="form-label fw-semibold small text-muted mb-0">Password</label>
            </div>
            <div class="input-group mt-1">
                <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-key"></i></span>
                <input type="password" name="password" class="form-control border-start-0 ps-0"
                       placeholder="••••••••" required>
            </div>
        </div>

        <button type="submit" name="login" class="btn btn-cems-primary w-100 py-2 d-flex align-items-center justify-content-center gap-2">
            <span>Sign In to Portal</span>
            <i class="bi bi-arrow-right"></i>
        </button>
    </form>

    <div class="mt-4 pt-3 border-top text-center text-muted small">
        <div class="d-flex justify-content-center gap-3">
            <a href="index.php" class="text-decoration-none text-muted">
                <i class="bi bi-arrow-left me-1"></i> Public Fest Home
            </a>
            <span class="text-muted">&bull;</span>
            <a href="view_events.php" class="text-decoration-none text-muted">
                Explore Events
            </a>
        </div>
    </div>
</div>

</body>
</html>
