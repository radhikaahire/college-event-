<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

$page_title = "Digital Event Pass";
$uid = (int)$_SESSION['user_id'];
$role = $_SESSION['role'];

$reg_id = (int)($_GET['reg_id'] ?? 0);
if ($reg_id <= 0) {
    set_flash('danger', "Invalid registration ID specified.");
    header("Location: index.php");
    exit;
}

// Fetch registration details with event and student info
$stmt = mysqli_prepare($conn, "
    SELECT ep.id AS reg_id, ep.approval_status, ep.applied_at,
           e.id AS event_id, e.title AS event_title, e.description, e.event_date, e.venue, e.created_by,
           s.id AS student_id, s.user_id AS student_user_id, s.name AS student_name, s.roll_no, s.department,
           u.username
    FROM event_participation ep
    JOIN events e ON ep.event_id = e.id
    JOIN students s ON ep.student_id = s.id
    JOIN users u ON s.user_id = u.id
    WHERE ep.id = ?
");
mysqli_stmt_bind_param($stmt, "i", $reg_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$ticket = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if (!$ticket) {
    set_flash('danger', "Event pass not found.");
    header("Location: index.php");
    exit;
}

// Strict Authorization / IDOR defense:
// Only the student themselves, the event organizer, or an admin may view this ticket
if ($role === 'student' && (int)$ticket['student_user_id'] !== $uid) {
    set_flash('danger', "Access denied. You can only view your own event passes.");
    header("Location: student_dashboard.php");
    exit;
} elseif ($role === 'organizer' && (int)$ticket['created_by'] !== $uid) {
    set_flash('danger', "Access denied. You do not manage this event.");
    header("Location: organizer_dashboard.php");
    exit;
}

// Ticket validity check
if ($ticket['approval_status'] !== 'approved') {
    set_flash('warning', "This event registration is currently " . strtoupper($ticket['approval_status']) . ". Digital passes are only issued for approved participants.");
    if ($role === 'student') {
        header("Location: student_dashboard.php");
    } else {
        header("Location: manage_participants.php?event_id=" . $ticket['event_id']);
    }
    exit;
}

// Verification token hash
$verify_hash = strtoupper(substr(hash('sha256', "CEMS-PASS-{$ticket['reg_id']}-{$ticket['student_id']}-{$ticket['event_id']}"), 0, 10));

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>

<div class="container py-5">
    <!-- Non-printable Toolbar -->
    <div class="d-print-none d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 mb-4">
        <div>
            <h2 class="fw-bold tracking-tight text-dark mb-1">Official E-Ticket Pass</h2>
            <p class="text-muted small mb-0">Present this digital pass or physical printout at the event venue check-in desk</p>
        </div>
        <div class="d-flex gap-2">
            <?php if ($role === 'student'): ?>
                <a href="student_dashboard.php" class="btn btn-sm btn-cems-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Dashboard
                </a>
            <?php else: ?>
                <a href="manage_participants.php?event_id=<?php echo $ticket['event_id']; ?>" class="btn btn-sm btn-cems-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Back to Event
                </a>
            <?php endif; ?>
            <button onclick="window.print()" class="btn btn-sm btn-cems-primary d-inline-flex align-items-center gap-2">
                <i class="bi bi-printer"></i> Print / Save Pass
            </button>
        </div>
    </div>

    <!-- Ticket Pass Container -->
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="ticket-card shadow-lg bg-white rounded-4 overflow-hidden border border-2 border-primary border-opacity-25">
                
                <!-- Ticket Header Banner -->
                <div class="ticket-header p-4 text-white" style="background: linear-gradient(135deg, #4338ca 0%, #312e81 100%);">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="badge bg-warning text-dark fw-bold text-uppercase px-3 py-1 mb-2">Official Delegate Pass</span>
                            <h3 class="fw-black mb-0 tracking-tight text-white"><?php echo e($ticket['event_title']); ?></h3>
                            <p class="mb-0 text-white-50 small mt-1">College Event Management System &bull; Annual Festival</p>
                        </div>
                        <div class="text-end d-none d-sm-block">
                            <span class="badge bg-success bg-opacity-75 text-white fs-6 px-3 py-2">
                                <i class="bi bi-check2-circle me-1"></i> CONFIRMED
                            </span>
                            <div class="text-white-50 small mt-1 font-monospace">REF: #CEMS-<?php echo str_pad($ticket['reg_id'], 5, '0', STR_PAD_LEFT); ?></div>
                        </div>
                    </div>
                </div>

                <!-- Perforated Divider Visual -->
                <div class="ticket-divider position-relative bg-light py-2 border-top border-bottom border-dashed border-secondary">
                    <div class="d-flex justify-content-between align-items-center px-4">
                        <span class="small font-monospace text-muted text-uppercase tracking-wider">ADMIT ONE &bull; ENTRY PASS</span>
                        <span class="small font-monospace text-success fw-bold"><i class="bi bi-shield-check me-1"></i>VERIFIED ENROLLMENT</span>
                    </div>
                </div>

                <!-- Ticket Body -->
                <div class="ticket-body p-4 p-md-5">
                    <div class="row g-4 align-items-center">
                        <!-- Delegate & Schedule Details -->
                        <div class="col-md-7">
                            <h6 class="text-uppercase tracking-wider text-muted fw-bold small mb-3">Participant Credentials</h6>
                            <div class="mb-3">
                                <div class="text-muted small">Participant Name</div>
                                <div class="fs-4 fw-bold text-dark"><?php echo e($ticket['student_name']); ?></div>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-6">
                                    <div class="text-muted small">Department</div>
                                    <div class="fw-semibold text-dark"><?php echo e($ticket['department'] ?? 'General'); ?></div>
                                </div>
                                <div class="col-6">
                                    <div class="text-muted small">Roll Number</div>
                                    <div class="fw-semibold text-dark font-monospace"><?php echo e(!empty($ticket['roll_no']) ? $ticket['roll_no'] : 'N/A'); ?></div>
                                </div>
                            </div>

                            <hr class="text-muted opacity-25">

                            <h6 class="text-uppercase tracking-wider text-muted fw-bold small mb-3">Event Schedule &amp; Venue</h6>
                            <div class="row g-3">
                                <div class="col-6">
                                    <div class="text-muted small"><i class="bi bi-calendar3 me-1"></i> Date</div>
                                    <div class="fw-bold text-dark"><?php echo date("l, d M Y", strtotime($ticket['event_date'])); ?></div>
                                </div>
                                <div class="col-6">
                                    <div class="text-muted small"><i class="bi bi-geo-alt me-1 text-danger"></i> Venue</div>
                                    <div class="fw-bold text-dark"><?php echo e(!empty($ticket['venue']) ? $ticket['venue'] : 'Campus Auditorium'); ?></div>
                                </div>
                            </div>
                        </div>

                        <!-- QR Code Verification Column -->
                        <div class="col-md-5 text-center border-start-md ps-md-4">
                            <div class="p-3 bg-light rounded-3 border d-inline-block shadow-sm mb-2">
                                <div id="ticket-qrcode" class="d-flex justify-content-center"></div>
                            </div>
                            <div class="font-monospace small fw-bold text-dark mt-2">
                                CODE: <?php echo $verify_hash; ?>
                            </div>
                            <p class="text-muted xsmall mb-0 mt-1" style="font-size: 0.75rem;">
                                Scan at entrance to verify registration authenticity and record attendance.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Ticket Footer Bar -->
                <div class="ticket-footer bg-light px-4 py-3 border-top d-flex justify-content-between align-items-center">
                    <div class="text-muted xsmall" style="font-size:0.8rem;">
                        Issued on <?php echo date("d M Y, h:i A", strtotime($ticket['applied_at'])); ?> &bull; Non-transferable
                    </div>
                    <div class="font-monospace small text-primary fw-bold">
                        CEMS FESTIVAL GATE PASS
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- Offline QR Code Generator Script -->
<script src="assets/qrcode.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    var qrData = JSON.stringify({
        cems_ref: "CEMS-<?php echo $ticket['reg_id']; ?>",
        student: "<?php echo addslashes($ticket['student_name']); ?>",
        roll: "<?php echo addslashes($ticket['roll_no']); ?>",
        event: "<?php echo addslashes($ticket['event_title']); ?>",
        date: "<?php echo $ticket['event_date']; ?>",
        auth_hash: "<?php echo $verify_hash; ?>"
    });

    new QRCode(document.getElementById("ticket-qrcode"), {
        text: qrData,
        width: 140,
        height: 140,
        colorDark : "#1e1b4b",
        colorLight : "#ffffff",
        correctLevel : QRCode.CorrectLevel.M
    });
});
</script>

<style>
@media print {
    body {
        background: #fff !important;
        color: #000 !important;
    }
    .ticket-card {
        box-shadow: none !important;
        border: 2px solid #000 !important;
        page-break-inside: avoid;
    }
    .ticket-header {
        background: #312e81 !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
}
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>
