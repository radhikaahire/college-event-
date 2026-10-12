<?php
include "includes/auth_check.php";
include "includes/db.php";

if ($_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit;
}

include "includes/header.php";
include "includes/navbar.php";

/* Winners */
$wins = mysqli_query($conn,"
    SELECT w.position, s.name, e.title
    FROM winners w
    JOIN students s ON w.student_id=s.id
    JOIN events e ON w.event_id=e.id
    ORDER BY e.event_date DESC
");

/* Announcements */
$ann = mysqli_query($conn,"
    SELECT a.message, e.title
    FROM announcements a
    JOIN events e ON a.event_id=e.id
    ORDER BY a.created_at DESC
");
?>

<div class="container mt-5">

<h2 class="fw-bold text-primary mb-4">Student Dashboard</h2>

<!-- ACTION CARDS -->
<div class="row g-4 mb-4">

  <div class="col-md-4">
    <a href="view_events.php" class="text-decoration-none">
      <div class="action-card">
        <i class="bi bi-calendar-check fs-1 text-primary"></i>
        <h6 class="mt-2">View Events</h6>
        <p class="text-muted small">Participate in events</p>
      </div>
    </a>
  </div>

  <!-- WINNERS -->
  <div class="col-md-4">
    <a href="winners.php" class="text-decoration-none">
      <div class="action-card">
        <i class="bi bi-trophy fs-1 text-warning"></i>
        <h6 class="mt-2">Winners</h6>
        <p class="text-muted small">View event results</p>
      </div>
    </a>
  </div>

  <!-- ANNOUNCEMENTS -->
  <div class="col-md-4">
    <a href="announcements.php" class="text-decoration-none">
      <div class="action-card">
        <i class="bi bi-megaphone fs-1 text-info"></i>
        <h6 class="mt-2">Announcements</h6>
        <p class="text-muted small">Latest updates</p>
      </div>
    </a>
  </div>

</div>

<!-- WINNERS SECTION -->
<div class="dashboard-card mb-4">

<h5 class="fw-bold mb-3">🏆 Winners</h5>

<?php if (mysqli_num_rows($wins) == 0) { ?>
  <p class="text-muted">No winners announced yet.</p>
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

  <?php while($w = mysqli_fetch_assoc($wins)) { ?>
    <tr>
      <td class="fw-semibold"><?php echo $w['title']; ?></td>
      <td>
        <span class="badge 
          <?php
            if ($w['position']=='1st') echo 'bg-success';
            else if ($w['position']=='2nd') echo 'bg-warning';
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

</div>

<!-- ANNOUNCEMENTS SECTION -->
<div class="dashboard-card">

<h5 class="fw-bold mb-3">📢 Announcements</h5>

<?php if (mysqli_num_rows($ann) == 0) { ?>
  <p class="text-muted">No announcements available.</p>
<?php } else { ?>

<ul class="list-group list-group-flush">
<?php while($a = mysqli_fetch_assoc($ann)) { ?>
  <li class="list-group-item">
    <span class="fw-semibold text-primary"><?php echo $a['title']; ?></span><br>
    <span class="text-muted"><?php echo $a['message']; ?></span>
  </li>
<?php } ?>
</ul>

<?php } ?>

</div>

</div>

<?php include "includes/footer.php"; ?>
