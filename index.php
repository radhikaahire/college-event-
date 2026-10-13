<?php
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

$page_title = "Welcome to Campus Fest";

// Fetch aggregate stats from database
$stat_events = 0;
$stat_students = 0;
$stat_winners = 0;

$r = mysqli_query($conn, "SELECT COUNT(*) c FROM events WHERE status='open'");
if ($r) $stat_events = mysqli_fetch_assoc($r)['c'] ?? 0;

$r = mysqli_query($conn, "SELECT COUNT(*) c FROM students");
if ($r) $stat_students = mysqli_fetch_assoc($r)['c'] ?? 0;

$r = mysqli_query($conn, "SELECT COUNT(*) c FROM winners");
if ($r) $stat_winners = mysqli_fetch_assoc($r)['c'] ?? 0;

// Fetch upcoming open events
$events_query = "
    SELECT e.*, u.username AS organizer_name
    FROM events e
    LEFT JOIN users u ON e.created_by = u.id
    WHERE e.status = 'open'
    ORDER BY e.event_date ASC
    LIMIT 6
";
$featured_events = mysqli_query($conn, $events_query);

// Target date for countdown (closest upcoming event date, or 7 days from now)
$target_date = date('Y-m-d H:i:s', strtotime('+7 days'));
$next_event_res = mysqli_query($conn, "SELECT event_date FROM events WHERE status='open' AND event_date >= CURDATE() ORDER BY event_date ASC LIMIT 1");
if ($next_event_res && $row = mysqli_fetch_assoc($next_event_res)) {
    $target_date = date('Y-m-d 09:00:00', strtotime($row['event_date']));
}

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>

<!-- Hero Section -->
<header class="fest-hero text-center text-lg-start">
  <div class="container py-4">
    <div class="row align-items-center g-5">
      <div class="col-lg-7">
        <span class="hero-pill anim-fade-in">
          <i class="bi bi-stars"></i> Annual College Symposium & Fest 2026
        </span>
        <h1 class="mb-3 anim-fade-in">Experience the Excitement of Campus Events.</h1>
        <p class="lead text-muted mb-4 fs-6">
          Discover competitions, technical hackathons, cultural showcases, and guest lectures across every department. Register with 1-click and track your performance.
        </p>

        <div class="d-flex flex-wrap gap-3 align-items-center justify-content-center justify-content-lg-start">
          <a href="view_events.php" class="btn btn-cems-primary px-4 py-2 fs-6 d-inline-flex align-items-center gap-2">
            <i class="bi bi-compass"></i> Explore All Events
          </a>
          <?php if (empty($_SESSION['user_id'])): ?>
            <a href="login.php" class="btn btn-cems-secondary px-4 py-2 fs-6 d-inline-flex align-items-center gap-2">
              <i class="bi bi-person"></i> Student / Faculty Sign In
            </a>
          <?php else: ?>
            <a href="<?php echo e($_SESSION['role']); ?>_dashboard.php" class="btn btn-cems-secondary px-4 py-2 fs-6 d-inline-flex align-items-center gap-2">
              <i class="bi bi-speedometer2"></i> Go to Dashboard
            </a>
          <?php endif; ?>
        </div>
      </div>

      <!-- Live Countdown Card -->
      <div class="col-lg-5 text-center">
        <div class="cems-card shadow-lg p-4 p-md-5 anim-fade-in">
          <span class="badge bg-primary-subtle text-primary text-uppercase fw-bold px-3 py-1 mb-2">Countdown to Next Big Fest</span>
          <h4 class="fw-bold mb-3">Event Day Kickoff</h4>
          
          <div class="countdown-box justify-content-center mb-3">
            <div class="countdown-segment">
              <div class="num" id="cdDays">00</div>
              <div class="lbl">Days</div>
            </div>
            <div class="countdown-segment">
              <div class="num" id="cdHours">00</div>
              <div class="lbl">Hours</div>
            </div>
            <div class="countdown-segment">
              <div class="num" id="cdMins">00</div>
              <div class="lbl">Mins</div>
            </div>
            <div class="countdown-segment">
              <div class="num" id="cdSecs">00</div>
              <div class="lbl">Secs</div>
            </div>
          </div>
          
          <p class="text-muted small mb-0">
            <i class="bi bi-geo-alt-fill text-danger me-1"></i> Main Campus Auditorium &amp; Grounds
          </p>
        </div>
      </div>
    </div>
  </div>
