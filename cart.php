<?php
// pages/cart.php
requireCustomerLogin(); // Require login to view DB cart

$page_title = 'ตะกร้าสินค้า';
$current_page = 'cart';

$cart_id = $_SESSION['cart_id'] ?? null;
$cart_items = [];
$total_amount = 0;

if ($cart_id) {
    try {
        // Fetch cart items joined with product details
        $stmt = $pdo->prepare("SELECT ci.product_id, ci.quantity, p.name, p.price -- , p.image_url (optional)
                               FROM cartitems ci
                               JOIN Products p ON ci.product_id = p.product_id
                               WHERE ci.cart_id = ?
                               ORDER BY p.name"); // Order items alphabetically
        $stmt->execute([$cart_id]);
        $cart_items = $stmt->fetchAll();

        // Calculate total amount based on fetched items
        foreach ($cart_items as $item) {
            $total_amount += $item['price'] * $item['quantity'];
        }

    } catch (PDOException $e) {
         $_SESSION['message'] = ['type' => 'danger', 'text' => 'เกิดข้อผิดพลาดในการโหลดข้อมูลตะกร้า'];
         error_log("Cart Page Fetch Error: " . $e->getMessage());
         $cart_items = []; // Ensure it's an empty array on error
         $total_amount = 0;
    }
} else {
     // Handle case where cart_id is missing from session (shouldn't happen if login is required)
     $_SESSION['message'] = ['type' => 'warning', 'text' => 'ไม่พบข้อมูลตะกร้าของคุณ โปรดลองเข้าสู่ระบบใหม่'];
}


?>

<h1 class="mb-4"><?php echo e($page_title); ?></h1>

<?php // Display alerts if any ?>
<?php // display_alert(); // Moved display_alert() inside header.php ?>


<?php if ($cart_items): ?>
    <div class="table-responsive">
        <table class="table align-middle table-hover">
             <thead class="table-light">
                <tr>
                    <th scope="col" style="width: 55%;">สินค้า</th>
                    <th scope="col" class="text-end">ราคาต่อหน่วย</th>
                    <?php /* Uncomment if quantity > 1
                    <th scope="col" class="text-center" style="width: 120px;">จำนวน</th>
                    */ ?>
                    <th scope="col" class="text-end">ราคารวม</th>
                    <th scope="col" class="text-center">ลบ</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cart_items as $item):
                    $subtotal = $item['price'] * $item['quantity'];
                    // Sanitization happens within e() and formatPrice()
                    $product_id_safe = e($item['product_id']);
                    $product_name_safe = e($item['name']);
                    $price_formatted = formatPrice($item['price']);
                    $subtotal_formatted = formatPrice($subtotal);
                ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <div>
                                    <a href="<?php echo BASE_URL . '/product/' . $product_id_safe; ?>" class="text-decoration-none text-dark fw-bold"><?php echo $product_name_safe; ?></a>
                                </div>
                            </div>
                        </td>
                        <td class="text-end"><?php echo $price_formatted; ?></td>
                         <?php /* Uncomment if quantity > 1
                        <td class="text-center">
                             <form action="<?php echo BASE_URL; ?>/cart-action" method="post" class="d-inline-block">
                                <input type="hidden" name="action" value="update">
                                <input type="hidden" name="product_id" value="<?php echo $product_id_safe; ?>">
                                <div class="input-group input-group-sm" style="width: 110px;">
                                    <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" min="1" class="form-control text-center">
                                    <button type="submit" class="btn btn-outline-secondary" title="อัปเดต"><i class="bi bi-check-lg"></i></button>
                                </div>
                            </form>
                        </td>
                         */ ?>
                        <td class="text-end fw-medium"><?php echo $subtotal_formatted; ?></td>
                        <td class="text-center">
                            <form action="<?php echo BASE_URL; ?>/cart-action" method="post" class="d-inline">
                                <input type="hidden" name="action" value="remove">
                                <input type="hidden" name="product_id" value="<?php echo $product_id_safe; ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="ลบสินค้าชิ้นนี้"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="row justify-content-between mt-4">
         <div class="col-md-6 col-lg-8 mb-3 mb-md-0">
             <form action="<?php echo BASE_URL; ?>/cart-action" method="post" class="d-inline" onsubmit="return confirm('ต้องการล้างตะกร้าสินค้าทั้งหมดใช่หรือไม่?');">
                <input type="hidden" name="action" value="clear">
                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-cart-x"></i> ล้างตะกร้าสินค้า</button>
            </form>
             <a href="<?php echo BASE_URL; ?>/products" class="btn btn-sm btn-outline-secondary ms-2"><i class="bi bi-arrow-left"></i> เลือกซื้อสินค้าต่อ</a>
        </div>
        <div class="col-md-6 col-lg-4">
             <div class="card shadow-sm border-primary">
                <div class="card-body">
                    <h5 class="card-title mb-3 text-primary">สรุปรายการ</h5>
                    <div class="d-flex justify-content-between mb-2">
                        <span>ยอดรวม</span>
                        <span class="fw-medium"><?php echo formatPrice($total_amount); ?> บาท</span>
                    </div>
                    <hr>
                     <div class="d-flex justify-content-between fw-bold fs-5 mb-3">
                        <span>ยอดสุทธิ</span>
                        <span><?php echo formatPrice($total_amount); ?> บาท</span>
                    </div>
                    <div class="d-grid gap-2">
                        <a href="<?php echo BASE_URL; ?>/checkout" class="btn btn-primary btn-lg">ดำเนินการสั่งซื้อ <i class="bi bi-arrow-right"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php else: // Cart is empty ?>
    <div class="alert alert-info text-center" role="alert">
      <i class="bi bi-info-circle me-2"></i> ตะกร้าสินค้าของคุณว่างเปล่า <a href="<?php echo BASE_URL; ?>/products" class="alert-link">เลือกซื้อสินค้า</a>
    </div>
<?php endif; ?>