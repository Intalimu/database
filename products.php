<?php
// pages/products.php
// $page_title, $current_page set by router

// Fetch categories for potential filtering dropdown
try {
    $cat_stmt = $pdo->query("SELECT category_id, name FROM Categories ORDER BY name");
    $categories = $cat_stmt->fetchAll();
} catch (PDOException $e) {
    $categories = [];
    error_log("Error fetching categories for products page: " . $e->getMessage());
}

// Base SQL to fetch available products
$sql = "SELECT p.*, c.name as category_name
        FROM Products p
        JOIN Categories c ON p.category_id = c.category_id
        WHERE p.status = 'พร้อมขาย'"; // Only show available products
$params = [];

// --- Filtering Logic (Example: By Category) ---
$selected_category = $_GET['category'] ?? null;
if ($selected_category) {
    // Validate category ID exists (simple check)
    $valid_cat = false;
    foreach ($categories as $cat) {
        if ($cat['category_id'] === $selected_category) {
            $valid_cat = true;
            break;
        }
    }
    if ($valid_cat) {
        $sql .= " AND p.category_id = ?";
        $params[] = $selected_category;
    } else {
        $selected_category = null; // Reset if invalid category ID
    }
}
// --- End Filtering Logic ---

$sql .= " ORDER BY c.name, p.name"; // Order by category, then product name

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll();
} catch (PDOException $e) {
    $products = [];
    $_SESSION['message'] = ['type' => 'danger', 'text' => 'เกิดข้อผิดพลาดในการดึงข้อมูลสินค้า'];
    error_log("Product Page Fetch Error: " . $e->getMessage());
}

?>
<h1 class="mb-4">สินค้าทั้งหมด</h1>

<!-- Optional Filter Form -->
<form method="get" class="row g-3 mb-4 align-items-end">
    <div class="col-md-4">
        <label for="categoryFilter" class="form-label">กรองตามหมวดหมู่:</label>
        <select name="category" id="categoryFilter" class="form-select">
            <option value="">-- ทุกหมวดหมู่ --</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?php echo e($cat['category_id']); ?>" <?php echo ($selected_category === $cat['category_id']) ? 'selected' : ''; ?>>
                    <?php echo e($cat['name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2">
        <button type="submit" class="btn btn-primary w-100">กรอง</button>
    </div>
     <?php if ($selected_category): ?>
     <div class="col-md-2">
        <a href="<?php echo BASE_URL; ?>/products" class="btn btn-outline-secondary w-100">ล้างตัวกรอง</a>
    </div>
     <?php endif; ?>
</form>
<hr>

<div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
    <?php if ($products): ?>
        <?php foreach ($products as $product): ?>
            <?php // Include the reusable product card template ?>
            <?php include APP_BASE_PATH . '/templates/_product_card.php'; ?>
        <?php endforeach; ?>
    <?php else: ?>
        <p class="text-center col-12">ไม่พบสินค้า<?php echo $selected_category ? 'ในหมวดหมู่นี้' : ''; ?></p>
    <?php endif; ?>
</div>