<?php
// pages/order_confirmation.php
requireCustomerLogin();

$order_id = $_GET['order_id'] ?? null;
$page_title = 'รายละเอียดคำสั่งซื้อ'; // Default title
$current_page = 'order-history'; // Highlight history menu

if (!$order_id) {
    $_SESSION['message'] = ['type' => 'danger', 'text' => 'ไม่พบรหัสคำสั่งซื้อ'];
    redirect('/order-history');
}

$order = null;
$order_details = [];
$allocated_accounts = []; // To store accounts linked to this order

try {
    // Fetch Order Info
    $stmt_order = $pdo->prepare("SELECT * FROM Orders WHERE order_id = ? AND customer_id = ?");
    $stmt_order->execute([$order_id, $_SESSION['customer_id']]);
    $order = $stmt_order->fetch();

    if (!$order) {
        $_SESSION['message'] = ['type' => 'danger', 'text' => 'ไม่พบคำสั่งซื้อ หรือไม่ใช่คำสั่งซื้อของคุณ'];
        redirect('/order-history');
    }

    $page_title = 'รายละเอียด Order: ' . e($order_id); // Update title

    // Fetch Order Details (Items ordered)
    $stmt_details = $pdo->prepare("SELECT od.*, p.name as product_name
                                   FROM OrderDetails od
                                   JOIN Products p ON od.product_id = p.product_id
                                   WHERE od.order_id = ?");
    $stmt_details->execute([$order_id]);
    $order_details = $stmt_details->fetchAll();

    // Fetch Allocated Accounts if order is completed
    if ($order['order_status'] === 'เสร็จสมบูรณ์') {
        $stmt_items = $pdo->prepare("SELECT oi.order_detail_id, sa.username, sa.password as account_password, sa.streaming_account_id
                                     FROM OrderItems oi
                                     JOIN StreamingAccounts sa ON oi.streaming_account_id = sa.streaming_account_id
                                     JOIN OrderDetails od ON oi.order_detail_id = od.order_detail_id
                                     WHERE od.order_id = ?");
         $stmt_items->execute([$order_id]);
         $items_data = $stmt_items->fetchAll();
         // Group accounts by order_detail_id for easier display
         foreach($items_data as $item) {
             $allocated_accounts[$item['order_detail_id']][] = $item;
         }

         // --- Clear the temporary session variable used in allocation (Demo only) ---
         // This is NOT a secure way to handle credentials.
         unset($_SESSION['last_allocated_accounts_' . $order_id]);
         // --- End Demo Clear ---

    }


} catch (PDOException $e) {
    $_SESSION['message'] = ['type' => 'danger', 'text' => 'เกิดข้อผิดพลาดในการดึงข้อมูลคำสั่งซื้อ'];
    error_log("Order Confirmation Fetch Error (Order ID: {$order_id}): " . $e->getMessage());
    redirect('/order-history');
}

?>

<h1 class="mb-4"><?php echo $page_title; ?></h1>

<div class="card mb-4">
    <div class="card-header">สรุปข้อมูลคำสั่งซื้อ</div>
    <div class="card-body">
        <p><strong>Order ID:</strong> <?php echo e($order['order_id']); ?></p>
        <p><strong>วันที่สั่งซื้อ:</strong> <?php echo date('d/m/Y H:i', strtotime($order['order_date'])); ?></p>
        <p><strong>ยอดรวม:</strong> <?php echo formatPrice($order['total_amount']); ?> บาท</p>
        <p><strong>สถานะ:</strong>
            <?php
                $status_class = 'secondary';
                if ($order['order_status'] === 'เสร็จสมบูรณ์') $status_class = 'success';
                if ($order['order_status'] === 'รอชำระเงิน' || $order['order_status'] === 'รอตรวจสอบการชำระเงิน') $status_class = 'warning';
                if (str_contains($order['order_status'], 'ล้มเหลว') || $order['order_status'] === 'ยกเลิก') $status_class = 'danger';
                 if ($order['order_status'] === 'ชำระเงินแล้ว') $status_class = 'info';
            ?>
            <span class="badge text-bg-<?php echo $status_class; ?>"><?php echo e($order['order_status']); ?></span>
        </p>
        <?php if ($order['order_status'] === 'รอชำระเงิน' || $order['order_status'] === 'การชำระเงินล้มเหลว'): ?>
            <a href="<?php echo BASE_URL . '/payment-upload?order_id=' . e($order['order_id']); ?>" class="btn btn-primary mt-2">แจ้งชำระเงิน</a>
        <?php endif; ?>
    </div>
</div>

<h4 class="mb-3">รายการสินค้า</h4>
<?php if ($order_details): ?>
<div class="list-group mb-4">
    <?php foreach($order_details as $detail): ?>
        <div class="list-group-item">
            <div class="d-flex w-100 justify-content-between">
                <h5 class="mb-1"><?php echo e($detail['product_name']); ?> <span class="text-muted small">(x<?php echo $detail['quantity']; ?>)</span></h5>
                <small class="text-muted"><?php echo formatPrice($detail['subtotal']); ?> บาท</small>
            </div>
            <p class="mb-1"><small>ราคา ณ วันสั่งซื้อ: <?php echo formatPrice($detail['price_at_order']); ?> / หน่วย</small></p>

            <?php // Display allocated accounts if available
            if ($order['order_status'] === 'เสร็จสมบูรณ์' && isset($allocated_accounts[$detail['order_detail_id']])): ?>
                <div class="mt-2 p-2 bg-light border rounded">
                    <h6><i class="bi bi-key-fill text-success"></i> บัญชีที่ได้รับ:</h6>
                    <?php foreach($allocated_accounts[$detail['order_detail_id']] as $account): ?>
                        <div class="mb-1">
                            <small>
                                <strong>Username:</strong> <?php echo e($account['username']); ?><br>
                                <!-- WARNING: Displaying password directly is INSECURE! Demo only. -->
                                <strong class="text-danger">Password (Demo):</strong> <?php echo e($account['account_password']); ?>
                                <!-- End Warning -->
                            </small>
                        </div>
                    <?php endforeach; ?>
                     <small class="text-danger d-block mt-1">*กรุณาเก็บข้อมูลบัญชีไว้เป็นความลับและเปลี่ยนรหัสผ่าน (ถ้าทำได้) หลังได้รับ</small>
                </div>
            <?php endif; ?>

        </div>
    <?php endforeach; ?>
</div>
<?php else: ?>
    <p>ไม่พบรายละเอียดรายการสินค้าสำหรับคำสั่งซื้อนี้</p>
<?php endif; ?>

<a href="<?php echo BASE_URL; ?>/order-history" class="btn btn-outline-secondary">กลับไปหน้าประวัติ</a>