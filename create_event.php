<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

require_role('organizer');

$page_title = "Create New Event";
$error = "";
$uid = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $event_date  = trim($_POST['event_date'] ?? '');
    $venue       = trim($_POST['venue'] ?? '');

    if (empty($title) || empty($event_date)) {
        $error = "Event title and event date are required.";
    } else {
        // Safe prepared statement insert
        $stmt = mysqli_prepare($conn, "
            INSERT INTO events (title, description, event_date, venue, created_by, status)
            VALUES (?, ?, ?, ?, ?, 'open')
        ");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "ssssi", $title, $description, $event_date, $venue, $uid);
            if (mysqli_stmt_execute($stmt)) {
                $new_event_id = mysqli_insert_id($conn);
                mysqli_stmt_close($stmt);

                set_flash('success', "Event '$title' scheduled successfully! Registrations are now open.");
                header("Location: my_events.php");
                exit;
            } else {
                $error = "A database error occurred while creating the event.";
                mysqli_stmt_close($stmt);
            }
        } else {
            $error = "System query preparation error.";
        }
    }
}

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>

<div class="container py-5" style="max-width: 760px;">

    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h2 class="fw-bold tracking-tight text-dark mb-1">Schedule New Campus Event</h2>
            <p class="text-muted small mb-0">Publish a new competition or symposium for student registration</p>
        </div>
        <a href="organizer_dashboard.php" class="btn btn-sm btn-cems-secondary">
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
        <form method="post" action="create_event.php" novalidate>
            <?php echo csrf_field(); ?>

            <div class="mb-3">
                <label class="form-label fw-semibold small text-muted">Event Title <span class="text-danger">*</span></label>
                <input type="text" name="title" class="form-control" 
                       placeholder="e.g. CodeStorm 2026 Hackathon" required value="<?php echo e($_POST['title'] ?? ''); ?>">
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold small text-muted">Event Description &amp; Guidelines</label>
                <textarea name="description" class="form-control" rows="4" 
                          placeholder="Provide event overview, team rules, eligibility, and prize details..."><?php echo e($_POST['description'] ?? ''); ?></textarea>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label fw-semibold small text-muted">Event Date <span class="text-danger">*</span></label>
                    <input type="date" name="event_date" class="form-control" required
                           value="<?php echo e($_POST['event_date'] ?? date('Y-m-d')); ?>">
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold small text-muted">Venue / Location</label>
                    <input type="text" name="venue" class="form-control" 
                           placeholder="e.g. Auditorium Hall B or Online" value="<?php echo e($_POST['venue'] ?? ''); ?>">
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                <a href="organizer_dashboard.php" class="btn btn-cems-secondary px-4">Cancel</a>
                <button type="submit" name="create_event" class="btn btn-cems-primary px-4 d-inline-flex align-items-center gap-2">
                    <i class="bi bi-calendar-plus"></i> Schedule &amp; Publish Event
                </button>
            </div>
        </form>
    </div>

</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
