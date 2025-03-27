<?php
// actions/cart_action.php
define('APP_BASE_PATH', dirname(__DIR__));
require_once APP_BASE_PATH . '/config/db_connect.php';

// --- Require Login for all Database Cart Actions ---
requireCustomerLogin();
// --- End Require Login ---

// Get cart_id from session (should be set during login)
$cart_id = $_SESSION['cart_id'] ?? null;
if (!$cart_id) {
    error_log("Cart Action Error: cart_id missing from session for customer {$_SESSION['customer_id']}.");
    $_SESSION['message'] = ['type' => 'danger', 'text' => 'เกิดข้อผิดพลาดกับตะกร้าสินค้า (ไม่พบ ID)'];
    redirect('/'); // Redirect home or to login
}

// Check if request method is POST and action is set
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['action'])) {
    redirect('/'); // Redirect if accessed incorrectly
}

$action = $_POST['action'];
$product_id = $_POST['product_id'] ?? null;
$quantity = 1; // Force quantity 1 for account products

$redirect_url = '/cart'; // Default redirect URL

try {
    // --- Action: Add ---
    if ($action === 'add' && $product_id) {

        // 1. Check Product Validity and Stock
        $prod_stmt = $pdo->prepare("SELECT name, status FROM Products WHERE product_id = ?");
        $prod_stmt->execute([$product_id]);
        $product_info = $prod_stmt->fetch();

        if (!$product_info || $product_info['status'] !== 'พร้อมขาย') {
            $_SESSION['message'] = ['type' => 'danger', 'text' => 'ขออภัย ไม่พบสินค้าหรือสินค้าหมดสต็อก'];
            redirect('/products');
        }

        $stock_stmt = $pdo->prepare("SELECT COUNT(*) FROM StreamingAccounts WHERE product_id = ? AND status = 'พร้อมใช้งาน'");
        $stock_stmt->execute([$product_id]);
        $stock_count = $stock_stmt->fetchColumn();

        // 2. Check quantity already in DB cart for this user/cart
        $cart_qty_stmt = $pdo->prepare("SELECT quantity FROM cartitems WHERE cart_id = ? AND product_id = ?");
        $cart_qty_stmt->execute([$cart_id, $product_id]);
        $quantity_in_cart = (int) $cart_qty_stmt->fetchColumn();

        // 3. Check if stock is sufficient
        if ($stock_count <= $quantity_in_cart) {
            $_SESSION['message'] = ['type' => 'warning', 'text' => 'ขออภัย สินค้า "' . e($product_info['name']) . '" ในสต็อกไม่เพียงพอ'];
            redirect($_POST['redirect_to'] ?? '/products');
        }

        // 4. Add or Update Cart Item
        if ($quantity_in_cart > 0) {
            // Item already exists - For accounts, just show message
            $_SESSION['message'] = ['type' => 'info', 'text' => 'คุณมีสินค้า "' . e($product_info['name']) . '" ในตะกร้าแล้ว'];
            // If allowing > 1 quantity:
            // $update_stmt = $pdo->prepare("UPDATE cartitems SET quantity = quantity + ? WHERE cart_id = ? AND product_id = ?");
            // $update_stmt->execute([$quantity, $cart_id, $product_id]);
            // $_SESSION['message'] = ['type' => 'success', 'text' => 'เพิ่มจำนวนสินค้าในตะกร้าแล้ว'];
        } else {
            // Item does not exist, INSERT new row
            $insert_stmt = $pdo->prepare("INSERT INTO cartitems (cart_id, product_id, quantity) VALUES (?, ?, ?)");
            $insert_stmt->execute([$cart_id, $product_id, $quantity]); // Insert with quantity 1
            $_SESSION['message'] = ['type' => 'success', 'text' => 'เพิ่ม "' . e($product_info['name']) . '" ลงตะกร้าแล้ว'];
        }
        $redirect_url = $_POST['redirect_to'] ?? '/cart'; // Redirect back or to cart


    // --- Action: Remove ---
    } elseif ($action === 'remove' && $product_id) {
        $delete_stmt = $pdo->prepare("DELETE FROM cartitems WHERE cart_id = ? AND product_id = ?");
        $delete_stmt->execute([$cart_id, $product_id]);
        if ($delete_stmt->rowCount() > 0) {
            $_SESSION['message'] = ['type' => 'warning', 'text' => 'ลบสินค้าออกจากตะกร้าแล้ว'];
        }
        $redirect_url = '/cart';


    // --- Action: Update (If quantity > 1 is allowed) ---
    } elseif ($action === 'update' && $product_id) {
        $new_quantity = isset($_POST['quantity']) ? max(1, (int)$_POST['quantity']) : 1;

        // Re-check stock for the new quantity
        $stock_stmt = $pdo->prepare("SELECT COUNT(*) FROM StreamingAccounts WHERE product_id = ? AND status = 'พร้อมใช้งาน'");
        $stock_stmt->execute([$product_id]);
        $stock_count = $stock_stmt->fetchColumn();

        if ($stock_count < $new_quantity) {
             $_SESSION['message'] = ['type' => 'warning', 'text' => 'สต็อกไม่พอสำหรับจำนวน '.$new_quantity];
        } else {
            $update_stmt = $pdo->prepare("UPDATE cartitems SET quantity = ? WHERE cart_id = ? AND product_id = ?");
            $update_stmt->execute([$new_quantity, $cart_id, $product_id]);
            $_SESSION['message'] = ['type' => 'info', 'text' => 'อัปเดตจำนวนสินค้าแล้ว'];
        }
        $redirect_url = '/cart';


    // --- Action: Clear ---
    } elseif ($action === 'clear') {
        $clear_stmt = $pdo->prepare("DELETE FROM cartitems WHERE cart_id = ?");
        $clear_stmt->execute([$cart_id]);
        $_SESSION['message'] = ['type' => 'info', 'text' => 'ล้างตะกร้าสินค้าแล้ว'];
        $redirect_url = '/cart';

    } else {
         // Unknown action
         $_SESSION['message'] = ['type' => 'danger', 'text' => 'การดำเนินการกับตะกร้าไม่ถูกต้อง'];
         $redirect_url = '/cart';
    }


} catch (PDOException $e) {
     $_SESSION['message'] = ['type' => 'danger', 'text' => 'เกิดข้อผิดพลาดของฐานข้อมูลในการจัดการตะกร้า'];
     error_log("Cart Action DB Error: " . $e->getMessage());
     // Redirect to cart or home on error
     $redirect_url = '/cart';
}

// Final Redirect
redirect($redirect_url);
?>