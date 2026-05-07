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

if (!function_exists('ensure_column_exists')) {
    function ensure_column_exists($conn, $table, $column, $definition) {
        $stmt = $conn->prepare("
            SELECT COUNT(*) AS total
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND COLUMN_NAME = ?
        ");
        $stmt->bind_param("ss", $table, $column);
        $stmt->execute();
        $exists = (int) $stmt->get_result()->fetch_assoc()['total'] > 0;
        $stmt->close();

        if (!$exists) {
            $conn->query("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
        }
    }
}

// Keep older databases compatible with the current tenant management screens.
ensure_column_exists($conn, 'users', 'room_price', "DECIMAL(10,2) DEFAULT '2500.00'");
ensure_column_exists($conn, 'users', 'occupant_count', "INT DEFAULT '1'");
ensure_column_exists($conn, 'users', 'meter_no', "VARCHAR(50) COLLATE utf8mb4_general_ci DEFAULT NULL");
ensure_column_exists($conn, 'users', 'water_meter', "VARCHAR(50) COLLATE utf8mb4_general_ci DEFAULT NULL");
ensure_column_exists($conn, 'users', 'electric_meter', "VARCHAR(50) COLLATE utf8mb4_general_ci DEFAULT NULL");

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
?>
