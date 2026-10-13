<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

require_role('organizer');

$page_title = "Manage Participants";
$uid = (int)$_SESSION['user_id'];

// Handle Decision (Approve / Reject) via Secure POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    verify_csrf();

    $part_id = (int)($_POST['participation_id'] ?? 0);
    $action = $_POST['action'] === 'approve' ? 'approved' : ($_POST['action'] === 'reject' ? 'rejected' : '');

    if ($action && $part_id > 0) {
        // Strict ownership check in UPDATE: event must belong to current organizer!
        $update_stmt = mysqli_prepare($conn, "
            UPDATE event_participation ep
            JOIN events e ON ep.event_id = e.id
            SET ep.approval_status = ?
            WHERE ep.id = ? AND e.created_by = ?
        ");
        if ($update_stmt) {
            mysqli_stmt_bind_param($update_stmt, "sii", $action, $part_id, $uid);
            mysqli_stmt_execute($update_stmt);
            if (mysqli_stmt_affected_rows($update_stmt) > 0) {
                set_flash('success', "Registration #" . $part_id . " marked as " . ucfirst($action) . ".");
            } else {
                set_flash('danger', "Permission denied or invalid registration ID.");
            }
            mysqli_stmt_close($update_stmt);
        }
    }
    
    // Redirect preserving filter
    $redirect_url = "manage_participants.php";
    if (!empty($_GET['status'])) $redirect_url .= "?status=" . urlencode($_GET['status']);
    header("Location: " . $redirect_url);
    exit;
}

// Filter parameters
$status_filter = $_GET['status'] ?? 'pending';
$event_filter  = (int)($_GET['event_id'] ?? 0);

// CSV Export Handler
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $csv_sql = "
        SELECT s.name AS student_name, s.roll_no, s.department, e.title AS event_title, 
               ep.applied_at, ep.approval_status
        FROM event_participation ep
        JOIN students s ON ep.student_id = s.id
        JOIN events e ON ep.event_id = e.id
        WHERE e.created_by = ?
    ";
    if ($status_filter !== 'all') {
        $csv_sql .= " AND ep.approval_status = '" . mysqli_real_escape_string($conn, $status_filter) . "'";
    }
    if ($event_filter > 0) {
        $csv_sql .= " AND ep.event_id = " . $event_filter;
    }
    $csv_sql .= " ORDER BY ep.applied_at DESC";

    $csv_stmt = mysqli_prepare($conn, $csv_sql);
    mysqli_stmt_bind_param($csv_stmt, "i", $uid);
    mysqli_stmt_execute($csv_stmt);
    $csv_res = mysqli_stmt_get_result($csv_stmt);

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=participants_export_' . date('Y-m-d') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Student Name', 'Roll Number', 'Department', 'Event Title', 'Applied Date', 'Status']);

    while ($row = mysqli_fetch_assoc($csv_res)) {
        fputcsv($output, [
            $row['student_name'],
            $row['roll_no'] ?? 'N/A',
            $row['department'] ?? 'N/A',
            $row['event_title'],
            $row['applied_at'],
            ucfirst($row['approval_status'])
        ]);
    }
    fclose($output);
    mysqli_stmt_close($csv_stmt);
    exit;
}

// Fetch participant records for this organizer
$sql = "
    SELECT ep.id, ep.approval_status, ep.applied_at,
           s.name AS student_name, s.roll_no, s.department,
           e.title AS event_title, e.id AS event_id
    FROM event_participation ep
    JOIN students s ON ep.student_id = s.id
    JOIN events e ON ep.event_id = e.id
    WHERE e.created_by = ?
";
$params = [$uid];
$types = "i";

if ($status_filter !== 'all') {
    $sql .= " AND ep.approval_status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if ($event_filter > 0) {
    $sql .= " AND ep.event_id = ?";
    $params[] = $event_filter;
    $types .= "i";
}

$sql .= " ORDER BY ep.applied_at DESC";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$participants = mysqli_stmt_get_result($stmt);

// Fetch list of this organizer's events for the filter dropdown
$ev_stmt = mysqli_prepare($conn, "SELECT id, title FROM events WHERE created_by = ? ORDER BY title ASC");
mysqli_stmt_bind_param($ev_stmt, "i", $uid);
mysqli_stmt_execute($ev_stmt);
$my_events_list = mysqli_stmt_get_result($ev_stmt);

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>

