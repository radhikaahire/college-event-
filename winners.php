<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

$page_title = "Hall of Fame & Winners";

$search = trim($_GET['q'] ?? '');

$sql = "
    SELECT w.id AS winner_id, w.position, s.name AS student_name, s.roll_no, s.department,
           e.title AS event_title, e.event_date, e.venue
    FROM winners w
    JOIN students s ON w.student_id = s.id
    JOIN events e ON w.event_id = e.id
    WHERE 1=1
";
$params = [];
$types = "";

if (!empty($search)) {
    $sql .= " AND (s.name LIKE ? OR e.title LIKE ? OR s.department LIKE ?)";
    $like = "%" . $search . "%";
    $types .= "sss";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$sql .= " ORDER BY e.event_date DESC, FIELD(w.position, '1st', '2nd', '3rd')";

$stmt = mysqli_prepare($conn, $sql);
if (!empty($types)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$winners_res = mysqli_stmt_get_result($stmt);

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>

<div class="container py-4">

    <!-- Header & Search -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h2 class="fw-bold tracking-tight text-dark mb-1">
                <i class="bi bi-trophy-fill text-warning me-2"></i>Campus Hall of Fame
            </h2>
            <p class="text-muted small mb-0">Official podium finishers and competition winners across departments</p>
        </div>

        <form method="get" class="d-flex gap-2">
            <div class="input-group">
                <span class="input-group-text bg-white text-muted border-end-0"><i class="bi bi-search"></i></span>
                <input type="text" name="q" class="form-control border-start-0 ps-0" 
                       placeholder="Search student, event, dept..." value="<?php echo e($search); ?>">
            </div>
            <button type="submit" class="btn btn-cems-primary px-3">Search</button>
            <?php if (!empty($search)): ?>
                <a href="winners.php" class="btn btn-outline-secondary">Reset</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Winners Roster Card -->
    <div class="cems-card">
        <div class="table-responsive">
            <table class="table-cems">
                <thead>
                    <tr>
                        <th style="width: 130px;">Podium Rank</th>
                        <th>Winner Name</th>
                        <th>Department &amp; Roll</th>
                        <th>Competition Event</th>
                        <th>Event Date</th>
                        <th class="text-end">Certificate</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($winners_res && mysqli_num_rows($winners_res) > 0): ?>
                        <?php while ($w = mysqli_fetch_assoc($winners_res)): ?>
                            <?php
                            $medal = match($w['position']) {
                                '1st' => '🥇 1st Place (Gold)',
                                '2nd' => '🥈 2nd Place (Silver)',
                                default => '🥉 3rd Place (Bronze)'
                            };
                            $badge_bg = match($w['position']) {
                                '1st' => 'bg-success-subtle text-success border border-success-subtle',
                                '2nd' => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
                                default => 'bg-secondary-subtle text-secondary border'
                            };
                            ?>
                            <tr>
                                <td>
                                    <span class="badge <?php echo $badge_bg; ?> fw-bold px-2 py-1">
                                        <?php echo $medal; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark fs-6"><?php echo e($w['student_name']); ?></div>
                                </td>
                                <td>
                                    <div class="small fw-semibold"><?php echo e(!empty($w['department']) ? $w['department'] : 'General'); ?></div>
                                    <div class="text-muted small">Roll: <?php echo e(!empty($w['roll_no']) ? $w['roll_no'] : 'N/A'); ?></div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border"><?php echo e($w['event_title']); ?></span>
                                </td>
                                <td class="text-muted small">
                                    <?php echo date("d M Y", strtotime($w['event_date'])); ?>
                                </td>
                                <td class="text-end">
                                    <a href="certificate.php?winner_id=<?php echo (int)$w['winner_id']; ?>" target="_blank" class="btn btn-sm btn-outline-warning text-dark fw-semibold py-1 px-2" title="View Official Certificate of Merit">
                                        <i class="bi bi-award-fill text-warning me-1"></i> Certificate
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-5">
                                <i class="bi bi-trophy fs-1 opacity-25 d-block mb-2"></i>
                                No winner records found matching your query.
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
