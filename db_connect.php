<?php
// Database configuration
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');    // <-- แก้ไขตาม username ของคุณ
define('DB_PASSWORD', '');        // <-- แก้ไขตาม password ของคุณ
define('DB_NAME', 'streaming_shop_db'); // <-- แก้ไขตามชื่อ DB ของคุณ

// Base URL Configuration
// Set this to '' if Apache's DocumentRoot points directly to the 'public' folder
$project_subfolder = ''; // <-- ตรวจสอบว่าเป็นค่าที่ถูกต้อง (น่าจะ '' ถ้า DocRoot คือ public)

// Define the Base URL dynamically (Only define it ONCE)
define('BASE_URL', sprintf(
    "%s://%s%s%s", // Format: protocol://domain<:port>/subfolder
    isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http',
    $_SERVER['SERVER_NAME'],
    (isset($_SERVER['SERVER_PORT']) && !in_array($_SERVER['SERVER_PORT'], [80, 443])) ? ':' . $_SERVER['SERVER_PORT'] : '',
    $project_subfolder
));
// --- End Base URL Configuration ---


// Timezone
date_default_timezone_set('Asia/Bangkok');

// Attempt to connect to MySQL database
try {
    $pdo = new PDO("mysql:host=" . DB_SERVER . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USERNAME, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (PDOException $e) {
    error_log("Database Connection Error: " . $e->getMessage());
    die("ขออภัย ระบบฐานข้อมูลขัดข้อง โปรดลองใหม่ภายหลัง");
}

// Start Session
if (session_status() == PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 3600,
        'path' => '/',
        'domain' => '',
        'secure' => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

// --- Helper Functions ---

// Function for Redirect
function redirect($url) {
    if (strpos($url, '://') === false && strpos($url, '//') !== 0) {
         if (strpos($url, '/') !== 0) { $url = '/' . $url; }
         $final_url = BASE_URL . $url;
    } elseif (strpos($url, BASE_URL) === 0) {
        $final_url = $url;
    } else {
        $final_url = BASE_URL . '/';
        error_log("Attempted unsafe redirect: " . $url);
    }
    // Ensure headers are not already sent before calling header()
    if (!headers_sent()) {
        header("Location: " . $final_url);
        exit;
    } else {
        // Log error if headers already sent
        error_log("Redirect failed: Headers already sent. Cannot redirect to " . $final_url);
        // Optionally display a JavaScript redirect as a fallback (less reliable)
        // echo '<script>window.location.href="' . $final_url . '";</script>';
        exit; // Still exit script execution
    }
}

// Function display alert
function display_alert() {
    if (isset($_SESSION['message'])) {
        $type = $_SESSION['message']['type'] ?? 'info';
        $text = $_SESSION['message']['text'] ?? '';
        $safe_type = htmlspecialchars($type, ENT_QUOTES, 'UTF-8');
        $safe_text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
        echo "<div class='alert alert-{$safe_type} alert-dismissible fade show mt-3' role='alert'>";
        echo $safe_text;
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        echo '</div>';
        unset($_SESSION['message']);
    }
}

// --- Require Core Function/Class Files ---
// Use __DIR__ to get the directory of the current file (config/)
// Then go up one level ('/../') to the project root, then into 'src/'
require_once __DIR__ . '/../src/functions.php';
require_once __DIR__ . '/../src/allocation.php'; // Include allocation functions
// --- End Require ---

?>