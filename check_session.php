<<<<<<< HEAD
<?php
// [เวอร์ชันสมบูรณ์]
// ตรวจสอบสถานะ session ก่อนเสมอ
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// "เปิดการทำงานของยาม"
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    
    // ถ้ายังไม่ล็อกอิน (ไม่ว่ากรณีใดก็ตาม)
    session_unset();
    session_destroy();
    
    // ส่งกลับไปหน้า login พร้อมข้อความเตือน
    header("Location: login.php?error=access"); 
    exit(); // หยุดการทำงานของสคริปต์หน้านั้นๆ ทันที
}

// ถ้าผ่าน... แสดงว่า "ล็อกอินแล้ว"
=======
<?php
// [เวอร์ชันสมบูรณ์]
// ตรวจสอบสถานะ session ก่อนเสมอ
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// "เปิดการทำงานของยาม"
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    
    // ถ้ายังไม่ล็อกอิน (ไม่ว่ากรณีใดก็ตาม)
    session_unset();
    session_destroy();
    
    // ส่งกลับไปหน้า login พร้อมข้อความเตือน
    header("Location: login.php?error=access"); 
    exit(); // หยุดการทำงานของสคริปต์หน้านั้นๆ ทันที
}

// ถ้าผ่าน... แสดงว่า "ล็อกอินแล้ว"
>>>>>>> b3c7638653082b907eb612c49ef346ef3806ad14
?>