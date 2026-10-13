<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$is_auth = !empty($_SESSION['user_id']);
$role = $_SESSION['role'] ?? null;
$curr_script = basename($_SERVER['PHP_SELF'] ?? '');
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
            <a class="nav-link <?php if ($curr_script === 'admin_dashboard.php') echo 'active fw-bold text-primary'; ?>" href="admin_dashboard.php">
              <i class="bi bi-speedometer2 me-1"></i> Dashboard
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?php if ($curr_script === 'add_student.php') echo 'active fw-bold text-primary'; ?>" href="add_student.php">
              <i class="bi bi-person-plus me-1"></i> Add Student
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?php if ($curr_script === 'add_organizer.php') echo 'active fw-bold text-primary'; ?>" href="add_organizer.php">
              <i class="bi bi-person-badge me-1"></i> Add Organizer
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?php if ($curr_script === 'view_events.php') echo 'active fw-bold text-primary'; ?>" href="view_events.php">
              <i class="bi bi-calendar-event me-1"></i> Events
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?php if ($curr_script === 'winners.php') echo 'active fw-bold text-primary'; ?>" href="winners.php">
              <i class="bi bi-trophy me-1"></i> Winners
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?php if ($curr_script === 'announcements.php') echo 'active fw-bold text-primary'; ?>" href="announcements.php">
              <i class="bi bi-megaphone me-1"></i> Notices
            </a>
          </li>

        <?php elseif ($is_auth && $role === 'organizer'): ?>
          <li class="nav-item">
            <a class="nav-link <?php if ($curr_script === 'organizer_dashboard.php') echo 'active fw-bold text-primary'; ?>" href="organizer_dashboard.php">
              <i class="bi bi-speedometer2 me-1"></i> Dashboard
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?php if ($curr_script === 'my_events.php') echo 'active fw-bold text-primary'; ?>" href="my_events.php">
              <i class="bi bi-calendar-check me-1"></i> My Events
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?php if ($curr_script === 'create_event.php') echo 'active fw-bold text-primary'; ?>" href="create_event.php">
              <i class="bi bi-plus-circle me-1"></i> Create Event
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?php if ($curr_script === 'manage_participants.php') echo 'active fw-bold text-primary'; ?>" href="manage_participants.php">
              <i class="bi bi-people me-1"></i> Approvals
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?php if ($curr_script === 'declare_winner.php') echo 'active fw-bold text-primary'; ?>" href="declare_winner.php">
              <i class="bi bi-award me-1"></i> Declare Winner
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?php if ($curr_script === 'winners.php') echo 'active fw-bold text-primary'; ?>" href="winners.php">
              <i class="bi bi-trophy me-1"></i> Hall of Fame
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?php if ($curr_script === 'announcements.php' || $curr_script === 'post_announcement.php') echo 'active fw-bold text-primary'; ?>" href="post_announcement.php">
              <i class="bi bi-megaphone me-1"></i> Bulletins
            </a>
          </li>

        <?php elseif ($is_auth && $role === 'student'): ?>
          <li class="nav-item">
            <a class="nav-link <?php if ($curr_script === 'student_dashboard.php') echo 'active fw-bold text-primary'; ?>" href="student_dashboard.php">
              <i class="bi bi-speedometer2 me-1"></i> Dashboard
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?php if ($curr_script === 'view_events.php') echo 'active fw-bold text-primary'; ?>" href="view_events.php">
              <i class="bi bi-compass me-1"></i> Explore Events
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?php if ($curr_script === 'winners.php') echo 'active fw-bold text-primary'; ?>" href="winners.php">
              <i class="bi bi-trophy me-1"></i> Winners
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?php if ($curr_script === 'announcements.php') echo 'active fw-bold text-primary'; ?>" href="announcements.php">
              <i class="bi bi-megaphone me-1"></i> Notices
            </a>
          </li>

        <?php else: ?>
          <li class="nav-item">
            <a class="nav-link <?php if ($curr_script === 'index.php') echo 'active fw-bold text-primary'; ?>" href="index.php">
              <i class="bi bi-house me-1"></i> Home
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?php if ($curr_script === 'view_events.php') echo 'active fw-bold text-primary'; ?>" href="view_events.php">
              <i class="bi bi-calendar-event me-1"></i> Browse Events
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?php if ($curr_script === 'winners.php') echo 'active fw-bold text-primary'; ?>" href="winners.php">
              <i class="bi bi-trophy me-1"></i> Hall of Fame
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?php if ($curr_script === 'announcements.php') echo 'active fw-bold text-primary'; ?>" href="announcements.php">
              <i class="bi bi-megaphone me-1"></i> Notices
            </a>
          </li>
        <?php endif; ?>
      </ul>
      
      <div class="d-flex align-items-center gap-2">
        <?php if ($is_auth): ?>
          <span class="user-badge text-capitalize">
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
