<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

require_role('organizer');

$page_title = "My Events";
$uid = (int)$_SESSION['user_id'];

// Handle Event Status Updates via Secure POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    verify_csrf();

    $event_id = (int)($_POST['event_id'] ?? 0);
    $new_status = $_POST['status'] ?? '';

    if (in_array($new_status, ['open', 'closed', 'completed'], true)) {
        // Enforce ownership check in UPDATE query
        $stmt = mysqli_prepare($conn, "UPDATE events SET status = ? WHERE id = ? AND created_by = ?");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "sii", $new_status, $event_id, $uid);
            mysqli_stmt_execute($stmt);
            if (mysqli_stmt_affected_rows($stmt) > 0) {
                set_flash('success', "Event status updated to '" . ucfirst($new_status) . "'.");
            } else {
                set_flash('warning', "No changes made, or you do not have permission for this event.");
            }
            mysqli_stmt_close($stmt);
        }
    }
    header("Location: my_events.php");
    exit;
}

// Fetch all events created by this organizer with registration count
$stmt = mysqli_prepare($conn, "
    SELECT e.*,
           COUNT(ep.id) AS total_applicants,
           SUM(CASE WHEN ep.approval_status = 'approved' THEN 1 ELSE 0 END) AS approved_count
    FROM events e
    LEFT JOIN event_participation ep ON e.id = ep.event_id
    WHERE e.created_by = ?
    GROUP BY e.id
    ORDER BY e.event_date DESC
");
mysqli_stmt_bind_param($stmt, "i", $uid);
mysqli_stmt_execute($stmt);
$events_result = mysqli_stmt_get_result($stmt);

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>

<div class="container py-4">

    <!-- Header & Action -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h2 class="fw-bold tracking-tight text-dark mb-1">My Managed Events</h2>
            <p class="text-muted small mb-0">Overview of competitions scheduled and coordinated by your account</p>
        </div>
        <div class="d-flex gap-2">
            <a href="organizer_dashboard.php" class="btn btn-sm btn-cems-secondary">
                <i class="bi bi-arrow-left me-1"></i> Dashboard
            </a>
            <a href="create_event.php" class="btn btn-sm btn-cems-primary d-inline-flex align-items-center gap-1">
                <i class="bi bi-plus-circle"></i> Create Event
            </a>
        </div>
    </div>

    <!-- Flash notifications -->
    <?php display_flash(); ?>

    <div class="cems-card">
        <div class="table-responsive">
            <table class="table-cems">
                <thead>
                    <tr>
                        <th>Event Title</th>
                        <th>Event Date</th>
                        <th>Venue</th>
                        <th>Participants</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($events_result && mysqli_num_rows($events_result) > 0): ?>
                        <?php while ($ev = mysqli_fetch_assoc($events_result)): ?>
                            <tr>
                                <td>
                                    <div class="fw-bold text-dark"><?php echo e($ev['title']); ?></div>
                                    <div class="text-muted small text-truncate" style="max-width: 280px;">
                                        <?php echo e(!empty($ev['description']) ? $ev['description'] : 'No description provided.'); ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark"><?php echo date("d M Y", strtotime($ev['event_date'])); ?></div>
                                    <div class="text-muted small"><?php echo date("l", strtotime($ev['event_date'])); ?></div>
                                </td>
                                <td>
                                    <i class="bi bi-geo-alt text-danger me-1"></i>
                                    <?php echo e(!empty($ev['venue']) ? $ev['venue'] : 'TBA'); ?>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        <?php echo (int)($ev['approved_count'] ?? 0); ?> approved / <?php echo (int)($ev['total_applicants'] ?? 0); ?> applied
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    $badge_class = match($ev['status']) {
                                        'open' => 'badge-status-open',
                                        'closed' => 'badge-status-closed',
                                        default => 'bg-info-subtle text-info'
                                    };
                                    ?>
                                    <span class="badge-status <?php echo $badge_class; ?>">
                                        <?php echo ucfirst(e($ev['status'])); ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-1">
                                        <!-- Manage Participants for this event -->
                                        <a href="manage_participants.php?event_id=<?php echo (int)$ev['id']; ?>" class="btn btn-sm btn-outline-primary" title="View Participants">
                                            <i class="bi bi-people"></i>
                                        </a>

                                        <!-- Status Toggle POST Form -->
                                        <form method="post" action="my_events.php" class="d-inline" onsubmit="return confirm('Change event registration status?');">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="event_id" value="<?php echo (int)$ev['id']; ?>">
                                            <?php if ($ev['status'] === 'open'): ?>
                                                <input type="hidden" name="status" value="closed">
                                                <button type="submit" name="update_status" class="btn btn-sm btn-outline-danger" title="Close Registrations">
                                                    <i class="bi bi-door-closed"></i> Close
                                                </button>
                                            <?php else: ?>
                                                <input type="hidden" name="status" value="open">
                                                <button type="submit" name="update_status" class="btn btn-sm btn-outline-success" title="Re-Open Registrations">
                                                    <i class="bi bi-door-open"></i> Open
                                                </button>
                                            <?php endif; ?>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                <i class="bi bi-calendar-plus fs-2 text-muted d-block mb-2"></i>
                                You haven't scheduled any events yet.<br>
                                <a href="create_event.php" class="btn btn-sm btn-cems-primary mt-2">Create Your First Event</a>
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
include __DIR__ . '/includes/footer.php'; 
?>
