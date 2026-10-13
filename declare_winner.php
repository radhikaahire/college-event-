<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

require_role('organizer');

$page_title = "Declare Event Winner";
$uid = (int)$_SESSION['user_id'];
$error = "";

$selected_event = (int)($_GET['event_id'] ?? 0);

// Handle Delete / Revoke Winner via Secure POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_winner'])) {
    verify_csrf();
    $win_id = (int)($_POST['winner_id'] ?? 0);
    
    // Ownership check: Winner's event must be owned by this organizer
    $del_stmt = mysqli_prepare($conn, "
        DELETE w FROM winners w
        JOIN events e ON w.event_id = e.id
        WHERE w.id = ? AND e.created_by = ?
    ");
    if ($del_stmt) {
        mysqli_stmt_bind_param($del_stmt, "ii", $win_id, $uid);
        mysqli_stmt_execute($del_stmt);
        if (mysqli_stmt_affected_rows($del_stmt) > 0) {
            set_flash('success', "Winner designation revoked successfully.");
        }
        mysqli_stmt_close($del_stmt);
    }
    header("Location: declare_winner.php?event_id=" . $selected_event);
    exit;
}

// Handle Declare Winner via Secure POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['declare'])) {
    verify_csrf();

    $event_id   = (int)($_POST['event_id'] ?? 0);
    $student_id = (int)($_POST['student_id'] ?? 0);
    $position   = trim($_POST['position'] ?? '');

    // 1. Verify that this event belongs to the logged-in organizer
    $chk_ev = mysqli_prepare($conn, "SELECT id, title FROM events WHERE id = ? AND created_by = ?");
    mysqli_stmt_bind_param($chk_ev, "ii", $event_id, $uid);
    mysqli_stmt_execute($chk_ev);
    $ev_res = mysqli_stmt_get_result($chk_ev);
    $event_data = mysqli_fetch_assoc($ev_res);
    mysqli_stmt_close($chk_ev);

    if (!$event_data) {
        $error = "Permission denied or invalid event.";
    } elseif (!in_array($position, ['1st', '2nd', '3rd'], true)) {
        $error = "Invalid podium position selected.";
    } else {
        // 2. Check if this position has ALREADY been awarded for this event
        $chk_pos = mysqli_prepare($conn, "SELECT id FROM winners WHERE event_id = ? AND position = ?");
        mysqli_stmt_bind_param($chk_pos, "is", $event_id, $position);
        mysqli_stmt_execute($chk_pos);
        $pos_res = mysqli_stmt_get_result($chk_pos);
        $pos_exists = mysqli_num_rows($pos_res) > 0;
        mysqli_stmt_close($chk_pos);

        // 3. Check if this student is already a winner in this event
        $chk_stu = mysqli_prepare($conn, "SELECT position FROM winners WHERE event_id = ? AND student_id = ?");
        mysqli_stmt_bind_param($chk_stu, "ii", $event_id, $student_id);
        mysqli_stmt_execute($chk_stu);
        $stu_res = mysqli_stmt_get_result($chk_stu);
        $already_won = mysqli_fetch_assoc($stu_res);
        mysqli_stmt_close($chk_stu);

        if ($pos_exists) {
            $error = "The $position place for this event has already been declared. Revoke the existing winner first to reassign.";
        } elseif ($already_won) {
            $error = "This student has already been awarded " . $already_won['position'] . " place in this competition.";
        } else {
            // 4. Safe insert into winners table
            $ins_win = mysqli_prepare($conn, "INSERT INTO winners (event_id, student_id, position) VALUES (?, ?, ?)");
            mysqli_stmt_bind_param($ins_win, "iis", $event_id, $student_id, $position);
            if (mysqli_stmt_execute($ins_win)) {
                mysqli_stmt_close($ins_win);
                set_flash('success', "$position place winner declared successfully for {$event_data['title']}!");
                header("Location: declare_winner.php?event_id=" . $event_id);
                exit;
            } else {
                $error = "A database error occurred while recording the winner.";
                mysqli_stmt_close($ins_win);
            }
        }
    }
}

