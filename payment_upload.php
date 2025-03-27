<?php
// pages/payment_upload.php
requireCustomerLogin();

$order_id = $_GET['order_id'] ?? null;

if (!$order_id) {
    $_SESSION['message'] = ['type' => 'danger', 'text' => 'ไม่พบรหัสคำสั่งซื้อ'];
    redirect('/order-history');
}

// ดึงข้อมูล Order เพื่อยืนยันว่าเป็นของ User และ Status ถูกต้อง
try {
    $stmt = $pdo->prepare("SELECT order_id, total_amount, order_status
                           FROM Orders
                           WHERE order_id = ? AND customer_id = ?");
    $stmt->execute([$order_id, $_SESSION['customer_id']]);
    $order = $stmt->fetch();

    if (!$order) {
        $_SESSION['message'] = ['type' => 'danger', 'text' => 'ไม่พบคำสั่งซื้อ หรือไม่ใช่คำสั่งซื้อของคุณ'];
        redirect('/order-history');
    }

    // อนุญาตให้อัปโหลดเฉพาะเมื่อสถานะเป็น 'รอชำระเงิน' หรือ 'การชำระเงินล้มเหลว'
    if (!in_array($order['order_status'], ['รอชำระเงิน', 'การชำระเงินล้มเหลว'])) {
         $_SESSION['message'] = ['type' => 'info', 'text' => 'คำสั่งซื้อนี้ (' . htmlspecialchars($order_id) . ') ไม่ได้อยู่ในสถานะที่รอการชำระเงิน'];
         redirect('/order-history');
    }

} catch (PDOException $e) {
     $_SESSION['message'] = ['type' => 'danger', 'text' => 'เกิดข้อผิดพลาดในการดึงข้อมูลคำสั่งซื้อ'];
     error_log("Payment Upload - Order Fetch Error: " . $e->getMessage());
     redirect('/order-history');
}
?>

<h1 class="mb-4">แจ้งชำระเงินสำหรับ Order: <?php echo htmlspecialchars($order_id); ?></h1>
<p>ยอดที่ต้องชำระ: <strong><?php echo number_format($order['total_amount'], 2); ?> บาท</strong></p>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
         <div class="card">
            <div class="card-body">
                 <div class="alert alert-info">
                    <p class="mb-1">กรุณาโอนเงินมาที่:</p>
                    <p class="mb-1"><strong>ธนาคาร:</strong> XXX Bank</p>
                    <p class="mb-1"><strong>เลขที่บัญชี:</strong> 123-4-56789-0</p>
                    <p class="mb-0"><strong>ชื่อบัญชี:</strong> Streaming Shop</p>
                </div>

                <form action="<?php echo BASE_URL; ?>/payment-upload-process" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="order_id" value="<?php echo htmlspecialchars($order_id); ?>">
                     <input type="hidden" name="amount" value="<?php echo $order['total_amount']; ?>">

                     <div class="mb-3">
                        <label for="payment_date" class="form-label">วันที่และเวลาโอน <span class="text-danger">*</span></label>
                        <input type="datetime-local" class="form-control" id="payment_date" name="payment_date" required>
                    </div>

                     <div class="mb-3">
                        <label for="payment_evidence" class="form-label">หลักฐานการโอน (สลิป) <span class="text-danger">*</span></label>
                        <input class="form-control" type="file" id="payment_evidence" name="payment_evidence" accept="image/png, image/jpeg, image/gif" required>
                         <div class="form-text">รองรับไฟล์ PNG, JPG, GIF เท่านั้น ขนาดไม่เกิน 2MB</div>
                    </div>

                     <div class="mb-3">
                        <label for="transaction_number" class="form-label">หมายเลขอ้างอิง (ถ้ามี)</label>
                        <input type="text" class="form-control" id="transaction_number" name="transaction_number">
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">แจ้งชำระเงิน</button>
                    </div>
                </form>
             </div>
         </div>
    </div>
</div>