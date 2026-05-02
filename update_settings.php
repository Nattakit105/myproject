<?php
include 'check_session.php';
include 'db_connect.php';

// ตรวจสอบว่าเป็น Admin
if ($_SESSION['role'] !== 'admin') { 
    die("Access Denied!"); 
}

// ตรวจสอบว่าเป็น POST Request
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 1. รับค่าและ Validate (ป้องกันค่าติดลบ หรือค่าว่าง)
    $water_rate = filter_var($_POST['water_rate'], FILTER_VALIDATE_FLOAT);
    $elec_rate = filter_var($_POST['elec_rate'], FILTER_VALIDATE_FLOAT);

    if ($water_rate === false || $water_rate < 0 || $elec_rate === false || $elec_rate < 0) {
        $_SESSION['error_message'] = "ค่าที่กรอกไม่ถูกต้อง กรุณาลองอีกครั้ง";
        header("Location: settings.php");
        exit();
    }

    // 2. อัปเดตค่าน้ำ (ใช้ Prepared Statement)
    $stmt_water = $conn->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'WATER_RATE_PER_PERSON'");
    $stmt_water->bind_param("s", $water_rate); // "s" เพราะใน DB เราตั้งเป็น VARCHAR
    $stmt_water->execute();
    $stmt_water->close();

    // 3. อัปเดตค่าไฟ (ใช้ Prepared Statement)
    $stmt_elec = $conn->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'ELECTRICITY_RATE_PER_UNIT'");
    $stmt_elec->bind_param("s", $elec_rate);
    $stmt_elec->execute();
    $stmt_elec->close();

    $conn->close();

    // 4. แจ้งเตือนและส่งกลับ
    $_SESSION['success_message'] = "บันทึกค่าใหม่สำเร็จ!";
    header("Location: settings.php");
    exit();

} else {
    // ถ้าไม่ใช่ POST ให้เด้งกลับ
    header("Location: settings.php");
    exit();
}
?>