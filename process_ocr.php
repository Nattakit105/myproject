<?php
// process_ocr.php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['image'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'กรุณาอัปโหลดรูปภาพ'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$ocr_url = getenv('OCR_SERVICE_URL') ?: 'http://ocr:5000/scan';
$curl = curl_init();

curl_setopt_array($curl, [
    CURLOPT_URL => $ocr_url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_TIMEOUT => 60,
    CURLOPT_POSTFIELDS => [
        'image' => new CURLFile(
            $_FILES['image']['tmp_name'],
            $_FILES['image']['type'],
            $_FILES['image']['name']
        )
    ],
]);

$response = curl_exec($curl);
$curl_error = curl_error($curl);
$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);

if ($response === false || $response === '') {
    http_response_code(502);
    echo json_encode([
        'success' => false,
        'message' => 'ไม่สามารถเชื่อมต่อระบบ OCR ได้',
        'debug' => $curl_error ?: 'Empty response from OCR service'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($http_code >= 400) {
    http_response_code($http_code);
}

echo $response;
