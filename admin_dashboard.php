<?php
include "includes/auth_check.php";
include "includes/db.php";

if ($_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

/* METRICS */
$total_users = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) AS c FROM users")
)['c'];

$total_students = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) AS c FROM users WHERE role='student'")
)['c'];

$total_organizers = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) AS c FROM users WHERE role='organizer'")
)['c'];

$total_events = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) AS c FROM events")
)['c'];

$open_events = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) AS c FROM events WHERE status='open'")
)['c'];

$participants = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) AS c FROM event_participation")
)['c'];

include "includes/header.php";
include "includes/navbar.php";
?>

<div class="container mt-4">
    <h3 class="mb-4">Admin Dashboard</h3>

    <div class="row g-4">

        <div class="col-md-4">
            <div class="card shadow-sm p-4 text-center">
                <h2><?php echo $total_users; ?></h2>
                <p class="text-muted">Total Users</p>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm p-4 text-center">
                <h2><?php echo $total_students; ?></h2>
                <p class="text-muted">Students</p>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm p-4 text-center">
                <h2><?php echo $total_organizers; ?></h2>
                <p class="text-muted">Organizers</p>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm p-4 text-center">
                <h2><?php echo $total_events; ?></h2>
                <p class="text-muted">Total Events</p>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm p-4 text-center">
                <h2><?php echo $open_events; ?></h2>
                <p class="text-muted">Open Events</p>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm p-4 text-center">
                <h2><?php echo $participants; ?></h2>
                <p class="text-muted">Total Participations</p>
            </div>
        </div>
        <div class="col-md-4 mt-4">
  <a href="add_student.php" class="text-decoration-none">
    <div class="action-card">
      <i class="bi bi-person-plus fs-1 text-primary"></i>
      <h6 class="mt-2">Add Student</h6>
    </div>
  </a>
</div>


<div class="col-md-4 mt-4">
  <a href="add_organizer.php" class="text-decoration-none">
    <div class="action-card">
      <i class="bi bi-person-badge fs-1 text-success"></i>
      <h6 class="mt-2">Add Organizer</h6>
    </div>
  </a>
</div>



    </div>
</div>

<?php include "includes/footer.php"; ?>
