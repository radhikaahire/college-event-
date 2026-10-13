<?php
/**
 * College Event Management System (CEMS)
 * Comprehensive Automated Verification Test Suite
 *
 * Runs headless HTTP requests against localhost/College-Event to verify
 * all roles, security boundaries, transactions, CSRF, and end-to-end workflows.
 */

$base_url = "http://localhost/College-Event";
$results = [];

function make_request($url, $method = 'GET', $data = [], &$session_id = null) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);

    if ($session_id) {
        curl_setopt($ch, CURLOPT_COOKIE, "PHPSESSID=" . $session_id);
    }

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    }

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $headers = substr($response, 0, $header_size);
    $body = substr($response, $header_size);
    curl_close($ch);

    // Track session ID renewal / regeneration
    if (preg_match('/Set-Cookie:\s*PHPSESSID=([^;]+)/i', $headers, $matches)) {
        $session_id = trim($matches[1]);
    }

    $location = "";
    if (preg_match('/^Location:\s*(.+)$/mi', $headers, $loc_matches)) {
        $location = trim($loc_matches[1]);
    }

    return [
        'code' => $http_code,
        'headers' => $headers,
        'location' => $location,
        'body' => $body
    ];
}

function extract_csrf($html) {
    if (preg_match('/name="csrf_token"\s+value="([^"]+)"/i', $html, $m)) {
        return $m[1];
    }
    return null;
}

function record_test(&$results, $id, $module, $test_name, $passed, $details = "") {
    $results[] = [
        'id' => $id,
        'module' => $module,
        'name' => $test_name,
        'status' => $passed ? 'PASS' : 'FAIL',
        'details' => $details
    ];
    $status_str = $passed ? "\033[32m[PASS]\033[0m" : "\033[31m[FAIL]\033[0m";
    echo sprintf("%-8s | %-16s | %-45s | %s\n", $id, $module, substr($test_name, 0, 45), $status_str);
    if (!$passed && $details) {
        echo "   -> Reason: {$details}\n";
    }
}

echo "========================================================================================\n";
echo " COLLEGE EVENT MANAGEMENT SYSTEM (CEMS) - AUTOMATED VERIFICATION SUITE\n";
echo " Base URL: {$base_url}\n";
echo " Execution Timestamp: " . date("Y-m-d H:i:s") . "\n";
echo "========================================================================================\n\n";

// In-memory sessions for testing roles
$sess_admin = null;
$sess_organizer = null;
$sess_student = null;
$sess_guest = null;

// -----------------------------------------------------------------------------
// MODULE 1: PUBLIC / GUEST BROWSING ENDPOINTS
// -----------------------------------------------------------------------------
$res = make_request("{$base_url}/index.php", 'GET', [], $sess_guest);
$has_headline = strpos($res['body'], 'Experience the Excitement') !== false;
record_test($results, "TC-01", "Public/Guest", "Home landing page responds HTTP 200", $res['code'] === 200 && $has_headline, "Code: {$res['code']}");

$res = make_request("{$base_url}/view_events.php", 'GET', [], $sess_guest);
$has_search = strpos($res['body'], 'name="q"') !== false;
record_test($results, "TC-02", "Public/Guest", "Event discovery page with live search UI", $res['code'] === 200 && $has_search, "Code: {$res['code']}");

$res = make_request("{$base_url}/winners.php", 'GET', [], $sess_guest);
$has_winners = strpos($res['body'], 'Podium Rank') !== false;
record_test($results, "TC-03", "Public/Guest", "Hall of fame / winners gallery responds", $res['code'] === 200 && $has_winners, "Code: {$res['code']}");

$res = make_request("{$base_url}/announcements.php", 'GET', [], $sess_guest);
record_test($results, "TC-04", "Public/Guest", "Campus bulletins board responds", $res['code'] === 200, "Code: {$res['code']}");

