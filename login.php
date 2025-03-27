<?php
// pages/login.php
if (isset($_SESSION['customer_id'])) { redirect('/'); } // ถ้า login แล้ว ไปหน้าแรก
$redirect_url = $_SESSION['redirect_after_login'] ?? '/'; // Get redirect URL if set
unset($_SESSION['redirect_after_login']); // Clear it after reading
?>
<div class="row justify-content-center">
    <div class="col-md-5">
        <h1 class="text-center mb-4">เข้าสู่ระบบ</h1>
        <form action="<?php echo BASE_URL; ?>/login-process" method="POST">
             <input type="hidden" name="redirect_to" value="<?php echo htmlspecialchars($redirect_url); ?>">
            <div class="mb-3">
                <label for="login_identity" class="form-label">ชื่อผู้ใช้ หรือ อีเมล</label>
                <input type="text" class="form-control" id="login_identity" name="login_identity" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">รหัสผ่าน</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <!-- Add "Remember Me" checkbox if needed -->
            <div class="d-grid mb-3">
                <button type="submit" class="btn btn-primary">เข้าสู่ระบบ</button>
            </div>
             <!-- Add Forgot Password link later -->
             <p class="mt-3 text-center">
                ยังไม่มีบัญชี? <a href="<?php echo BASE_URL; ?>/register">สมัครสมาชิกที่นี่</a>
            </p>
        </form>
    </div>
</div>