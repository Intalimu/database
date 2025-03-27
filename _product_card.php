<?php
// templates/_product_card.php
// Requires $product variable (array) to be set before including

if (!isset($product)) return; // Safety check

$product_id_safe = e($product['product_id']);
$product_name_safe = e($product['name']);
// Construct image URL carefully using BASE_URL
$image_url = $product['image_url'] ? BASE_URL . e($product['image_url']) : BASE_URL . '/images/placeholder.png'; // Make sure placeholder exists
$category_name_safe = e($product['category_name'] ?? 'N/A');
// Use mb_substr for multi-byte characters like Thai
$description_short = mb_substr(e($product['description'] ?? ''), 0, 80, 'UTF-8') . (mb_strlen(e($product['description'] ?? ''), 'UTF-8') > 80 ? '...' : '');
$price_formatted = formatPrice($product['price']);
$duration_safe = e($product['duration_value']);

// Check stock for this specific product (simple count for demo)
$is_in_stock = false;
if ($product['status'] === 'พร้อมขาย') {
    try {
        // This query inside a loop can be inefficient on high traffic pages. Consider fetching stock counts differently.
         $stock_stmt_card = $pdo->prepare("SELECT COUNT(*) FROM StreamingAccounts WHERE product_id = ? AND status = 'พร้อมใช้งาน'");
         $stock_stmt_card->execute([$product['product_id']]);
         $stock_count_card = $stock_stmt_card->fetchColumn();
         if ($stock_count_card > 0) {
             $is_in_stock = true;
         }
    } catch (PDOException $e) {
        error_log("Stock check error in product card for {$product_id_safe}: " . $e->getMessage());
        // Assume out of stock if error occurs during check
    }
}

?>
<div class="col">
    <div class="card h-100 shadow-sm product-card">
        <a href="<?php echo BASE_URL . '/product/' . $product_id_safe; ?>">
           <img src="<?php echo $image_url; ?>" class="card-img-top product-card-img" alt="<?php echo $product_name_safe; ?>">
        </a>
        <div class="card-body d-flex flex-column">
            <h5 class="card-title flex-grow-0">
                <a href="<?php echo BASE_URL . '/product/' . $product_id_safe; ?>" class="text-decoration-none text-dark stretched-link">
                    <?php echo $product_name_safe; ?>
                </a>
            </h5>
            <p class="card-text text-muted small mb-2"><?php echo $category_name_safe; ?> - <?php echo $duration_safe; ?> วัน</p>
            <p class="card-text small flex-grow-1"><?php echo $description_short; ?></p>
            <div class="mt-auto">
                <div class="d-flex justify-content-between align-items-center">
                     <span class="fw-bold fs-5 text-primary"><?php echo $price_formatted; ?> บาท</span>
                     <?php if ($is_in_stock): ?>
                         <!-- Add to cart form -->
                         <form action="<?php echo BASE_URL; ?>/cart-action" method="post" class="d-inline add-to-cart-form">
                            <input type="hidden" name="action" value="add">
                            <input type="hidden" name="product_id" value="<?php echo $product_id_safe; ?>">
                            <input type="hidden" name="quantity" value="1">
                            <input type="hidden" name="redirect_to" value="<?php echo e($_SERVER['REQUEST_URI']); ?>">
                            <button type="submit" class="btn btn-sm btn-outline-primary" title="เพิ่มลงตะกร้า">
                                <i class="bi bi-cart-plus"></i> <span class="d-none d-md-inline">เพิ่ม</span>
                            </button>
                        </form>
                     <?php else: ?>
                          <span class="badge text-bg-secondary">สินค้าหมด</span>
                     <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<style>
    /* Optional: Style for product card images */
    .product-card-img {
        height: 180px; /* Adjust as needed */
        object-fit: contain; /* Or 'cover' */
        padding: 10px;
    }
</style>