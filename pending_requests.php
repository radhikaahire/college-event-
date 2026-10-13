<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/helpers.php';

require_role('organizer');

// Seamlessly forward to unified participant management queue
header("Location: manage_participants.php?status=pending");
exit;
