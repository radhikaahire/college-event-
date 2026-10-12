<?php
include "includes/auth_check.php";
include "includes/db.php";

if ($_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

/* GET STUDENT ID */
$stu = mysqli_query($conn, "SELECT id FROM students WHERE user_id='$user_id'");
if (mysqli_num_rows($stu) == 0) {
    die("Student profile not found. Please contact admin.");
}
$student = mysqli_fetch_assoc($stu);
$student_id = $student['id'];

/* PARTICIPATE */
if (isset($_GET['event_id'])) {
    $event_id = $_GET['event_id'];

    mysqli_query($conn,"
        INSERT IGNORE INTO event_participation (event_id, student_id)
        VALUES ('$event_id','$student_id')
    ");
}

/* FETCH EVENTS */
$events = mysqli_query($conn, "SELECT * FROM events WHERE status='open'");

include "includes/header.php";
include "includes/navbar.php";
?>

<div class="container mt-5">

<h2 class="fw-bold text-primary mb-4">Available Events</h2>

<div class="dashboard-card">

<?php if (mysqli_num_rows($events) == 0) { ?>
    <p class="text-muted text-center">No events available.</p>
<?php } else { ?>

<table class="table align-middle">
<thead class="table-light">
<tr>
    <th>Event</th>
    <th>Date</th>
    <th>Venue</th>
    <th>Status</th>
</tr>
</thead>
<tbody>

<?php while ($e = mysqli_fetch_assoc($events)) {

    // CHECK PARTICIPATION STATUS
    $check = mysqli_query($conn,"
        SELECT approval_status
        FROM event_participation
        WHERE event_id='{$e['id']}'
        AND student_id='$student_id'
    ");

    $status = null;
    if (mysqli_num_rows($check) > 0) {
        $row = mysqli_fetch_assoc($check);
        $status = $row['approval_status'];
    }
?>
<tr>
    <td class="fw-semibold"><?php echo $e['title']; ?></td>
    <td><?php echo date("d M Y", strtotime($e['event_date'])); ?></td>
    <td><?php echo $e['venue']; ?></td>

    <td>
    <?php if ($status === null) { ?>
        <a href="view_events.php?event_id=<?php echo $e['id']; ?>"
           class="btn btn-outline-primary btn-sm">
           Participate
        </a>

    <?php } elseif ($status === 'pending') { ?>
        <span class="badge bg-warning text-dark">Pending</span>

    <?php } elseif ($status === 'approved') { ?>
        <span class="badge bg-success">Approved</span>

    <?php } else { ?>
        <span class="badge bg-danger">Rejected</span>
    <?php } ?>
    </td>
</tr>

<?php } ?>

</tbody>
</table>

<?php } ?>

<div class="text-end">
  <a href="student_dashboard.php" class="btn btn-outline-secondary btn-sm">
    Back to Dashboard
  </a>
</div>

</div>
</div>

<?php include "includes/footer.php"; ?>
