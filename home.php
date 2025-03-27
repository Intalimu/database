<?php
// pages/home.php
// $page_title, $current_page set by router
?>

<div class="p-5 mb-4 bg-light rounded-3">
  <div class="container-fluid py-5">
    <h1 class="display-5 fw-bold">ยินดีต้อนรับสู่ Streaming Shop</h1>
    <p class="col-md-8 fs-4">เลือกซื้อบัญชีสตรีมมิ่งพรีเมียม Netflix, Spotify, Disney+ และอื่นๆ ในราคาสุดคุ้ม</p>
    <a href="<?php echo BASE_URL; ?>/products" class="btn btn-primary btn-lg" type="button">เลือกซื้อสินค้าทั้งหมด</a>
  </div>
</div>

<h2 class="mt-5 mb-3">สินค้าแนะนำ</h2>
<div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
    <?php
    try {
        // Fetch a few products marked as 'พร้อมขาย' (e.g., 4 newest)
        $stmt = $pdo->query("SELECT p.*, c.name as category_name
                             FROM Products p
                             JOIN Categories c ON p.category_id = c.category_id
                             WHERE p.status = 'พร้อมขาย'
                             ORDER BY p.product_id DESC -- Or some other criteria like popularity
                             LIMIT 4");
        $products = $stmt->fetchAll();

        if ($products) {
            foreach ($products as $product) {
                 // Use the reusable product card template
                 include APP_BASE_PATH . '/templates/_product_card.php';
            }
        } else {
            echo "<p class='text-center col-12'>ยังไม่มีสินค้าแนะนำในขณะนี้</p>";
        }
    } catch (PDOException $e) {
        echo "<div class='alert alert-danger col-12'>เกิดข้อผิดพลาด: ไม่สามารถโหลดสินค้าแนะนำได้</div>";
        error_log("Home Product Error: " . $e->getMessage()); // Log error for admin
    }
    ?>
</div>