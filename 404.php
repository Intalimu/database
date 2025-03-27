<?php
// pages/404.php
// $page_title is set by the router
http_response_code(404); // Ensure correct HTTP status code
?>

<div class="container text-center mt-5 pt-5">
    <h1 class="display-1 text-danger">404</h1>
    <h2 class="mb-4">ไม่พบหน้าที่คุณต้องการ</h2>
    <p class="lead mb-4">ขออภัย ดูเหมือนว่าหน้าที่คุณกำลังมองหาไม่มีอยู่ หรืออาจถูกย้ายไปที่อื่น</p>
    <a href="<?php echo BASE_URL; ?>/" class="btn btn-primary">กลับหน้าแรก</a>
    <a href="<?php echo BASE_URL; ?>/products" class="btn btn-outline-secondary">ดูสินค้าทั้งหมด</a>
</div>