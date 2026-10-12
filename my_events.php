<?php
include "includes/auth_check.php";
include "includes/db.php";

if ($_SESSION['role'] !== 'organizer') {
    header("Location: login.php");
    exit;
}

$uid = $_SESSION['user_id'];

/* CLOSE EVENT */
if (isset($_GET['close'])) {
    $event_id = $_GET['close'];
    mysqli_query($conn,"
        UPDATE events
        SET status='closed'
        WHERE id='$event_id'
        AND created_by='$uid'
    ");
}

/* FETCH ALL EVENTS (OPEN + CLOSED + PAST) */
$result = mysqli_query($conn,"
    SELECT *,
           CASE
             WHEN status='closed' THEN 'Closed'
             WHEN event_date < CURDATE() THEN 'Completed'
             ELSE 'Open'
           END AS display_status
    FROM events
    WHERE created_by='$uid'
    ORDER BY event_date DESC
");

include "includes/header.php";
include "includes/navbar.php";
?>

<div class="container mt-5">

<h2 class="fw-bold text-primary mb-4">My Events</h2>

<div class="dashboard-card">

<?php if (mysqli_num_rows($result) == 0) { ?>
    <p class="text-muted text-center">
        No events found for this organizer.
    </p>
<?php } else { ?>

<table class="table align-middle">
<thead class="table-light">
<tr>
    <th>Event Title</th>
    <th>Date</th>
    <th>Venue</th>
    <th>Status</th>
    <th>Action</th>
</tr>
</thead>
<tbody>

<?php while ($row = mysqli_fetch_assoc($result)) { ?>
<tr>
    <td class="fw-semibold"><?php echo $row['title']; ?></td>
    <td><?php echo date("d M Y", strtotime($row['event_date'])); ?></td>
    <td><?php echo $row['venue']; ?></td>
    <td>
        <span class="badge
        <?php
            if ($row['display_status'] == 'Open') echo 'bg-success';
            else if ($row['display_status'] == 'Completed') echo 'bg-warning';
            else echo 'bg-secondary';
        ?>">
            <?php echo $row['display_status']; ?>
        </span>
    </td>
    <td>
        <?php if ($row['status'] == 'open') { ?>
            <a href="my_events.php?close=<?php echo $row['id']; ?>"
               class="btn btn-danger btn-sm"
               onclick="return confirm('Close this event?');">
               Close Event
            </a>
        <?php } else { ?>
            <span class="text-muted">No Action</span>
        <?php } ?>
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
