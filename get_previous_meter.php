<?php
include 'db_connect.php'; // เชื่อมต่อฐานข้อมูล

$room = $_GET['room'] ?? '';

if ($room) {
    // 1. ดึงเลขมิเตอร์ล่าสุดของห้องนี้จากตาราง billing_records
    $meter_sql = "SELECT elec_new FROM billing_records WHERE room_number = ? ORDER BY id DESC LIMIT 1";
    $stmt_meter = $conn->prepare($meter_sql);
    $stmt_meter->bind_param("s", $room);
    $stmt_meter->execute();
    $meter_result = $stmt_meter->get_result()->fetch_assoc();
    $previous_meter = $meter_result['elec_new'] ?? 0;

    // 2. ดึงราคาห้องที่ตั้งไว้จากตาราง users
    $price_sql = "SELECT room_price FROM users WHERE username = ? LIMIT 1";
    $stmt_price = $conn->prepare($price_sql);
    $stmt_price->bind_param("s", $room);
    $stmt_price->execute();
    $price_result = $stmt_price->get_result()->fetch_assoc();
    
    // หากยังไม่เคยตั้งราคา ให้ใช้ค่าพื้นฐาน 2500.00
    $room_price = $price_result['room_price'] ?? 2500.00;

    // 3. ส่งข้อมูลกลับไปเป็นรูปแบบ JSON เพื่อให้ JavaScript ใช้งานได้
    echo json_encode([
        'previous_meter' => $previous_meter,
        'room_price' => $room_price
    ]);
}
>>>>>>> b3c7638653082b907eb612c49ef346ef3806ad14
?>