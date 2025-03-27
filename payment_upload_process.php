<?php
// actions/payment_upload_process.php
// Define APP_BASE_PATH relative to this file's location
define('APP_BASE_PATH', dirname(__DIR__));
require_once APP_BASE_PATH . '/config/db_connect.php'; // Ensure path is correct
requireCustomerLogin(); // Make sure user is logged in

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/'); // Redirect home if not POST
}

// --- Get POST data ---
$order_id = $_POST['order_id'] ?? null;
$amount = $_POST['amount'] ?? 0; // Get amount from form (should match order total)
$payment_date_str = $_POST['payment_date'] ?? null; // Date/Time string from datetime-local input
$transaction_number = trim($_POST['transaction_number'] ?? ''); // Optional transaction reference
$payment_evidence_file = $_FILES['payment_evidence'] ?? null; // Uploaded file info

// --- Basic Input Validation ---
if (!$order_id || !$payment_date_str || !$payment_evidence_file || $payment_evidence_file['error'] !== UPLOAD_ERR_OK) {
     $_SESSION['message'] = ['type' => 'danger', 'text' => 'กรุณากรอกข้อมูลวันที่/เวลา และแนบสลิปการโอนเงินให้ครบถ้วน'];
     // Redirect back to the upload form with the order ID
     redirect('/payment-upload?order_id=' . $order_id);
}

