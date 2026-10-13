<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

require_role('admin');

$page_title = "Add Student";
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $username   = trim($_POST['username'] ?? '');
    $password   = $_POST['password'] ?? '';
    $name       = trim($_POST['name'] ?? '');
    $roll_no    = trim($_POST['roll_no'] ?? '');
    $department = trim($_POST['department'] ?? '');

    if (empty($username) || empty($password) || empty($name)) {
        $error = "Username, password, and student name are required fields.";
    } elseif (strlen($password) < 4) {
        $error = "Password must be at least 4 characters long.";
    } else {
        // Check if username already exists
        $chk = mysqli_prepare($conn, "SELECT id FROM users WHERE username = ? LIMIT 1");
        mysqli_stmt_bind_param($chk, "s", $username);
        mysqli_stmt_execute($chk);
        $chk_res = mysqli_stmt_get_result($chk);
        
        if (mysqli_num_rows($chk_res) > 0) {
            $error = "The username '$username' is already registered. Please choose another.";
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
                    VALUES ('student', ?, ?, 'active')
                ");
                mysqli_stmt_bind_param($u_stmt, "ss", $username, $hash);
                mysqli_stmt_execute($u_stmt);
                $new_user_id = mysqli_insert_id($conn);
                mysqli_stmt_close($u_stmt);

                // 2. Insert into students table
                $s_stmt = mysqli_prepare($conn, "
                    INSERT INTO students (user_id, name, roll_no, department)
                    VALUES (?, ?, ?, ?)
                ");
                mysqli_stmt_bind_param($s_stmt, "isss", $new_user_id, $name, $roll_no, $department);
                mysqli_stmt_execute($s_stmt);
                mysqli_stmt_close($s_stmt);

                mysqli_commit($conn);

                set_flash('success', "Student account '$username' for $name was created successfully!");
                header("Location: admin_dashboard.php");
                exit;

            } catch (Exception $e) {
                mysqli_rollback($conn);
                error_log("Student creation transaction error: " . $e->getMessage());
                $error = "A database error occurred while creating the student. Please try again.";
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
            <h2 class="fw-bold tracking-tight text-dark mb-1">Onboard New Student</h2>
            <p class="text-muted small mb-0">Create credentials and academic department profile</p>
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
        <form method="post" action="add_student.php" novalidate>
            <?php echo csrf_field(); ?>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold small text-muted">Username <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text bg-white text-muted border-end-0"><i class="bi bi-person"></i></span>
                        <input type="text" name="username" class="form-control border-start-0 ps-0" 
                               placeholder="e.g. rahul_patil" required value="<?php echo e($_POST['username'] ?? ''); ?>">
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

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold small text-muted">Full Student Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" 
                           placeholder="e.g. Rahul Patil" required value="<?php echo e($_POST['name'] ?? ''); ?>">
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold small text-muted">Roll / PRN Number</label>
                    <input type="text" name="roll_no" class="form-control" 
                           placeholder="e.g. CS2026-042" value="<?php echo e($_POST['roll_no'] ?? ''); ?>">
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label fw-semibold small text-muted">Academic Department</label>
                <select name="department" class="form-select">
                    <option value="Computer Science" <?php if (($_POST['department'] ?? '') === 'Computer Science') echo 'selected'; ?>>Computer Science &amp; Engineering</option>
                    <option value="Information Technology" <?php if (($_POST['department'] ?? '') === 'Information Technology') echo 'selected'; ?>>Information Technology</option>
                    <option value="Electronics &amp; Telecom" <?php if (($_POST['department'] ?? '') === 'Electronics & Telecom') echo 'selected'; ?>>Electronics &amp; Telecommunication</option>
                    <option value="Mechanical Engineering" <?php if (($_POST['department'] ?? '') === 'Mechanical Engineering') echo 'selected'; ?>>Mechanical Engineering</option>
                    <option value="Civil Engineering" <?php if (($_POST['department'] ?? '') === 'Civil Engineering') echo 'selected'; ?>>Civil Engineering</option>
                    <option value="Business &amp; Management" <?php if (($_POST['department'] ?? '') === 'Business & Management') echo 'selected'; ?>>Business Administration / Management</option>
                </select>
            </div>

            <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                <a href="admin_dashboard.php" class="btn btn-cems-secondary px-4">Cancel</a>
                <button type="submit" name="add_student" class="btn btn-cems-primary px-4 d-inline-flex align-items-center gap-2">
                    <i class="bi bi-check-lg"></i> Create Student Account
                </button>
            </div>
        </form>
    </div>

</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
