<?php
include "includes/auth_check.php";
include "includes/db.php";

if ($_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit;
}

$wins = mysqli_query($conn,"
    SELECT w.position, s.name, e.title
    FROM winners w
    JOIN students s ON w.student_id = s.id
    JOIN events e ON w.event_id = e.id
    ORDER BY e.event_date DESC
");

include "includes/header.php";
include "includes/navbar.php";
?>

<div class="container mt-5">

<h2 class="fw-bold text-primary mb-4">🏆 Winners</h2>

<div class="dashboard-card">

<?php if (mysqli_num_rows($wins) == 0) { ?>
    <p class="text-muted text-center">No winners announced yet.</p>
<?php } else { ?>

<table class="table align-middle">
<thead class="table-light">
<tr>
    <th>Event</th>
    <th>Position</th>
    <th>Student</th>
</tr>
</thead>
<tbody>

<?php while ($w = mysqli_fetch_assoc($wins)) { ?>
<tr>
    <td class="fw-semibold"><?php echo $w['title']; ?></td>
    <td>
        <span class="badge
        <?php
            if ($w['position'] == '1st') echo 'bg-success';
            else if ($w['position'] == '2nd') echo 'bg-warning';
            else echo 'bg-secondary';
        ?>">
        <?php echo $w['position']; ?>
        </span>
    </td>
    <td><?php echo $w['name']; ?></td>
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