</header>

<!-- Live Stats Counters -->
<section class="py-4 bg-white border-bottom">
  <div class="container">
    <div class="row g-3 text-center">
      <div class="col-4">
        <div class="fw-extrabold fs-2 text-dark"><?php echo (int)$stat_events; ?></div>
        <div class="text-muted small fw-semibold">Active Events</div>
      </div>
      <div class="col-4 border-start border-end">
        <div class="fw-extrabold fs-2 text-dark"><?php echo (int)$stat_students; ?></div>
        <div class="text-muted small fw-semibold">Registered Students</div>
      </div>
      <div class="col-4">
        <div class="fw-extrabold fs-2 text-dark"><?php echo (int)$stat_winners; ?></div>
        <div class="text-muted small fw-semibold">Podium Winners</div>
      </div>
    </div>
  </div>
</section>

<!-- Upcoming Events Showcase Grid -->
<section class="py-5">
  <div class="container">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-4">
      <div>
        <h3 class="fw-bold text-dark mb-1">Featured Campus Events</h3>
        <p class="text-muted small mb-0">Upcoming competitions open for participation</p>
      </div>
      <a href="view_events.php" class="btn btn-sm btn-cems-secondary align-self-start align-self-md-auto">
        View All Events <i class="bi bi-arrow-right ms-1"></i>
      </a>
    </div>

    <?php if (!$featured_events || mysqli_num_rows($featured_events) === 0): ?>
      <div class="cems-card text-center py-5">
        <i class="bi bi-calendar-x fs-1 text-muted d-block mb-3"></i>
        <h5 class="fw-bold">No Events Open Right Now</h5>
        <p class="text-muted small mb-0">Check back soon or sign in as an organizer to schedule new events.</p>
      </div>
    <?php else: ?>
      <div class="row g-4">
        <?php while ($ev = mysqli_fetch_assoc($featured_events)): ?>
          <div class="col-md-6 col-lg-4">
            <div class="event-card cems-card-hover">
              <div class="card-banner">
                <span class="card-category-badge">
                  <?php echo e($ev['category'] ?? 'Campus Event'); ?>
                </span>
                <div>
                  <div class="small opacity-75">Event Date</div>
                  <div class="fw-bold fs-6">
                    <i class="bi bi-calendar-event me-1"></i> <?php echo date("D, d M Y", strtotime($ev['event_date'])); ?>
                  </div>
                </div>
              </div>

              <div class="card-body">
                <h5 class="fw-bold mb-1 text-dark"><?php echo e($ev['title']); ?></h5>
                <p class="text-muted small mb-3 flex-grow-1 text-truncate-2">
                  <?php echo e(!empty($ev['description']) ? $ev['description'] : 'Join this exciting competition organized by college departments.'); ?>
                </p>

                <div class="event-meta pt-2 border-top">
                  <span>
                    <i class="bi bi-geo-alt text-danger me-1"></i>
                    <?php echo e(!empty($ev['venue']) ? $ev['venue'] : 'Campus Grounds'); ?>
                  </span>
                  <span class="badge-status badge-status-open ms-auto">
                    Open
                  </span>
                </div>

                <a href="view_events.php" class="btn btn-sm btn-cems-primary w-100 mt-2">
                  View Details &amp; Apply
                </a>
              </div>
            </div>
          </div>
        <?php endwhile; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<!-- JavaScript Countdown Timer -->
<script>
  (function() {
    const target = new Date("<?php echo e($target_date); ?>").getTime();
    function updateCountdown() {
      const now = new Date().getTime();
      const diff = target - now;

      if (diff > 0) {
        const days = Math.floor(diff / (1000 * 60 * 60 * 24));
        const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const mins = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
        const secs = Math.floor((diff % (1000 * 60)) / 1000);

        document.getElementById("cdDays").textContent = String(days).padStart(2, '0');
        document.getElementById("cdHours").textContent = String(hours).padStart(2, '0');
        document.getElementById("cdMins").textContent = String(mins).padStart(2, '0');
        document.getElementById("cdSecs").textContent = String(secs).padStart(2, '0');
      }
    }
    updateCountdown();
    setInterval(updateCountdown, 1000);
  })();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
