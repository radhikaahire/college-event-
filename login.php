<?php
session_start();
include "includes/db.php";

$error = "";

if (isset($_POST['login'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];

    $sql = "SELECT * FROM users 
            WHERE username='$username' 
            AND password='$password' 
            AND status='active'";

    $res = mysqli_query($conn, $sql);

    if (mysqli_num_rows($res) == 1) {
        $row = mysqli_fetch_assoc($res);

        $_SESSION['user_id'] = $row['id'];
        $_SESSION['role']    = $row['role'];

        header("Location: ".$row['role']."_dashboard.php");
        exit;
    } else {
        $error = "Invalid username or password";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Login | College Event Management</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body{
    min-height:100vh;
    background:linear-gradient(135deg,#eef2ff,#f8fafc);
    display:flex;
    align-items:center;
    justify-content:center;
    font-family:'Segoe UI',sans-serif;
}

.login-card{
    width:420px;
    background:white;
    border-radius:20px;
    padding:40px;
    box-shadow:0 20px 40px rgba(0,0,0,.12);
    animation:slideUp 1s ease;
}

@keyframes slideUp{
    from{opacity:0; transform:translateY(40px)}
    to{opacity:1; transform:translateY(0)}
}

.login-card h2{
    font-weight:700;
    color:#2563eb;
}

.form-control{
    border-radius:12px;
    padding:12px;
}

.form-control:focus{
    box-shadow:0 0 0 0.2rem rgba(37,99,235,.25);
    border-color:#2563eb;
}

.btn-login{
    background:#2563eb;
    border:none;
    border-radius:12px;
    padding:12px;
    font-weight:600;
    transition:.3s;
}

.btn-login:hover{
    background:#1e40af;
    transform:translateY(-2px);
}

.fade-error{
    animation:shake .4s;
}

@keyframes shake{
    0%{transform:translateX(0)}
    25%{transform:translateX(-5px)}
    50%{transform:translateX(5px)}
    75%{transform:translateX(-5px)}
    100%{transform:translateX(0)}
}
</style>
</head>

<body>

<div class="login-card <?php if($error) echo 'fade-error'; ?>">

    <h2 class="text-center mb-2">College Event System</h2>
    <p class="text-center text-muted mb-4">
        Login to continue
    </p>

    <?php if ($error) { ?>
        <div class="alert alert-danger text-center">
            <?php echo $error; ?>
        </div>
    <?php } ?>

    <form method="post">

        <div class="mb-3">
            <label class="form-label fw-semibold">Username</label>
            <input type="text" name="username" class="form-control"
                   placeholder="Enter username" required>
        </div>

        <div class="mb-4">
            <label class="form-label fw-semibold">Password</label>
            <input type="password" name="password" class="form-control"
                   placeholder="Enter password" required>
        </div>

        <button type="submit" name="login" class="btn btn-login w-100 text-white">
            Login
        </button>

    </form>

    <p class="text-center text-muted mt-4 small">
        © College Event Management System
    </p>

</div>

</body>
</html>
