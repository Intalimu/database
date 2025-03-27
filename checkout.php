<?php
// pages/checkout.php
requireCustomerLogin(); // ต้อง Login ก่อน

// --- NEW: Read cart items from DATABASE ---
$cart_id = $_SESSION['cart_id'] ?? null;
$cart_items_db = []; // Array to store items from DB
$total_amount = 0;    // Initialize total amount

if (!$cart_id) {
    // This really shouldn't happen if requireCustomerLogin() works
    error_log("Checkout Error: cart_id missing from session for customer {$_SESSION['customer_id']}.");
    $_SESSION['message'] = ['type' => 'danger', 'text' => 'เกิดข้อผิดพลาด ไม่พบตะกร้าสินค้า'];
    redirect('/cart');
}

try {
    // Fetch cart items joined with product details for display and price
    $stmt_cart_db = $pdo->prepare("SELECT ci.product_id, ci.quantity, p.name, p.price
                                FROM cartitems ci
                                JOIN Products p ON ci.product_id = p.product_id
                                WHERE ci.cart_id = ? AND p.status = 'พร้อมขาย'"); // Ensure product is still available
    $stmt_cart_db->execute([$cart_id]);
    $cart_items_db = $stmt_cart_db->fetchAll();

    // Redirect if cart is empty in the database
    if (empty($cart_items_db)) {
        $_SESSION['message'] = ['type' => 'warning', 'text' => 'ตะกร้าสินค้าของคุณว่างเปล่า'];
        redirect('/cart');
    }

    // Calculate total amount based on current prices from fetched items
    foreach ($cart_items_db as $item) {
         // Optional: Add another stock check here for extra safety
         // $stock_stmt = $pdo->prepare("SELECT COUNT(*) FROM StreamingAccounts WHERE product_id = ? AND status = 'พร้อมใช้งาน'");
         // $stock_stmt->execute([$item['product_id']]);
         // if ($stock_stmt->fetchColumn() < $item['quantity']) { ... redirect with error ... }

        $total_amount += $item['price'] * $item['quantity'];
    }

} catch (PDOException $e) {
    $_SESSION['message'] = ['type' => 'danger', 'text' => 'เกิดข้อผิดพลาดในการโหลดข้อมูลตะกร้าสำหรับ Checkout'];
    error_log("Checkout Cart Fetch Error: " . $e->getMessage());
    redirect('/cart'); // Redirect to cart page on error
}
// --- END NEW: Read cart items from DATABASE ---


// --- Fetch Customer Info (Keep this part) ---
try {
    $stmt_cust = $pdo->prepare("SELECT email, username FROM Customers WHERE customer_id = ?");
    $stmt_cust->execute([$_SESSION['customer_id']]);
    $customer_info = $stmt_cust->fetch();
} catch(PDOException $e){
    $customer_info = ['email' => 'N/A', 'username' => $_SESSION['username']]; // Fallback
    error_log("Checkout Customer Fetch Error: " . $e->getMessage());
}
?>

<h1 class="mb-4">ยืนยันการสั่งซื้อและชำระเงิน</h1>

<div class="row g-5">
    <div class="col-md-7 col-lg-8 order-md-last">
        <h4 class="d-flex justify-content-between align-items-center mb-3">
          <span class="text-primary">รายการสินค้า</span>
          <?php // Count items fetched from DB ?>
          <span class="badge bg-primary rounded-pill"><?php echo count($cart_items_db); ?></span>
        </h4>
        <ul class="list-group mb-3">
            <?php // --- NEW: Loop through items from DB --- ?>
            <?php foreach ($cart_items_db as $item):
                $subtotal_item = $item['price'] * $item['quantity'];
            ?>
            <li class="list-group-item d-flex justify-content-between lh-sm">
                <div>
                  <h6 class="my-0"><?php echo e($item['name']); ?></h6>
                  <small class="text-muted">จำนวน: <?php echo $item['quantity']; ?></small>
                </div>
                <span class="text-muted"><?php echo formatPrice($subtotal_item); ?> บาท</span>
            </li>
            <?php endforeach; ?>
            <?php // --- END NEW Loop --- ?>
           <li class="list-group-item d-flex justify-content-between bg-light">
                <span class="fw-bold">ยอดรวมทั้งสิ้น</span>
                 <?php // Use total calculated from DB items ?>
                <strong class="fw-bold"><?php echo formatPrice($total_amount); ?> บาท</strong>
          </li>
        </ul>

        <hr class="my-4">

         <h4 class="mb-3">วิธีการชำระเงิน</h4>

         <?php // --- CHANGE ACTION HERE to use the correct route --- ?>
         <form action="<?php echo BASE_URL; ?>/order-process" method="POST" id="checkout-form">
            <div class="my-3">
              <div class="form-check">
                <input id="bank_transfer" name="payment_method" type="radio" class="form-check-input" value="โอนเงิน" checked required>
                <label class="form-check-label" for="bank_transfer">โอนเงินผ่านบัญชีธนาคาร</label>
              </div>
               <div class="mt-3 p-3 bg-light border rounded">
                    <p class="mb-1">กรุณาโอนเงินมาที่:</p>
                    <p class="mb-1"><strong>ธนาคาร:</strong> XXX Bank</p>
                    <p class="mb-1"><strong>เลขที่บัญชี:</strong> 123-4-56789-0</p>
                    <p class="mb-0"><strong>ชื่อบัญชี:</strong> Streaming Shop</p>
                    <small class="text-danger">* หลังจากกด "ยืนยันคำสั่งซื้อ" กรุณาอัปโหลดหลักฐานการโอนเงินในหน้าถัดไป</small>
                </div>
            </div>

            <hr class="my-4">

            <button class="w-100 btn btn-primary btn-lg" type="submit">ยืนยันคำสั่งซื้อ</button>
         </form>

    </div>
    <div class="col-md-5 col-lg-4">
         <h4 class="mb-3">ข้อมูลผู้สั่งซื้อ</h4>
         <p><strong>ชื่อผู้ใช้:</strong> <?php echo e($customer_info['username']); ?></p>
         <p><strong>อีเมล:</strong> <?php echo e($customer_info['email']); ?></p>
         <p><small>สินค้าจะถูกจัดส่ง (ข้อมูลบัญชี) ไปยังระบบและสามารถดูได้ในประวัติการสั่งซื้อหลังการชำระเงินได้รับการยืนยัน</small></p>
         <hr>
          <a href="<?php echo BASE_URL; ?>/cart" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> กลับไปแก้ไขตะกร้า</a>
    </div>
</div>