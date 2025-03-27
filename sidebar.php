<?php
// templates/sidebar.php
$current_admin_page = $current_admin_page ?? ''; // Get from including file
?>
<div class="d-flex flex-column flex-shrink-0 p-3 bg-light" style="width: 280px; min-height: calc(100vh - 56px);"> <!-- 56px is approx navbar height -->
    <a href="<?php echo BASE_URL; ?>/admin/" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto link-dark text-decoration-none">
      <span class="fs-4">Admin Menu</span>
    </a>
    <hr>
    <ul class="nav nav-pills flex-column mb-auto">
      <li class="nav-item">
        <a href="<?php echo BASE_URL; ?>/admin/" class="nav-link <?php echo ($current_admin_page == 'dashboard') ? 'active' : 'link-dark'; ?>" aria-current="page">
          Dashboard
        </a>
      </li>
      <li>
        <a href="<?php echo BASE_URL; ?>/admin/orders.php" class="nav-link <?php echo ($current_admin_page == 'orders') ? 'active' : 'link-dark'; ?>">
          คำสั่งซื้อ
        </a>
      </li>
      <li>
        <a href="<?php echo BASE_URL; ?>/admin/payments.php" class="nav-link <?php echo ($current_admin_page == 'payments') ? 'active' : 'link-dark'; ?>">
          การชำระเงิน
        </a>
      </li>
       <li>
        <a href="<?php echo BASE_URL; ?>/admin/products.php" class="nav-link <?php echo ($current_admin_page == 'products') ? 'active' : 'link-dark'; ?>">
          จัดการสินค้า
        </a>
      </li>
       <li>
        <a href="<?php echo BASE_URL; ?>/admin/accounts.php" class="nav-link <?php echo ($current_admin_page == 'accounts') ? 'active' : 'link-dark'; ?>">
          จัดการบัญชีสตรีมมิ่ง
        </a>
      </li>
      <li>
        <a href="<?php echo BASE_URL; ?>/admin/stock_transactions.php" class="nav-link <?php echo ($current_admin_page == 'stock') ? 'active' : 'link-dark'; ?>">
          ประวัติสต็อก
        </a>
      </li>
       <!-- Add Categories, Users, Roles later if needed -->
    </ul>
    <hr>
    <div class="dropdown">
      <a href="#" class="d-flex align-items-center link-dark text-decoration-none dropdown-toggle" id="dropdownUser2" data-bs-toggle="dropdown" aria-expanded="false">
        <strong><?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?></strong>
      </a>
      <ul class="dropdown-menu text-small shadow" aria-labelledby="dropdownUser2">
        <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/admin/logout.php">ออกจากระบบ</a></li>
      </ul>
    </div>
  </div>