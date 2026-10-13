<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

$page_title = "Certificate of Achievement";
$uid = (int)$_SESSION['user_id'];
$role = $_SESSION['role'];

$reg_id = (int)($_GET['reg_id'] ?? 0);
$winner_id = (int)($_GET['winner_id'] ?? 0);

$cert_data = null;
$cert_type = "PARTICIPATION";
$position = "";

if ($winner_id > 0) {
    // Lookup by winner id
    $stmt = mysqli_prepare($conn, "
        SELECT w.id AS win_id, w.position,
               e.id AS event_id, e.title AS event_title, e.event_date, e.venue, e.created_by,
               s.id AS student_id, s.user_id AS student_user_id, s.name AS student_name, s.roll_no, s.department
        FROM winners w
        JOIN events e ON w.event_id = e.id
        JOIN students s ON w.student_id = s.id
        WHERE w.id = ?
    ");
    mysqli_stmt_bind_param($stmt, "i", $winner_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $cert_data = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);

    if ($cert_data) {
        $cert_type = "MERIT";
        $position = $cert_data['position'];
    }
} elseif ($reg_id > 0) {
    // Lookup by registration id
    $stmt = mysqli_prepare($conn, "
        SELECT ep.id AS reg_id, ep.approval_status, ep.applied_at,
               e.id AS event_id, e.title AS event_title, e.event_date, e.venue, e.created_by,
               s.id AS student_id, s.user_id AS student_user_id, s.name AS student_name, s.roll_no, s.department,
               w.position
        FROM event_participation ep
        JOIN events e ON ep.event_id = e.id
        JOIN students s ON ep.student_id = s.id
        LEFT JOIN winners w ON (w.event_id = e.id AND w.student_id = s.id)
        WHERE ep.id = ?
    ");
    mysqli_stmt_bind_param($stmt, "i", $reg_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $cert_data = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);

    if ($cert_data && !empty($cert_data['position'])) {
        $cert_type = "MERIT";
        $position = $cert_data['position'];
    }
}

if (!$cert_data) {
    set_flash('danger', "Certificate record not found.");
    header("Location: index.php");
    exit;
}

// Strict Authorization / IDOR defense:
if ($role === 'student' && (int)$cert_data['student_user_id'] !== $uid) {
    set_flash('danger', "Access denied. You can only view your own certificates.");
    header("Location: student_dashboard.php");
    exit;
} elseif ($role === 'organizer' && (int)$cert_data['created_by'] !== $uid) {
    set_flash('danger', "Access denied. You do not manage this event.");
    header("Location: organizer_dashboard.php");
    exit;
}

// If derived from participation, must be approved
if (isset($cert_data['approval_status']) && $cert_data['approval_status'] !== 'approved') {
    set_flash('warning', "Certificates are only issued for confirmed participants.");
    header("Location: student_dashboard.php");
    exit;
}

$cert_code = "CEMS-CERT-" . date("Y", strtotime($cert_data['event_date'])) . "-" . str_pad($cert_data['student_id'] * 100 + $cert_data['event_id'], 6, '0', STR_PAD_LEFT);

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>

<div class="container py-4">
    <!-- Non-printable Toolbar -->
    <div class="d-print-none d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 mb-4">
        <div>
            <h2 class="fw-bold tracking-tight text-dark mb-1">Official E-Certificate</h2>
            <p class="text-muted small mb-0">Verified academic certificate of merit and campus participation</p>
        </div>
        <div class="d-flex gap-2">
            <?php if ($role === 'student'): ?>
                <a href="student_dashboard.php" class="btn btn-sm btn-cems-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Dashboard
                </a>
            <?php else: ?>
                <a href="winners.php" class="btn btn-sm btn-cems-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Winners Hall
                </a>
            <?php endif; ?>
            <button onclick="window.print()" class="btn btn-sm btn-cems-primary d-inline-flex align-items-center gap-2">
                <i class="bi bi-printer-fill"></i> Print / Download PDF
            </button>
        </div>
    </div>

    <!-- Certificate Container -->
    <div class="certificate-wrapper mx-auto">
        <div class="certificate-frame p-4 p-md-5 bg-white text-center shadow-lg position-relative">
            
            <!-- Ornate Inner Border -->
            <div class="certificate-inner p-4 p-md-5">
                
                <!-- College Emblem & Institution Header -->
                <div class="mb-3">
                    <div class="d-inline-flex align-items-center justify-content-center bg-primary bg-opacity-10 rounded-circle p-3 mb-2">
                        <i class="bi bi-mortarboard-fill text-primary fs-1"></i>
                    </div>
                    <div class="text-uppercase fw-bold text-muted small tracking-widest" style="letter-spacing: 0.25em;">
                        College Event Management System
                    </div>
                    <h5 class="fw-black text-dark text-uppercase mb-0 mt-1" style="letter-spacing: 0.1em;">
                        Department of Academic &amp; Student Affairs
                    </h5>
                </div>

                <!-- Certificate Title -->
                <div class="my-4">
                    <h1 class="display-5 fw-bold text-uppercase tracking-tight" style="color: #1e1b4b; font-family: 'Playfair Display', serif, Georgia;">
                        Certificate of <?php echo ($cert_type === 'MERIT') ? 'Merit' : 'Participation'; ?>
                    </h1>
                    <div class="mx-auto bg-warning" style="height: 3px; width: 120px; border-radius: 2px;"></div>
                </div>

                <!-- Award Copy -->
                <p class="lead text-muted mb-2 fst-italic">This certificate is proudly presented to</p>
                
                <h2 class="display-6 fw-bold text-dark text-capitalize mb-2" style="font-family: 'Playfair Display', serif, Georgia; text-decoration: underline; text-decoration-color: #f59e0b; text-underline-offset: 8px;">
                    <?php echo e($cert_data['student_name']); ?>
                </h2>
                
                <p class="text-muted mb-4">
                    Roll No: <strong class="text-dark font-monospace"><?php echo e(!empty($cert_data['roll_no']) ? $cert_data['roll_no'] : 'N/A'); ?></strong>
                    &bull; Department of <strong class="text-dark"><?php echo e($cert_data['department'] ?? 'General Engineering'); ?></strong>
                </p>

                <!-- Achievement Description -->
                <div class="col-lg-9 mx-auto mb-4">
                    <p class="fs-6 text-secondary lh-base mb-0">
                        <?php if ($cert_type === 'MERIT'): ?>
                            For securing <span class="badge bg-warning text-dark fs-6 px-3 py-1 fw-bold"><?php echo e($position); ?> Place</span> 
                            in the campus competition <strong class="text-dark"><?php echo e($cert_data['event_title']); ?></strong>, 
                            exhibiting commendable talent, dedication, and sportsmanship.
                        <?php else: ?>
                            In recognition of active participation and enthusiasm shown in the campus event 
                            <strong class="text-dark"><?php echo e($cert_data['event_title']); ?></strong> organized by the College Event Management System.
                        <?php endif; ?>
                    </p>
                </div>

                <!-- Date & Venue line -->
                <p class="small text-muted mb-5">
                    Conducted on <strong class="text-dark"><?php echo date("F d, Y", strtotime($cert_data['event_date'])); ?></strong> 
                    at <strong class="text-dark"><?php echo e(!empty($cert_data['venue']) ? $cert_data['venue'] : 'Campus Auditorium'); ?></strong>.
                </p>

                <!-- Signatures Row -->
                <div class="row align-items-end pt-3 g-4">
                    <div class="col-4">
                        <div class="border-bottom border-dark border-2 pb-1 mx-3">
                            <span class="font-monospace small text-muted fst-italic">Prof. Coordinator</span>
                        </div>
                        <div class="small fw-bold text-dark mt-1">Faculty Coordinator</div>
                        <div class="xsmall text-muted" style="font-size:0.75rem;">Event Committee</div>
                    </div>
                    
                    <div class="col-4">
                        <div class="d-inline-flex align-items-center justify-content-center p-2 rounded-circle border border-2 border-warning bg-warning bg-opacity-10 shadow-sm">
                            <i class="bi bi-patch-check-fill text-warning fs-2"></i>
                        </div>
                        <div class="xsmall font-monospace text-muted mt-1" style="font-size:0.75rem;">
                            ID: <?php echo $cert_code; ?>
                        </div>
                    </div>

                    <div class="col-4">
                        <div class="border-bottom border-dark border-2 pb-1 mx-3">
                            <span class="font-monospace small text-muted fst-italic">Dr. Principal</span>
                        </div>
                        <div class="small fw-bold text-dark mt-1">Dean / Principal</div>
                        <div class="xsmall text-muted" style="font-size:0.75rem;">Head of Institution</div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<style>
.certificate-wrapper {
    max-width: 900px;
}
.certificate-frame {
    border: 10px solid #1e1b4b;
    border-radius: 8px;
    background-color: #ffffff;
}
.certificate-inner {
    border: 2px dashed #f59e0b;
    border-radius: 4px;
}
@media print {
    body {
        background: #fff !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    .d-print-none {
        display: none !important;
    }
    .certificate-wrapper {
        max-width: 100% !important;
        width: 100% !important;
        margin: 0 !important;
    }
    .certificate-frame {
        border: 8px solid #1e1b4b !important;
        box-shadow: none !important;
        page-break-inside: avoid;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
    @page {
        size: landscape;
        margin: 10mm;
    }
}
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>
