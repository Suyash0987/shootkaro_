<?php
/**
 * Action Controller: Creator Registration Onboarding
 */
require_once dirname(__DIR__) . '/config/app.php';
require_once INCLUDES_PATH . '/csrf.php';
require_once INCLUDES_PATH . '/auth.php';
require_once INCLUDES_PATH . '/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('creator-register.php');
}

require_csrf();

$pdo = get_db_connection();

$name            = trim($_POST['name'] ?? '');
$studioName      = trim($_POST['studio_name'] ?? '');
$email           = trim($_POST['email'] ?? '');
$phone           = trim($_POST['phone'] ?? '');
$password        = $_POST['password'] ?? '';
$cityId          = (int) ($_POST['city_id'] ?? 0);
$areaId          = (int) ($_POST['area_id'] ?? 0);
$services        = $_POST['services'] ?? [];
$startingPrice   = (float) ($_POST['starting_price'] ?? 3000.00);
$experienceYears = (int) ($_POST['experience_years'] ?? 1);
$bio             = trim($_POST['bio'] ?? '');
$portfolioUrl    = trim($_POST['portfolio_url'] ?? '');
$instagramUrl    = trim($_POST['instagram_url'] ?? '');

// Validation
if (empty($name) || empty($studioName) || empty($email) || empty($phone) || empty($password) || empty($cityId) || empty($areaId) || empty($bio)) {
    set_flash('error', 'Please fill in all mandatory creator registration fields.');
    redirect('creator-register.php');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    set_flash('error', 'Please enter a valid email address.');
    redirect('creator-register.php');
}

if (strlen($password) < 6) {
    set_flash('error', 'Password must be at least 6 characters.');
    redirect('creator-register.php');
}

if (empty($services) || !is_array($services)) {
    set_flash('error', 'Please select at least one shooting service (Camera, Mobile, or Drone).');
    redirect('creator-register.php');
}

// Check duplicate email
$stmtCheck = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
$stmtCheck->execute([$email]);
if ($stmtCheck->fetch()) {
    set_flash('error', 'An account with this email already exists. Please log in.');
    redirect('login.php');
}

// Handle Profile Photo Upload (if provided)
$profileImageName = null;
if (!empty($_FILES['profile_image']['name'])) {
    $uploadResult = upload_file($_FILES['profile_image'], 'profiles', ['jpg', 'jpeg', 'png', 'webp']);
    if (!$uploadResult['success']) {
        set_flash('error', 'Profile photo upload error: ' . $uploadResult['error']);
        redirect('creator-register.php');
    }
    $profileImageName = $uploadResult['filename'];
}

$passwordHash = password_hash($password, PASSWORD_BCRYPT);

try {
    $pdo->beginTransaction();

    // 1. Insert into users with 'pending' status
    $userStmt = $pdo->prepare("
        INSERT INTO users (role, name, email, phone, password_hash, status)
        VALUES ('creator', ?, ?, ?, ?, 'pending')
    ");
    $userStmt->execute([$name, $email, $phone, $passwordHash]);
    $userId = (int) $pdo->lastInsertId();

    // 2. Insert into creator_profiles with is_approved = 0
    $profileStmt = $pdo->prepare("
        INSERT INTO creator_profiles (user_id, studio_name, city_id, area_id, bio, profile_image, starting_price, experience_years, portfolio_url, instagram_url, is_approved, is_available)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 1)
    ");
    $profileStmt->execute([
        $userId,
        $studioName,
        $cityId,
        $areaId,
        $bio,
        $profileImageName,
        $startingPrice,
        $experienceYears,
        $portfolioUrl,
        $instagramUrl
    ]);
    $creatorProfileId = (int) $pdo->lastInsertId();

    // 3. Insert creator services
    $serviceInsertStmt = $pdo->prepare("
        INSERT INTO creator_services (creator_id, service_type, service_title, base_price, description, is_active)
        VALUES (?, ?, ?, ?, ?, 1)
    ");

    $serviceTitleMap = [
        'camera' => 'Professional Camera Shoot',
        'mobile' => 'Mobile Reels & Social Shoot',
        'drone'  => '4K Drone Aerial Coverage'
    ];

    foreach ($services as $srv) {
        if (in_array($srv, ['camera', 'mobile', 'drone'], true)) {
            $title = $serviceTitleMap[$srv] ?? 'Shooting Service';
            $serviceInsertStmt->execute([$creatorProfileId, $srv, $title, $startingPrice, "Standard {$title} service by {$studioName}"]);
        }
    }

    // 4. Map primary service area
    $areaStmt = $pdo->prepare("INSERT INTO creator_service_areas (creator_id, area_id) VALUES (?, ?)");
    $areaStmt->execute([$creatorProfileId, $areaId]);

    // 5. Create admin notification
    $adminStmt = $pdo->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
    $admin = $adminStmt->fetch();
    if ($admin) {
        $notifStmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, link) VALUES (?, ?, ?, ?)");
        $notifStmt->execute([
            $admin['id'],
            'New Creator Application',
            "{$studioName} ({$name}) submitted a creator registration and is awaiting approval.",
            'admin/creators.php'
        ]);
    }

    $pdo->commit();

    set_flash('success', 'Application submitted successfully! Your creator profile is pending admin approval. You will be able to log in once reviewed.');
    redirect('login.php');

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Creator registration failed: " . $e->getMessage());
    set_flash('error', 'Could not complete registration due to a server error. Please try again.');
    redirect('creator-register.php');
}
