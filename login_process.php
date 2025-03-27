<?php
// actions/login_process.php
define('APP_BASE_PATH', dirname(__DIR__));
require_once APP_BASE_PATH . '/config/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/');
}

$login_identity = trim($_POST['login_identity'] ?? '');
$password = $_POST['password'] ?? '';
$redirect_to = $_POST['redirect_to'] ?? '/';

if (empty($login_identity) || empty($password)) {
    $_SESSION['message'] = ['type' => 'danger', 'text' => 'กรุณากรอกชื่อผู้ใช้/อีเมล และรหัสผ่าน'];
    redirect('/login');
}

try {
    // Find user by username or email
    $stmt = $pdo->prepare("SELECT customer_id, username, password FROM Customers WHERE username = ? OR email = ?");
    $stmt->execute([$login_identity, $login_identity]);
    $user = $stmt->fetch();

    if ($user && verifyPassword($password, $user['password'])) {
        // Password is correct

        // *** NEW: Fetch Cart ID ***
        $stmt_cart = $pdo->prepare("SELECT cart_id FROM Carts WHERE customer_id = ?");
        $stmt_cart->execute([$user['customer_id']]);
        $cart_id = $stmt_cart->fetchColumn();
        // *** END NEW ***

        // Safety check: if cart doesn't exist (e.g., old user before cart implementation)
        if (!$cart_id) {
             error_log("WARNING: Cart not found for customer {$user['customer_id']} during login. Attempting to create one.");
             // Try to create a cart (optional, for robustness)
             try {
                 $cart_id = generateUniqueID('CART');
                 $create_cart_stmt = $pdo->prepare("INSERT INTO Carts (cart_id, customer_id, cart_created_at) VALUES (?, ?, NOW())");
                 $create_cart_stmt->execute([$cart_id, $user['customer_id']]);
             } catch (PDOException $cart_e) {
                 error_log("CRITICAL: Failed to create cart for customer {$user['customer_id']} during login: " . $cart_e->getMessage());
                 // Redirect with error or handle differently?
                 $_SESSION['message'] = ['type' => 'danger', 'text' => 'เกิดข้อผิดพลาดในการเข้าถึงตะกร้าสินค้าของคุณ'];
                 redirect('/login');
             }
        }

        // Set session variables
        session_regenerate_id(true);
        $_SESSION['customer_id'] = $user['customer_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['cart_id'] = $cart_id; // *** Store cart_id in session ***

        // Prevent redirecting to external URLs or sensitive paths
        if (strpos($redirect_to, BASE_URL) !== 0 || strpos($redirect_to, '..') !== false || strpos($redirect_to, '/admin') !== false) {
            $redirect_to = '/'; // Default to home if redirect URL seems unsafe
        }
        redirect($redirect_to);

    } else {
        // Invalid credentials
        $_SESSION['message'] = ['type' => 'danger', 'text' => 'ชื่อผู้ใช้/อีเมล หรือรหัสผ่านไม่ถูกต้อง'];
        redirect('/login');
    }

} catch (PDOException $e) {
    error_log("Login Error: " . $e->getMessage());
    $_SESSION['message'] = ['type' => 'danger', 'text' => 'เกิดข้อผิดพลาดในการเข้าสู่ระบบ โปรดลองอีกครั้ง'];
    redirect('/login');
}
?>