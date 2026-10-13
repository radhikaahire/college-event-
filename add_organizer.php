<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

require_role('admin');

$page_title = "Add Organizer";
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $username   = trim($_POST['username'] ?? '');
    $password   = $_POST['password'] ?? '';
    $name       = trim($_POST['name'] ?? '');
    $department = trim($_POST['department'] ?? '');

    if (empty($username) || empty($password) || empty($name)) {
        $error = "Username, password, and coordinator name are required.";
    } elseif (strlen($password) < 4) {
        $error = "Password must be at least 4 characters long.";
    } else {
        // Check if username already exists
        $chk = mysqli_prepare($conn, "SELECT id FROM users WHERE username = ? LIMIT 1");
        mysqli_stmt_bind_param($chk, "s", $username);
        mysqli_stmt_execute($chk);
        $chk_res = mysqli_stmt_get_result($chk);
        
        if (mysqli_num_rows($chk_res) > 0) {
            $error = "The username '$username' is already taken. Please choose another.";
            mysqli_stmt_close($chk);
        } else {
            mysqli_stmt_close($chk);

            // Execute atomic transaction
            mysqli_begin_transaction($conn);
            try {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                
                // 1. Insert into users table
                $u_stmt = mysqli_prepare($conn, "
                    INSERT INTO users (role, username, password, status)
                    VALUES ('organizer', ?, ?, 'active')
                ");
                mysqli_stmt_bind_param($u_stmt, "ss", $username, $hash);
                mysqli_stmt_execute($u_stmt);
                $new_user_id = mysqli_insert_id($conn);
                mysqli_stmt_close($u_stmt);

                // 2. Insert into organizers table
                $o_stmt = mysqli_prepare($conn, "
                    INSERT INTO organizers (user_id, name, department)
                    VALUES (?, ?, ?)
                ");
                mysqli_stmt_bind_param($o_stmt, "iss", $new_user_id, $name, $department);
                mysqli_stmt_execute($o_stmt);
                mysqli_stmt_close($o_stmt);

                mysqli_commit($conn);

                set_flash('success', "Faculty/Coordinator account '$username' for $name was created successfully!");
                header("Location: admin_dashboard.php");
                exit;

            } catch (Exception $e) {
                mysqli_rollback($conn);
                error_log("Organizer creation transaction error: " . $e->getMessage());
                $error = "A database error occurred while creating the organizer. Please try again.";
            }
        }
    }
}

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>

<div class="container py-5" style="max-width: 760px;">
    
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h2 class="fw-bold tracking-tight text-dark mb-1">Onboard Event Coordinator</h2>
            <p class="text-muted small mb-0">Authorize faculty or student leads to manage campus events</p>
        </div>
        <a href="admin_dashboard.php" class="btn btn-sm btn-cems-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
        </a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger d-flex align-items-center gap-2 mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill fs-5"></i>
            <div><?php echo e($error); ?></div>
        </div>
    <?php endif; ?>

    <div class="cems-card">
        <form method="post" action="add_organizer.php" novalidate>
            <?php echo csrf_field(); ?>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold small text-muted">Username <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text bg-white text-muted border-end-0"><i class="bi bi-person"></i></span>
                        <input type="text" name="username" class="form-control border-start-0 ps-0" 
                               placeholder="e.g. prof_sharma" required value="<?php echo e($_POST['username'] ?? ''); ?>">
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold small text-muted">Password <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text bg-white text-muted border-end-0"><i class="bi bi-key"></i></span>
                        <input type="password" name="password" class="form-control border-start-0 ps-0" 
                               placeholder="Temporary password" required>
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold small text-muted">Coordinator Full Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control" 
                       placeholder="e.g. Prof. Ananya Sharma" required value="<?php echo e($_POST['name'] ?? ''); ?>">
            </div>

            <div class="mb-4">
                <label class="form-label fw-semibold small text-muted">Department</label>
                <select name="department" class="form-select">
                    <option value="Computer Science" <?php if (($_POST['department'] ?? '') === 'Computer Science') echo 'selected'; ?>>Computer Science &amp; Engineering</option>
                    <option value="Information Technology" <?php if (($_POST['department'] ?? '') === 'Information Technology') echo 'selected'; ?>>Information Technology</option>
                    <option value="Electronics &amp; Telecom" <?php if (($_POST['department'] ?? '') === 'Electronics & Telecom') echo 'selected'; ?>>Electronics &amp; Telecommunication</option>
                    <option value="Mechanical Engineering" <?php if (($_POST['department'] ?? '') === 'Mechanical Engineering') echo 'selected'; ?>>Mechanical Engineering</option>
                    <option value="Cultural Affairs" <?php if (($_POST['department'] ?? '') === 'Cultural Affairs') echo 'selected'; ?>>Cultural Affairs &amp; Arts Committee</option>
                    <option value="Sports &amp; Athletics" <?php if (($_POST['department'] ?? '') === 'Sports & Athletics') echo 'selected'; ?>>Sports &amp; Athletics Council</option>
                </select>
            </div>

            <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                <a href="admin_dashboard.php" class="btn btn-cems-secondary px-4">Cancel</a>
                <button type="submit" name="add_organizer" class="btn btn-cems-primary px-4 d-inline-flex align-items-center gap-2">
                    <i class="bi bi-check-lg"></i> Create Organizer Account
                </button>
            </div>
        </form>
    </div>

</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
