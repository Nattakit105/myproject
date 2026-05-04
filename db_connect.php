<?php
/**
 * 🚩 ส่วนที่แก้ไขเพื่อ Docker
 * ใช้ getenv() ดึงค่าจากไฟล์ docker-compose.yml 
 * ถ้าหาไม่เจอ (เช่นรันใน XAMPP) จะใช้ค่าเริ่มต้น (Default) ด้านหลังแทนครับ
 */
$servername = getenv('DB_HOST') ?: "localhost"; 
$username   = getenv('DB_USER') ?: "root";
$password   = getenv('DB_PASS') ?: "";
$dbname     = getenv('DB_NAME') ?: "dorm_db"; 

// สร้างการเชื่อมต่อ
$conn = new mysqli($servername, $username, $password, $dbname);

// ตรวจสอบการเชื่อมต่อ
if ($conn->connect_error) {
    die("เชื่อมต่อฐานข้อมูลล้มเหลว: " . $conn->connect_error);
}

// ตั้งค่า character set
$conn->set_charset("utf8mb4");

// --- [ รหัสส่วนเดิมของคุณ: ดึงค่าตั้งค่าระบบ ] ---
$sql = "SELECT setting_key, setting_value FROM settings";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        if (!defined($row['setting_key'])) {
            define($row['setting_key'], $row['setting_value']);
        }
    }
} else {
    // กรณีฉุกเฉิน ถ้าตาราง settings ไม่มีข้อมูล
    die("Critical Error: ไม่สามารถโหลดค่าตั้งค่าระบบ (ค่าน้ำ/ค่าไฟ) จากฐานข้อมูลได้");
}
>>>>>>> b3c7638653082b907eb612c49ef346ef3806ad14
?>