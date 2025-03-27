<?php
// actions/logout.php
require_once dirname(__DIR__) . '/config/db_connect.php'; // To start session

// Unset all session variables for the customer
unset($_SESSION['customer_id']);
unset($_SESSION['username']);
// unset($_SESSION['cart']); // Decide if you want to clear cart on logout

// Destroy the session
// session_destroy(); // This might log out admin too if on same browser. Better unset specific keys.

$_SESSION['message'] = ['type' => 'info', 'text' => 'ออกจากระบบเรียบร้อยแล้ว'];
redirect('/login'); // Redirect to login page
?>