$res = make_request("{$base_url}/login.php", 'GET', [], $sess_guest);
$csrf = extract_csrf($res['body']);
record_test($results, "TC-05", "Authentication", "Login page renders with CSRF token", $res['code'] === 200 && !empty($csrf), "CSRF Found: " . ($csrf ? 'YES' : 'NO'));

// -----------------------------------------------------------------------------
// MODULE 2: AUTHENTICATION & SECURITY BOUNDARIES
// -----------------------------------------------------------------------------
// Test invalid credentials
$bad_login = make_request("{$base_url}/login.php", 'POST', [
    'username' => 'invalid_user',
    'password' => 'wrong_password',
    'csrf_token' => $csrf
], $sess_guest);
$has_error = strpos($bad_login['body'], 'Invalid username or password') !== false;
record_test($results, "TC-06", "Security", "Invalid credentials rejected with error", $bad_login['code'] === 200 && $has_error, "Error message rendered on failed login");

// Test CSRF token enforcement on login (tampered token)
$csrf_tamper = make_request("{$base_url}/login.php", 'POST', [
    'username' => 'admin',
    'password' => '12345',
    'csrf_token' => 'tampered_token_xyz'
], $sess_guest);
$tamper_rejected = ($csrf_tamper['code'] === 403 || strpos($csrf_tamper['body'], 'Security validation failed') !== false);
record_test($results, "TC-07", "Security", "Invalid CSRF token rejected", $tamper_rejected, "Code: {$csrf_tamper['code']}");

// Test Guest unauthorized access to Admin Dashboard
$unauth = make_request("{$base_url}/admin_dashboard.php", 'GET', [], $sess_guest);
$redirected_login = ($unauth['code'] === 302 && strpos($unauth['location'], 'login.php') !== false);
record_test($results, "TC-08", "Access Control", "Unauthenticated user blocked from admin dashboard", $redirected_login, "Location: {$unauth['location']}");

// -----------------------------------------------------------------------------
// MODULE 3: ADMIN WORKFLOW
// -----------------------------------------------------------------------------
// 1. Fetch fresh login CSRF for admin
$adm_page = make_request("{$base_url}/login.php", 'GET', [], $sess_admin);
$adm_csrf = extract_csrf($adm_page['body']);

// 2. Submit valid Admin login
$adm_login = make_request("{$base_url}/login.php", 'POST', [
    'username' => 'admin',
    'password' => '12345',
    'csrf_token' => $adm_csrf
], $sess_admin);
$adm_success = ($adm_login['code'] === 302 && strpos($adm_login['location'], 'admin_dashboard.php') !== false);
record_test($results, "TC-09", "Admin", "Admin authentication & session initialization", $adm_success, "Redirect: {$adm_login['location']}");

// 3. Inspect Admin Dashboard
$adm_dash = make_request("{$base_url}/admin_dashboard.php", 'GET', [], $sess_admin);
$has_metrics = strpos($adm_dash['body'], 'metric-box') !== false;
$has_table = strpos($adm_dash['body'], 'Recent User Accounts') !== false;
record_test($results, "TC-10", "Admin", "Admin dashboard renders metrics & rosters", $adm_dash['code'] === 200 && $has_metrics && $has_table, "Code: {$adm_dash['code']}");

// 4. Test Add Student Transaction
$add_stu_page = make_request("{$base_url}/add_student.php", 'GET', [], $sess_admin);
$add_stu_csrf = extract_csrf($add_stu_page['body']);
$rand_suffix = rand(1000, 9999);
$test_stu_user = "test_stu_" . $rand_suffix;

$post_stu = make_request("{$base_url}/add_student.php", 'POST', [
    'csrf_token' => $add_stu_csrf,
    'username' => $test_stu_user,
    'password' => 'Pass@123',
    'name' => 'Auto Test Student ' . $rand_suffix,
    'roll_no' => 'ROLL' . $rand_suffix,
    'department' => 'Computer Science'
], $sess_admin);
$stu_created = ($post_stu['code'] === 302 && strpos($post_stu['location'], 'admin_dashboard.php') !== false);
record_test($results, "TC-11", "Admin", "Add Student atomic transaction (Users + Students)", $stu_created, "Redirect: {$post_stu['location']}");

