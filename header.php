<?php // templates/header.php ?>
<?php require_once __DIR__ . '/../config/db_connect.php'; ?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($page_title ?? 'Streaming Shop'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/custom.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
        <div class="container">
            <a class="navbar-brand" href="<?php echo BASE_URL; ?>/">Streaming Shop</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link <?php echo ($current_page ?? '') === 'home' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/">หน้าแรก</a></li>
                    <li class="nav-item"><a class="nav-link <?php echo ($current_page ?? '') === 'products' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/products">สินค้า</a></li>
                </ul>
                <ul class="navbar-nav ms-auto">
                     <li class="nav-item">
                        <a class="nav-link position-relative <?php echo ($current_page ?? '') === 'cart' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/cart">
                            <i class="bi bi-cart"></i> ตะกร้า
                            <?php
                                // *** NEW: Count items from DB cart if logged in ***
                                $cart_count = 0;
                                if (isset($_SESSION['cart_id'])) { // Check if cart_id exists (user logged in)
                                    try {
                                        $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM cartitems WHERE cart_id = ?");
                                        $count_stmt->execute([$_SESSION['cart_id']]);
                                        $cart_count = $count_stmt->fetchColumn();
                                    } catch (PDOException $e) {
                                        error_log("Header cart count error: ".$e->getMessage());
                                        $cart_count = '?'; // Indicate error
                                    }
                                }
                                // *** END NEW ***
                            ?>
                            <?php if($cart_count > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                <?php echo $cart_count; ?>
                                <span class="visually-hidden">items in cart</span>
                            </span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <?php if (isset($_SESSION['customer_id'])): // User is logged in ?>
                        <li class="nav-item dropdown">
                          <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle"></i> <?php echo e($_SESSION['username']); ?>
                          </a>
                          <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/order-history">ประวัติการสั่งซื้อ</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/logout">ออกจากระบบ</a></li>
                          </ul>
                        </li>
                    <?php else: // User is not logged in ?>
                        <li class="nav-item"><a class="nav-link <?php echo ($current_page ?? '') === 'login' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/login">เข้าสู่ระบบ</a></li>
                        <li class="nav-item"><a class="nav-link <?php echo ($current_page ?? '') === 'register' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/register">สมัครสมาชิก</a></li>
                    <?php endif; ?>
                    <?php if (isset($_SESSION['admin_id'])): // Link to Admin if Admin is logged in ?>
                         <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>/admin/" target="_blank"><i class="bi bi-gear-fill"></i> ส่วนจัดการ</a></li>
                     <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    <div class="container mt-4 mb-5">
        <?php display_alert(); ?>
        <!-- Page content starts here -->