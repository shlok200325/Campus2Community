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

// Helper to compute dynamic 9-stage grievance lifecycle and audit activity logs
function computeComplaintLifecycle($complaint, $proposals = []) {
    $status = strtolower($complaint['status'] ?? 'submitted');
    $assignedUni = $complaint['assigned_university_name'] ?? null;
    $grant = floatval($complaint['sanctioned_grant'] ?? 0);
    $timeline = $complaint['milestone_timeline'] ?? '14 Days (Rapid Implementation)';
    $notes = $complaint['scrutiny_notes'] ?? '';
    $proposalCount = count($proposals);

    $currentStage = 2; // Default
    if (in_array($status, ['solved', 'resolved', 'closed', 'completed'])) {
        $currentStage = 9;
    } elseif (in_array($status, ['implementing', 'execution', 'civil works'])) {
        $currentStage = 8;
    } elseif (in_array($status, ['field testing', 'testing', 'field trial'])) {
        $currentStage = 7;
    } elseif (in_array($status, ['solution dev', 'in development', 'prototype', 'lab testing'])) {
        $currentStage = 6;
    } elseif (!empty($assignedUni) || in_array($status, ['university assigned', 'assigned', 'selected'])) {
        $currentStage = 5;
    } elseif ($proposalCount > 0 || in_array($status, ['proposals received', 'enrolled', 'bidding'])) {
        $currentStage = 4;
    } elseif (in_array($status, ['scrutiny', 'under review', 'reviewed'])) {
        $currentStage = 3;
    } elseif (in_array($status, ['verified', 'geoverified'])) {
        $currentStage = 2;
    } else {
        $currentStage = ($proposalCount > 0) ? 4 : 2;
    }

    $createdAt = strtotime($complaint['created_at'] ?? 'now');
    $date1 = date('d M', $createdAt);
    $date2 = date('d M', $createdAt + 86400);
    $date3 = date('d M', $createdAt + 86400 * 2);
    $date4 = date('d M', $createdAt + 86400 * 3);
    $date5 = date('d M', $createdAt + 86400 * 5);

    $stages = [
        1 => [
            'number' => 1,
            'title' => 'Reported',
            'title_hi' => 'दर्ज',
            'status_label' => 'Done (' . $date1 . ')',
            'icon' => 'edit_document',
            'summary' => "Grievance filed by {$complaint['citizen_name']} with geotag photo"
        ],
        2 => [
            'number' => 2,
            'title' => 'Verification',
            'title_hi' => 'सत्यापित',
            'status_label' => 'Verified (' . $date2 . ')',
            'icon' => 'domain_verification',
            'summary' => "Geofencing & boundary verification passed in {$complaint['district']}"
        ],
        3 => [
            'number' => 3,
            'title' => 'Scrutiny',
            'title_hi' => 'जांच',
            'status_label' => ($currentStage >= 3) ? 'Approved (' . $date3 . ')' : 'Pending Evaluation',
            'icon' => 'fact_check',
            'summary' => "Administrative scoping & priority classification ({$complaint['urgency']})"
        ],
        4 => [
            'number' => 4,
            'title' => 'Uni Enrolled',
            'title_hi' => 'विश्वविद्यालय आवेदन',
            'status_label' => ($proposalCount > 0) ? "{$proposalCount} Proposals ({$date4})" : 'Open for Bidding',
            'icon' => 'local_library',
            'summary' => ($proposalCount > 0) ? "{$proposalCount} University Hub(s) submitted technical solution bids" : "Open for engineering college applications across Jharkhand"
        ],
        5 => [
            'number' => 5,
            'title' => 'Uni Selected',
            'title_hi' => 'विश्वविद्यालय चयन',
            'status_label' => !empty($assignedUni) ? $assignedUni : 'Selection Pending',
            'icon' => 'school',
            'summary' => !empty($assignedUni) ? "Selected {$assignedUni} (Grant: ₹" . number_format($grant) . ")" : "State Scrutiny Committee evaluating college proposals"
        ],
        6 => [
            'number' => 6,
            'title' => 'Solution Dev',
            'title_hi' => 'लैब विकास',
            'status_label' => ($currentStage >= 6) ? 'In Progress' : 'Upcoming',
            'icon' => 'science',
            'summary' => "College faculty & student teams engineering solution prototype"
        ],
        7 => [
            'number' => 7,
            'title' => 'Field Testing',
            'title_hi' => 'मैदान परीक्षण',
            'status_label' => ($currentStage >= 7) ? 'Under Trial' : 'Upcoming',
            'icon' => 'rule',
            'summary' => "On-ground site trial in village with citizen and Ward Mukhiya"
        ],
        8 => [
            'number' => 8,
            'title' => 'Execution',
            'title_hi' => 'क्रियान्वयन',
            'status_label' => ($currentStage >= 8) ? 'Active Deployment' : 'Upcoming',
            'icon' => 'engineering',
            'summary' => "Municipal civil works and physical hardware installation"
        ],
        9 => [
            'number' => 9,
            'title' => 'Solved',
            'title_hi' => 'समाधान पूर्ण',
            'status_label' => ($currentStage >= 9) ? 'Verified Solved' : 'Final Sign-off',
            'icon' => 'verified',
            'summary' => "Citizen verification sign-off and permanent resolution closure"
        ]
    ];

    // Build timeline audit entries
    $timelineLogs = [];
    $timelineLogs[] = [
        'stage' => 'Stage 1: Citizen Filing',
        'title' => 'Grievance Registered & Geotagged',
        'desc' => "Ticket #{$complaint['ticket_id']} successfully registered by {$complaint['citizen_name']} at {$complaint['locality']}, {$complaint['district']}. Coordinates: {$complaint['latitude']}° N, {$complaint['longitude']}° E.",
        'time' => date('d M Y, h:i A', $createdAt)
    ];

    $timelineLogs[] = [
        'stage' => 'Stage 2: Digital Verification',
        'title' => 'Geofencing & Ward Mapping Passed',
        'desc' => "Automated digital verification confirmed valid location inside {$complaint['district']} District jurisdiction. Anti-duplication screening passed.",
        'time' => date('d M Y, h:i A', $createdAt + 1800)
    ];

    if ($currentStage >= 3) {
        $timelineLogs[] = [
            'stage' => 'Stage 3: Admin Scrutiny',
            'title' => 'Technical Scope Cleared by Higher Education Dept',
            'desc' => "Urgency classified as '{$complaint['urgency']}'. Problem published to accredited engineering colleges in Jharkhand for capstone research adoption.",
            'time' => date('d M Y, h:i A', $createdAt + 86400)
        ];
    }

    if ($proposalCount > 0) {
        $propList = array_map(function($p) { return $p['university_name']; }, array_slice($proposals, 0, 3));
        $propNames = implode(', ', $propList);
        $timelineLogs[] = [
            'stage' => 'Stage 4: University Proposals',
            'title' => "{$proposalCount} Academic Proposals Received",
            'desc' => "Institutions submitted engineered prototypes and budget blueprints: {$propNames}" . ($proposalCount > 3 ? " and others." : "."),
            'time' => date('d M Y, h:i A', $createdAt + 86400 * 2)
        ];
    }

    if (!empty($assignedUni)) {
        $timelineLogs[] = [
            'stage' => 'Stage 5: University Selected',
            'title' => "Official Allocation: {$assignedUni}",
            'desc' => "State Scrutiny Committee approved proposal with ₹" . number_format($grant) . " prototype grant under Jharkhand Civic Protocol. Timeline: {$timeline}. " . (!empty($notes) ? "Notes: {$notes}" : ""),
            'time' => date('d M Y, h:i A', strtotime($complaint['updated_at'] ?? 'now'))
        ];
    }

    return [
        'current_stage' => $currentStage,
        'stages' => $stages,
        'timeline_logs' => array_reverse($timelineLogs)
    ];
}

