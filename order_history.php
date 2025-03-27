<?php
// pages/order_history.php
requireCustomerLogin();

$page_title = 'ประวัติการสั่งซื้อ';
$current_page = 'order-history';

try {
    $stmt = $pdo->prepare("SELECT order_id, order_date, order_status, total_amount
                           FROM Orders
                           WHERE customer_id = ?
                           ORDER BY order_date DESC");
    $stmt->execute([$_SESSION['customer_id']]);
    $orders = $stmt->fetchAll();
} catch (PDOException $e) {
    $orders = [];
    $_SESSION['message'] = ['type' => 'danger', 'text' => 'เกิดข้อผิดพลาดในการดึงข้อมูลประวัติการสั่งซื้อ'];
    error_log("Order History Fetch Error: " . $e->getMessage());
}

?>

<h1 class="mb-4"><?php echo $page_title; ?></h1>

<?php if ($orders): ?>
<div class="table-responsive">
    <table class="table table-striped table-hover">
        <thead class="table-light">
            <tr>
                <th>Order ID</th>
                <th>วันที่สั่งซื้อ</th>
                <th>ยอดรวม</th>
                <th>สถานะ</th>
                <th>จัดการ</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($orders as $order):
                $status_class = 'secondary';
                if ($order['order_status'] === 'เสร็จสมบูรณ์') $status_class = 'success';
                if ($order['order_status'] === 'รอชำระเงิน' || $order['order_status'] === 'รอตรวจสอบการชำระเงิน') $status_class = 'warning';
                if (str_contains($order['order_status'], 'ล้มเหลว') || $order['order_status'] === 'ยกเลิก') $status_class = 'danger';
                if ($order['order_status'] === 'ชำระเงินแล้ว') $status_class = 'info';
            ?>
                <tr>
                    <td><?php echo e($order['order_id']); ?></td>
                    <td><?php echo date('d/m/Y H:i', strtotime($order['order_date'])); ?></td>
                    <td class="text-end"><?php echo formatPrice($order['total_amount']); ?></td>
                    <td><span class="badge text-bg-<?php echo $status_class; ?>"><?php echo e($order['order_status']); ?></span></td>
                    <td>
                        <?php if ($order['order_status'] === 'รอชำระเงิน' || $order['order_status'] === 'การชำระเงินล้มเหลว'): ?>
                            <a href="<?php echo BASE_URL . '/payment-upload?order_id=' . e($order['order_id']); ?>" class="btn btn-sm btn-primary">แจ้งชำระเงิน</a>
                         <?php elseif ($order['order_status'] === 'เสร็จสมบูรณ์'): ?>
                             <a href="<?php echo BASE_URL . '/order-confirmation?order_id=' . e($order['order_id']); ?>" class="btn btn-sm btn-info">ดูรายละเอียด/บัญชี</a>
                        <?php else: ?>
                             <a href="<?php echo BASE_URL . '/order-confirmation?order_id=' . e($order['order_id']); ?>" class="btn btn-sm btn-secondary">ดูรายละเอียด</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php else: ?>
    <div class="alert alert-info">ยังไม่มีประวัติการสั่งซื้อ</div>
<?php endif; ?>