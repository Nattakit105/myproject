<<<<<<< HEAD
<?php
include 'check_session.php'; // ตรวจสอบสิทธิ์การเข้าถึง
include 'db_connect.php';    // เชื่อมต่อฐานข้อมูล

// ตรวจสอบว่าเป็น Admin เท่านั้น
if ($_SESSION['role'] !== 'admin') {
    die("Access Denied!");
}

// ตรวจสอบว่ามีการส่งข้อมูลผ่าน POST มาจริง
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['record_id'])) {
    
    $id = $_POST['record_id'];
    $month = isset($_POST['month']) ? $_POST['month'] : ''; // รับค่าเดือนเพื่อใช้ส่งกลับหน้าเดิม
    $current_date = date('Y-m-d H:i:s');

    // อัปเดตสถานะเป็น 'paid' และบันทึกวันที่ชำระเงิน
    // โดยใช้ Prepared Statement เพื่อความปลอดภัย
    $stmt = $conn->prepare("UPDATE billing_records SET status = 'paid', payment_date = ? WHERE id = ?");
    $stmt->bind_param("si", $current_date, $id);

    if ($stmt->execute()) {
        $_SESSION['success_message'] = "บันทึกการชำระเงินสำเร็จแล้วครับ";
    } else {
        $_SESSION['error_message'] = "เกิดข้อผิดพลาด: " . $conn->error;
    }
    $stmt->close();

    // 🚀 จุดสำคัญ: เปลี่ยนจาก index.php เป็น manage_bills.php
    // พร้อมแนบพารามิเตอร์ month กลับไปด้วยเพื่อให้หน้าจอไม่รีเซ็ต
    $redirect_url = "manage_bills.php";
    if (!empty($month)) {
        $redirect_url .= "?month=" . urlencode($month);
    }
    
    header("Location: " . $redirect_url);
    exit();

} else {
    // หากเข้าไฟล์นี้โดยไม่ผ่านฟอร์ม ให้กลับไปหน้าจัดการบิล
    header("Location: manage_bills.php");
    exit();
}

$conn->close();
=======
<?php
include 'check_session.php'; // ตรวจสอบสิทธิ์การเข้าถึง
include 'db_connect.php';    // เชื่อมต่อฐานข้อมูล

// ตรวจสอบว่าเป็น Admin เท่านั้น
if ($_SESSION['role'] !== 'admin') {
    die("Access Denied!");
}

// ตรวจสอบว่ามีการส่งข้อมูลผ่าน POST มาจริง
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['record_id'])) {
    
    $id = $_POST['record_id'];
    $month = isset($_POST['month']) ? $_POST['month'] : ''; // รับค่าเดือนเพื่อใช้ส่งกลับหน้าเดิม
    $current_date = date('Y-m-d H:i:s');

    // อัปเดตสถานะเป็น 'paid' และบันทึกวันที่ชำระเงิน
    // โดยใช้ Prepared Statement เพื่อความปลอดภัย
    $stmt = $conn->prepare("UPDATE billing_records SET status = 'paid', payment_date = ? WHERE id = ?");
    $stmt->bind_param("si", $current_date, $id);

    if ($stmt->execute()) {
        $_SESSION['success_message'] = "บันทึกการชำระเงินสำเร็จแล้วครับ";
    } else {
        $_SESSION['error_message'] = "เกิดข้อผิดพลาด: " . $conn->error;
    }
    $stmt->close();

    // 🚀 จุดสำคัญ: เปลี่ยนจาก index.php เป็น manage_bills.php
    // พร้อมแนบพารามิเตอร์ month กลับไปด้วยเพื่อให้หน้าจอไม่รีเซ็ต
    $redirect_url = "manage_bills.php";
    if (!empty($month)) {
        $redirect_url .= "?month=" . urlencode($month);
    }
    
    header("Location: " . $redirect_url);
    exit();

} else {
    // หากเข้าไฟล์นี้โดยไม่ผ่านฟอร์ม ให้กลับไปหน้าจัดการบิล
    header("Location: manage_bills.php");
    exit();
}

$conn->close();
>>>>>>> b3c7638653082b907eb612c49ef346ef3806ad14
?>