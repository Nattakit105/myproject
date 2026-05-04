<?php
include 'check_session.php';
include 'db_connect.php'; // (ไฟล์นี้จะ define() ค่าน้ำ/ไฟ)

// ตรวจสอบว่าเป็น Admin
if ($_SESSION['role'] !== 'admin') {
    die("Access Denied!");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // --- 1. รับค่าและ Validate ---
    $id = $_POST['id'];
    $room_number = $_POST['room_number'];
    $num_people = filter_var($_POST['num_people'], FILTER_VALIDATE_INT);
    $billing_month = $_POST['billing_month'] . '-01'; 
    $room_rent = filter_var($_POST['room_rent'], FILTER_VALIDATE_FLOAT);
    $elec_prev = filter_var($_POST['elec_prev'], FILTER_VALIDATE_INT);
    $elec_new = filter_var($_POST['elec_new'], FILTER_VALIDATE_INT);
    $old_elec_image_path = $_POST['old_elec_image_path']; 

    $errors = [];

    if ($num_people === false || $num_people <= 0) { $errors[] = "จำนวนคนต้องเป็นตัวเลขที่มากกว่า 0"; }
    if ($room_rent === false || $room_rent < 0) { $errors[] = "ค่าห้องต้องเป็นตัวเลขไม่ติดลบ"; }
    if ($elec_prev === false || $elec_prev < 0) { $errors[] = "เลขมิเตอร์ครั้งก่อนต้องเป็นตัวเลขไม่ติดลบ"; }
    if ($elec_new === false || $elec_new < 0) { $errors[] = "เลขมิเตอร์ครั้งนี้ต้องเป็นตัวเลขไม่ติดลบ"; }
    if (empty($errors) && $elec_new < $elec_prev) { $errors[] = "เลขมิเตอร์ครั้งนี้ (" . $elec_new . ") ต้องไม่น้อยกว่าครั้งก่อน (" . $elec_prev . ")"; }

    // --- 2. จัดการการอัปโหลดรูปภาพใหม่ (ถ้ามีการอัปโหลด) ---
    $elec_image_path_to_db = $old_elec_image_path; 

    if (isset($_FILES['elec_image']) && $_FILES['elec_image']['error'] == UPLOAD_ERR_OK) {
        $file_tmp_name = $_FILES['elec_image']['tmp_name'];
        $file_name = basename($_FILES['elec_image']['name']);
        $file_type = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $file_size = $_FILES['elec_image']['size'];

        $allowed_types = ['jpg', 'jpeg', 'png'];
        if (!in_array($file_type, $allowed_types)) {
            $errors[] = "เฉพาะไฟล์ JPG, JPEG, PNG เท่านั้นที่อนุญาต";
        }
        if ($file_size > 2 * 1024 * 1024) { // 2MB
            $errors[] = "ขนาดไฟล์รูปภาพต้องไม่เกิน 2MB";
        }
        
        $new_file_name = 'elec_' . $room_number . '_' . date('YmdHis') . '.' . $file_type;
        $upload_dir = 'meter_readings/';
        $destination = $upload_dir . $new_file_name;

        if (empty($errors)) {
            if (move_uploaded_file($file_tmp_name, $destination)) {
                $elec_image_path_to_db = $destination; 
                // ลบรูปเก่า (ถ้ามี)
                if ($old_elec_image_path && file_exists($old_elec_image_path) && $old_elec_image_path !== $destination) {
                    unlink($old_elec_image_path);
                }
            } else {
                $errors[] = "มีปัญหาในการอัปโหลดไฟล์รูปภาพใหม่";
            }
        }
    } elseif (isset($_FILES['elec_image']) && $_FILES['elec_image']['error'] != UPLOAD_ERR_NO_FILE) {
        $errors[] = "เกิดข้อผิดพลาดในการอัปโหลดรูปภาพ: " . $_FILES['elec_image']['error'];
    }

    // 3. ถ้ามี Error ให้แสดงผลและหยุด
    if (!empty($errors)) {
        // (ส่ง Error กลับไปหน้า edit.php)
        include 'header.php';
        echo '<div class="container mt-4"><div class="alert alert-danger">';
        echo '<h4 class="alert-heading">❌ ข้อมูลไม่ถูกต้อง!</h4><ul>';
        foreach ($errors as $error) { echo '<li>' . $error . '</li>'; }
        echo '</ul><hr><a href="edit.php?id=' . $id . '" class="btn btn-primary">กลับไปแก้ไข</a>';
        echo '</div></div>';
        include 'footer.php';
        exit();
    }

    // 4. คำนวณ (ถ้าผ่านมาได้)
    $water_cost = $num_people * WATER_RATE_PER_PERSON;
    $elec_units = $elec_new - $elec_prev;
    $elec_cost = $elec_units * ELECTRICITY_RATE_PER_UNIT;
    $total_cost = $room_rent + $water_cost + $elec_cost;

    $water_prev = 0;
    $water_new = 0;
    $water_units = 0;

    // 5. อัปเดตฐานข้อมูล
    $stmt = $conn->prepare(
        "UPDATE billing_records SET 
            room_number = ?, billing_month = ?, num_people = ?, 
            water_prev = ?, water_new = ?, water_units = ?, water_cost = ?, 
            elec_prev = ?, elec_new = ?, elec_units = ?, elec_cost = ?, 
            room_rent = ?, total_cost = ?, elec_image_path = ? 
         WHERE id = ?"
    ); // <-- 15 question marks
    
    // --- [ นี่คือจุดที่แก้ไข ] ---
    // Type string "ssiiiidiiddddsi" (15 types)
    $stmt->bind_param("ssiiiidiiddddsi", 
        $room_number, $billing_month, $num_people, 
        $water_prev, $water_new, $water_units, 
        $water_cost, 
        $elec_prev, $elec_new, $elec_units, $elec_cost, $room_rent, $total_cost, 
        $elec_image_path_to_db, $id
    ); // <-- 15 variables
    // --- [ จบส่วนแก้ไข ] ---

    if ($stmt->execute()) {
        $_SESSION['success_message'] = "บันทึกการแก้ไขสำเร็จ!";
    } else {
        // นี่คือจุดที่สร้าง Error ที่คุณเห็น
        $_SESSION['error_message'] = "เกิดข้อผิดพลาดในการอัปเดต: " . htmlspecialchars($stmt->error);
    }
    $stmt->close();
    $conn->close();

    // 6. ส่งกลับไปหน้าจัดการบิล
    header("Location: manage_bills.php?month=" . date('Y-m', strtotime($billing_month)));
    exit();

} else {
    // ถ้าไม่ใช่ POST ให้เด้งกลับไปหน้าหลัก
    header("Location: index.php");
    exit();
}
>>>>>>> b3c7638653082b907eb612c49ef346ef3806ad14
?>