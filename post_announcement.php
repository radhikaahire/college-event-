<?php
include "includes/auth_check.php";
include "includes/db.php";

if ($_SESSION['role'] !== 'organizer') {
    header("Location: login.php");
    exit;
}

$uid = $_SESSION['user_id'];
$msg = "";

/* POST ANNOUNCEMENT */
if (isset($_POST['post'])) {
    $event_id = $_POST['event_id'];
    $message  = $_POST['message'];

    mysqli_query($conn,"
        INSERT INTO announcements (event_id, message)
        VALUES ('$event_id','$message')
    ");

    $msg = "Announcement posted successfully";
}

/* FETCH ONLY ORGANIZER EVENTS */
$events = mysqli_query($conn,"
    SELECT id, title
    FROM events
    WHERE created_by='$uid'
    ORDER BY event_date DESC
");

include "includes/header.php";
include "includes/navbar.php";
?>

<div class="container mt-5">

<h2 class="fw-bold text-primary mb-4">Post Announcement</h2>

<div class="dashboard-card">

<?php if ($msg) { ?>
    <div class="alert alert-success"><?php echo $msg; ?></div>
<?php } ?>

<?php if (mysqli_num_rows($events) == 0) { ?>

    <p class="text-muted text-center">
        No events found. Please create an event first.
    </p>

<?php } else { ?>

<form method="post">

    <div class="mb-3">
        <label class="form-label fw-semibold">Select Event</label>
        <select name="event_id" class="form-select" required>
            <option value="">-- Select Event --</option>
            <?php while ($e = mysqli_fetch_assoc($events)) { ?>
                <option value="<?php echo $e['id']; ?>">
                    <?php echo $e['title']; ?>
                </option>
            <?php } ?>
        </select>
    </div>

    <div class="mb-4">
        <label class="form-label fw-semibold">Announcement Message</label>
        <textarea name="message" class="form-control" rows="4"
                  placeholder="Enter announcement message" required></textarea>
    </div>

    <div class="text-end">
        <button type="submit" name="post" class="btn btn-primary">
            Post Announcement
        </button>
        <a href="organizer_dashboard.php"
           class="btn btn-outline-secondary ms-2">
           Back
        </a>
    </div>

</form>

<?php } ?>

</div>
</div>

<?php include "includes/footer.php"; ?>
