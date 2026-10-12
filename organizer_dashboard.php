<?php
include "includes/auth_check.php";
include "includes/db.php";

if($_SESSION['role']!=='organizer'){
  header("Location:login.php"); exit;
}

$uid=$_SESSION['user_id'];

$events = mysqli_fetch_assoc(mysqli_query($conn,
"SELECT COUNT(*) c FROM events WHERE created_by='$uid'"))['c'];

$pending = mysqli_fetch_assoc(mysqli_query($conn,
"SELECT COUNT(*) c FROM event_participation ep
 JOIN events e ON ep.event_id=e.id
 WHERE e.created_by='$uid' AND ep.approval_status='pending'"))['c'];

include "includes/header.php";
include "includes/navbar.php";
?>

<div class="container mt-5">

<h2 class="fw-bold mb-4 text-primary">Organizer Dashboard</h2>

<div class="row g-4 mb-4">
  <div class="col-md-6">
  <a href="my_events.php" class="text-decoration-none text-dark">
    <div class="metric-card">
      <i class="bi bi-calendar-event fs-1 text-primary"></i>
      <h1 class="fw-bold"><?php echo $events; ?></h1>
      <p class="text-muted">My Events</p>
    </div>
  </a>
</div>


  <div class="col-md-6">
  <a href="pending_requests.php" class="text-decoration-none text-dark">
    <div class="metric-card">
      <i class="bi bi-hourglass-split fs-1 text-warning"></i>
      <h1 class="fw-bold"><?php echo $pending; ?></h1>
      <p class="text-muted">Pending Requests</p>
    </div>
  </a>
</div>

</div>

<div class="row g-4">
  <div class="col-md-3">
    <a href="create_event.php" class="text-decoration-none">
      <div class="action-card">
        <i class="bi bi-plus-circle fs-1 text-primary"></i>
        <h6 class="mt-2">Create Event</h6>
      </div>
    </a>
  </div>

  <div class="col-md-3">
    <a href="manage_participants.php" class="text-decoration-none">
      <div class="action-card">
        <i class="bi bi-people fs-1 text-success"></i>
        <h6 class="mt-2">Approve Students</h6>
      </div>
    </a>
  </div>

  <div class="col-md-3">
    <a href="declare_winner.php" class="text-decoration-none">
      <div class="action-card">
        <i class="bi bi-trophy fs-1 text-warning"></i>
        <h6 class="mt-2">Declare Winner</h6>
      </div>
    </a>
  </div>

  <div class="col-md-3">
    <a href="post_announcement.php" class="text-decoration-none">
      <div class="action-card">
        <i class="bi bi-megaphone fs-1 text-info"></i>
        <h6 class="mt-2">Announcement</h6>
      </div>
    </a>
  </div>
</div>

</div>

<?php include "includes/footer.php"; ?>