<div class="container py-4">

    <!-- Header & CSV Export -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h2 class="fw-bold tracking-tight text-dark mb-1">Participant Management &amp; Approvals</h2>
            <p class="text-muted small mb-0">Review student registrations, filter queues, and export attendance rosters</p>
        </div>
        <div class="d-flex gap-2">
            <a href="manage_participants.php?export=csv&status=<?php echo urlencode($status_filter); ?>&event_id=<?php echo $event_filter; ?>" 
               class="btn btn-sm btn-outline-success d-inline-flex align-items-center gap-1">
                <i class="bi bi-file-earmark-spreadsheet"></i> Export CSV
            </a>
            <a href="organizer_dashboard.php" class="btn btn-sm btn-cems-secondary">
                <i class="bi bi-arrow-left me-1"></i> Dashboard
            </a>
        </div>
    </div>

    <!-- Flash notifications -->
    <?php display_flash(); ?>

    <!-- Filter Bar -->
    <div class="cems-card p-3 mb-4">
        <form method="get" class="row g-2 align-items-center">
            <div class="col-md-4">
                <div class="btn-group w-100" role="group">
                    <a href="manage_participants.php?status=pending<?php echo $event_filter ? '&event_id=' . $event_filter : ''; ?>" 
                       class="btn btn-sm <?php echo $status_filter === 'pending' ? 'btn-primary' : 'btn-outline-secondary'; ?>">
                        Pending
                    </a>
                    <a href="manage_participants.php?status=approved<?php echo $event_filter ? '&event_id=' . $event_filter : ''; ?>" 
                       class="btn btn-sm <?php echo $status_filter === 'approved' ? 'btn-primary' : 'btn-outline-secondary'; ?>">
                        Approved
                    </a>
                    <a href="manage_participants.php?status=rejected<?php echo $event_filter ? '&event_id=' . $event_filter : ''; ?>" 
                       class="btn btn-sm <?php echo $status_filter === 'rejected' ? 'btn-primary' : 'btn-outline-secondary'; ?>">
                        Rejected
                    </a>
                    <a href="manage_participants.php?status=all<?php echo $event_filter ? '&event_id=' . $event_filter : ''; ?>" 
                       class="btn btn-sm <?php echo $status_filter === 'all' ? 'btn-primary' : 'btn-outline-secondary'; ?>">
                        All
                    </a>
                </div>
            </div>

            <div class="col-md-5">
                <select name="event_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="0">-- All My Events --</option>
                    <?php if ($my_events_list): ?>
                        <?php while ($e = mysqli_fetch_assoc($my_events_list)): ?>
                            <option value="<?php echo (int)$e['id']; ?>" <?php if ($event_filter === (int)$e['id']) echo 'selected'; ?>>
                                <?php echo e($e['title']); ?>
                            </option>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </select>
                <input type="hidden" name="status" value="<?php echo e($status_filter); ?>">
            </div>

            <div class="col-md-3 text-end">
                <?php if ($event_filter > 0 || $status_filter !== 'pending'): ?>
                    <a href="manage_participants.php" class="btn btn-sm btn-link text-muted text-decoration-none">Reset Filters</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Participants Table -->
    <div class="cems-card">
        <div class="table-responsive">
            <table class="table-cems">
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Roll No &amp; Dept</th>
                        <th>Target Event</th>
                        <th>Registration Date</th>
                        <th>Current Status</th>
                        <th class="text-end">Decision Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($participants && mysqli_num_rows($participants) > 0): ?>
                        <?php while ($p = mysqli_fetch_assoc($participants)): ?>
                            <tr>
                                <td>
                                    <div class="fw-bold text-dark"><?php echo e($p['student_name']); ?></div>
                                </td>
                                <td>
                                    <div class="small fw-semibold"><?php echo e(!empty($p['roll_no']) ? $p['roll_no'] : 'N/A'); ?></div>
                                    <div class="text-muted small"><?php echo e(!empty($p['department']) ? $p['department'] : 'General'); ?></div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border"><?php echo e($p['event_title']); ?></span>
                                </td>
                                <td class="text-muted small">
                                    <?php echo date("d M Y, h:i A", strtotime($p['applied_at'])); ?>
                                </td>
                                <td>
                                    <?php
                                    $badge = match($p['approval_status']) {
                                        'approved' => 'badge-status-approved',
                                        'rejected' => 'badge-status-rejected',
                                        default => 'badge-status-pending'
                                    };
                                    ?>
                                    <span class="badge-status <?php echo $badge; ?>">
                                        <?php echo ucfirst(e($p['approval_status'])); ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <?php if ($p['approval_status'] === 'approved'): ?>
                                        <a href="ticket.php?reg_id=<?php echo (int)$p['id']; ?>" class="btn btn-sm btn-outline-info px-2 py-1 me-1" title="View Digital Delegate Pass">
                                            <i class="bi bi-qr-code"></i> Pass
                                        </a>
                                    <?php endif; ?>
                                    <form method="post" action="manage_participants.php?status=<?php echo urlencode($status_filter); ?>" class="d-inline">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="participation_id" value="<?php echo (int)$p['id']; ?>">
                                        <?php if ($p['approval_status'] !== 'approved'): ?>
                                            <button type="submit" name="action" value="approve" class="btn btn-sm btn-success px-2 py-1 me-1">
                                                <i class="bi bi-check-lg"></i> Approve
                                            </button>
                                        <?php endif; ?>
                                        <?php if ($p['approval_status'] !== 'rejected'): ?>
                                            <button type="submit" name="action" value="reject" class="btn btn-sm btn-danger px-2 py-1">
                                                <i class="bi bi-x-lg"></i> Reject
                                            </button>
                                        <?php endif; ?>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                <i class="bi bi-inbox fs-2 text-muted d-block mb-2"></i>
                                No student registrations found matching your filter criteria.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php 
mysqli_stmt_close($stmt);
mysqli_stmt_close($ev_stmt);
include __DIR__ . '/includes/footer.php'; 
?>