// --- Validate Order ID, Customer, and Status ---
try {
    // *** MODIFIED: Check for order status 'รอชำระเงิน' OR 'การชำระเงินล้มเหลว' ***
    $stmt_check_order = $pdo->prepare("SELECT total_amount, order_status
                                       FROM Orders
                                       WHERE order_id = ? AND customer_id = ?
                                       AND order_status IN ('รอชำระเงิน', 'การชำระเงินล้มเหลว')");
    $stmt_check_order->execute([$order_id, $_SESSION['customer_id']]);
    $order_data = $stmt_check_order->fetch();

    if ($order_data === false) {
        // Order not found, doesn't belong to user, or status is not correct
        $_SESSION['message'] = ['type' => 'danger', 'text' => 'ไม่พบคำสั่งซื้อที่สามารถแจ้งชำระเงินได้ หรือคำสั่งซื้อไม่ถูกต้อง'];
        redirect('/order-history'); // Redirect to order history
    }
    $order_amount_db = $order_data['total_amount']; // Get order amount from DB
    $original_order_status = $order_data['order_status']; // Store the original status before update

    // Optional: Compare submitted amount with order amount
    // if ((float)$amount !== (float)$order_amount_db) { ... handle amount mismatch ... }

} catch (PDOException $e) {
    error_log("Payment Upload - Order Validation Error: " . $e->getMessage());
    $_SESSION['message'] = ['type' => 'danger', 'text' => 'เกิดข้อผิดพลาดในการตรวจสอบข้อมูลคำสั่งซื้อ'];
    redirect('/payment-upload?order_id=' . $order_id); // Redirect back to upload form
}
// --- End Order Validation ---


// --- File Upload Handling ---
$upload_dir = APP_BASE_PATH . '/public/uploads/evidence/'; // Use absolute path based on APP_BASE_PATH
$allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf']; // Added PDF support example
$max_size = 5 * 1024 * 1024; // Increased to 5MB example

// Create directory if it doesn't exist (with proper permissions if possible)
if (!is_dir($upload_dir)) {
    if (!mkdir($upload_dir, 0775, true)) { // Try to create recursively with permissions
         error_log("Failed to create upload directory: " . $upload_dir);
         $_SESSION['message'] = ['type' => 'danger', 'text' => 'เกิดข้อผิดพลาดของระบบ (ไม่สามารถสร้างโฟลเดอร์อัปโหลด)'];
         redirect('/payment-upload?order_id=' . $order_id);
    }
}
// Check writability again after potential creation
if (!is_writable($upload_dir)) {
     error_log("Upload directory is not writable: " . $upload_dir);
     $_SESSION['message'] = ['type' => 'danger', 'text' => 'เกิดข้อผิดพลาดของระบบ (โฟลเดอร์อัปโหลดไม่สามารถเขียนได้)'];
     redirect('/payment-upload?order_id=' . $order_id);
}


// Validate file type
if (!in_array(strtolower($payment_evidence_file['type']), $allowed_types)) {
    $_SESSION['message'] = ['type' => 'danger', 'text' => 'ประเภทไฟล์สลิปไม่ถูกต้อง (รองรับ: JPG, PNG, GIF, PDF)'];
    redirect('/payment-upload?order_id=' . $order_id);
}

// Validate file size
if ($payment_evidence_file['size'] > $max_size) {
    $_SESSION['message'] = ['type' => 'danger', 'text' => 'ขนาดไฟล์สลิปเกิน ' . ($max_size / 1024 / 1024) . 'MB'];
    redirect('/payment-upload?order_id=' . $order_id);
}

// Generate unique filename to prevent overwrites and conflicts
$file_extension = strtolower(pathinfo($payment_evidence_file['name'], PATHINFO_EXTENSION));
$new_filename = 'evd_' . $order_id . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $file_extension; // Add random bytes for more uniqueness
$upload_path = $upload_dir . $new_filename; // Full path to save the file
$db_filepath = '/uploads/evidence/' . $new_filename; // Relative path to store in DB (relative to web root 'public')

// Attempt to move the uploaded file
if (!move_uploaded_file($payment_evidence_file['tmp_name'], $upload_path)) {
    // Log detailed error from $_FILES['payment_evidence']['error'] if needed
    error_log("Failed to move uploaded payment evidence file for order {$order_id}. Error code: " . $payment_evidence_file['error']);
    $_SESSION['message'] = ['type' => 'danger', 'text' => 'เกิดข้อผิดพลาดในการบันทึกไฟล์สลิป โปรดลองอีกครั้ง'];
    redirect('/payment-upload?order_id=' . $order_id);
}
// --- End File Upload Handling ---


// --- Insert Payment Record and Update Order Status (Transaction) ---
$pdo->beginTransaction();
try {
    // 1. Generate Payment ID
    $payment_id = generateUniqueID('PAY');
    $payment_method = 'โอนเงิน'; // Hardcoded for bank transfer flow
    $payment_status = 'รอตรวจสอบ'; // Initial status for manual verification

    // 2. Convert datetime-local string to SQL timestamp format
    // Ensure the format matches 'YYYY-MM-DD HH:MM:SS'
    try {
         $payment_timestamp = (new DateTime($payment_date_str))->format('Y-m-d H:i:s');
    } catch (Exception $date_e) {
         // Handle invalid date format from input
         throw new Exception("รูปแบบวันที่และเวลาที่ระบุไม่ถูกต้อง");
    }


    // 3. Insert into Payments table
    $stmt_pay = $pdo->prepare(
        "INSERT INTO Payments (payment_id, order_id, payment_method, payment_date, amount, transaction_number, payment_status, payment_evidence)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt_pay->execute([
        $payment_id,
        $order_id,
        $payment_method,
        $payment_timestamp,
        $order_amount_db, // Use amount from validated order
        $transaction_number,
        $payment_status,
        $db_filepath // Store the relative path
    ]);

    // 4. Update Order status to 'รอตรวจสอบการชำระเงิน'
    //    Only update if the original status was 'รอชำระเงิน' or 'การชำระเงินล้มเหลว'
    // *** MODIFIED: Use original status in WHERE clause ***
    $stmt_order_update = $pdo->prepare(
        "UPDATE Orders SET order_status = 'รอตรวจสอบการชำระเงิน'
         WHERE order_id = ? AND order_status = ?" // Use the original status fetched earlier
    );
    $stmt_order_update->execute([$order_id, $original_order_status]);

    // Check if the order status was actually updated (important for concurrency/race conditions)
    if ($stmt_order_update->rowCount() == 0) {
        // This could happen if the order status changed between the initial check and the update
        throw new Exception("ไม่สามารถอัปเดตสถานะคำสั่งซื้อได้ อาจมีการเปลี่ยนแปลงเกิดขึ้น โปรดตรวจสอบสถานะล่าสุด");
    }

    // 5. Commit the transaction
    $pdo->commit();

    // --- Success ---
    $_SESSION['message'] = ['type' => 'success', 'text' => 'แจ้งชำระเงินเรียบร้อยแล้ว คำสั่งซื้อของคุณ ('.e($order_id).') อยู่ระหว่างการตรวจสอบ'];
    redirect('/order-history'); // Redirect to order history page

} catch (Exception $e) { // Catch both PDOException and general Exception
    $pdo->rollBack(); // Rollback transaction on error

    // Attempt to delete the uploaded file if the DB operations failed
    if (isset($upload_path) && file_exists($upload_path)) {
        @unlink($upload_path); // Use @ to suppress errors if unlink fails
    }

    error_log("Payment Upload DB Error/Exception (Order ID: {$order_id}): " . $e->getMessage());
    $_SESSION['message'] = ['type' => 'danger', 'text' => 'เกิดข้อผิดพลาดในการบันทึกข้อมูล: ' . $e->getMessage()];
    redirect('/payment-upload?order_id=' . $order_id); // Redirect back to upload form
}
// --- End Insert Payment Record ---
?>