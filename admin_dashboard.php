<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

require_role('admin');

$page_title = "Admin Control Center";

// 1. Core Aggregate Metrics
$total_users = 0;
$total_students = 0;
$total_organizers = 0;
$total_events = 0;
$open_events = 0;
$total_participants = 0;

$r = mysqli_query($conn, "SELECT COUNT(*) c FROM users");
if ($r) $total_users = (int)mysqli_fetch_assoc($r)['c'];

$r = mysqli_query($conn, "SELECT COUNT(*) c FROM students");
if ($r) $total_students = (int)mysqli_fetch_assoc($r)['c'];

$r = mysqli_query($conn, "SELECT COUNT(*) c FROM organizers");
if ($r) $total_organizers = (int)mysqli_fetch_assoc($r)['c'];

$r = mysqli_query($conn, "SELECT COUNT(*) c FROM events");
if ($r) $total_events = (int)mysqli_fetch_assoc($r)['c'];

$r = mysqli_query($conn, "SELECT COUNT(*) c FROM events WHERE status='open'");
if ($r) $open_events = (int)mysqli_fetch_assoc($r)['c'];

$r = mysqli_query($conn, "SELECT COUNT(*) c FROM event_participation");
if ($r) $total_participants = (int)mysqli_fetch_assoc($r)['c'];

// 2. Department distribution for Chart.js
$dept_counts = [];
$res = mysqli_query($conn, "SELECT department, COUNT(*) c FROM students GROUP BY department");
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $dept_name = !empty($row['department']) ? $row['department'] : 'General';
        $dept_counts[$dept_name] = (int)$row['c'];
    }
}
$dept_labels = array_keys($dept_counts);
$dept_values = array_values($dept_counts);

// 3. Data for Event Status Chart
$status_open = $open_events;
$status_closed = 0;
$r = mysqli_query($conn, "SELECT COUNT(*) c FROM events WHERE status='closed'");
if ($r) $status_closed = (int)mysqli_fetch_assoc($r)['c'];

// 4. Tab and Filter Selection
$tab = trim($_GET['tab'] ?? 'all');
if (!in_array($tab, ['all', 'students', 'organizers', 'registrations'], true)) {
    $tab = 'all';
}