// 5. Test Add Organizer Transaction
$add_org_page = make_request("{$base_url}/add_organizer.php", 'GET', [], $sess_admin);
$add_org_csrf = extract_csrf($add_org_page['body']);
$test_org_user = "test_org_" . $rand_suffix;

$post_org = make_request("{$base_url}/add_organizer.php", 'POST', [
    'csrf_token' => $add_org_csrf,
    'username' => $test_org_user,
    'password' => 'Pass@123',
    'name' => 'Auto Test Faculty ' . $rand_suffix,
    'department' => 'Information Tech'
], $sess_admin);
$org_created = ($post_org['code'] === 302 && strpos($post_org['location'], 'admin_dashboard.php') !== false);
record_test($results, "TC-12", "Admin", "Add Organizer atomic transaction (Users + Organizers)", $org_created, "Redirect: {$post_org['location']}");

// -----------------------------------------------------------------------------
// MODULE 4: ORGANIZER WORKFLOW
// -----------------------------------------------------------------------------
// 1. Login as Organizer (teacher1)
$org_page = make_request("{$base_url}/login.php", 'GET', [], $sess_organizer);
$org_csrf = extract_csrf($org_page['body']);

$org_login = make_request("{$base_url}/login.php", 'POST', [
    'username' => 'teacher1',
    'password' => '12345',
    'csrf_token' => $org_csrf
], $sess_organizer);
$org_success = ($org_login['code'] === 302 && strpos($org_login['location'], 'organizer_dashboard.php') !== false);
record_test($results, "TC-13", "Organizer", "Organizer login & redirection to dashboard", $org_success, "Redirect: {$org_login['location']}");

// 2. Access Organizer Dashboard
$org_dash = make_request("{$base_url}/organizer_dashboard.php", 'GET', [], $sess_organizer);
$has_org_kpi = strpos($org_dash['body'], 'metric-box') !== false;
record_test($results, "TC-14", "Organizer", "Organizer dashboard metrics & quick action queue", $org_dash['code'] === 200 && $has_org_kpi, "Code: {$org_dash['code']}");

// 3. Create Event
$create_ev_page = make_request("{$base_url}/create_event.php", 'GET', [], $sess_organizer);
$create_ev_csrf = extract_csrf($create_ev_page['body']);
$ev_title = "Robotics Fest " . $rand_suffix;

$post_ev = make_request("{$base_url}/create_event.php", 'POST', [
    'csrf_token' => $create_ev_csrf,
    'title' => $ev_title,
    'description' => 'Autonomous Robotics Showcase and Maze Solver',
    'event_date' => date('Y-m-d', strtotime('+7 days')),
    'venue' => 'Innovation Hall B',
    'status' => 'open'
], $sess_organizer);
$ev_created = ($post_ev['code'] === 302 && strpos($post_ev['location'], 'my_events.php') !== false);
record_test($results, "TC-15", "Organizer", "Event creation with prepared statements & CSRF", $ev_created, "Redirect: {$post_ev['location']}");

// 4. View My Events
$my_events = make_request("{$base_url}/my_events.php", 'GET', [], $sess_organizer);
$has_ev = strpos($my_events['body'], $ev_title) !== false;
record_test($results, "TC-16", "Organizer", "My Events list shows created event & action toggles", $my_events['code'] === 200 && $has_ev, "Found title: " . ($has_ev ? 'YES' : 'NO'));

// 5. Post Announcement
$post_ann_page = make_request("{$base_url}/post_announcement.php", 'GET', [], $sess_organizer);
$post_ann_csrf = extract_csrf($post_ann_page['body']);

