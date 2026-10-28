<?php
/**
 * Global Utility Functions for Shoot Karo
 */

/**
 * HTML Escaping
 */
function e(?string $value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/**
 * Format Currency in Indian Rupees (INR)
 */
function format_inr(float $amount): string {
    return '₹' . number_format($amount, 2);
}

/**
 * Format Date
 */
function format_date(?string $datetime, string $format = 'd M Y'): string {
    if (empty($datetime)) return 'N/A';
    $timestamp = strtotime($datetime);
    return $timestamp ? date($format, $timestamp) : 'N/A';
}

/**
 * Format Time
 */
function format_time(?string $time, string $format = 'h:i A'): string {
    if (empty($time)) return 'N/A';
    $timestamp = strtotime($time);
    return $timestamp ? date($format, $timestamp) : 'N/A';
}

/**
 * Set Flash Message
 * Types: success, error, info, warning
 */
function set_flash(string $type, string $message): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['flash_messages'][] = [
        'type'    => $type,
        'message' => $message,
    ];
}

/**
 * Retrieve and Clear Flash Messages
 */
function get_flash(): array {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $messages = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_messages']);
    return $messages;
}

/**
 * Render Flash Alerts HTML (clean, no emojis)
 */
function render_flash(): string {
    $messages = get_flash();
    if (empty($messages)) {
        return '';
    }

    $html = '<div class="flash-messages-container">';
    foreach ($messages as $msg) {
        $type = e($msg['type']);
        $text = e($msg['message']);
        $html .= "<div class=\"alert alert-{$type}\" role=\"alert\">";
        $html .= "<span>{$text}</span>";
        $html .= "<button type=\"button\" class=\"alert-close\" onclick=\"this.parentElement.remove()\" aria-label=\"Close\">&times;</button>";
        $html .= "</div>";
    }
    $html .= '</div>';

    return $html;
}

/**
 * Safe Redirect
 */
function redirect(string $path): void {
    $target = (strpos($path, 'http://') === 0 || strpos($path, 'https://') === 0) ? $path : url($path);
    header("Location: {$target}");
    exit;
}

/**
 * Generate Unique Booking Reference: SK-BK-YYYYMMDD-XXXX
 */
function generate_booking_reference(): string {
    return 'SK-BK-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));
}

/**
 * Generate Unique Payment Reference: PAY-MOCK-YYYYMMDD-XXXX
 */
function generate_payment_reference(): string {
    return 'PAY-MOCK-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
}

/**
 * Secure File Uploader
 * 
 * @param array $file $_FILES['input_name']
 * @param string $destinationSubfolder Subfolder inside /uploads (e.g., 'profiles', 'portfolio', 'deliverables')
 * @param array $allowedExtensions Whitelisted extensions
 * @param int $maxSizeBytes Max file size in bytes (default 15MB)
 * @return array ['success' => bool, 'filename' => string|null, 'error' => string|null]
 */
function upload_file(array $file, string $destinationSubfolder, array $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'pdf', 'zip'], int $maxSizeBytes = 15728640): array {
    if (empty($file) || !isset($file['error'])) {
        return ['success' => false, 'filename' => null, 'error' => 'No file was uploaded.'];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errorMessages = [
            UPLOAD_ERR_INI_SIZE   => 'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
            UPLOAD_ERR_FORM_SIZE  => 'The uploaded file exceeds the MAX_FILE_SIZE directive.',
            UPLOAD_ERR_PARTIAL    => 'The uploaded file was only partially uploaded.',
            UPLOAD_ERR_NO_FILE    => 'No file was chosen.',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder on server.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
            UPLOAD_ERR_EXTENSION  => 'A PHP extension stopped the file upload.',
        ];
        return ['success' => false, 'filename' => null, 'error' => $errorMessages[$file['error']] ?? 'File upload error.'];
    }

    if ($file['size'] > $maxSizeBytes) {
        $maxMb = round($maxSizeBytes / 1048576, 1);
        return ['success' => false, 'filename' => null, 'error' => "File size exceeds the {$maxMb}MB limit."];
    }

    $originalName = $file['name'];
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

    if (!in_array($extension, $allowedExtensions, true)) {
        return ['success' => false, 'filename' => null, 'error' => 'Unsupported file format. Allowed formats: ' . implode(', ', $allowedExtensions)];
    }

    // Verify MIME type using finfo
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);

    $allowedMimeMap = [
        'jpg'  => ['image/jpeg', 'image/pjpeg'],
        'jpeg' => ['image/jpeg', 'image/pjpeg'],
        'png'  => ['image/png'],
        'webp' => ['image/webp'],
        'pdf'  => ['application/pdf'],
        'zip'  => ['application/zip', 'application/x-zip-compressed', 'multipart/x-zip'],
        'mp4'  => ['video/mp4'],
    ];

    $validMimes = $allowedMimeMap[$extension] ?? [];
    if (!empty($validMimes) && !in_array($mimeType, $validMimes, true)) {
        return ['success' => false, 'filename' => null, 'error' => 'File MIME verification failed.'];
    }

    // Target directory
    $targetDir = UPLOADS_PATH . DIRECTORY_SEPARATOR . trim($destinationSubfolder, '/\\');
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    // Cryptographically random unique filename
    $randomName = bin2hex(random_bytes(16)) . '.' . $extension;
    $targetPath = $targetDir . DIRECTORY_SEPARATOR . $randomName;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['success' => false, 'filename' => null, 'error' => 'Failed to move uploaded file to destination.'];
    }

    return ['success' => true, 'filename' => $randomName, 'error' => null];
}

/**
 * Get aggregated creator rating and shoot statistics
 */
function get_creator_stats(int $creatorId): array {
    $pdo = get_db_connection();

    // Average rating and count
    $stmt = $pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as review_count FROM reviews WHERE creator_id = ? AND is_approved = 1");
    $stmt->execute([$creatorId]);
    $reviewStats = $stmt->fetch();

    // Number of completed shoots
    $stmtShoots = $pdo->prepare("SELECT COUNT(*) as completed_count FROM bookings WHERE creator_id = ? AND status = 'completed'");
    $stmtShoots->execute([$creatorId]);
    $shootStats = $stmtShoots->fetch();

    $avgRating = $reviewStats && $reviewStats['avg_rating'] !== null ? round((float) $reviewStats['avg_rating'], 1) : 5.0;
    $reviewCount = $reviewStats ? (int) $reviewStats['review_count'] : 0;
    $completedShoots = $shootStats ? (int) $shootStats['completed_count'] : 0;

    return [
        'avg_rating'       => $avgRating,
        'review_count'     => $reviewCount,
        'completed_shoots' => $completedShoots,
    ];
}

/**
 * Get CSS badge class for status display
 */
function get_status_badge(string $status): string {
    $map = [
        'pending'     => 'badge-pending',
        'accepted'    => 'badge-info',
        'rejected'    => 'badge-danger',
        'confirmed'   => 'badge-success',
        'in_progress' => 'badge-primary',
        'completed'   => 'badge-completed',
        'cancelled'   => 'badge-danger',
        'active'      => 'badge-success',
        'suspended'   => 'badge-danger',
        'paid'        => 'badge-success',
        'advance_paid'=> 'badge-info',
        'fully_paid'  => 'badge-success',
        'open'        => 'badge-pending',
        'resolved'    => 'badge-success',
    ];
    $cls = $map[strtolower($status)] ?? 'badge-secondary';
    $label = ucwords(str_replace('_', ' ', $status));
    return "<span class=\"badge {$cls}\">{$label}</span>";
}
