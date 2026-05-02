<?php 
include 'check_session.php'; // 1. ตรวจสอบสิทธิ์ (ยาม)
include 'db_connect.php'; // เชื่อมต่อฐานข้อมูล

// 2. ตรวจสอบว่าเป็น Admin เท่านั้น
if ($_SESSION['role'] !== 'admin') {
    die("Access Denied!");
}

// 3. ตรวจสอบว่าเป็น POST Request เท่านั้น เพื่อความปลอดภัย
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (isset($_POST['id'])) {
        $id = $_POST['id'];

        // ใช้ Prepared Statement เพื่อความปลอดภัยจากการ SQL Injection
        $stmt = $conn->prepare("DELETE FROM billing_records WHERE id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            $_SESSION['success_message'] = "ลบรายการบิลสำเร็จเรียบร้อยแล้ว";
        } else {
            $_SESSION['error_message'] = "เกิดข้อผิดพลาดในการลบ: " . $conn->error;
        }
        $stmt->close();
        
        // 4. แก้ไขจุดนี้: ส่งกลับไปหน้าประวัติการชำระเงิน (manage_bills.php) แทนหน้าหลัก
        // และส่งค่า month กลับไปด้วยเพื่อให้หน้าจอไม่รีเซ็ตตัวกรองเดือนครับ
        $month_param = isset($_POST['month']) ? '?month=' . $_POST['month'] : '';
        header("Location: manage_bills.php" . $month_param);
        exit();

    } else {
        // กรณีไม่มี id ส่งมา ให้กลับไปหน้าจัดการบิล
        header("Location: manage_bills.php");
        exit();
    }

} else {
    // ถ้ามีการแอบพิมพ์ URL เข้ามาตรงๆ (GET) ให้ปฏิเสธการเข้าถึง
    die("Access Denied. Invalid request method.");
}
$conn->close();
?>