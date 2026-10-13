<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

require_role('organizer');

$page_title = "Organizer Dashboard";
$uid = (int)$_SESSION['user_id'];

// 1. My Events Count
$my_events = 0;
$stmt = mysqli_prepare($conn, "SELECT COUNT(*) c FROM events WHERE created_by = ?");
mysqli_stmt_bind_param($stmt, "i", $uid);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
if ($row = mysqli_fetch_assoc($res)) $my_events = (int)$row['c'];
mysqli_stmt_close($stmt);

// 2. Pending Requests for this organizer's events
$pending_count = 0;
$stmt = mysqli_prepare($conn, "
    SELECT COUNT(*) c
    FROM event_participation ep
    JOIN events e ON ep.event_id = e.id
    WHERE e.created_by = ? AND ep.approval_status = 'pending'
");
mysqli_stmt_bind_param($stmt, "i", $uid);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
if ($row = mysqli_fetch_assoc($res)) $pending_count = (int)$row['c'];
mysqli_stmt_close($stmt);

// 3. Approved Participants Count
$approved_count = 0;
$stmt = mysqli_prepare($conn, "
    SELECT COUNT(*) c
    FROM event_participation ep
    JOIN events e ON ep.event_id = e.id
    WHERE e.created_by = ? AND ep.approval_status = 'approved'
");
mysqli_stmt_bind_param($stmt, "i", $uid);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
if ($row = mysqli_fetch_assoc($res)) $approved_count = (int)$row['c'];
mysqli_stmt_close($stmt);

// 4. Declared Winners Count
$winners_count = 0;
$stmt = mysqli_prepare($conn, "
    SELECT COUNT(*) c
    FROM winners w
    JOIN events e ON w.event_id = e.id
    WHERE e.created_by = ?
");
mysqli_stmt_bind_param($stmt, "i", $uid);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
if ($row = mysqli_fetch_assoc($res)) $winners_count = (int)$row['c'];
mysqli_stmt_close($stmt);

// 5. Fetch Recent Pending Requests for Quick Action
$recent_stmt = mysqli_prepare($conn, "
    SELECT ep.id, s.name as student_name, s.roll_no, e.title as event_title, ep.applied_at
    FROM event_participation ep
    JOIN students s ON ep.student_id = s.id
    JOIN events e ON ep.event_id = e.id
    WHERE e.created_by = ? AND ep.approval_status = 'pending'
    ORDER BY ep.applied_at DESC
    LIMIT 5
");
mysqli_stmt_bind_param($recent_stmt, "i", $uid);
mysqli_stmt_execute($recent_stmt);
$recent_requests = mysqli_stmt_get_result($recent_stmt);

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>

<div class="container py-4">

    <!-- Header & Quick Event Creation -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h2 class="fw-bold tracking-tight text-dark mb-1">Event Coordinator Hub</h2>
            <p class="text-muted small mb-0">Manage scheduling, student participation approvals, and competition outcomes</p>
        </div>
        <a href="create_event.php" class="btn btn-cems-primary d-inline-flex align-items-center gap-2">
            <i class="bi bi-plus-circle"></i> Create New Event
        </a>
    </div>

    <!-- Flash notifications -->
    <?php display_flash(); ?>

    <!-- 4 Live Metric KPI Boxes -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-3">
            <a href="my_events.php" class="text-decoration-none">
                <div class="metric-box">
                    <div class="metric-icon-wrap" style="background:#eef2ff; color:#4f46e5;">
                        <i class="bi bi-calendar2-week"></i>
                    </div>
                    <div class="metric-val"><?php echo $my_events; ?></div>
                    <div class="metric-lbl">Events Managed</div>
                </div>
            </a>
        </div>

        <div class="col-sm-6 col-lg-3">
            <a href="manage_participants.php" class="text-decoration-none">
                <div class="metric-box">
                    <div class="metric-icon-wrap" style="background:#fffbeb; color:#f59e0b;">
                        <i class="bi bi-hourglass-split"></i>
                    </div>
                    <div class="metric-val"><?php echo $pending_count; ?></div>
                    <div class="metric-lbl">Pending Approvals</div>
                </div>
            </a>
        </div>

        <div class="col-sm-6 col-lg-3">
            <a href="manage_participants.php?filter=approved" class="text-decoration-none">
                <div class="metric-box">
                    <div class="metric-icon-wrap" style="background:#ecfdf5; color:#10b981;">
                        <i class="bi bi-person-check"></i>
                    </div>
                    <div class="metric-val"><?php echo $approved_count; ?></div>
                    <div class="metric-lbl">Approved Attendees</div>
                </div>
            </a>
        </div>

        <div class="col-sm-6 col-lg-3">
            <a href="declare_winner.php" class="text-decoration-none">
                <div class="metric-box">
                    <div class="metric-icon-wrap" style="background:#fdf4ff; color:#d946ef;">
                        <i class="bi bi-trophy"></i>
                    </div>
                    <div class="metric-val"><?php echo $winners_count; ?></div>
                    <div class="metric-lbl">Podium Winners</div>
                </div>
            </a>
        </div>
    </div>

    <!-- Operational Workflow Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <a href="create_event.php" class="text-decoration-none">
                <div class="cems-card cems-card-hover text-center p-3">
                    <div class="fs-2 text-primary mb-2"><i class="bi bi-calendar-plus"></i></div>
                    <h6 class="fw-bold text-dark mb-1">Create Event</h6>
                    <p class="text-muted small mb-0">Schedule campus competition</p>
                </div>
            </a>
        </div>

        <div class="col-md-3">
            <a href="manage_participants.php" class="text-decoration-none">
                <div class="cems-card cems-card-hover text-center p-3">
                    <div class="fs-2 text-warning mb-2"><i class="bi bi-check2-square"></i></div>
                    <h6 class="fw-bold text-dark mb-1">Review Queue</h6>
                    <p class="text-muted small mb-0">Approve/reject applicants</p>
                </div>
            </a>
        </div>

        <div class="col-md-3">
            <a href="declare_winner.php" class="text-decoration-none">
                <div class="cems-card cems-card-hover text-center p-3">
                    <div class="fs-2 text-success mb-2"><i class="bi bi-award"></i></div>
                    <h6 class="fw-bold text-dark mb-1">Declare Winner</h6>
                    <p class="text-muted small mb-0">Record podium results</p>
                </div>
            </a>
        </div>

        <div class="col-md-3">
            <a href="post_announcement.php" class="text-decoration-none">
                <div class="cems-card cems-card-hover text-center p-3">
                    <div class="fs-2 text-info mb-2"><i class="bi bi-megaphone"></i></div>
                    <h6 class="fw-bold text-dark mb-1">Broadcast Notice</h6>
                    <p class="text-muted small mb-0">Post urgent event alerts</p>
                </div>
            </a>
        </div>
    </div>

    <!-- Quick Action Pending Queue -->
    <div class="cems-card">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h5 class="fw-bold text-dark mb-1">Pending Approval Queue</h5>
                <p class="text-muted small mb-0">Recent student participation requests requiring your decision</p>
            </div>
            <a href="manage_participants.php" class="btn btn-sm btn-cems-secondary">
                View All Queue (<?php echo $pending_count; ?>)
            </a>
        </div>

        <div class="table-responsive">
            <table class="table-cems">
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Roll No</th>
                        <th>Event</th>
                        <th>Applied On</th>
                        <th class="text-end">Immediate Decision</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($recent_requests && mysqli_num_rows($recent_requests) > 0): ?>
                        <?php while ($r = mysqli_fetch_assoc($recent_requests)): ?>
                            <tr>
                                <td class="fw-bold text-dark"><?php echo e($r['student_name']); ?></td>
                                <td class="text-muted small"><?php echo e(!empty($r['roll_no']) ? $r['roll_no'] : 'N/A'); ?></td>
                                <td><span class="badge bg-light text-dark border"><?php echo e($r['event_title']); ?></span></td>
                                <td class="text-muted small"><?php echo date("d M Y, h:i A", strtotime($r['applied_at'])); ?></td>
                                <td class="text-end">
                                    <form method="post" action="manage_participants.php" class="d-inline">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="participation_id" value="<?php echo (int)$r['id']; ?>">
                                        <button type="submit" name="action" value="approve" class="btn btn-sm btn-success px-2 py-1 me-1">
                                            <i class="bi bi-check-lg"></i> Approve
                                        </button>
                                        <button type="submit" name="action" value="reject" class="btn btn-sm btn-danger px-2 py-1">
                                            <i class="bi bi-x-lg"></i> Reject
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                <i class="bi bi-check-circle text-success fs-4 d-block mb-1"></i>
                                No pending requests awaiting review!
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php 
if (isset($recent_stmt)) mysqli_stmt_close($recent_stmt);
include __DIR__ . '/includes/footer.php'; 
?>
