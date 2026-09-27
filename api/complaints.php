<?php
/**
 * Campus2Community Complaints & GPS Geotagging API
 * Manages grievance submission, tracking, university bids, and administrative assignments.
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../db.php';

$pdo = getDbConnection();
if (!$pdo) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Support JSON input as well
$raw = file_get_contents('php://input');
if (!empty($raw)) {
    $json = json_decode($raw, true);
    if (is_array($json)) {
        $_POST = array_merge($_POST, $json);
        if (empty($action) && isset($json['action'])) {
            $action = $json['action'];
        }
    }
}

// -------------------------------------------------------------
// 1. Submit New Grievance with Live GPS & Geotag
// -------------------------------------------------------------
if ($action === 'submit') {
    // Enforce authentication: Complaints can only be registered after logging in
    if (empty($_SESSION['c2c_user'])) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'require_login' => true,
            'message' => 'Authentication required: You must be logged in to register a complaint. Please log in with your mobile number or citizen account.'
        ]);
        exit;
    }

    $currentUser = $_SESSION['c2c_user'];

    $title = trim($_POST['problem_title'] ?? $_POST['title'] ?? '');
    $category = trim($_POST['category'] ?? 'General');
    $district = trim($_POST['district'] ?? ($currentUser['district'] ?? 'Ranchi'));
    $locality = trim($_POST['locality'] ?? 'Chutia, Ranchi');
    
    // Bind citizen credentials from authenticated session
    $citizenName = !empty($currentUser['name']) ? $currentUser['name'] : trim($_POST['citizen_name'] ?? 'Verified Resident');
    $mobile = !empty($currentUser['mobile']) ? $currentUser['mobile'] : preg_replace('/[^0-9]/', '', $_POST['mobile'] ?? '');
    
    $description = trim($_POST['description'] ?? '');
    $urgency = trim($_POST['urgency'] ?? 'High');

    // GPS & Geotag data
    $lat = isset($_POST['latitude']) && is_numeric($_POST['latitude']) ? floatval($_POST['latitude']) : 23.3441;
    $lng = isset($_POST['longitude']) && is_numeric($_POST['longitude']) ? floatval($_POST['longitude']) : 85.3096;
    $accuracy = isset($_POST['accuracy_meters']) && is_numeric($_POST['accuracy_meters']) ? floatval($_POST['accuracy_meters']) : 4.5;
    $geotagAddress = trim($_POST['geotag_address'] ?? '');

    if (empty($geotagAddress)) {
        $geotagAddress = "{$locality}, {$district}, Jharkhand (GPS: {$lat}° N, {$lng}° E)";
    }

    if (empty($title)) {
        echo json_encode(['success' => false, 'message' => 'Problem title is required.']);
        exit;
    }
    if (empty($description)) {
        echo json_encode(['success' => false, 'message' => 'Detailed problem description is required.']);
        exit;
    }
    if (empty($mobile) || strlen($mobile) !== 10) {
        echo json_encode(['success' => false, 'message' => 'A valid 10-digit mobile number linked to your account is required.']);
        exit;
    }

    // Handle photo upload if present
    $photoUrl = 'https://images.unsplash.com/photo-1541888946425-d0fbb186c5f7?auto=format&fit=crop&w=800&q=80';
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $uploadsDir = __DIR__ . '/../uploads';
        if (!is_dir($uploadsDir)) {
            mkdir($uploadsDir, 0755, true);
        }
        $filename = 'evidence_' . time() . '_' . rand(100, 999) . '.' . pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        if (move_uploaded_file($_FILES['photo']['tmp_name'], $uploadsDir . '/' . $filename)) {
            $photoUrl = 'uploads/' . $filename;
        }
    } elseif (!empty($_POST['photo_url'])) {
        $photoUrl = $_POST['photo_url'];
    }

    // Generate unique Ticket ID: JC2C-2026-XXXXX
    $ticketNum = rand(100, 999);
    $ticketId = "JC2C-2026-00" . $ticketNum;
    
    // Ensure uniqueness
    $stmt = $pdo->prepare("SELECT id FROM complaints WHERE ticket_id = ?");
    $stmt->execute([$ticketId]);
    if ($stmt->fetch()) {
        $ticketId = "JC2C-2026-0" . rand(1000, 9999);
    }

    $stmt = $pdo->prepare("
        INSERT INTO `complaints` 
        (`ticket_id`, `citizen_name`, `citizen_mobile`, `district`, `locality`, `category`, `problem_title`, `description`, `latitude`, `longitude`, `accuracy_meters`, `geotag_address`, `photo_url`, `status`, `urgency`) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Submitted', ?)
    ");
    $stmt->execute([
        $ticketId, $citizenName, $mobile, $district, $locality, $category, 
        $title, $description, $lat, $lng, $accuracy, $geotagAddress, $photoUrl, $urgency
    ]);

    $complaintId = $pdo->lastInsertId();

    // Auto-seed matching university solution bids so universities can immediately be scrutinized
    $proposals = [
        [
            'uni' => 'Birla Institute of Technology (BIT), Mesra',
            'title' => "Engineered {$category} Assessment & Rapid Solution Setup",
            'timeline' => '14 Days (Rapid Implementation)',
            'cost' => 85000.00,
            'lead' => 'Dr. S. K. Verma, Dept of Civil & Water Resources',
            'students' => 6,
            'score' => 96
        ],
        [
            'uni' => 'National Institute of Technology (NIT), Jamshedpur',
            'title' => "IoT-Enabled Civic Infrastructure Re-engineering for {$district}",
            'timeline' => '21 Days',
            'cost' => 95000.00,
            'lead' => 'Prof. A. Banerjee, Mechanical & Civic Innovation Lab',
            'students' => 4,
            'score' => 89
        ],
        [
            'uni' => 'IIT (ISM) Dhanbad',
            'title' => "Geotechnical & Sensor-Integrated System",
            'timeline' => '28 Days',
            'cost' => 120000.00,
            'lead' => 'Prof. K. R. Sen, Applied Engineering Cell',
            'students' => 8,
            'score' => 92
        ]
    ];

    $propStmt = $pdo->prepare("
        INSERT INTO `university_proposals` 
        (`complaint_id`, `university_name`, `proposal_title`, `timeline`, `estimated_cost`, `faculty_lead`, `student_count`, `match_score`, `status`) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending')
    ");
    foreach ($proposals as $prop) {
        $propStmt->execute([
            $complaintId, $prop['uni'], $prop['title'], $prop['timeline'], 
            $prop['cost'], $prop['lead'], $prop['students'], $prop['score']
        ]);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Grievance registered and geotagged successfully!',
        'ticket_id' => $ticketId,
        'complaint_id' => $complaintId,
        'latitude' => $lat,
        'longitude' => $lng,
        'geotag_address' => $geotagAddress
    ]);
    exit;
}

// -------------------------------------------------------------
// 2. Track Complaint by Ticket ID or Phone
// -------------------------------------------------------------
if ($action === 'track') {
    $query = trim($_GET['ticket_id'] ?? $_GET['query'] ?? $_POST['ticket_id'] ?? '');
    if (empty($query)) {
        echo json_encode(['success' => false, 'message' => 'Please enter a Problem ID or Mobile Number to track.']);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT * FROM `complaints` 
        WHERE `ticket_id` = ? OR `citizen_mobile` = ? OR `id` = ?
        ORDER BY `id` DESC LIMIT 1
    ");
    $cleanMobile = preg_replace('/[^0-9]/', '', $query);
    $stmt->execute([$query, $cleanMobile, $query]);
    $complaint = $stmt->fetch();

    if (!$complaint) {
        echo json_encode(['success' => false, 'message' => "No grievance record found for '{$query}'. Please verify your Ticket ID (e.g. JC2C-2026-00125)."]);
        exit;
    }

    // Get enrolled university proposals
    $propStmt = $pdo->prepare("SELECT * FROM `university_proposals` WHERE `complaint_id` = ? ORDER BY `match_score` DESC");
    $propStmt->execute([$complaint['id']]);
    $proposals = $propStmt->fetchAll();

    echo json_encode([
        'success' => true,
        'complaint' => $complaint,
        'proposals' => $proposals
    ]);
    exit;
}

// -------------------------------------------------------------
// 3. List All Complaints (for Admin & Civic Hub)
// -------------------------------------------------------------
if ($action === 'list') {
    $stmt = $pdo->query("SELECT * FROM `complaints` ORDER BY `id` DESC");
    $complaints = $stmt->fetchAll();

    foreach ($complaints as &$c) {
        $pStmt = $pdo->prepare("SELECT * FROM `university_proposals` WHERE `complaint_id` = ? ORDER BY `match_score` DESC");
        $pStmt->execute([$c['id']]);
        $c['proposals'] = $pStmt->fetchAll();
    }

    echo json_encode([
        'success' => true,
        'complaints' => $complaints
    ]);
    exit;
}

// -------------------------------------------------------------
// 4. Admin Assign University to Complaint
// -------------------------------------------------------------
if ($action === 'assign_university') {
    $complaintId = intval($_POST['complaint_id'] ?? 0);
    $uniName = trim($_POST['university_name'] ?? '');
    $grant = floatval($_POST['grant_amount'] ?? 0);
    $timeline = trim($_POST['milestone_timeline'] ?? '14 Days');
    $notes = trim($_POST['scrutiny_notes'] ?? '');

    if ($complaintId <= 0 || empty($uniName)) {
        echo json_encode(['success' => false, 'message' => 'Complaint ID and University Name are required.']);
        exit;
    }

    // Update Complaint
    $stmt = $pdo->prepare("
        UPDATE `complaints` 
        SET 
            `assigned_university_name` = ?,
            `sanctioned_grant` = ?,
            `milestone_timeline` = ?,
            `scrutiny_notes` = ?,
            `status` = 'University Assigned'
        WHERE `id` = ?
    ");
    $stmt->execute([$uniName, $grant, $timeline, $notes, $complaintId]);

    // Update proposals status
    $pdo->prepare("UPDATE `university_proposals` SET `status` = 'Reviewed' WHERE `complaint_id` = ?")->execute([$complaintId]);
    $pdo->prepare("UPDATE `university_proposals` SET `status` = 'Assigned' WHERE `complaint_id` = ? AND `university_name` = ?")->execute([$complaintId, $uniName]);

    // Fetch updated complaint
    $updatedStmt = $pdo->prepare("SELECT * FROM `complaints` WHERE `id` = ?");
    $updatedStmt->execute([$complaintId]);
    $updated = $updatedStmt->fetch();

    echo json_encode([
        'success' => true,
        'message' => "Successfully assigned {$uniName} with grant of ₹" . number_format($grant, 2),
        'complaint' => $updated
    ]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action request.']);
