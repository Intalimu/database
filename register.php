<?php
// pages/register.php
if (isset($_SESSION['customer_id'])) { redirect('/'); }
?>
<div class="row justify-content-center">
    <div class="col-md-6">
        <h1 class="text-center mb-4">สมัครสมาชิก</h1>
        <?php display_alert(); // Show validation errors if redirected back ?>
        <form action="<?php echo BASE_URL; ?>/register-process" method="POST"> <?php // --- เปลี่ยน action --- ?>
            <div class="mb-3">
                <label for="username" class="form-label">ชื่อผู้ใช้</label>
                <input type="text" class="form-control" id="username" name="username" required value="<?php echo e($_SESSION['form_data']['username'] ?? ''); ?>"> <?php // Retain value on error ?>
                 <div class="form-text">ต้องไม่ซ้ำกับผู้อื่น</div>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">อีเมล</label>
                <input type="email" class="form-control" id="email" name="email" required value="<?php echo e($_SESSION['form_data']['email'] ?? ''); ?>"> <?php // Retain value on error ?>
                 <div class="form-text">ต้องไม่ซ้ำกับผู้อื่น และใช้อีเมลจริง</div>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">รหัสผ่าน</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
             <div class="mb-3">
                <label for="confirm_password" class="form-label">ยืนยันรหัสผ่าน</label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
            </div>
            <div class="d-grid">
                <button type="submit" class="btn btn-primary">สมัครสมาชิก</button>
            </div>
             <p class="mt-3 text-center">
                มีบัญชีอยู่แล้ว? <a href="<?php echo BASE_URL; ?>/login">เข้าสู่ระบบที่นี่</a>
            </p>
        </form>
        <?php unset($_SESSION['form_data']); // Clear retained form data ?>
    </div>
</div>