<?php
// process_ocr.php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['image'])) {
    $curl = curl_init();
    
    // host.docker.internal คือที่อยู่ของเครื่อง Windows เมื่อมองจากใน Docker
    curl_setopt_array($curl, [
        CURLOPT_URL => 'http://host.docker.internal:5000/scan',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => [
            'image' => new CURLFile($_FILES['image']['tmp_name'], $_FILES['image']['type'], $_FILES['image']['name'])
        ],
    ]);

    $response = curl_exec($curl);
    curl_close($curl);

    echo $response; // ส่งผลลัพธ์จาก Python กลับไปหน้าเว็บ
}