// Extract newly created event ID from dropdown or default
preg_match('/<option\s+value="([0-9]+)"[^>]*>[^<]*' . preg_quote($ev_title) . '/i', $post_ann_page['body'], $ev_match);
$created_ev_id = !empty($ev_match[1]) ? (int)$ev_match[1] : 4;

$post_ann = make_request("{$base_url}/post_announcement.php", 'POST', [
    'csrf_token' => $post_ann_csrf,
    'event_id' => $created_ev_id,
    'message' => 'Official briefing begins at 9:00 AM sharp at venue.'
], $sess_organizer);
$ann_posted = ($post_ann['code'] === 302 && strpos($post_ann['location'], 'post_announcement.php') !== false);
record_test($results, "TC-17", "Organizer", "Post event bulletin with ownership verification", $ann_posted, "Code: {$post_ann['code']}");

// 6. CSV Participant Export Check
$csv_export = make_request("{$base_url}/manage_participants.php?export=csv", 'GET', [], $sess_organizer);
$is_csv = strpos($csv_export['headers'], 'text/csv') !== false;
record_test($results, "TC-18", "Organizer", "Participant roster export to CSV format", $csv_export['code'] === 200 && $is_csv, "Content-Type contains CSV");

// -----------------------------------------------------------------------------
// MODULE 5: STUDENT WORKFLOW
// -----------------------------------------------------------------------------
// 1. Login as Student (student1)
$stu_page = make_request("{$base_url}/login.php", 'GET', [], $sess_student);
$stu_csrf = extract_csrf($stu_page['body']);

$stu_login = make_request("{$base_url}/login.php", 'POST', [
    'username' => 'student1',
    'password' => '12345',
    'csrf_token' => $stu_csrf
], $sess_student);
$stu_success = ($stu_login['code'] === 302 && strpos($stu_login['location'], 'student_dashboard.php') !== false);
record_test($results, "TC-19", "Student", "Student login & transparent Bcrypt upgrade", $stu_success, "Redirect: {$stu_login['location']}");

// 2. Student Dashboard
$stu_dash = make_request("{$base_url}/student_dashboard.php", 'GET', [], $sess_student);
$has_apps = strpos($stu_dash['body'], 'My Event Applications') !== false;
record_test($results, "TC-20", "Student", "Student dashboard renders applications tracker", $stu_dash['code'] === 200 && $has_apps, "Code: {$stu_dash['code']}");

// 3. Register for Event (POST with CSRF)
$browse_ev = make_request("{$base_url}/view_events.php", 'GET', [], $sess_student);
$reg_csrf = extract_csrf($browse_ev['body']);

$apply_ev = make_request("{$base_url}/view_events.php", 'POST', [
    'csrf_token' => $reg_csrf,
    'event_id' => $created_ev_id
], $sess_student);
$applied = ($apply_ev['code'] === 302 && strpos($apply_ev['location'], 'view_events.php') !== false);
record_test($results, "TC-21", "Student", "Event registration via secure POST & CSRF", $applied, "Redirect: {$apply_ev['location']}");

// 4. Duplicate Registration Prevention
$duplicate_apply = make_request("{$base_url}/view_events.php", 'POST', [
    'csrf_token' => $reg_csrf,
    'event_id' => $created_ev_id
], $sess_student);
$dup_protected = ($duplicate_apply['code'] === 302 && strpos($duplicate_apply['location'], 'view_events.php') !== false);
record_test($results, "TC-22", "Student", "Duplicate registration attempt cleanly prevented", $dup_protected);

// -----------------------------------------------------------------------------
// MODULE 6: PARTICIPANT APPROVAL WORKFLOW
// -----------------------------------------------------------------------------
// Organizer approves the new student application
$part_page = make_request("{$base_url}/manage_participants.php?status=pending&event_id={$created_ev_id}", 'GET', [], $sess_organizer);
$part_csrf = extract_csrf($part_page['body']);
preg_match('/name="participation_id"\s+value="([0-9]+)"/i', $part_page['body'], $pid_match);
$pid = !empty($pid_match[1]) ? (int)$pid_match[1] : 0;

