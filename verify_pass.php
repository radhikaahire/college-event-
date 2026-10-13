<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

$token = (string)($_GET['token'] ?? '');
$pass = null;

if (preg_match('/^[a-f0-9]{64}$/', $token)) {
    $stmt = mysqli_prepare($conn, "
        SELECT ep.approval_status, s.name AS student_name, s.roll_no,
               e.title AS event_title, e.event_date, e.venue
        FROM event_participation ep
        JOIN students s ON s.id = ep.student_id
        JOIN events e ON e.id = ep.event_id
        WHERE ep.qr_token = ?
        LIMIT 1
    ");
    mysqli_stmt_bind_param($stmt, "s", $token);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $pass = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
}

$is_valid = $pass && $pass['approval_status'] === 'approved';
http_response_code($is_valid ? 200 : 404);
$page_title = $is_valid ? 'Verified Event Pass' : 'Invalid Event Pass';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo e($page_title); ?> | CEMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <main class="container py-5" style="max-width: 720px;">
        <section class="card border-0 shadow-sm">
            <div class="card-body p-4 p-md-5 text-center">
                <?php if ($is_valid): ?>
                    <div class="text-success display-4 mb-3" aria-hidden="true">&#10003;</div>
                    <h1 class="h3 text-success mb-2">Valid Event Pass</h1>
                    <p class="text-muted">This pass matches an approved registration in CEMS.</p>
                    <hr>
                    <dl class="row text-start mb-0">
                        <dt class="col-sm-4">Participant</dt>
                        <dd class="col-sm-8"><?php echo e($pass['student_name']); ?></dd>
                        <dt class="col-sm-4">Roll number</dt>
                        <dd class="col-sm-8"><?php echo e($pass['roll_no'] ?: 'N/A'); ?></dd>
                        <dt class="col-sm-4">Event</dt>
                        <dd class="col-sm-8"><?php echo e($pass['event_title']); ?></dd>
                        <dt class="col-sm-4">Date</dt>
                        <dd class="col-sm-8"><?php echo e(date('d M Y', strtotime($pass['event_date']))); ?></dd>
                        <dt class="col-sm-4">Venue</dt>
                        <dd class="col-sm-8"><?php echo e($pass['venue'] ?: 'To be announced'); ?></dd>
                    </dl>
                <?php else: ?>
                    <div class="text-danger display-4 mb-3" aria-hidden="true">&#10007;</div>
                    <h1 class="h3 text-danger mb-2">Pass Not Valid</h1>
                    <p class="mb-0">This token is invalid, unknown, or does not belong to an approved registration.</p>
                <?php endif; ?>
            </div>
        </section>
    </main>
</body>
</html>
