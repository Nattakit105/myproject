<?php
session_start();
// ล้างค่า session ทั้งหมด
session_unset();
session_destroy();
// กลับไปยังหน้า login
header("Location: login.php");
exit();
?>