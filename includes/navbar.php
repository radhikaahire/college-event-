<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$is_auth = !empty($_SESSION['user_id']);
$role = $_SESSION['role'] ?? null;
?>
<nav class="navbar navbar-expand-lg navbar-cems sticky-top">
  <div class="container-fluid">
    <a class="navbar-brand" href="<?php echo $is_auth ? $role . '_dashboard.php' : 'index.php'; ?>">
      <i class="bi bi-mortarboard-fill text-primary"></i>
      <span>College Event System</span>
    </a>
    
    <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#cemsNav" aria-controls="cemsNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    
    <div class="collapse navbar-collapse" id="cemsNav">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-3">
        <?php if ($is_auth && $role === 'admin'): ?>
          <li class="nav-item">
            <a class="nav-link" href="admin_dashboard.php"><i class="bi bi-speedometer2 me-1"></i> Dashboard</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="add_student.php"><i class="bi bi-person-plus me-1"></i> Add Student</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="add_organizer.php"><i class="bi bi-person-badge me-1"></i> Add Organizer</a>
          </li>
        <?php elseif ($is_auth && $role === 'organizer'): ?>
          <li class="nav-item">
            <a class="nav-link" href="organizer_dashboard.php"><i class="bi bi-speedometer2 me-1"></i> Dashboard</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="my_events.php"><i class="bi bi-calendar-check me-1"></i> My Events</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="create_event.php"><i class="bi bi-plus-circle me-1"></i> Create Event</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="manage_participants.php"><i class="bi bi-people me-1"></i> Approvals</a>
          </li>
        <?php elseif ($is_auth && $role === 'student'): ?>
          <li class="nav-item">
            <a class="nav-link" href="student_dashboard.php"><i class="bi bi-speedometer2 me-1"></i> Dashboard</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="view_events.php"><i class="bi bi-compass me-1"></i> Explore Events</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="winners.php"><i class="bi bi-trophy me-1"></i> Winners</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="announcements.php"><i class="bi bi-megaphone me-1"></i> Notices</a>
          </li>
        <?php else: ?>
          <li class="nav-item">
            <a class="nav-link" href="index.php"><i class="bi bi-house me-1"></i> Home</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="view_events.php"><i class="bi bi-calendar-event me-1"></i> Browse Events</a>
          </li>
        <?php endif; ?>
      </ul>
      
      <div class="d-flex align-items-center gap-2">
        <?php if ($is_auth): ?>
          <span class="user-badge">
            <i class="bi bi-shield-check"></i> <?php echo e($role); ?>
          </span>
          <a href="logout.php" class="btn btn-sm btn-outline-danger d-inline-flex align-items-center gap-1">
            <i class="bi bi-box-arrow-right"></i> Logout
          </a>
        <?php else: ?>
          <a href="login.php" class="btn btn-sm btn-cems-primary d-inline-flex align-items-center gap-1">
            <i class="bi bi-box-arrow-in-right"></i> Sign In
          </a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</nav>
