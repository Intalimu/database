<?php
$plain_password = 'password'; // หรือรหัสผ่านที่คุณต้องการใช้สำหรับ admin
$hashed_password = password_hash($plain_password, PASSWORD_DEFAULT);
echo "Password: " . $plain_password . "<br>";
echo "Generated Hash: <pre>" . htmlspecialchars($hashed_password) . "</pre>";
?>