// 5. Query data based on selected tab
if ($tab === 'students') {
    $table_data = mysqli_query($conn, "
        SELECT u.id, u.username, u.status, s.name, s.roll_no, s.department
        FROM users u
        JOIN students s ON u.id = s.user_id
        ORDER BY u.id DESC
    ");
} elseif ($tab === 'organizers') {
    $table_data = mysqli_query($conn, "
        SELECT u.id, u.username, u.status, o.name, o.department
        FROM users u
        JOIN organizers o ON u.id = o.user_id
        ORDER BY u.id DESC
    ");
} elseif ($tab === 'registrations') {
    $table_data = mysqli_query($conn, "
        SELECT ep.id, ep.applied_at, ep.approval_status, s.name AS student_name, s.roll_no, s.department, e.title AS event_title, e.id AS event_id
        FROM event_participation ep
        JOIN students s ON ep.student_id = s.id
        JOIN events e ON ep.event_id = e.id
        ORDER BY ep.applied_at DESC
    ");
} else {
    // Default: All Users
    $table_data = mysqli_query($conn, "
        SELECT u.id, u.username, u.role, u.status,
               COALESCE(s.name, o.name, 'System Administrator') AS display_name,
               COALESCE(s.department, o.department, 'Administration') AS department
        FROM users u
        LEFT JOIN students s ON u.id = s.user_id
        LEFT JOIN organizers o ON u.id = o.user_id
        ORDER BY u.id DESC
    ");
}

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>

<div class="container py-4">

    <!-- Header & Quick Actions -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h2 class="fw-bold tracking-tight text-dark mb-1">Administrative Control Center</h2>
            <p class="text-muted small mb-0">System governance, live participation metrics, and account provisioning</p>
        </div>
        <div class="d-flex gap-2">
            <a href="add_student.php" class="btn btn-sm btn-cems-primary d-inline-flex align-items-center gap-1">
                <i class="bi bi-person-plus"></i> Add Student
            </a>
            <a href="add_organizer.php" class="btn btn-sm btn-cems-secondary d-inline-flex align-items-center gap-1">
                <i class="bi bi-person-badge"></i> Add Organizer
            </a>
        </div>
    </div>

    <!-- Flash notifications -->
    <?php display_flash(); ?>

    <!-- 6 Live Metric KPI Boxes (Every Tab is Clickable) -->
    <div class="row g-3 mb-4">
        <!-- 1. Total Users -->
        <div class="col-sm-6 col-lg-4 col-xl-2">
            <a href="admin_dashboard.php?tab=all#roster" class="metric-box-clickable <?php if ($tab === 'all') echo 'active-tab'; ?>" title="View All Registered Accounts">
                <div class="metric-box">
                    <div class="metric-icon-wrap" style="background:#eef2ff; color:#4f46e5;">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <div class="metric-val"><?php echo $total_users; ?></div>
                    <div class="metric-lbl">Total Users</div>
                </div>
            </a>
        </div>

        <!-- 2. Students -->
        <div class="col-sm-6 col-lg-4 col-xl-2">
            <a href="admin_dashboard.php?tab=students#roster" class="metric-box-clickable <?php if ($tab === 'students') echo 'active-tab'; ?>" title="View &amp; Manage Enrolled Students">
                <div class="metric-box">
                    <div class="metric-icon-wrap" style="background:#ecfdf5; color:#10b981;">
                        <i class="bi bi-mortarboard-fill"></i>
                    </div>
                    <div class="metric-val"><?php echo $total_students; ?></div>
                    <div class="metric-lbl">Students</div>
                </div>
            </a>
        </div>

        <!-- 3. Organizers -->
        <div class="col-sm-6 col-lg-4 col-xl-2">
            <a href="admin_dashboard.php?tab=organizers#roster" class="metric-box-clickable <?php if ($tab === 'organizers') echo 'active-tab'; ?>" title="View &amp; Manage Faculty Organizers">
                <div class="metric-box">
                    <div class="metric-icon-wrap" style="background:#fffbeb; color:#f59e0b;">
                        <i class="bi bi-person-badge-fill"></i>
                    </div>
                    <div class="metric-val"><?php echo $total_organizers; ?></div>
                    <div class="metric-lbl">Organizers</div>
                </div>
            </a>
        </div>

        <!-- 4. Total Events -->
        <div class="col-sm-6 col-lg-4 col-xl-2">
            <a href="view_events.php" class="metric-box-clickable" title="View &amp; Explore All Events">
                <div class="metric-box">
                    <div class="metric-icon-wrap" style="background:#f0f9ff; color:#0284c7;">
                        <i class="bi bi-calendar-event-fill"></i>
                    </div>
                    <div class="metric-val"><?php echo $total_events; ?></div>
                    <div class="metric-lbl">Total Events</div>
                </div>
            </a>
        </div>

        <!-- 5. Open Events -->
        <div class="col-sm-6 col-lg-4 col-xl-2">
            <a href="view_events.php?status=open" class="metric-box-clickable" title="View Active Open Registration Events">
                <div class="metric-box">
                    <div class="metric-icon-wrap" style="background:#fef2f2; color:#ef4444;">
                        <i class="bi bi-door-open-fill"></i>
                    </div>
                    <div class="metric-val"><?php echo $open_events; ?></div>
                    <div class="metric-lbl">Open Events</div>
                </div>
            </a>
        </div>

        <!-- 6. Registrations -->
        <div class="col-sm-6 col-lg-4 col-xl-2">
            <a href="admin_dashboard.php?tab=registrations#roster" class="metric-box-clickable <?php if ($tab === 'registrations') echo 'active-tab'; ?>" title="View All Student Registrations">
                <div class="metric-box">
                    <div class="metric-icon-wrap" style="background:#faf5ff; color:#a855f7;">
                        <i class="bi bi-check2-circle"></i>
                    </div>
                    <div class="metric-val"><?php echo $total_participants; ?></div>
                    <div class="metric-lbl">Registrations</div>
                </div>
            </a>
        </div>
    </div>

    <!-- Visual Analytics: Chart.js -->
    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="cems-card h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold text-dark mb-0"><i class="bi bi-pie-chart-fill text-primary me-2"></i>Department-wise Student Distribution</h6>
                    <span class="badge bg-light text-muted small">Live Data</span>
                </div>
                <div style="height: 250px; position: relative;">
                    <canvas id="deptChart"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="cems-card h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold text-dark mb-0"><i class="bi bi-bar-chart-fill text-success me-2"></i>Event Lifecycle Overview</h6>
                    <span class="badge bg-light text-muted small">Active vs Closed</span>
                </div>
                <div style="height: 250px; position: relative;">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Interactive Data Roster Section (Anchor Target) -->
    <div class="cems-card" id="roster">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3">
            <div>
                <h5 class="fw-bold text-dark mb-1">Recent User Accounts &amp; System Directory</h5>
                <p class="text-muted small mb-0">
                    Currently viewing: <strong class="text-primary text-uppercase"><?php echo e($tab); ?></strong>
                </p>
            </div>
            <div class="d-flex gap-2">
                <a href="add_student.php" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1">
                    <i class="bi bi-person-plus"></i> New Student
                </a>
                <a href="add_organizer.php" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1">
                    <i class="bi bi-person-badge"></i> New Coordinator
                </a>
            </div>
        </div>

        <!-- Interactive Tab Filter Pills -->
        <div class="d-flex flex-wrap gap-2 mb-3 pb-2 border-bottom">
            <a href="admin_dashboard.php?tab=all#roster" class="btn btn-sm <?php echo $tab === 'all' ? 'btn-primary' : 'btn-outline-secondary'; ?> d-inline-flex align-items-center gap-1">
                <i class="bi bi-people-fill"></i> All Accounts (<?php echo $total_users; ?>)
            </a>
            <a href="admin_dashboard.php?tab=students#roster" class="btn btn-sm <?php echo $tab === 'students' ? 'btn-success text-white' : 'btn-outline-secondary'; ?> d-inline-flex align-items-center gap-1">
                <i class="bi bi-mortarboard-fill"></i> Students (<?php echo $total_students; ?>)
            </a>
            <a href="admin_dashboard.php?tab=organizers#roster" class="btn btn-sm <?php echo $tab === 'organizers' ? 'btn-warning text-dark' : 'btn-outline-secondary'; ?> d-inline-flex align-items-center gap-1">
                <i class="bi bi-person-badge-fill"></i> Coordinators (<?php echo $total_organizers; ?>)
            </a>
            <a href="admin_dashboard.php?tab=registrations#roster" class="btn btn-sm <?php echo $tab === 'registrations' ? 'btn-info text-white' : 'btn-outline-secondary'; ?> d-inline-flex align-items-center gap-1">
                <i class="bi bi-check2-circle"></i> Registrations (<?php echo $total_participants; ?>)
            </a>
            <a href="view_events.php" class="btn btn-sm btn-outline-primary ms-auto d-inline-flex align-items-center gap-1">
                <i class="bi bi-calendar-event"></i> Manage Events (<?php echo $total_events; ?>) &rarr;
            </a>
        </div>

        <div class="table-responsive">
            <table class="table-cems">
                <?php if ($tab === 'students'): ?>
                    <!-- Students View -->
                    <thead>
                        <tr>
                            <th>User ID</th>
                            <th>Username</th>
                            <th>Student Name</th>
                            <th>Roll Number</th>
                            <th>Department</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($table_data && mysqli_num_rows($table_data) > 0): ?>
                            <?php while ($s = mysqli_fetch_assoc($table_data)): ?>
                                <tr>
                                    <td class="text-muted fw-semibold">#<?php echo (int)$s['id']; ?></td>
                                    <td class="fw-bold text-dark"><?php echo e($s['username']); ?></td>
                                    <td><span class="fw-semibold text-dark"><?php echo e($s['name']); ?></span></td>
                                    <td><span class="font-monospace text-muted"><?php echo e(!empty($s['roll_no']) ? $s['roll_no'] : 'N/A'); ?></span></td>
                                    <td><span class="badge bg-light text-dark border"><?php echo e(!empty($s['department']) ? $s['department'] : 'General'); ?></span></td>
                                    <td>
                                        <span class="badge-status <?php echo $s['status'] === 'active' ? 'badge-status-approved' : 'badge-status-rejected'; ?>">
                                            <?php echo ucfirst(e($s['status'])); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="text-center text-muted py-4">No student records found.</td></tr>
                        <?php endif; ?>
                    </tbody>

                <?php elseif ($tab === 'organizers'): ?>
                    <!-- Organizers View -->
                    <thead>
                        <tr>
                            <th>User ID</th>
                            <th>Username</th>
                            <th>Faculty Name</th>
                            <th>Assigned Department</th>
                            <th>Role Scope</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($table_data && mysqli_num_rows($table_data) > 0): ?>
                            <?php while ($o = mysqli_fetch_assoc($table_data)): ?>
                                <tr>
                                    <td class="text-muted fw-semibold">#<?php echo (int)$o['id']; ?></td>
                                    <td class="fw-bold text-dark"><?php echo e($o['username']); ?></td>
                                    <td><span class="fw-semibold text-dark"><?php echo e($o['name']); ?></span></td>
                                    <td><span class="badge bg-light text-dark border"><?php echo e(!empty($o['department']) ? $o['department'] : 'General'); ?></span></td>
                                    <td><span class="badge bg-warning-subtle text-warning-emphasis">Event Coordinator</span></td>
                                    <td>
                                        <span class="badge-status <?php echo $o['status'] === 'active' ? 'badge-status-approved' : 'badge-status-rejected'; ?>">
                                            <?php echo ucfirst(e($o['status'])); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="text-center text-muted py-4">No organizer records found.</td></tr>
                        <?php endif; ?>
                    </tbody>

                <?php elseif ($tab === 'registrations'): ?>
                    <!-- Registrations View -->
                    <thead>
                        <tr>
                            <th>Reg ID</th>
                            <th>Student Delegate</th>
                            <th>Roll &amp; Department</th>
                            <th>Target Event</th>
                            <th>Applied On</th>
                            <th>Approval Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($table_data && mysqli_num_rows($table_data) > 0): ?>
                            <?php while ($r = mysqli_fetch_assoc($table_data)): ?>
                                <tr>
                                    <td class="text-muted fw-semibold">#CEMS-<?php echo str_pad($r['id'], 4, '0', STR_PAD_LEFT); ?></td>
                                    <td class="fw-bold text-dark"><?php echo e($r['student_name']); ?></td>
                                    <td>
                                        <span class="text-muted small">Roll: <?php echo e(!empty($r['roll_no']) ? $r['roll_no'] : 'N/A'); ?></span> &bull; 
                                        <span class="small fw-semibold"><?php echo e($r['department']); ?></span>
                                    </td>
                                    <td>
                                        <a href="view_events.php" class="badge bg-light text-primary border text-decoration-none">
                                            <?php echo e($r['event_title']); ?>
                                        </a>
                                    </td>
                                    <td class="text-muted small"><?php echo date("d M Y, h:i A", strtotime($r['applied_at'])); ?></td>
                                    <td>
                                        <span class="badge-status <?php echo match($r['approval_status']) {
                                            'approved' => 'badge-status-approved',
                                            'rejected' => 'badge-status-rejected',
                                            default => 'badge-status-pending'
                                        }; ?>">
                                            <?php echo ucfirst(e($r['approval_status'])); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="text-center text-muted py-4">No student registrations recorded yet.</td></tr>
                        <?php endif; ?>
                    </tbody>

                <?php else: ?>
                    <!-- Default: All Users Roster -->
                    <thead>
                        <tr>
                            <th>User ID</th>
                            <th>Username</th>
                            <th>Display Name</th>
                            <th>System Role</th>
                            <th>Department / Scope</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($table_data && mysqli_num_rows($table_data) > 0): ?>
                            <?php while ($u = mysqli_fetch_assoc($table_data)): ?>
                                <tr>
                                    <td class="text-muted fw-semibold">#<?php echo (int)$u['id']; ?></td>
                                    <td class="fw-bold text-dark"><?php echo e($u['username']); ?></td>
                                    <td class="text-dark fw-semibold"><?php echo e($u['display_name']); ?></td>
                                    <td>
                                        <span class="badge <?php 
                                            echo match($u['role']) {
                                                'admin' => 'bg-danger-subtle text-danger',
                                                'organizer' => 'bg-warning-subtle text-warning-emphasis',
                                                default => 'bg-primary-subtle text-primary'
                                            };
                                        ?> text-capitalize px-2 py-1">
                                            <?php echo e($u['role']); ?>
                                        </span>
                                    </td>
                                    <td class="text-muted small"><?php echo e($u['department']); ?></td>
                                    <td>
                                        <span class="badge-status <?php echo $u['status'] === 'active' ? 'badge-status-approved' : 'badge-status-rejected'; ?>">
                                            <i class="bi <?php echo $u['status'] === 'active' ? 'bi-check-circle-fill' : 'bi-x-circle-fill'; ?>"></i>
                                            <?php echo ucfirst(e($u['status'])); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">No users found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                <?php endif; ?>
            </table>
        </div>
    </div>

</div>

<!-- Chart.js Library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    // 1. Department Breakdown Doughnut Chart
    const deptCtx = document.getElementById('deptChart');
    if (deptCtx) {
        new Chart(deptCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($dept_labels); ?>,
                datasets: [{
                    data: <?php echo json_encode($dept_values); ?>,
                    backgroundColor: [
                        '#4f46e5',
                        '#0ea5e9',
                        '#10b981',
                        '#f59e0b',
                        '#ec4899',
                        '#8b5cf6'
                    ],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            boxWidth: 12,
                            font: { size: 11, family: 'Inter' }
                        }
                    }
                },
                cutout: '65%'
            }
        });
    }

    // 2. Event Lifecycle Overview Bar Chart
    const statusCtx = document.getElementById('statusChart');
    if (statusCtx) {
        new Chart(statusCtx, {
            type: 'bar',
            data: {
                labels: ['Open Events', 'Closed Events'],
                datasets: [{
                    label: 'Event Count',
                    data: [<?php echo $status_open; ?>, <?php echo $status_closed; ?>],
                    backgroundColor: ['#10b981', '#64748b'],
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1, font: { family: 'Inter' } },
                        grid: { color: '#f1f5f9' }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { font: { family: 'Inter' } }
                    }
                }
            }
        });
    }
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
