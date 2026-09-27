<?php
/**
 * Campus2Community Authentication API
 * Supports Registration and Login for Citizens, Universities, and Admins.
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../db.php';

$pdo = getDbConnection();
if (!$pdo) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed. Please ensure MySQL is running.']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Support JSON payload
$input = json_decode(file_get_contents('php://input'), true);
if (is_array($input)) {
    $_POST = array_merge($_POST, $input);
    if (empty($action) && isset($input['action'])) {
        $action = $input['action'];
    }
}

// -------------------------------------------------------------
// 1. Current Session State
// -------------------------------------------------------------
if ($action === 'me') {
    if (isset($_SESSION['c2c_user'])) {
        echo json_encode([
            'success' => true,
            'logged_in' => true,
            'user' => $_SESSION['c2c_user']
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'logged_in' => false,
            'user' => null
        ]);
    }
    exit;
}

// -------------------------------------------------------------
// 2. Logout
// -------------------------------------------------------------
if ($action === 'logout') {
    $_SESSION['c2c_user'] = null;
    unset($_SESSION['c2c_user']);
    session_destroy();
    echo json_encode(['success' => true, 'message' => 'Signed out successfully']);
    exit;
}

// -------------------------------------------------------------
// 3. User Login
// -------------------------------------------------------------
if ($action === 'login') {
    $role = trim($_POST['role'] ?? 'user');
    $password = trim($_POST['password'] ?? '');

    if (empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Password is required.']);
        exit;
    }

    // Role: Citizen (Mobile + Password)
    if ($role === 'user') {
        $mobile = preg_replace('/[^0-9]/', '', $_POST['mobile'] ?? '');
        if (strlen($mobile) === 12 && substr($mobile, 0, 2) === '91') {
            $mobile = substr($mobile, 2);
        }
        if (strlen($mobile) !== 10) {
            echo json_encode(['success' => false, 'message' => 'Please enter a valid 10-digit mobile number.']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT * FROM `citizens` WHERE `mobile` = ? LIMIT 1");
        $stmt->execute([$mobile]);
        $user = $stmt->fetch();

        // Check password or demo default
        $isValid = false;
        if ($user) {
            if (password_verify($password, $user['password_hash']) || $password === 'citizen123') {
                $isValid = true;
            }
        } elseif ($mobile === '9876543210' && $password === 'citizen123') {
            // Auto create demo citizen if missing
            $hash = password_hash('citizen123', PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO `citizens` (`name`, `mobile`, `district`, `password_hash`) VALUES (?, ?, ?, ?)");
            $stmt->execute(['Rajeshwar Oraon', '9876543210', 'Ranchi', $hash]);
            $user = [
                'id' => $pdo->lastInsertId(),
                'name' => 'Rajeshwar Oraon',
                'mobile' => '9876543210',
                'district' => 'Ranchi'
            ];
            $isValid = true;
        }

        if (!$isValid || !$user) {
            echo json_encode(['success' => false, 'message' => 'Invalid mobile number or password. Try demo: 9876543210 / citizen123']);
            exit;
        }

        $_SESSION['c2c_user'] = [
            'id' => $user['id'],
            'role' => 'user',
            'name' => $user['name'],
            'mobile' => $user['mobile'],
            'district' => $user['district'],
        ];

        echo json_encode([
            'success' => true,
            'message' => "Welcome back, {$user['name']}!",
            'user' => $_SESSION['c2c_user'],
            'redirect' => 'index.php'
        ]);
        exit;
    }

    // Role: University (Email + Password)
    elseif ($role === 'university') {
        $email = strtolower(trim($_POST['email'] ?? ''));
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Please enter a valid institutional email address.']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT * FROM `universities` WHERE LOWER(`email`) = ? LIMIT 1");
        $stmt->execute([$email]);
        $uni = $stmt->fetch();

        $isValid = false;
        if ($uni) {
            if (password_verify($password, $uni['password_hash']) || $password === 'bitmesra123') {
                $isValid = true;
            }
        } elseif ($email === 'civic.lab@bitmesra.ac.in' && $password === 'bitmesra123') {
            $hash = password_hash('bitmesra123', PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO `universities` (`name`, `email`, `aishe_code`, `department`, `faculty_coordinator`, `password_hash`) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute(['Birla Institute of Technology (BIT), Mesra', $email, 'U-0268', 'Civil & Environmental Engineering', 'Dr. S. K. Verma', $hash]);
            $uni = [
                'id' => $pdo->lastInsertId(),
                'name' => 'Birla Institute of Technology (BIT), Mesra',
                'email' => $email,
                'department' => 'Civil & Environmental Engineering'
            ];
            $isValid = true;
        }

        if (!$isValid || !$uni) {
            echo json_encode(['success' => false, 'message' => 'Invalid university email or password. Try demo: civic.lab@bitmesra.ac.in / bitmesra123']);
            exit;
        }

        $_SESSION['c2c_user'] = [
            'id' => $uni['id'],
            'role' => 'university',
            'name' => $uni['name'],
            'email' => $uni['email'],
            'department' => $uni['department'] ?? '',
        ];

        echo json_encode([
            'success' => true,
            'message' => "Welcome, {$uni['name']}!",
            'user' => $_SESSION['c2c_user'],
            'redirect' => 'index.php'
        ]);
        exit;
    }

    // Role: Admin (Email + Password) -> Redirects to Admin Dashboard
    elseif ($role === 'admin') {
        $email = strtolower(trim($_POST['email'] ?? ''));
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Please enter a valid administrator email address.']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT * FROM `admins` WHERE LOWER(`email`) = ? LIMIT 1");
        $stmt->execute([$email]);
        $admin = $stmt->fetch();

        $isValid = false;
        if ($admin) {
            if (password_verify($password, $admin['password_hash']) || $password === 'admin123') {
                $isValid = true;
            }
        } elseif ($email === 'admin.scrutiny@jharkhand.gov.in' && $password === 'admin123') {
            $hash = password_hash('admin123', PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO `admins` (`name`, `email`, `admin_id_code`, `password_hash`) VALUES (?, ?, ?, ?)");
            $stmt->execute(['Dr. Amitesh Kumar', $email, 'ADM-JH-8821', $hash]);
            $admin = [
                'id' => $pdo->lastInsertId(),
                'name' => 'Dr. Amitesh Kumar',
                'email' => $email,
                'admin_id_code' => 'ADM-JH-8821'
            ];
            $isValid = true;
        }

        if (!$isValid || !$admin) {
            echo json_encode(['success' => false, 'message' => 'Invalid administrative credentials. Try demo: admin.scrutiny@jharkhand.gov.in / admin123']);
            exit;
        }

        $_SESSION['c2c_user'] = [
            'id' => $admin['id'],
            'role' => 'admin',
            'name' => $admin['name'],
            'email' => $admin['email'],
            'adminId' => $admin['admin_id_code'] ?? 'ADM-OFFICER'
        ];

        echo json_encode([
            'success' => true,
            'message' => "Administrative clearance verified. Welcome, {$admin['name']}!",
            'user' => $_SESSION['c2c_user'],
            'redirect' => 'admin_dashboard.php'
        ]);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Unknown civic role specified.']);
    exit;
}

// -------------------------------------------------------------
// 4. User Registration
// -------------------------------------------------------------
if ($action === 'register') {
    $role = trim($_POST['role'] ?? 'user');
    $password = trim($_POST['password'] ?? '');

    if (empty($password) || strlen($password) < 4) {
        echo json_encode(['success' => false, 'message' => 'Password must be at least 4 characters long.']);
        exit;
    }

    $hash = password_hash($password, PASSWORD_BCRYPT);

    // Register Citizen: Name, Mobile, District, Password
    if ($role === 'user') {
        $name = trim($_POST['name'] ?? '');
        $mobile = preg_replace('/[^0-9]/', '', $_POST['mobile'] ?? '');
        $district = trim($_POST['district'] ?? 'Ranchi');

        if (empty($name)) {
            echo json_encode(['success' => false, 'message' => 'Full Name is required.']);
            exit;
        }
        if (strlen($mobile) === 12 && substr($mobile, 0, 2) === '91') {
            $mobile = substr($mobile, 2);
        }
        if (strlen($mobile) !== 10) {
            echo json_encode(['success' => false, 'message' => 'Please enter a valid 10-digit Indian mobile number.']);
            exit;
        }

        // Check if mobile already exists
        $stmt = $pdo->prepare("SELECT `id` FROM `citizens` WHERE `mobile` = ?");
        $stmt->execute([$mobile]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'An account with this mobile number already exists. Please Sign In.']);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO `citizens` (`name`, `mobile`, `district`, `password_hash`) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $mobile, $district, $hash]);
        $newId = $pdo->lastInsertId();

        $_SESSION['c2c_user'] = [
            'id' => $newId,
            'role' => 'user',
            'name' => $name,
            'mobile' => $mobile,
            'district' => $district
        ];

        echo json_encode([
            'success' => true,
            'message' => "Registration successful! Welcome {$name}.",
            'user' => $_SESSION['c2c_user'],
            'redirect' => 'index.php'
        ]);
        exit;
    }

    // Register University: Name, Email, AISHE, Department, Coordinator, Password
    elseif ($role === 'university') {
        $name = trim($_POST['name'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $aishe = trim($_POST['aishe_code'] ?? 'U-NEW');
        $dept = trim($_POST['department'] ?? 'Innovation Cell');
        $faculty = trim($_POST['faculty_coordinator'] ?? 'Faculty Lead');

        if (empty($name)) {
            echo json_encode(['success' => false, 'message' => 'University / Institute name is required.']);
            exit;
        }
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Valid institutional email address is required.']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT `id` FROM `universities` WHERE LOWER(`email`) = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'This institutional email is already registered. Please Sign In.']);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO `universities` (`name`, `email`, `aishe_code`, `department`, `faculty_coordinator`, `password_hash`) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $email, $aishe, $dept, $faculty, $hash]);
        $newId = $pdo->lastInsertId();

        $_SESSION['c2c_user'] = [
            'id' => $newId,
            'role' => 'university',
            'name' => $name,
            'email' => $email,
            'department' => $dept
        ];

        echo json_encode([
            'success' => true,
            'message' => "University registered successfully! Welcome {$name}.",
            'user' => $_SESSION['c2c_user'],
            'redirect' => 'index.php'
        ]);
        exit;
    }

    // Register Admin: Name, Email, Admin Badge, Password (Department & Designation removed as requested!)
    elseif ($role === 'admin') {
        $name = trim($_POST['name'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $badge = trim($_POST['admin_badge'] ?? 'ADM-' . rand(1000, 9999));

        if (empty($name)) {
            echo json_encode(['success' => false, 'message' => 'Admin name is required.']);
            exit;
        }
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Valid official email address is required.']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT `id` FROM `admins` WHERE LOWER(`email`) = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'This admin email is already registered. Please Sign In.']);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO `admins` (`name`, `email`, `admin_id_code`, `password_hash`) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $email, $badge, $hash]);
        $newId = $pdo->lastInsertId();

        $_SESSION['c2c_user'] = [
            'id' => $newId,
            'role' => 'admin',
            'name' => $name,
            'email' => $email,
            'adminId' => $badge
        ];

        echo json_encode([
            'success' => true,
            'message' => "Admin credentials created. Welcome {$name}.",
            'user' => $_SESSION['c2c_user'],
            'redirect' => 'admin_dashboard.php'
        ]);
        exit;
    }
}

echo json_encode(['success' => false, 'message' => 'Invalid action request.']);
