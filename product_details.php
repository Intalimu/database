<?php
// pages/product_details.php
// $item_id is set by the router (public/index.php)

if (!$item_id) {
    $_SESSION['message'] = ['type' => 'danger', 'text' => 'ไม่พบรหัสสินค้า'];
    redirect('/products');
}

try {
    $stmt = $pdo->prepare("SELECT p.*, c.name as category_name
                         FROM Products p
                         JOIN Categories c ON p.category_id = c.category_id
                         WHERE p.product_id = ?");
    $stmt->execute([$item_id]);
    $product = $stmt->fetch();

    if (!$product) {
        $_SESSION['message'] = ['type' => 'danger', 'text' => 'ไม่พบสินค้าที่ต้องการ'];
        redirect('/products');
    }

     // Set page title dynamically
    $page_title = e($product['name']);

    // Check stock (simple count for demo)
     $stock_stmt = $pdo->prepare("SELECT COUNT(*) FROM StreamingAccounts WHERE product_id = ? AND status = 'พร้อมใช้งาน'");
     $stock_stmt->execute([$product['product_id']]);
     $stock_count = $stock_stmt->fetchColumn();


} catch (PDOException $e) {
    $_SESSION['message'] = ['type' => 'danger', 'text' => 'เกิดข้อผิดพลาดในการดึงข้อมูลสินค้า'];
    error_log("Product Detail Fetch Error: " . $e->getMessage());
    redirect('/products');
}

$img_url = $product['image_url'] ? BASE_URL . e($product['image_url']) : BASE_URL . '/images/placeholder.png'; // Placeholder path

?>

<nav aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/">หน้าแรก</a></li>
    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/products">สินค้า</a></li>
    <li class="breadcrumb-item active" aria-current="page"><?php echo e($product['name']); ?></li>
  </ol>
</nav>

<div class="row g-4">
    <div class="col-md-5 text-center">
        <img src="<?php echo $img_url; ?>" class="img-fluid rounded border" style="max-height: 400px;" alt="<?php echo e($product['name']); ?>">
    </div>
    <div class="col-md-7">
        <h1><?php echo e($product['name']); ?></h1>
        <p class="text-muted">หมวดหมู่: <?php echo e($product['category_name']); ?> | ระยะเวลา: <?php echo e($product['duration_value']); ?> วัน</p>
        <hr>
        <p class="fs-3 fw-bold text-primary"><?php echo formatPrice($product['price']); ?> บาท</p>

        <div class="mb-3">
            <strong>สถานะ:</strong>
            <?php if ($product['status'] === 'พร้อมขาย' && $stock_count > 0): ?>
                 <span class="badge text-bg-success">มีสินค้า <small>(<?php echo $stock_count; ?> ชิ้น)</small></span>
            <?php else: ?>
                 <span class="badge text-bg-danger">สินค้าหมด</span>
            <?php endif; ?>
        </div>

        <div class="product-description mb-4">
            <h5>รายละเอียดสินค้า</h5>
            <p><?php echo nl2br(e($product['description'] ?? 'ไม่มีรายละเอียด')); ?></p> <!-- nl2br to respect line breaks -->
        </div>

        <?php if ($product['status'] === 'พร้อมขาย' && $stock_count > 0): ?>
            <form action="<?php echo BASE_URL; ?>/cart-action" method="post">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="product_id" value="<?php echo e($product['product_id']); ?>">
                <input type="hidden" name="quantity" value="1">
                <input type="hidden" name="redirect_to" value="<?php echo $_SERVER['REQUEST_URI']; ?>">
                <button type="submit" class="btn btn-primary btn-lg"><i class="bi bi-cart-plus"></i> เพิ่มลงตะกร้า</button>
            </form>
        <?php else: ?>
            <button type="button" class="btn btn-secondary btn-lg" disabled>สินค้าหมด</button>
        <?php endif; ?>
    </div>
</div>

<!-- อาจจะเพิ่มส่วน สินค้าที่เกี่ยวข้อง -->