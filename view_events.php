<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

$page_title = "Explore Events";

$user_id = $_SESSION['user_id'] ?? null;
$role = $_SESSION['role'] ?? null;
$student_id = null;

// If logged in as student, get student profile ID
if ($user_id && $role === 'student') {
    $stu_stmt = mysqli_prepare($conn, "SELECT id FROM students WHERE user_id = ?");
    if ($stu_stmt) {
        mysqli_stmt_bind_param($stu_stmt, "i", $user_id);
        mysqli_stmt_execute($stu_stmt);
        $res = mysqli_stmt_get_result($stu_stmt);
        if ($stu_row = mysqli_fetch_assoc($res)) {
            $student_id = (int)$stu_row['id'];
        }
        mysqli_stmt_close($stu_stmt);
    }
}

// Handle Event Registration via Secure POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    if (!$user_id || $role !== 'student' || !$student_id) {
        set_flash('danger', 'You must be signed in as a student to participate in events.');
        header("Location: login.php");
        exit;
    }

    $event_id = (int)($_POST['event_id'] ?? 0);

    // Verify event exists and is open
    $ev_chk = mysqli_prepare($conn, "SELECT status, title FROM events WHERE id = ?");
    mysqli_stmt_bind_param($ev_chk, "i", $event_id);
    mysqli_stmt_execute($ev_chk);
    $ev_res = mysqli_stmt_get_result($ev_chk);
    $event_data = mysqli_fetch_assoc($ev_res);
    mysqli_stmt_close($ev_chk);

    if (!$event_data) {
        set_flash('danger', 'The selected event does not exist.');
    } elseif ($event_data['status'] !== 'open') {
        set_flash('warning', 'Registrations for this event are currently closed.');
    } else {
        // Safe insert into event_participation
        $ins = mysqli_prepare($conn, "
            INSERT IGNORE INTO event_participation (event_id, student_id, approval_status)
            VALUES (?, ?, 'pending')
        ");
        if ($ins) {
            mysqli_stmt_bind_param($ins, "ii", $event_id, $student_id);
            mysqli_stmt_execute($ins);
            if (mysqli_stmt_affected_rows($ins) > 0) {
                set_flash('success', 'Your registration request for "' . $event_data['title'] . '" was submitted successfully! Awaiting coordinator approval.');
            } else {
                set_flash('info', 'You have already applied for this event.');
            }
            mysqli_stmt_close($ins);
        }
    }
    header("Location: view_events.php");
    exit;
}

// Search and filter parameters
$search = trim($_GET['q'] ?? '');
$filter_status = trim($_GET['status'] ?? 'open');

// Fetch events query
$sql = "SELECT e.*, u.username as creator FROM events e LEFT JOIN users u ON e.created_by = u.id WHERE 1=1";
$params = [];
$types = "";

if ($filter_status === 'open') {
    $sql .= " AND e.status = 'open'";
} elseif ($filter_status === 'closed') {
    $sql .= " AND e.status = 'closed'";
}

