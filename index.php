<?php
// public/index.php
define('APP_BASE_PATH', dirname(__DIR__));
require_once APP_BASE_PATH . '/config/db_connect.php';

// --- Routing Logic ---
$base_uri = parse_url(BASE_URL, PHP_URL_PATH);
$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($base_uri && $base_uri !== '/' && strpos($request_uri, $base_uri) === 0) {
    $route = substr($request_uri, strlen($base_uri));
} else {
    $route = $request_uri;
}
$route = trim($route, '/');
$request_method = $_SERVER['REQUEST_METHOD']; // Get HTTP method

// Default page
$page_file_to_load = APP_BASE_PATH . '/pages/home.php';
$page_title = 'หน้าแรก';
$current_page = 'home';
$item_id = null;

// Whitelist allowed pages/actions
$allowed_routes = [
    // Format: 'METHOD/ROUTE_KEY' => ['config']
    'GET/'                      => ['file' => 'home.php',             'title' => 'หน้าแรก',                'menu' => 'home'],
    'GET/products'              => ['file' => 'products.php',           'title' => 'สินค้าทั้งหมด',            'menu' => 'products'],
    'GET/product'               => ['file' => 'product_details.php',    'title' => 'รายละเอียดสินค้า',       'menu' => 'products',        'needs_id' => true],
    'GET/cart'                  => ['file' => 'cart.php',               'title' => 'ตะกร้าสินค้า',            'menu' => 'cart'],
    'GET/checkout'              => ['file' => 'checkout.php',           'title' => 'ดำเนินการสั่งซื้อ',       'menu' => 'cart'],
    'GET/order-confirmation'    => ['file' => 'order_confirmation.php', 'title' => 'ยืนยันการสั่งซื้อ',        'menu' => 'order-history'],
    'GET/payment-upload'        => ['file' => 'payment_upload.php',     'title' => 'แจ้งชำระเงิน',           'menu' => 'cart'],
    'GET/register'              => ['file' => 'register.php',           'title' => 'สมัครสมาชิก',            'menu' => 'register'],
    'GET/login'                 => ['file' => 'login.php',              'title' => 'เข้าสู่ระบบ',             'menu' => 'login'],
    'GET/order-history'         => ['file' => 'order_history.php',      'title' => 'ประวัติการสั่งซื้อ',       'menu' => 'order-history'],

    // --- POST Actions ---
    'POST/register-process'     => ['action_file' => '../actions/register_process.php'], // Path relative to this index.php
    'POST/login-process'        => ['action_file' => '../actions/login_process.php'],
    'POST/cart-action'          => ['action_file' => '../actions/cart_action.php'],
    'POST/order-process'        => ['action_file' => '../actions/order_process.php'],
    'POST/payment-upload-process' => ['action_file' => '../actions/payment_upload_process.php'],

    // --- Logout (usually GET) ---
    'GET/logout'                => ['action_file' => '../actions/logout.php'], // Handle logout

    // --- 404 ---
    'GET/404'                   => ['file' => '404.php',                'title' => 'ไม่พบหน้า',             'menu' => ''], // Internal 404 key
];

// Determine Route Key
$route_parts = explode('/', $route);
$route_key = $route_parts[0];

// Construct Method/Route Key
$method_route_key = $request_method . '/' . $route_key;
// Handle root path ('')
if ($route_key === '') {
    $method_route_key = $request_method . '/';
}

$route_config = null;
$is_action = false;

// Find matching route
if (isset($allowed_routes[$method_route_key])) {
    $route_config = $allowed_routes[$method_route_key];

    // Check if it's an action file or a page file
    if (isset($route_config['action_file'])) {
        $is_action = true;
        $action_file_path = __DIR__ . '/' . $route_config['action_file']; // Build path from index.php location
    } else {
        // Handle ID requirement for pages like 'product'
        if ($route_config['needs_id'] ?? false) {
            $item_id = $route_parts[1] ?? null;
            if (!$item_id) {
                $method_route_key = 'GET/404'; // Force 404 if ID missing
                $route_config = $allowed_routes[$method_route_key];
            }
        }
        $page_file_to_load = APP_BASE_PATH . '/pages/' . $route_config['file'];
        $page_title = $route_config['title'];
        $current_page = $route_config['menu'];
    }

} else {
    // Route not found in allowed list for the specific method
    $method_route_key = 'GET/404'; // Default to GET 404 page
    $route_config = $allowed_routes[$method_route_key];
    $page_file_to_load = APP_BASE_PATH . '/pages/' . $route_config['file'];
    $page_title = $route_config['title'];
    $current_page = $route_config['menu'];
    http_response_code(404); // Set 404 status
}


// --- Execute Action or Load Page ---
if ($is_action) {
    // If it's an action, include the action file and let it handle the request (including redirects)
    if (file_exists($action_file_path)) {
        require_once $action_file_path;
    } else {
        http_response_code(500);
        error_log("Routing Error: Action file not found - " . $action_file_path);
        // Display a generic error, don't include header/footer here as action files shouldn't output HTML directly
        die("Internal Server Error - Action file missing.");
    }
} else {
    // If it's a page, load header, page content, and footer
    if (file_exists($page_file_to_load)) {
        require_once APP_BASE_PATH . '/templates/header.php';
        require_once $page_file_to_load;
        require_once APP_BASE_PATH . '/templates/footer.php';
    } else {
        http_response_code(500);
        error_log("Routing Error: Page file not found - " . $page_file_to_load);
        // Load header/footer for error page
        require_once APP_BASE_PATH . '/templates/header.php';
        echo "<div class='container mt-5'><div class='alert alert-danger'><h1>500 Internal Server Error</h1><p>เกิดข้อผิดพลาดภายในระบบ</p></div></div>";
        require_once APP_BASE_PATH . '/templates/footer.php';
    }
}
// --- End Execution ---
?>