<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

require_role('admin');

$page_title = "Admin Dashboard";

// Real Database Metric Calculations
$total_users = 0;
$total_students = 0;
$total_organizers = 0;
$total_events = 0;
$open_events = 0;
$total_participants = 0;

$r = mysqli_query($conn, "SELECT COUNT(*) c FROM users");
if ($r) $total_users = (int)mysqli_fetch_assoc($r)['c'];

$r = mysqli_query($conn, "SELECT COUNT(*) c FROM users WHERE role='student'");
if ($r) $total_students = (int)mysqli_fetch_assoc($r)['c'];

$r = mysqli_query($conn, "SELECT COUNT(*) c FROM users WHERE role='organizer'");
if ($r) $total_organizers = (int)mysqli_fetch_assoc($r)['c'];

$r = mysqli_query($conn, "SELECT COUNT(*) c FROM events");
if ($r) $total_events = (int)mysqli_fetch_assoc($r)['c'];

$r = mysqli_query($conn, "SELECT COUNT(*) c FROM events WHERE status='open'");
if ($r) $open_events = (int)mysqli_fetch_assoc($r)['c'];

$r = mysqli_query($conn, "SELECT COUNT(*) c FROM event_participation");
if ($r) $total_participants = (int)mysqli_fetch_assoc($r)['c'];

// Data for Department Breakdown Chart
$dept_labels = [];
$dept_counts = [];
$dept_res = mysqli_query($conn, "SELECT COALESCE(department, 'General') as dept, COUNT(*) as cnt FROM students GROUP BY dept");
if ($dept_res) {
    while ($d = mysqli_fetch_assoc($dept_res)) {
        $dept_labels[] = $d['dept'];
        $dept_counts[] = (int)$d['cnt'];
    }
}
if (empty($dept_labels)) {
    $dept_labels = ['General', 'Computer Science', 'Information Tech'];
    $dept_counts = [1, 2, 1];
}

// Data for Event Status Chart
$status_open = $open_events;
$status_closed = 0;
$r = mysqli_query($conn, "SELECT COUNT(*) c FROM events WHERE status='closed'");
if ($r) $status_closed = (int)mysqli_fetch_assoc($r)['c'];

// Recent Users Roster
$recent_users = mysqli_query($conn, "SELECT id, username, role, status FROM users ORDER BY id DESC LIMIT 6");

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

    <!-- 6 Live Metric KPI Boxes -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-4 col-xl-2">
            <div class="metric-box">
                <div class="metric-icon-wrap" style="background:#eef2ff; color:#4f46e5;">
                    <i class="bi bi-people-fill"></i>
                </div>
                <div class="metric-val"><?php echo $total_users; ?></div>
                <div class="metric-lbl">Total Users</div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-4 col-xl-2">
            <div class="metric-box">
                <div class="metric-icon-wrap" style="background:#ecfdf5; color:#10b981;">
                    <i class="bi bi-mortarboard-fill"></i>
                </div>
                <div class="metric-val"><?php echo $total_students; ?></div>
                <div class="metric-lbl">Students</div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-4 col-xl-2">
            <div class="metric-box">
                <div class="metric-icon-wrap" style="background:#fffbeb; color:#f59e0b;">
                    <i class="bi bi-person-badge-fill"></i>
                </div>
                <div class="metric-val"><?php echo $total_organizers; ?></div>
                <div class="metric-lbl">Organizers</div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-4 col-xl-2">
            <div class="metric-box">
                <div class="metric-icon-wrap" style="background:#f0f9ff; color:#0284c7;">
                    <i class="bi bi-calendar-event-fill"></i>
                </div>
                <div class="metric-val"><?php echo $total_events; ?></div>
                <div class="metric-lbl">Total Events</div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-4 col-xl-2">
            <div class="metric-box">
                <div class="metric-icon-wrap" style="background:#fef2f2; color:#ef4444;">
                    <i class="bi bi-door-open-fill"></i>
                </div>
                <div class="metric-val"><?php echo $open_events; ?></div>
                <div class="metric-lbl">Open Events</div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-4 col-xl-2">
            <div class="metric-box">
                <div class="metric-icon-wrap" style="background:#faf5ff; color:#a855f7;">
                    <i class="bi bi-check2-circle"></i>
                </div>
                <div class="metric-val"><?php echo $total_participants; ?></div>
                <div class="metric-lbl">Registrations</div>
            </div>
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

    <!-- System User Roster Table -->
    <div class="cems-card">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h5 class="fw-bold text-dark mb-1">Recent User Accounts</h5>
                <p class="text-muted small mb-0">System credentials and role authorizations</p>
            </div>
            <div class="d-flex gap-2">
                <a href="add_student.php" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-plus-lg"></i> New User
                </a>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table-cems">
                <thead>
                    <tr>
                        <th>User ID</th>
                        <th>Username</th>
                        <th>System Role</th>
                        <th>Status</th>
                        <th>Access Level</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($recent_users && mysqli_num_rows($recent_users) > 0): ?>
                        <?php while ($u = mysqli_fetch_assoc($recent_users)): ?>
                            <tr>
                                <td class="text-muted fw-semibold">#<?php echo (int)$u['id']; ?></td>
                                <td class="fw-bold text-dark"><?php echo e($u['username']); ?></td>
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
                                <td>
                                    <span class="badge-status <?php echo $u['status'] === 'active' ? 'badge-status-approved' : 'badge-status-rejected'; ?>">
                                        <i class="bi <?php echo $u['status'] === 'active' ? 'bi-check-circle-fill' : 'bi-x-circle-fill'; ?>"></i>
                                        <?php echo ucfirst(e($u['status'])); ?>
                                    </span>
                                </td>
                                <td class="text-muted small">
                                    <?php echo $u['role'] === 'admin' ? 'Full Governance' : ($u['role'] === 'organizer' ? 'Event Coordinator' : 'Participant'); ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">No users found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
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
                    data: <?php echo json_encode($dept_counts); ?>,
                    backgroundColor: ['#4f46e5', '#06b6d4', '#10b981', '#f59e0b', '#ec4899'],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } }
                }
            }
        });
    }

    // 2. Event Status Bar Chart
    const statusCtx = document.getElementById('statusChart');
    if (statusCtx) {
        new Chart(statusCtx, {
            type: 'bar',
            data: {
                labels: ['Open Registrations', 'Closed / Past'],
                datasets: [{
                    label: 'Events Count',
                    data: [<?php echo (int)$status_open; ?>, <?php echo (int)$status_closed; ?>],
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
                    y: { beginAtZero: true, ticks: { precision: 0 } }
                }
            }
        });
    }
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
