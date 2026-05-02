// === แก้ไขชื่อผู้ใช้และรหัสผ่านใหม่ที่ต้องการตรงนี้ ===
// ======================================================
$new_admin_username = 'admin2'; // ตั้งชื่อ Username แอดมินใหม่ที่ต้องการ
$new_admin_password = 'password5678'; // ตั้งรหัสผ่านใหม่ที่ต้องการ
// ======================================================

// --- ระบบจะทำการเข้ารหัสรหัสผ่านให้ ---
$hashed_password = password_hash($new_admin_password, PASSWORD_DEFAULT);
$role = 'admin'; // กำหนดสิทธิ์ให้เป็น admin

// เตรียมคำสั่ง SQL เพื่อเพิ่มผู้ใช้ใหม่
$stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $new_admin_username, $hashed_password, $role);

if ($stmt->execute()) {
    echo "<h1>สร้างผู้ใช้แอดมิน '{$new_admin_username}' สำเร็จ!</h1>";
    echo "<p>รหัสผ่านคือ: <strong style='font-size: 20px; color: red;'>{$new_admin_password}</strong></p>";
    echo "<p style='color: orange; font-weight: bold;'>คำเตือน: กรุณาลบไฟล์ create_new_admin.php นี้ทิ้งทันทีเพื่อความปลอดภัย!</p>";
    echo "<a href='login.php'>ไปที่หน้าล็อกอิน</a>";
} else {
    // ตรวจสอบว่ามีชื่อผู้ใช้นี้อยู่แล้วหรือไม่
    if ($conn->errno == 1062) { // 1062 คือรหัส Error ของ Duplicate entry
        echo "<h1>เกิดข้อผิดพลาด!</h1>";
        echo "<p>ไม่สามารถสร้างผู้ใช้ '{$new_admin_username}' ได้ เนื่องจากมีชื่อผู้ใช้นี้อยู่ในระบบแล้ว</p>";
        echo "<a href='login.php'>กลับไปหน้าล็อกอิน</a>";
    } else {
        echo "เกิดข้อผิดพลาดในการสร้างผู้ใช้: " . $stmt->error;
    }
}

$stmt->close();
$conn->close();
=======
<?php
include 'db_connect.php';

// ======================================================
// === แก้ไขชื่อผู้ใช้และรหัสผ่านใหม่ที่ต้องการตรงนี้ ===
// ======================================================
$new_admin_username = 'admin2'; // ตั้งชื่อ Username แอดมินใหม่ที่ต้องการ
$new_admin_password = 'password5678'; // ตั้งรหัสผ่านใหม่ที่ต้องการ
// ======================================================

// --- ระบบจะทำการเข้ารหัสรหัสผ่านให้ ---
$hashed_password = password_hash($new_admin_password, PASSWORD_DEFAULT);
$role = 'admin'; // กำหนดสิทธิ์ให้เป็น admin

// เตรียมคำสั่ง SQL เพื่อเพิ่มผู้ใช้ใหม่
$stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $new_admin_username, $hashed_password, $role);

if ($stmt->execute()) {
    echo "<h1>สร้างผู้ใช้แอดมิน '{$new_admin_username}' สำเร็จ!</h1>";
    echo "<p>รหัสผ่านคือ: <strong style='font-size: 20px; color: red;'>{$new_admin_password}</strong></p>";
    echo "<p style='color: orange; font-weight: bold;'>คำเตือน: กรุณาลบไฟล์ create_new_admin.php นี้ทิ้งทันทีเพื่อความปลอดภัย!</p>";
    echo "<a href='login.php'>ไปที่หน้าล็อกอิน</a>";
} else {
    // ตรวจสอบว่ามีชื่อผู้ใช้นี้อยู่แล้วหรือไม่
    if ($conn->errno == 1062) { // 1062 คือรหัส Error ของ Duplicate entry
        echo "<h1>เกิดข้อผิดพลาด!</h1>";
        echo "<p>ไม่สามารถสร้างผู้ใช้ '{$new_admin_username}' ได้ เนื่องจากมีชื่อผู้ใช้นี้อยู่ในระบบแล้ว</p>";
        echo "<a href='login.php'>กลับไปหน้าล็อกอิน</a>";
    } else {
        echo "เกิดข้อผิดพลาดในการสร้างผู้ใช้: " . $stmt->error;
    }
}

$stmt->close();
$conn->close();
?>