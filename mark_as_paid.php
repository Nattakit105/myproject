<?php
include 'check_session.php'; // ตรวจสอบสิทธิ์ Admin
include 'db_connect.php';    // เชื่อมต่อฐานข้อมูล

if ($_SESSION['role'] !== 'admin') {
    die("Access Denied!");
}

// รับค่า ID บิลและเดือนเพื่อใช้ในการ Redirect กลับ
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');
    $current_date = date('Y-m-d H:i:s');

    // อัปเดตสถานะเป็น 'paid' และบันทึกวันที่ชำระเงิน
    $stmt = $conn->prepare("UPDATE billing_records SET status = 'paid', payment_date = ? WHERE id = ?");
    $stmt->bind_param("si", $current_date, $id);

    if ($stmt->execute()) {
        $_SESSION['success_message'] = "บันทึกการชำระเงินสำเร็จ!";
    } else {
        $_SESSION['error_message'] = "เกิดข้อผิดพลาด: " . $conn->error;
    }
    $stmt->close();

    // 🚀 จุดสำคัญ: ส่งกลับไปที่หน้า manage_bills.php พร้อมเดือนเดิม
    header("Location: manage_bills.php?month=" . $month);
    exit();

} else {
    header("Location: manage_bills.php");
    exit();
}

$conn->close();
?>