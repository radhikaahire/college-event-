<?php
include "includes/auth_check.php";
include "includes/db.php";

if ($_SESSION['role'] !== 'organizer') {
    header("Location: login.php");
    exit;
}

$msg = "";

if (isset($_POST['create'])) {
    $title = $_POST['title'];
    $desc  = $_POST['description'];
    $date  = $_POST['event_date'];
    $venue = $_POST['venue'];
    $uid   = $_SESSION['user_id'];

    $sql = "INSERT INTO events (title, description, event_date, venue, created_by)
            VALUES ('$title','$desc','$date','$venue','$uid')";

    if (mysqli_query($conn, $sql)) {
        $msg = "Event created successfully";
    }
}

include "includes/header.php";
include "includes/navbar.php";
?>

<div class="container mt-5">

  <h2 class="fw-bold text-primary mb-4">Create New Event</h2>

  <div class="dashboard-card">

    <?php if ($msg) { ?>
      <div class="alert alert-success">
        <?php echo $msg; ?>
      </div>
    <?php } ?>

    <form method="post">

      <div class="mb-3">
        <label class="form-label fw-semibold">Event Title</label>
        <input type="text" name="title" class="form-control" placeholder="Enter event title" required>
      </div>

      <div class="mb-3">
        <label class="form-label fw-semibold">Event Description</label>
        <textarea name="description" class="form-control" rows="4" placeholder="Enter event description"></textarea>
      </div>

      <div class="row">
        <div class="col-md-6 mb-3">
          <label class="form-label fw-semibold">Event Date</label>
          <input type="date" name="event_date" class="form-control" required>
        </div>

        <div class="col-md-6 mb-3">
          <label class="form-label fw-semibold">Venue</label>
          <input type="text" name="venue" class="form-control" placeholder="Enter venue">
        </div>
      </div>

      <div class="text-end">
        <button type="submit" name="create" class="btn btn-primary px-4">
          <i class="bi bi-plus-circle"></i> Create Event
        </button>
        <a href="organizer_dashboard.php" class="btn btn-outline-secondary ms-2">
          Back
        </a>
      </div>

    </form>

  </div>
</div>

<?php include "includes/footer.php"; ?>
