<?php
// actions/register_process.php
define('APP_BASE_PATH', dirname(__DIR__));
require_once APP_BASE_PATH . '/config/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/');
}

$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

$_SESSION['form_data'] = ['username' => $username, 'email' => $email];

$errors = [];
if (empty($username)) { $errors[] = 'กรุณากรอกชื่อผู้ใช้'; }
if (empty($email)) { $errors[] = 'กรุณากรอกอีเมล'; }
if (empty($password)) { $errors[] = 'กรุณากรอกรหัสผ่าน'; }
if (empty($confirm_password)) { $errors[] = 'กรุณายืนยันรหัสผ่าน'; }
if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors[] = 'รูปแบบอีเมลไม่ถูกต้อง'; }
if (!empty($password) && strlen($password) < 6) { $errors[] = 'รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร'; }
if ($password !== $confirm_password) { $errors[] = 'รหัสผ่านและการยืนยันรหัสผ่านไม่ตรงกัน'; }

if (!empty($errors)) {
    $_SESSION['message'] = ['type' => 'danger', 'text' => implode('<br>', $errors)];
    redirect('/register');
}

try {
    $stmt_check = $pdo->prepare("SELECT customer_id FROM Customers WHERE username = ? OR email = ?");
    $stmt_check->execute([$username, $email]);
    if ($stmt_check->fetch()) {
        $_SESSION['message'] = ['type' => 'danger', 'text' => 'ชื่อผู้ใช้หรืออีเมลนี้มีอยู่ในระบบแล้ว'];
        redirect('/register');
    }

    // --- Start Transaction ---
    $pdo->beginTransaction();

    $customer_id = generateUniqueID('C');
    $cart_id = generateUniqueID('CART'); // *** Generate Cart ID ***
    $hashed_password = hashPassword($password);

    // Insert into Customers
    $stmt_cust = $pdo->prepare("INSERT INTO Customers (customer_id, email, username, password, registration_date) VALUES (?, ?, ?, ?, NOW())");
    $stmt_cust->execute([$customer_id, $email, $username, $hashed_password]);

    // *** NEW: Insert into Carts ***
    $stmt_cart = $pdo->prepare("INSERT INTO Carts (cart_id, customer_id, cart_created_at) VALUES (?, ?, NOW())");
    $stmt_cart->execute([$cart_id, $customer_id]);
    // *** END NEW ***

    $pdo->commit();
    // --- End Transaction ---

    unset($_SESSION['form_data']);
    $_SESSION['message'] = ['type' => 'success', 'text' => 'สมัครสมาชิกสำเร็จ! กรุณาเข้าสู่ระบบ'];
    redirect('/login');

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Registration Error: " . $e->getMessage());
    $_SESSION['message'] = ['type' => 'danger', 'text' => 'เกิดข้อผิดพลาดในการสมัครสมาชิก โปรดลองอีกครั้ง'];
    redirect('/register');
}
?>