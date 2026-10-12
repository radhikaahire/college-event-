<?php
include "includes/auth_check.php";
include "includes/db.php";

if ($_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$msg = "";

if (isset($_POST['add'])) {

    $username   = $_POST['username'];
    $password   = $_POST['password'];
    $name       = $_POST['name'];
    $roll_no    = $_POST['roll_no'];
    $department = $_POST['department'];

    // 1️⃣ Insert into users table
    $u = mysqli_query($conn,
        "INSERT INTO users (role, username, password, status)
         VALUES ('student','$username','$password','active')"
    );

    if ($u) {
        $user_id = mysqli_insert_id($conn);

        // 2️⃣ Insert into students table
        mysqli_query($conn,
            "INSERT INTO students (user_id, name, roll_no, department)
             VALUES ('$user_id','$name','$roll_no','$department')"
        );

        $msg = "Student added successfully";
    } else {
        $msg = "Username already exists";
    }
}

include "includes/header.php";
include "includes/navbar.php";
?>

<div class="container mt-5">
<h2 class="fw-bold text-primary mb-4">Add Student</h2>

<div class="dashboard-card">

<?php if ($msg) { ?>
  <div class="alert alert-info"><?php echo $msg; ?></div>
<?php } ?>

<form method="post">

  <div class="row">
    <div class="col-md-6 mb-3">
      <label class="form-label fw-semibold">Username</label>
      <input type="text" name="username" class="form-control" required>
    </div>

    <div class="col-md-6 mb-3">
      <label class="form-label fw-semibold">Password</label>
      <input type="text" name="password" class="form-control" required>
    </div>
  </div>

  <div class="row">
    <div class="col-md-6 mb-3">
      <label class="form-label fw-semibold">Student Name</label>
      <input type="text" name="name" class="form-control" required>
    </div>

    <div class="col-md-6 mb-3">
      <label class="form-label fw-semibold">Roll No</label>
      <input type="text" name="roll_no" class="form-control">
    </div>
  </div>

  <div class="mb-3">
    <label class="form-label fw-semibold">Department</label>
    <input type="text" name="department" class="form-control">
  </div>

  <div class="text-end">
    <button name="add" class="btn btn-primary">
      Add Student
    </button>
    <a href="admin_dashboard.php" class="btn btn-outline-secondary ms-2">
      Back
    </a>
  </div>

</form>
</div>
</div>

<?php include "includes/footer.php"; ?>