// Fetch only events created by THIS organizer
$stmt = mysqli_prepare($conn, "SELECT id, title, status, event_date FROM events WHERE created_by = ? ORDER BY event_date DESC");
mysqli_stmt_bind_param($stmt, "i", $uid);
mysqli_stmt_execute($stmt);
$my_events = mysqli_stmt_get_result($stmt);

// Fetch approved students for the selected event
$students = null;
$existing_winners = null;

if ($selected_event > 0) {
    // Approved students query
    $stu_stmt = mysqli_prepare($conn, "
        SELECT s.id, s.name, s.roll_no, s.department
        FROM event_participation ep
        JOIN students s ON ep.student_id = s.id
        JOIN events e ON ep.event_id = e.id
        WHERE ep.event_id = ? AND e.created_by = ? AND ep.approval_status = 'approved'
        ORDER BY s.name ASC
    ");
    mysqli_stmt_bind_param($stu_stmt, "ii", $selected_event, $uid);
    mysqli_stmt_execute($stu_stmt);
    $students = mysqli_stmt_get_result($stu_stmt);

    // Existing winners for this event query
    $win_stmt = mysqli_prepare($conn, "
        SELECT w.id, w.position, s.name, s.roll_no, s.department
        FROM winners w
        JOIN students s ON w.student_id = s.id
        WHERE w.event_id = ?
        ORDER BY FIELD(w.position, '1st', '2nd', '3rd')
    ");
    mysqli_stmt_bind_param($win_stmt, "i", $selected_event);
    mysqli_stmt_execute($win_stmt);
    $existing_winners = mysqli_stmt_get_result($win_stmt);
}

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>

<div class="container py-4">

    <!-- Header & Action -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h2 class="fw-bold tracking-tight text-dark mb-1">Declare Event Winners</h2>
            <p class="text-muted small mb-0">Record official 1st, 2nd, and 3rd place podium placements for your events</p>
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
        <!-- Event Selection & Form Column -->
        <div class="col-lg-7">
            <div class="cems-card mb-4">
                <form method="get" class="mb-4">
                    <label class="form-label fw-semibold text-muted small">Select Your Managed Event</label>
                    <select name="event_id" class="form-select" onchange="this.form.submit()">
                        <option value="0">-- Select an Event --</option>
                        <?php if ($my_events): ?>
                            <?php while ($e = mysqli_fetch_assoc($my_events)): ?>
                                <option value="<?php echo (int)$e['id']; ?>" <?php if ($selected_event === (int)$e['id']) echo 'selected'; ?>>
                                    <?php echo e($e['title']); ?> (<?php echo date("d M Y", strtotime($e['event_date'])); ?> &bull; <?php echo ucfirst(e($e['status'])); ?>)
                                </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </form>

                <?php if ($selected_event > 0): ?>
                    <?php if ($students && mysqli_num_rows($students) > 0): ?>
                        <h5 class="fw-bold text-dark mb-3">Award Podium Position</h5>
                        <form method="post" action="declare_winner.php?event_id=<?php echo $selected_event; ?>">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="event_id" value="<?php echo $selected_event; ?>">

                            <div class="mb-3">
                                <label class="form-label fw-semibold small text-muted">Eligible Participant (Approved) <span class="text-danger">*</span></label>
                                <select name="student_id" class="form-select" required>
                                    <option value="">-- Choose Approved Student --</option>
                                    <?php while ($s = mysqli_fetch_assoc($students)): ?>
                                        <option value="<?php echo (int)$s['id']; ?>">
                                            <?php echo e($s['name']); ?> (Roll: <?php echo e(!empty($s['roll_no']) ? $s['roll_no'] : 'N/A'); ?> &bull; <?php echo e($s['department'] ?? 'General'); ?>)
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-semibold small text-muted">Podium Placement <span class="text-danger">*</span></label>
                                <div class="row g-2">
                                    <div class="col-4">
                                        <input type="radio" class="btn-check" name="position" id="pos1" value="1st" checked>
                                        <label class="btn btn-outline-success w-100 py-2 fw-bold" for="pos1">
                                            🥇 1st Place
                                        </label>
                                    </div>
                                    <div class="col-4">
                                        <input type="radio" class="btn-check" name="position" id="pos2" value="2nd">
                                        <label class="btn btn-outline-warning text-dark w-100 py-2 fw-bold" for="pos2">
                                            🥈 2nd Place
                                        </label>
                                    </div>
                                    <div class="col-4">
                                        <input type="radio" class="btn-check" name="position" id="pos3" value="3rd">
                                        <label class="btn btn-outline-secondary w-100 py-2 fw-bold" for="pos3">
                                            🥉 3rd Place
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end">
                                <button type="submit" name="declare" class="btn btn-cems-primary px-4 d-inline-flex align-items-center gap-2">
                                    <i class="bi bi-award"></i> Declare Official Winner
                                </button>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="text-center py-4 text-muted">
                            <i class="bi bi-people fs-2 d-block mb-2"></i>
                            No approved participants found for this event.<br>
                            <a href="manage_participants.php?event_id=<?php echo $selected_event; ?>" class="btn btn-sm btn-outline-primary mt-2">
                                Review &amp; Approve Students First
                            </a>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-cursor-fill fs-2 d-block mb-2"></i>
                        Please select an event above to view participants and assign awards.
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Current Event Podium Column -->
        <div class="col-lg-5">
            <div class="cems-card h-100">
                <h5 class="fw-bold text-dark mb-3"><i class="bi bi-trophy-fill text-warning me-2"></i>Current Event Podium</h5>

                <?php if ($selected_event > 0 && $existing_winners && mysqli_num_rows($existing_winners) > 0): ?>
                    <div class="d-flex flex-column gap-3">
                        <?php while ($w = mysqli_fetch_assoc($existing_winners)): ?>
                            <?php
                            $rank_color = match($w['position']) {
                                '1st' => '#10b981',
                                '2nd' => '#f59e0b',
                                default => '#64748b'
                            };
                            $rank_icon = match($w['position']) {
                                '1st' => '🥇',
                                '2nd' => '🥈',
                                default => '🥉'
                            };
                            ?>
                            <div class="p-3 border rounded-3 d-flex align-items-center justify-content-between" style="border-left: 4px solid <?php echo $rank_color; ?> !important;">
                                <div class="d-flex align-items-center gap-3">
                                    <span class="fs-3"><?php echo $rank_icon; ?></span>
                                    <div>
                                        <div class="fw-bold text-dark"><?php echo e($w['name']); ?></div>
                                        <div class="text-muted small">
                                            Roll: <?php echo e(!empty($w['roll_no']) ? $w['roll_no'] : 'N/A'); ?> &bull; <?php echo e($w['department'] ?? 'General'); ?>
                                        </div>
                                    </div>
                                </div>
                                <form method="post" action="declare_winner.php?event_id=<?php echo $selected_event; ?>" onsubmit="return confirm('Revoke this winner award?');">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="winner_id" value="<?php echo (int)$w['id']; ?>">
                                    <button type="submit" name="delete_winner" class="btn btn-sm btn-outline-danger py-1 px-2" title="Revoke Winner">
                                        <i class="bi bi-trash3"></i>
                                    </button>
                                </form>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-trophy fs-1 opacity-25 d-block mb-2"></i>
                        No winners recorded yet for this event.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>

<?php 
mysqli_stmt_close($stmt);
if (isset($stu_stmt)) mysqli_stmt_close($stu_stmt);
if (isset($win_stmt)) mysqli_stmt_close($win_stmt);
include __DIR__ . '/includes/footer.php'; 
?>