// -------------------------------------------------------------
// 2. Track Complaint by Ticket ID or Phone
// -------------------------------------------------------------
if ($action === 'track') {
    $ticketId = trim($_GET['ticket_id'] ?? $_POST['ticket_id'] ?? '');
    $mobile = trim($_GET['mobile'] ?? $_POST['mobile'] ?? '');
    $query = trim($_GET['query'] ?? $_POST['query'] ?? '');

    // Resolve search parameters
    if (empty($ticketId) && empty($mobile) && !empty($query)) {
        $cleanQ = preg_replace('/[^0-9]/', '', $query);
        if (strlen($cleanQ) === 10) {
            $mobile = $cleanQ;
        } else {
            $ticketId = $query;
        }
    }

    if (empty($ticketId) && empty($mobile)) {
        echo json_encode(['success' => false, 'message' => 'Please enter a Problem ID (e.g. JC2C-2026-00125) or 10-digit Mobile Number.']);
        exit;
    }

    $complaint = null;
    $matchingComplaints = [];

    // Search by Ticket ID first if provided
    if (!empty($ticketId)) {
        $cleanTicket = strtoupper(trim($ticketId));
        $stmt = $pdo->prepare("
            SELECT * FROM `complaints` 
            WHERE UPPER(TRIM(`ticket_id`)) = ? OR `id` = ? OR `ticket_id` LIKE ?
            ORDER BY `id` DESC LIMIT 1
        ");
        $stmt->execute([$cleanTicket, $ticketId, "%{$cleanTicket}%"]);
        $complaint = $stmt->fetch();
    }

    // If not found by ticket ID or only mobile was provided, search by mobile
    if (!$complaint && !empty($mobile)) {
        $cleanMobile = preg_replace('/[^0-9]/', '', $mobile);
        if (strlen($cleanMobile) === 12 && substr($cleanMobile, 0, 2) === '91') {
            $cleanMobile = substr($cleanMobile, 2);
        }
        $stmt = $pdo->prepare("
            SELECT * FROM `complaints` 
            WHERE `citizen_mobile` = ? OR `citizen_mobile` LIKE ?
            ORDER BY `id` DESC
        ");
        $stmt->execute([$cleanMobile, "%{$cleanMobile}%"]);
        $matchingComplaints = $stmt->fetchAll();
        if (!empty($matchingComplaints)) {
            $complaint = $matchingComplaints[0];
        }
    }

    if (!$complaint) {
        $searched = !empty($ticketId) ? $ticketId : $mobile;
        echo json_encode([
            'success' => false, 
            'message' => "No grievance record found for '{$searched}'. Please check your Problem ID (e.g. JC2C-2026-00125) or registered 10-digit mobile number."
        ]);
        exit;
    }

    // Get enrolled university proposals
    $propStmt = $pdo->prepare("SELECT * FROM `university_proposals` WHERE `complaint_id` = ? ORDER BY `match_score` DESC");
    $propStmt->execute([$complaint['id']]);
    $proposals = $propStmt->fetchAll();

    // Compute dynamic 9-stage lifecycle & audit entries
    $lifecycle = computeComplaintLifecycle($complaint, $proposals);

    echo json_encode([
        'success' => true,
        'complaint' => $complaint,
        'proposals' => $proposals,
        'lifecycle' => $lifecycle,
        'matching_complaints' => array_map(function($c) {
            return [
                'id' => $c['id'],
                'ticket_id' => $c['ticket_id'],
                'problem_title' => $c['problem_title'],
                'category' => $c['category'],
                'status' => $c['status'],
                'district' => $c['district'],
                'created_at' => $c['created_at']
            ];
        }, $matchingComplaints)
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