if (!empty($search)) {
    $sql .= " AND (e.title LIKE ? OR e.venue LIKE ? OR e.description LIKE ?)";
    $like = "%" . $search . "%";
    $types .= "sss";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$sql .= " ORDER BY e.event_date ASC";

$stmt = mysqli_prepare($conn, $sql);
if (!empty($types)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$events_result = mysqli_stmt_get_result($stmt);

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>

<div class="container py-4">

    <!-- Header & Search Controls -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h2 class="fw-bold tracking-tight text-dark mb-1">Campus Event Discovery</h2>
            <p class="text-muted small mb-0">Browse scheduled competitions, symposia, and workshops</p>
        </div>

        <!-- Search Form -->
        <form method="get" class="d-flex gap-2">
            <div class="input-group">
                <span class="input-group-text bg-white text-muted border-end-0"><i class="bi bi-search"></i></span>
                <input type="text" name="q" class="form-control border-start-0 ps-0"
                       placeholder="Search title, venue..." value="<?php echo e($search); ?>">
            </div>
            <button type="submit" class="btn btn-cems-primary px-3">
                Search
            </button>
            <?php if (!empty($search)): ?>
                <a href="view_events.php" class="btn btn-outline-secondary">Reset</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Flash notifications -->
    <?php display_flash(); ?>

    <!-- Events Grid -->
    <?php if (!$events_result || mysqli_num_rows($events_result) === 0): ?>
        <div class="cems-card text-center py-5">
            <i class="bi bi-search fs-1 text-muted d-block mb-3"></i>
            <h5 class="fw-bold">No Events Found</h5>
            <p class="text-muted small mb-0">Try adjusting your search criteria or check back later.</p>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php while ($ev = mysqli_fetch_assoc($events_result)): ?>
                <?php
                // Check current student's participation status
                $my_status = null;
                if ($student_id) {
                    $chk = mysqli_prepare($conn, "SELECT approval_status FROM event_participation WHERE event_id = ? AND student_id = ?");
                    mysqli_stmt_bind_param($chk, "ii", $ev['id'], $student_id);
                    mysqli_stmt_execute($chk);
                    $chk_res = mysqli_stmt_get_result($chk);
                    if ($chk_row = mysqli_fetch_assoc($chk_res)) {
                        $my_status = $chk_row['approval_status'];
                    }
                    mysqli_stmt_close($chk);
                }
                ?>
                <div class="col-md-6 col-lg-4">
                    <div class="event-card cems-card-hover">
                        <div class="card-banner">
                            <span class="card-category-badge">
                                <?php echo e($ev['category'] ?? 'Event'); ?>
                            </span>
                            <div>
                                <div class="small opacity-75">Date &amp; Time</div>
                                <div class="fw-bold fs-6">
                                    <i class="bi bi-calendar-check me-1"></i>
                                    <?php echo date("D, d M Y", strtotime($ev['event_date'])); ?>
                                </div>
                            </div>
                        </div>

                        <div class="card-body">
                            <h5 class="fw-bold text-dark mb-1"><?php echo e($ev['title']); ?></h5>
                            <p class="text-muted small flex-grow-1 text-truncate-2 mb-3">
                                <?php echo e(!empty($ev['description']) ? $ev['description'] : 'No detailed description provided.'); ?>
                            </p>

                            <div class="event-meta pt-2 border-top">
                                <span>
                                    <i class="bi bi-geo-alt text-danger me-1"></i>
                                    <?php echo e(!empty($ev['venue']) ? $ev['venue'] : 'TBA'); ?>
                                </span>
                                <span class="badge-status <?php echo $ev['status'] === 'open' ? 'badge-status-open' : 'badge-status-closed'; ?> ms-auto">
                                    <?php echo ucfirst(e($ev['status'])); ?>
                                </span>
                            </div>

                            <div class="mt-2">
                                <?php if (!$user_id): ?>
                                    <a href="login.php" class="btn btn-sm btn-cems-secondary w-100">
                                        <i class="bi bi-box-arrow-in-right me-1"></i> Sign In to Apply
                                    </a>
                                <?php elseif ($role !== 'student'): ?>
                                    <button class="btn btn-sm btn-light w-100 text-muted" disabled>
                                        Student Role Required
                                    </button>
                                <?php elseif ($my_status === null && $ev['status'] === 'open'): ?>
                                    <form method="post" action="view_events.php">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="event_id" value="<?php echo (int)$ev['id']; ?>">
                                        <button type="submit" name="register_event" class="btn btn-sm btn-cems-primary w-100">
                                            <i class="bi bi-send-check me-1"></i> Register / Apply Now
                                        </button>
                                    </form>
                                <?php elseif ($my_status === 'pending'): ?>
                                    <div class="badge-status badge-status-pending w-100 py-2 justify-content-center">
                                        <i class="bi bi-hourglass-split"></i> Application Pending Approval
                                    </div>
                                <?php elseif ($my_status === 'approved'): ?>
                                    <div class="badge-status badge-status-approved w-100 py-2 justify-content-center">
                                        <i class="bi bi-check-circle-fill"></i> Registration Approved
                                    </div>
                                <?php elseif ($my_status === 'rejected'): ?>
                                    <div class="badge-status badge-status-rejected w-100 py-2 justify-content-center">
                                        <i class="bi bi-x-circle-fill"></i> Application Rejected
                                    </div>
                                <?php else: ?>
                                    <button class="btn btn-sm btn-light w-100 text-muted" disabled>
                                        Registrations Closed
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php endif; ?>

</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
