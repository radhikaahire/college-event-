<?php
include "includes/auth_check.php";
include "includes/db.php";

if ($_SESSION['role'] !== 'organizer') {
    header("Location: login.php");
    exit;
}

$msg = "";
$error = "";

/* Declare Winner */
if (isset($_POST['declare'])) {

    $event_id   = $_POST['event_id'];
    $student_id = $_POST['student_id'];
    $position   = $_POST['position'];

    // 🔒 CHECK EVENT STATUS
    $check = mysqli_query($conn,"
        SELECT status FROM events WHERE id='$event_id'
    ");
    $event = mysqli_fetch_assoc($check);

    if ($event['status'] === 'closed') {
        $error = "This event is closed. Winner cannot be declared.";
    } else {

        mysqli_query($conn,"
            INSERT INTO winners (event_id, student_id, position)
            VALUES ('$event_id','$student_id','$position')
        ");

        $msg = "Winner declared successfully";
    }
}

/* FETCH ONLY OPEN EVENTS */
$events = mysqli_query($conn,"
    SELECT * FROM events WHERE status='open'
");

/* Selected event */
$selected_event = isset($_GET['event_id']) ? $_GET['event_id'] : "";

/* CHECK SELECTED EVENT STATUS */
$event_status = "";
if ($selected_event != "") {
    $es = mysqli_query($conn,"
        SELECT status FROM events WHERE id='$selected_event'
    ");
    if (mysqli_num_rows($es) > 0) {
        $row = mysqli_fetch_assoc($es);
        $event_status = $row['status'];
    }
}

/* Fetch approved students only if event is OPEN */
$students = null;
if ($selected_event != "" && $event_status === 'open') {
    $students = mysqli_query($conn,"
        SELECT s.id, s.name
        FROM event_participation ep
        JOIN students s ON ep.student_id = s.id
        WHERE ep.event_id = '$selected_event'
        AND ep.approval_status = 'approved'
    ");
}

include "includes/header.php";
include "includes/navbar.php";
?>

<div class="container mt-5">

<h2 class="fw-bold text-primary mb-4">Declare Winner</h2>

<div class="dashboard-card">

<?php if ($msg) { ?>
    <div class="alert alert-success"><?php echo $msg; ?></div>
<?php } ?>

<?php if ($error) { ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php } ?>

<!-- SELECT EVENT -->
<form method="get" class="mb-4">
    <label class="form-label fw-semibold">Select Event</label>
    <select name="event_id" class="form-select" onchange="this.form.submit()" required>
        <option value="">-- Select Event --</option>
        <?php while ($e = mysqli_fetch_assoc($events)) { ?>
            <option value="<?php echo $e['id']; ?>"
                <?php if ($selected_event == $e['id']) echo "selected"; ?>>
                <?php echo $e['title']; ?>
            </option>
        <?php } ?>
    </select>
</form>

<?php
if ($selected_event != "") {

    if ($event_status === 'closed') {
        echo "<p class='text-danger fw-semibold'>This event is closed. Winner declaration is disabled.</p>";
    }
    elseif ($students && mysqli_num_rows($students) > 0) {
?>

<!-- DECLARE WINNER FORM -->
<form method="post">

    <input type="hidden" name="event_id" value="<?php echo $selected_event; ?>">

    <div class="mb-3">
        <label class="form-label fw-semibold">Select Student</label>
        <select name="student_id" class="form-select" required>
            <?php while ($s = mysqli_fetch_assoc($students)) { ?>
                <option value="<?php echo $s['id']; ?>">
                    <?php echo $s['name']; ?>
                </option>
            <?php } ?>
        </select>
    </div>

    <div class="mb-3">
        <label class="form-label fw-semibold">Position</label>
        <select name="position" class="form-select" required>
            <option value="1st">1st</option>
            <option value="2nd">2nd</option>
            <option value="3rd">3rd</option>
        </select>
    </div>

    <div class="text-end">
        <button type="submit" name="declare" class="btn btn-primary">
            Declare Winner
        </button>
        <a href="organizer_dashboard.php" class="btn btn-outline-secondary ms-2">
            Back
        </a>
    </div>

</form>

<?php
    } else {
        echo "<p class='text-muted'>No approved students for this event.</p>";
    }
}
?>

</div>
</div>

<?php include "includes/footer.php"; ?>
