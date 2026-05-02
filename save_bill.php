<?php
include 'check_session.php'; 
include 'db_connect.php'; 

// 1. ตรวจสอบสิทธิ์และวิธีการส่งข้อมูล
if ($_SESSION['role'] !== 'admin' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Access Denied!");
}

// 2. รับค่าจากฟอร์ม
$room_number = $_POST['room_number'];
$billing_month_str = $_POST['billing_month']; // รูปแบบ YYYY-MM
$room_rent = (float)$_POST['room_rent'];
$num_people = (int)$_POST['num_people'];
$image_name = $_POST['image_name'] ?? '';

// 🔥 รับค่ามิเตอร์เป็น String เพื่อรักษาเลข 0 ตัวหน้า (เช่น 0125)
$elec_prev = trim($_POST['elec_prev']); 
$elec_new = trim($_POST['elec_new_confirmed']); 

// 3. 🔥 [Snapshot Logic] ดึงชื่อผู้เช่าปัจจุบันมาเก็บไว้ในบิล
// เพื่อให้ข้อมูลประวัติไม่เปลี่ยนตามผู้เช่าคนใหม่ในอนาคต
$stmt_user = $conn->prepare("SELECT full_name FROM users WHERE username = ?");
$stmt_user->bind_param("s", $room_number);
$stmt_user->execute();
$res_user = $stmt_user->get_result();
$user_data = $res_user->fetch_assoc();

// หากไม่พบชื่อ (กรณีห้องว่างแต่เผลอออกบิล) ให้ใส่ค่าเริ่มต้นไว้
$tenant_name_snapshot = $user_data['full_name'] ?? 'ผู้เช่าทั่วไป'; 
$stmt_user->close();

// 4. ตรวจสอบบิลซ้ำในเดือนเดียวกัน (Data Integrity)
$billing_month_date = $billing_month_str . '-01';
$check_sql = "SELECT id FROM billing_records WHERE room_number = ? AND billing_month = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("ss", $room_number, $billing_month_date);
$check_stmt->execute();
if ($check_stmt->get_result()->num_rows > 0) {
    $_SESSION['alert_error'] = "ข้อผิดพลาด: ห้อง $room_number มีการบันทึกบิลของเดือนนี้ไปแล้ว!";
    header("Location: manage_bills.php?month=" . $billing_month_str);
    exit;
}
$check_stmt->close();

// 5. ส่วนคำนวณบิล
$price_per_elec_unit = 8.00; 
$water_cost_per_person = 100.00; 
$total_water_cost = $num_people * $water_cost_per_person;

// คำนวณหน่วยไฟ (แปลงเป็น float เฉพาะตอนคำนวณ)
$elec_units = (float)$elec_new - (float)$elec_prev; 
if ($elec_units < 0) $elec_units = 0; 
$elec_cost = $elec_units * $price_per_elec_unit;

// คำนวณยอดรวมสุทธิ
$total_cost = $room_rent + $elec_cost + $total_water_cost;

// 6. บันทึกลง Database
$status = 'pending'; // สถานะเริ่มต้นคือ "ยังไม่ชำระ"

$stmt = $conn->prepare(
    "INSERT INTO billing_records 
    (room_number, tenant_name, billing_month, num_people, room_rent, elec_prev, elec_new, elec_units, elec_cost, water_cost, total_cost, status, record_date, elec_image_path) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)"
);

if ($stmt === false) {
     die("<b>Database Error:</b> " . $conn->error);
}

// ผูกตัวแปร (ใช้ "s" สำหรับเลขมิเตอร์เพื่อรักษาเลข 0)
// ลำดับ: room, tenant, month, people, rent, prev, new, units, e_cost, w_cost, total, status, image
$stmt->bind_param("sssisssddddss", 
    $room_number, 
    $tenant_name_snapshot, // 🔥 บันทึกชื่อผู้เช่า ณ วันที่ออกบิล
    $billing_month_date, 
    $num_people, 
    $room_rent, 
    $elec_prev, 
    $elec_new, 
    $elec_units, 
    $elec_cost, 
    $total_water_cost, 
    $total_cost,
    $status,
    $image_name
);

if ($stmt->execute()) {
    $stmt->close();
    $conn->close();
    
    // ตั้งข้อความแจ้งเตือนความสำเร็จ
    $_SESSION['alert_success'] = "บันทึกบิลห้อง $room_number สำเร็จ! (ผู้เช่า: $tenant_name_snapshot)";
    
    // ย้อนกลับไปหน้าจัดการบิล
    header("Location: manage_bills.php?month=" . $billing_month_str); 
    exit;
} else {
    $_SESSION['alert_error'] = "Error: ไม่สามารถบันทึกข้อมูลได้ " . $stmt->error;
    header("Location: create_bill.php");
    exit;
}
?>