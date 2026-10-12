<?php
include "includes/auth_check.php";
include "includes/db.php";

if ($_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit;
}

$ann = mysqli_query($conn,"
    SELECT a.message, a.created_at, e.title
    FROM announcements a
    JOIN events e ON a.event_id = e.id
    ORDER BY a.created_at DESC
");

include "includes/header.php";
include "includes/navbar.php";
?>

<div class="container mt-5">

<h2 class="fw-bold text-primary mb-4">📢 Announcements</h2>

<div class="dashboard-card">

<?php if (mysqli_num_rows($ann) == 0) { ?>
    <p class="text-muted text-center">No announcements available.</p>
<?php } else { ?>

<ul class="list-group list-group-flush">

<?php while ($a = mysqli_fetch_assoc($ann)) { ?>
<li class="list-group-item">
    <div class="fw-semibold text-primary"><?php echo $a['title']; ?></div>
    <div class="text-muted small mb-1">
        <?php echo date("d M Y", strtotime($a['created_at'])); ?>
    </div>
    <div><?php echo $a['message']; ?></div>
</li>
<?php } ?>

</ul>

<?php } ?>

<div class="text-end mt-3">
    <a href="student_dashboard.php" class="btn btn-outline-secondary btn-sm">
        Back to Dashboard
    </a>
</div>

</div>
</div>

<?php include "includes/footer.php"; ?>
