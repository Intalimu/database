<?php
// src/functions.php

/**
 * สร้าง Unique ID แบบง่ายๆ สำหรับ Demo
 * @param string $prefix คำนำหน้า ID
 * @return string Generated ID
 */
function generateUniqueID(string $prefix = ''): string {
    return $prefix . strtoupper(substr(md5(uniqid(microtime(), true)), 0, 8));
}

/**
 * สร้าง Hash รหัสผ่าน
 * @param string $password
 * @return string Hashed password
 */
function hashPassword(string $password): string {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * ตรวจสอบรหัสผ่านกับ Hash
 * @param string $password
 * @param string $hashedPassword
 * @return bool True if match, false otherwise
 */
function verifyPassword(string $password, string $hashedPassword): bool {
    return password_verify($password, $hashedPassword);
}

/**
 * ตรวจสอบว่า Admin Login หรือยัง และ Redirect ถ้ายัง
 */
function requireAdminLogin() {
    if (!isset($_SESSION['admin_id'])) {
        $_SESSION['message'] = ['type' => 'warning', 'text' => 'กรุณาเข้าสู่ระบบสำหรับผู้ดูแล'];
        redirect('/admin/login.php'); // Adjust path if base url is complex
    }
}

/**
 * ตรวจสอบว่า Customer Login หรือยัง และ Redirect ถ้ายัง
 */
function requireCustomerLogin() {
    if (!isset($_SESSION['customer_id'])) {
        $_SESSION['message'] = ['type' => 'warning', 'text' => 'กรุณาเข้าสู่ระบบเพื่อดำเนินการต่อ'];
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        redirect('/login');
    }
}

/**
 * Format price
 */
function formatPrice(float $price): string {
    return number_format($price, 2);
}

/**
 * Sanitize output for HTML display
 */
function e(string $string): string {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

?>