<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

require_role('organizer');

$page_title = "Post Announcement";
$uid = (int)$_SESSION['user_id'];
$error = "";

// Handle Delete Announcement via Secure POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_announcement'])) {
    verify_csrf();
    $ann_id = (int)($_POST['announcement_id'] ?? 0);

    $del = mysqli_prepare($conn, "
        DELETE a FROM announcements a
        JOIN events e ON a.event_id = e.id
        WHERE a.id = ? AND e.created_by = ?
    ");
    if ($del) {
        mysqli_stmt_bind_param($del, "ii", $ann_id, $uid);
        mysqli_stmt_execute($del);
        if (mysqli_stmt_affected_rows($del) > 0) {
            set_flash('success', "Notice deleted successfully.");
        }
        mysqli_stmt_close($del);
    }
    header("Location: post_announcement.php");
    exit;
}

// Handle Post Announcement via Secure POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['delete_announcement'])) {
    verify_csrf();

    $event_id = (int)($_POST['event_id'] ?? 0);
    $message  = trim($_POST['message'] ?? '');

    // Verify event ownership
    $chk = mysqli_prepare($conn, "SELECT id, title FROM events WHERE id = ? AND created_by = ?");
    mysqli_stmt_bind_param($chk, "ii", $event_id, $uid);
    mysqli_stmt_execute($chk);
    $res = mysqli_stmt_get_result($chk);
    $event_data = mysqli_fetch_assoc($res);
    mysqli_stmt_close($chk);

    if (!$event_data) {
        $error = "Permission denied or invalid event.";
    } elseif (empty($message)) {
        $error = "Announcement message cannot be empty.";
    } else {
        $ins = mysqli_prepare($conn, "INSERT INTO announcements (event_id, message) VALUES (?, ?)");
        if ($ins) {
            mysqli_stmt_bind_param($ins, "is", $event_id, $message);
            if (mysqli_stmt_execute($ins)) {
                mysqli_stmt_close($ins);
                set_flash('success', "Announcement posted successfully for '{$event_data['title']}'!");
                header("Location: post_announcement.php");
                exit;
            } else {
                $error = "Database error while posting announcement.";
                mysqli_stmt_close($ins);
            }
        }
    }
}

// Fetch events managed by this organizer
$ev_stmt = mysqli_prepare($conn, "SELECT id, title, event_date FROM events WHERE created_by = ? ORDER BY event_date DESC");
mysqli_stmt_bind_param($ev_stmt, "i", $uid);
mysqli_stmt_execute($ev_stmt);
$my_events = mysqli_stmt_get_result($ev_stmt);

// Fetch recent announcements posted by this organizer
$recent_stmt = mysqli_prepare($conn, "
    SELECT a.id, a.message, a.created_at, e.title as event_title
    FROM announcements a
    JOIN events e ON a.event_id = e.id
    WHERE e.created_by = ?
    ORDER BY a.created_at DESC
");
mysqli_stmt_bind_param($recent_stmt, "i", $uid);
mysqli_stmt_execute($recent_stmt);
$recent_announcements = mysqli_stmt_get_result($recent_stmt);

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>

<div class="container py-4">

    <!-- Header & Action -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h2 class="fw-bold tracking-tight text-dark mb-1">Campus Event Announcements</h2>
            <p class="text-muted small mb-0">Broadcast schedule changes, reporting times, or venue guidelines to students</p>
        </div>
        <a href="organizer_dashboard.php" class="btn btn-sm btn-cems-secondary">
            <i class="bi bi-arrow-left me-1"></i> Dashboard
        </a>
    </div>

    <!-- Flash notifications -->
    <?php display_flash(); ?>

    <?php if ($error): ?>
        <div class="alert alert-danger d-flex align-items-center gap-2 mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill fs-5"></i>
            <div><?php echo e($error); ?></div>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- New Announcement Form Column -->
        <div class="col-lg-6">
            <div class="cems-card">
                <h5 class="fw-bold text-dark mb-3"><i class="bi bi-megaphone-fill text-primary me-2"></i>Post New Circular</h5>

                <?php if ($my_events && mysqli_num_rows($my_events) > 0): ?>
                    <form method="post" action="post_announcement.php" novalidate>
                        <?php echo csrf_field(); ?>

                        <div class="mb-3">
                            <label class="form-label fw-semibold small text-muted">Target Event <span class="text-danger">*</span></label>
                            <select name="event_id" class="form-select" required>
                                <option value="">-- Choose Managed Event --</option>
                                <?php while ($e = mysqli_fetch_assoc($my_events)): ?>
                                    <option value="<?php echo (int)$e['id']; ?>">
                                        <?php echo e($e['title']); ?> (<?php echo date("d M Y", strtotime($e['event_date'])); ?>)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold small text-muted">Circular Message <span class="text-danger">*</span></label>
                            <textarea name="message" class="form-control" rows="4" 
                                      placeholder="e.g. Participants are requested to report at Hall B by 9:30 AM with their college ID..." required></textarea>
                        </div>

                        <div class="d-flex justify-content-end">
                            <button type="submit" name="post_announcement" class="btn btn-cems-primary px-4 d-inline-flex align-items-center gap-2">
                                <i class="bi bi-send-fill"></i> Broadcast Announcement
                            </button>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-calendar-x fs-2 d-block mb-2"></i>
                        You need to schedule an event before you can broadcast announcements.<br>
                        <a href="create_event.php" class="btn btn-sm btn-cems-primary mt-2">Create an Event</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Announcements History Column -->
        <div class="col-lg-6">
            <div class="cems-card h-100">
                <h5 class="fw-bold text-dark mb-3"><i class="bi bi-clock-history text-muted me-2"></i>Broadcast History</h5>

                <?php if ($recent_announcements && mysqli_num_rows($recent_announcements) > 0): ?>
                    <div class="d-flex flex-column gap-3">
                        <?php while ($a = mysqli_fetch_assoc($recent_announcements)): ?>
                            <div class="p-3 border rounded-3 bg-light position-relative">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="badge bg-primary-subtle text-primary fw-bold">
                                        <?php echo e($a['event_title']); ?>
                                    </span>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="text-muted small">
                                            <?php echo date("d M Y, h:i A", strtotime($a['created_at'])); ?>
                                        </span>
                                        <form method="post" action="post_announcement.php" class="d-inline" onsubmit="return confirm('Delete this announcement?');">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="announcement_id" value="<?php echo (int)$a['id']; ?>">
                                            <button type="submit" name="delete_announcement" class="btn btn-sm btn-link text-danger p-0" title="Delete Announcement">
                                                <i class="bi bi-trash3"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                                <div class="text-dark small mt-2"><?php echo nl2br(e($a['message'])); ?></div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-chat-left-dots fs-1 opacity-25 d-block mb-2"></i>
                        No announcements posted yet.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>

<?php 
mysqli_stmt_close($ev_stmt);
mysqli_stmt_close($recent_stmt);
include __DIR__ . '/includes/footer.php'; 
?>
