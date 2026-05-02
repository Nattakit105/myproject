<?php
include 'check_session.php'; // ตรวจสอบสิทธิ์ Admin
include 'db_connect.php';    // เชื่อมต่อฐานข้อมูล

if ($_SESSION['role'] !== 'admin') {
    die("Access Denied!");
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['record_id'])) {
    $id = $_POST['record_id'];
    $month = isset($_POST['month']) ? $_POST['month'] : '';

    // สั่งคืนค่าสถานะเป็น 'pending' และล้างวันที่ชำระเงิน (NULL)
    $stmt = $conn->prepare("UPDATE billing_records SET status = 'pending', payment_date = NULL WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        $_SESSION['success_message'] = "ยกเลิกการชำระเงินเรียบร้อยแล้ว";
    } else {
        $_SESSION['error_message'] = "เกิดข้อผิดพลาด: " . $conn->error;
    }
    $stmt->close();

    // 🚀 ส่งกลับไปหน้าเดิมพร้อมค่าเดือนเดิม
    $redirect_url = "manage_bills.php" . (!empty($month) ? "?month=" . urlencode($month) : "");
    header("Location: " . $redirect_url);
    exit();
}
$conn->close();
?>