<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

$page_title = "Campus Bulletins & Announcements";

$search = trim($_GET['q'] ?? '');

$sql = "
    SELECT a.id, a.message, a.created_at, e.title AS event_title, e.event_date
    FROM announcements a
    JOIN events e ON a.event_id = e.id
    WHERE 1=1
";
$params = [];
$types = "";

if (!empty($search)) {
    $sql .= " AND (a.message LIKE ? OR e.title LIKE ?)";
    $like = "%" . $search . "%";
    $types .= "ss";
    $params[] = $like;
    $params[] = $like;
}

$sql .= " ORDER BY a.created_at DESC";

$stmt = mysqli_prepare($conn, $sql);
if (!empty($types)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$ann_res = mysqli_stmt_get_result($stmt);

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>

<div class="container py-4">

    <!-- Header & Search -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h2 class="fw-bold tracking-tight text-dark mb-1">
                <i class="bi bi-megaphone-fill text-info me-2"></i>Campus Bulletins &amp; Notices
            </h2>
            <p class="text-muted small mb-0">Official updates, reporting schedules, and guidelines issued by event coordinators</p>
        </div>

        <form method="get" class="d-flex gap-2">
            <div class="input-group">
                <span class="input-group-text bg-white text-muted border-end-0"><i class="bi bi-search"></i></span>
                <input type="text" name="q" class="form-control border-start-0 ps-0" 
                       placeholder="Search circulars..." value="<?php echo e($search); ?>">
            </div>
            <button type="submit" class="btn btn-cems-primary px-3">Search</button>
            <?php if (!empty($search)): ?>
                <a href="announcements.php" class="btn btn-outline-secondary">Reset</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Announcements Feed -->
    <div class="row g-3">
        <?php if ($ann_res && mysqli_num_rows($ann_res) > 0): ?>
            <?php while ($a = mysqli_fetch_assoc($ann_res)): ?>
                <div class="col-12">
                    <div class="cems-card p-3 p-md-4 cems-card-hover">
                        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mb-2 pb-2 border-bottom">
                            <span class="badge bg-primary-subtle text-primary fw-bold px-3 py-1">
                                <i class="bi bi-calendar-event me-1"></i> <?php echo e($a['event_title']); ?>
                            </span>
                            <span class="text-muted small">
                                <i class="bi bi-clock me-1"></i> Broadcasted on <?php echo date("d M Y, h:i A", strtotime($a['created_at'])); ?>
                            </span>
                        </div>
                        <div class="text-dark mt-2" style="font-size: 0.95rem; line-height: 1.6;">
                            <?php echo nl2br(e($a['message'])); ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="cems-card text-center py-5 text-muted">
                    <i class="bi bi-chat-left-dots fs-1 opacity-25 d-block mb-2"></i>
                    No bulletins found matching your query.
                </div>
            </div>
        <?php endif; ?>
    </div>

</div>

<?php 
mysqli_stmt_close($stmt);
include __DIR__ . '/includes/footer.php'; 
?>
