<?php
include "includes/auth_check.php";
include "includes/db.php";

if ($_SESSION['role'] !== 'organizer') {
    header("Location: login.php");
    exit;
}

$uid = $_SESSION['user_id'];

/* APPROVE / REJECT */
if (isset($_GET['approve'])) {
    $id = $_GET['approve'];
    mysqli_query($conn,"
        UPDATE event_participation
        SET approval_status='approved'
        WHERE id='$id'
    ");
}

if (isset($_GET['reject'])) {
    $id = $_GET['reject'];
    mysqli_query($conn,"
        UPDATE event_participation
        SET approval_status='rejected'
        WHERE id='$id'
    ");
}

/* FETCH ONLY PENDING REQUESTS FOR THIS ORGANIZER */
$data = mysqli_query($conn,"
    SELECT ep.id, s.name AS student_name, e.title AS event_title, ep.applied_at
    FROM event_participation ep
    JOIN students s ON ep.student_id = s.id
    JOIN events e ON ep.event_id = e.id
    WHERE e.created_by = '$uid'
    AND ep.approval_status = 'pending'
    ORDER BY ep.applied_at DESC
");

include "includes/header.php";
include "includes/navbar.php";
?>

<div class="container mt-5">

<h2 class="fw-bold text-primary mb-4">Pending Requests</h2>

<div class="dashboard-card">

<?php if (mysqli_num_rows($data) == 0) { ?>
    <p class="text-muted text-center">No pending requests.</p>
<?php } else { ?>

<table class="table align-middle">
<thead class="table-light">
<tr>
    <th>Student</th>
    <th>Event</th>
    <th>Applied On</th>
    <th>Action</th>
</tr>
</thead>
<tbody>

<?php while ($row = mysqli_fetch_assoc($data)) { ?>
<tr>
    <td class="fw-semibold"><?php echo $row['student_name']; ?></td>
    <td><?php echo $row['event_title']; ?></td>
    <td><?php echo date("d M Y", strtotime($row['applied_at'])); ?></td>
    <td>
        <a href="?approve=<?php echo $row['id']; ?>"
           class="btn btn-success btn-sm">Approve</a>

        <a href="?reject=<?php echo $row['id']; ?>"
           class="btn btn-danger btn-sm ms-2">Reject</a>
    </td>
</tr>
<?php } ?>

</tbody>
</table>

<?php } ?>

<div class="text-end">
  <a href="organizer_dashboard.php" class="btn btn-outline-secondary btn-sm">
    Back to Dashboard
  </a>
</div>

</div>
</div>

<?php include "includes/footer.php"; ?>