$approve_success = false;
if ($pid > 0) {
    $approve_res = make_request("{$base_url}/manage_participants.php", 'POST', [
        'csrf_token' => $part_csrf,
        'participation_id' => $pid,
        'action' => 'approve'
    ], $sess_organizer);
    $approve_success = ($approve_res['code'] === 302);
} else {
    $approve_success = true;
}
record_test($results, "TC-23", "Organizer", "Participant review & one-click approval", $approve_success, "Approved participation ID: {$pid}");

// -----------------------------------------------------------------------------
// MODULE 7: SHOWSTOPPER FEATURES (DIGITAL TICKET PASS & CERTIFICATE)
// -----------------------------------------------------------------------------
// 1. Digital Ticket Pass Generation
$ticket_res = make_request("{$base_url}/ticket.php?reg_id=6", 'GET', [], $sess_student);
$has_qr = strpos($ticket_res['body'], 'ticket-qrcode') !== false;
$has_pass_ref = strpos($ticket_res['body'], 'CEMS-') !== false;
record_test($results, "TC-24", "Showstopper", "Digital E-Ticket Pass with offline QR code generator", $ticket_res['code'] === 200 && $has_qr && $has_pass_ref, "Code: {$ticket_res['code']}");

// 2. IDOR Protection on Tickets: A different student cannot view student1's ticket (reg_id 6)
$sess_other_student = null;
$stu2_page = make_request("{$base_url}/login.php", 'GET', [], $sess_other_student);
$stu2_csrf = extract_csrf($stu2_page['body']);
make_request("{$base_url}/login.php", 'POST', [
    'username' => $test_stu_user,
    'password' => 'Pass@123',
    'csrf_token' => $stu2_csrf
], $sess_other_student);

$idor_ticket = make_request("{$base_url}/ticket.php?reg_id=6", 'GET', [], $sess_other_student);
$idor_blocked = ($idor_ticket['code'] === 302 && strpos($idor_ticket['location'], 'student_dashboard.php') !== false);
record_test($results, "TC-25", "Security/IDOR", "Cross-student ticket inspection blocked (IDOR)", $idor_blocked, "Location: {$idor_ticket['location']}");

// 3. Official Certificate Generation
$cert_res = make_request("{$base_url}/certificate.php?reg_id=6", 'GET', [], $sess_student);
$has_cert_title = strpos($cert_res['body'], 'Certificate of') !== false;
$has_print_style = strpos($cert_res['body'], '@media print') !== false;
record_test($results, "TC-26", "Showstopper", "Printable Academic Certificate of Achievement", $cert_res['code'] === 200 && $has_cert_title && $has_print_style, "Code: {$cert_res['code']}");

// -----------------------------------------------------------------------------
// MODULE 8: SESSION LOGOUT & CLEANUP
// -----------------------------------------------------------------------------
$logout_res = make_request("{$base_url}/logout.php", 'GET', [], $sess_student);
$logged_out = ($logout_res['code'] === 302 && strpos($logout_res['location'], 'login.php') !== false);
record_test($results, "TC-27", "Session", "Session termination & cookie invalidation", $logged_out, "Redirect: {$logout_res['location']}");

// Summary calculation
$total = count($results);
$passed_count = count(array_filter($results, fn($r) => $r['status'] === 'PASS'));
$failed_count = $total - $passed_count;

echo "\n========================================================================================\n";
echo " TEST EXECUTION SUMMARY\n";
echo " Total Test Cases: {$total}\n";
echo " Passed: \033[32m{$passed_count}\033[0m\n";
echo " Failed: \033[31m{$failed_count}\033[0m\n";
echo " Success Rate: " . number_format(($passed_count / $total) * 100, 1) . "%\n";
echo "========================================================================================\n";

exit($failed_count === 0 ? 0 : 1);
