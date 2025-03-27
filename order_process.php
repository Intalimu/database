<?php
// actions/order_process.php
define('APP_BASE_PATH', dirname(__DIR__));
require_once APP_BASE_PATH . '/config/db_connect.php';
requireCustomerLogin(); // Ensure user is logged in

// Get cart_id from session
$cart_id = $_SESSION['cart_id'] ?? null;
if (!$cart_id) {
    error_log("Order Process Error: cart_id not found in session for customer {$_SESSION['customer_id']}");
    $_SESSION['message'] = ['type' => 'danger', 'text' => 'เกิดข้อผิดพลาดกับตะกร้าสินค้าของคุณ'];
    redirect('/cart');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Get payment method
    $payment_method = $_POST['payment_method'] ?? null;
    if ($payment_method !== 'โอนเงิน') { // Only allow bank transfer for now
        $_SESSION['message'] = ['type' => 'danger', 'text' => 'วิธีการชำระเงินไม่ถูกต้อง'];
        redirect('/checkout');
    }

    $customer_id = $_SESSION['customer_id'];
    $order_id = generateUniqueID('O');
    $total_amount = 0;
    $cart_items_from_db = []; // To store items read from DB

    // --- Read items from Database Cart ---
    try {
        $stmt_items = $pdo->prepare("SELECT ci.product_id, ci.quantity, p.price -- Read current price
                                    FROM cartitems ci
                                    JOIN Products p ON ci.product_id = p.product_id
                                    WHERE ci.cart_id = ?");
        $stmt_items->execute([$cart_id]);
        $cart_items_from_db = $stmt_items->fetchAll();

        if (empty($cart_items_from_db)) {
            $_SESSION['message'] = ['type' => 'warning', 'text' => 'ตะกร้าสินค้าของคุณว่างเปล่า ไม่สามารถสั่งซื้อได้'];
            redirect('/cart');
        }

        // Calculate total amount based on DB items and current prices
        foreach ($cart_items_from_db as $item) {
             // Pre-check stock again before transaction (optional but safer)
             $stock_stmt = $pdo->prepare("SELECT COUNT(*) FROM StreamingAccounts WHERE product_id = ? AND status = 'พร้อมใช้งาน'");
             $stock_stmt->execute([$item['product_id']]);
             if ($stock_stmt->fetchColumn() < $item['quantity']) {
                 $_SESSION['message'] = ['type' => 'danger', 'text' => 'ขออภัย สินค้าบางรายการมีไม่เพียงพอในสต็อกขณะนี้'];
                 redirect('/cart');
             }
             // Calculate total
             $total_amount += $item['price'] * $item['quantity'];
        }

    } catch (PDOException $e) {
        error_log("Order Process - Read Cart DB Error: " . $e->getMessage());
        $_SESSION['message'] = ['type' => 'danger', 'text' => 'เกิดข้อผิดพลาดในการอ่านข้อมูลตะกร้า'];
        redirect('/checkout');
    }
    // --- End Read items ---


    // --- Start Transaction ---
    $pdo->beginTransaction();
    try {
        // 1. Insert into Orders
        $order_status = 'รอชำระเงิน';
        $cart_id_in_order = $cart_id; // Keep this NULL as discussed

        $stmt_order = $pdo->prepare("INSERT INTO Orders (order_id, customer_id, cart_id, order_date, order_status, total_amount) VALUES (?, ?, ?, NOW(), ?, ?)");
        $stmt_order->execute([$order_id, $customer_id, $cart_id_in_order, $order_status, $total_amount]); // <-- ใช้ $cart_id_in_order ตรงนี้

        // 2. Insert into OrderDetails (using data read from DB cart)
        $stmt_detail = $pdo->prepare("INSERT INTO OrderDetails (order_id, product_id, quantity, price_at_order, subtotal) VALUES (?, ?, ?, ?, ?)");
        foreach ($cart_items_from_db as $item) {
            $subtotal = $item['price'] * $item['quantity']; // Use current price read from DB
            $stmt_detail->execute([$order_id, $item['product_id'], $item['quantity'], $item['price'], $subtotal]);
        }

        // 3. *** Delete items from the Database Cart ***
        $stmt_delete_cart = $pdo->prepare("DELETE FROM cartitems WHERE cart_id = ?");
        $stmt_delete_cart->execute([$cart_id]);
        // *** End Delete from Cart ***

        // 4. Commit Transaction
        $pdo->commit();

        // 5. Redirect to payment upload page
        $_SESSION['message'] = ['type' => 'success', 'text' => 'สร้างคำสั่งซื้อสำเร็จ! กรุณาแจ้งชำระเงิน'];
        redirect('/payment-upload?order_id=' . $order_id);

    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Order Process Transaction Error: " . $e->getMessage());
        $_SESSION['message'] = ['type' => 'danger', 'text' => 'เกิดข้อผิดพลาดในการสร้างคำสั่งซื้อ โปรดลองอีกครั้ง'];
        redirect('/checkout');
    }

} else {
    redirect('/');
}
?>