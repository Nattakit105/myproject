<?php
include 'db_connect.php';

$room = $_GET['room'] ?? '';
$data = [
    'labels' => [], 
    'elec_cost' => [], 
    'water_cost' => [], 
    'room_rent' => []
];

if ($room) {
    // ดึงข้อมูลย้อนหลัง 12 เดือน
    $sql = "SELECT billing_month, elec_cost, water_cost, room_rent 
            FROM billing_records 
            WHERE room_number = ? 
            ORDER BY billing_month DESC 
            LIMIT 12";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $room);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $temp = [];
    while($row = $result->fetch_assoc()){ $temp[] = $row; }
    $temp = array_reverse($temp); // เรียงจากเก่าไปใหม่

    $thai_short = ["01" => "ม.ค.", "02" => "ก.พ.", "03" => "มี.ค.", "04" => "เม.ย.", "05" => "พ.ค.", "06" => "มิ.ย.", "07" => "ก.ค.", "08" => "ส.ค.", "09" => "ก.ย.", "10" => "ต.ค.", "11" => "พ.ย.", "12" => "ธ.ค."];

    foreach($temp as $r) {
        $date = strtotime($r['billing_month']);
        $label = $thai_short[date('m', $date)] . " " . (date('y', $date) + 43);
        
        $data['labels'][] = $label;
        $data['elec_cost'][] = (float)$r['elec_cost'];
        $data['water_cost'][] = (float)$r['water_cost'];
        $data['room_rent'][] = (float)$r['room_rent'];
    }
}

header('Content-Type: application/json');
echo json_encode($data);
?>