<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

require_role('student');

$page_title = "Student Dashboard";
$user_id = (int)$_SESSION['user_id'];

// Get student profile record
$student_id = 0;
$student_name = $_SESSION['username'];
$roll_no = "N/A";
$department = "General";

$stu_stmt = mysqli_prepare($conn, "SELECT id, name, roll_no, department FROM students WHERE user_id = ?");
if ($stu_stmt) {
    mysqli_stmt_bind_param($stu_stmt, "i", $user_id);
    mysqli_stmt_execute($stu_stmt);
    $stu_res = mysqli_stmt_get_result($stu_stmt);
    if ($stu_row = mysqli_fetch_assoc($stu_res)) {
        $student_id = (int)$stu_row['id'];
        $student_name = $stu_row['name'];
        $roll_no = $stu_row['roll_no'] ?? 'N/A';
        $department = $stu_row['department'] ?? 'General';
    }
    mysqli_stmt_close($stu_stmt);
}

// 1. My Registrations Count & List
$applied_count = 0;
$approved_count = 0;
$my_events_list = [];

if ($student_id > 0) {
    $my_reg_stmt = mysqli_prepare($conn, "
        SELECT ep.id AS reg_id, ep.approval_status, ep.applied_at,
               e.id AS event_id, e.title, e.event_date, e.venue, e.status AS event_status,
               w.id AS win_id, w.position AS winner_position
        FROM event_participation ep
        JOIN events e ON ep.event_id = e.id
        LEFT JOIN winners w ON (w.event_id = e.id AND w.student_id = ep.student_id)
        WHERE ep.student_id = ?
        ORDER BY e.event_date ASC
    ");
    mysqli_stmt_bind_param($my_reg_stmt, "i", $student_id);
    mysqli_stmt_execute($my_reg_stmt);
    $my_reg_res = mysqli_stmt_get_result($my_reg_stmt);
    while ($row = mysqli_fetch_assoc($my_reg_res)) {
        $my_events_list[] = $row;
        $applied_count++;
        if ($row['approval_status'] === 'approved') {
            $approved_count++;
        }
    }
    mysqli_stmt_close($my_reg_stmt);
}

// 2. My Podium Wins Count
$my_wins_count = 0;
if ($student_id > 0) {
    $win_chk = mysqli_prepare($conn, "SELECT COUNT(*) c FROM winners WHERE student_id = ?");
    mysqli_stmt_bind_param($win_chk, "i", $student_id);
    mysqli_stmt_execute($win_chk);
    $win_chk_res = mysqli_stmt_get_result($win_chk);
    if ($wrow = mysqli_fetch_assoc($win_chk_res)) {
        $my_wins_count = (int)$wrow['c'];
    }
    mysqli_stmt_close($win_chk);
}

// 3. Recent Campus Winners Feed
$wins = mysqli_query($conn, "
    SELECT w.position, s.name, e.title, e.event_date
    FROM winners w
    JOIN students s ON w.student_id = s.id
    JOIN events e ON w.event_id = e.id
    ORDER BY e.event_date DESC
    LIMIT 5
");

// 4. Latest Announcements Feed
$ann = mysqli_query($conn, "
    SELECT a.message, a.created_at, e.title
    FROM announcements a
    JOIN events e ON a.event_id = e.id
    ORDER BY a.created_at DESC
    LIMIT 4
");

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>

<div class="container py-4">

    <!-- Header & Student Profile Chip -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h2 class="fw-bold tracking-tight text-dark mb-1">
                Welcome back, <?php echo e($student_name); ?>! 👋
            </h2>
            <p class="text-muted small mb-0">
                Department: <strong class="text-dark"><?php echo e($department); ?></strong> &bull; Roll: <strong class="text-dark"><?php echo e($roll_no); ?></strong>
            </p>
        </div>
        <a href="view_events.php" class="btn btn-cems-primary d-inline-flex align-items-center gap-2">
            <i class="bi bi-compass"></i> Explore New Events
        </a>
    </div>

    <!-- Flash notifications -->
    <?php display_flash(); ?>

    <!-- 3 Key Metric KPI Boxes -->
    <div class="row g-3 mb-4">
        <div class="col-sm-4">
            <a href="#my-applications" class="metric-box-clickable" title="View My Applications">
                <div class="metric-box">
                    <div class="metric-icon-wrap" style="background:#eef2ff; color:#4f46e5;">
                        <i class="bi bi-send-check"></i>
                    </div>
                    <div class="metric-val"><?php echo $applied_count; ?></div>
                    <div class="metric-lbl">Total Applications</div>
                </div>
            </a>
        </div>

        <div class="col-sm-4">
            <a href="#my-applications" class="metric-box-clickable" title="View Approved Event Passes">
                <div class="metric-box">
                    <div class="metric-icon-wrap" style="background:#ecfdf5; color:#10b981;">
                        <i class="bi bi-ticket-perforated"></i>
                    </div>
                    <div class="metric-val"><?php echo $approved_count; ?></div>
                    <div class="metric-lbl">Approved Event Passes</div>
                </div>
            </a>
        </div>

        <div class="col-sm-4">
            <a href="winners.php" class="metric-box-clickable" title="View Podium Medals in Hall of Fame">
                <div class="metric-box">
                    <div class="metric-icon-wrap" style="background:#fffbeb; color:#f59e0b;">
                        <i class="bi bi-trophy"></i>
                    </div>
                    <div class="metric-val"><?php echo $my_wins_count; ?></div>
                    <div class="metric-lbl">Podium Medals Won</div>
                </div>
            </a>
        </div>
    </div>

    <!-- My Registered Events Tracker Section -->
    <div class="cems-card mb-4" id="my-applications">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h5 class="fw-bold text-dark mb-1">My Event Applications</h5>
                <p class="text-muted small mb-0">Live status of competitions you have registered for</p>
            </div>
            <a href="view_events.php" class="btn btn-sm btn-outline-primary">Browse More</a>
        </div>

        <div class="table-responsive">
            <table class="table-cems">
                <thead>
                    <tr>
                        <th>Event Title</th>
                        <th>Event Date</th>
                        <th>Venue</th>
                        <th>Applied On</th>
                        <th>Approval Status</th>
                        <th class="text-end">Pass &amp; Certificate</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($my_events_list)): ?>
                        <?php foreach ($my_events_list as $reg): ?>
                            <tr>
                                <td>
                                    <div class="fw-bold text-dark"><?php echo e($reg['title']); ?></div>
                                    <?php if (!empty($reg['winner_position'])): ?>
                                        <span class="badge bg-warning text-dark fw-bold small mt-1">
                                            🏆 <?php echo e($reg['winner_position']); ?> Place Winner
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark"><?php echo date("d M Y", strtotime($reg['event_date'])); ?></div>
                                    <div class="text-muted small"><?php echo date("l", strtotime($reg['event_date'])); ?></div>
                                </td>
                                <td>
                                    <i class="bi bi-geo-alt text-danger me-1"></i>
                                    <?php echo e(!empty($reg['venue']) ? $reg['venue'] : 'TBA'); ?>
                                </td>
                                <td class="text-muted small">
                                    <?php echo date("d M Y", strtotime($reg['applied_at'])); ?>
                                </td>
                                <td>
                                    <?php
                                    $badge = match($reg['approval_status']) {
                                        'approved' => 'badge-status-approved',
                                        'rejected' => 'badge-status-rejected',
                                        default => 'badge-status-pending'
                                    };
                                    ?>
                                    <span class="badge-status <?php echo $badge; ?>">
                                        <?php echo ucfirst(e($reg['approval_status'])); ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <?php if ($reg['approval_status'] === 'approved'): ?>
                                        <div class="d-inline-flex gap-1 flex-wrap justify-content-end">
                                            <a href="ticket.php?reg_id=<?php echo (int)$reg['reg_id']; ?>" class="btn btn-sm btn-outline-success py-1 px-2 d-inline-flex align-items-center gap-1" title="View Digital Event Gate Pass">
                                                <i class="bi bi-qr-code"></i> E-Pass
                                            </a>
                                            <a href="certificate.php?reg_id=<?php echo (int)$reg['reg_id']; ?>" target="_blank" class="btn btn-sm btn-outline-primary py-1 px-2 d-inline-flex align-items-center gap-1" title="Print Academic Certificate">
                                                <i class="bi bi-award"></i> Certificate
                                            </a>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted xsmall" style="font-size:0.8rem;">Upon approval</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                <i class="bi bi-calendar2-plus fs-2 text-muted d-block mb-2"></i>
                                You have not registered for any events yet.<br>
                                <a href="view_events.php" class="btn btn-sm btn-cems-primary mt-2">Explore Available Events</a>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Circulars & Hall of Fame Grid -->
    <div class="row g-4">
        <!-- Announcements Feed -->
        <div class="col-lg-6">
            <div class="cems-card h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold text-dark mb-0"><i class="bi bi-megaphone-fill text-info me-2"></i>Campus Bulletins</h5>
                    <a href="announcements.php" class="btn btn-sm btn-link text-muted text-decoration-none">View All</a>
                </div>

                <?php if ($ann && mysqli_num_rows($ann) > 0): ?>
                    <div class="d-flex flex-column gap-3">
                        <?php while ($a = mysqli_fetch_assoc($ann)): ?>
                            <div class="p-3 border rounded-3 bg-light">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="badge bg-primary-subtle text-primary fw-bold"><?php echo e($a['title']); ?></span>
                                    <span class="text-muted small"><?php echo date("d M Y", strtotime($a['created_at'])); ?></span>
                                </div>
                                <div class="text-dark small mt-2"><?php echo nl2br(e($a['message'])); ?></div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted text-center py-4">No recent circulars broadcasted.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Winners Podium -->
        <div class="col-lg-6">
            <div class="cems-card h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold text-dark mb-0"><i class="bi bi-trophy-fill text-warning me-2"></i>Recent Podium Results</h5>
                    <a href="winners.php" class="btn btn-sm btn-link text-muted text-decoration-none">Full Hall of Fame</a>
                </div>

                <?php if ($wins && mysqli_num_rows($wins) > 0): ?>
                    <div class="d-flex flex-column gap-2">
                        <?php while ($w = mysqli_fetch_assoc($wins)): ?>
                            <div class="d-flex align-items-center justify-content-between p-2 border-bottom">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge <?php 
                                        echo match($w['position']) {
                                            '1st' => 'bg-success',
                                            '2nd' => 'bg-warning text-dark',
                                            default => 'bg-secondary'
                                        };
                                    ?> px-2 py-1"><?php echo e($w['position']); ?></span>
                                    <span class="fw-semibold text-dark"><?php echo e($w['name']); ?></span>
                                </div>
                                <span class="text-muted small"><?php echo e($w['title']); ?></span>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted text-center py-4">No winners declared yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
