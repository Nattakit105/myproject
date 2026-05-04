<?php
require_once __DIR__ . '/vendor/autoload.php';
include 'db_connect.php';

if (!isset($_GET['id'])) { die("ไม่พบรหัสรายการ"); }
$id = $_GET['id'];

// 🔥 แก้ไข SQL: ดึงข้อมูลชื่อผู้เช่าปัจจุบันหรือชื่อที่บันทึกไว้
$sql = "SELECT b.*, u.full_name AS renter_name 
        FROM billing_records AS b 
        LEFT JOIN users AS u ON b.room_number = u.username 
        WHERE b.id = ?";

$stmt = $conn->prepare($sql);

if ($stmt === false) {
    die("<b>SQL Error:</b> " . $conn->error);
}

$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) { die("ไม่พบข้อมูล"); }
$record = $result->fetch_assoc();
$stmt->close();
$conn->close();

// เลือกชื่อ: ใช้ชื่อในบิล (tenant_name) เป็นหลัก
$display_name = !empty($record['tenant_name']) ? $record['tenant_name'] : ($record['renter_name'] ?? '(ย้ายออกแล้ว)');

$dorm_name = "บ้านปรายฟ้ารีสอร์ท"; 
$dorm_address = "567 ม.9 ถ.ลำปาง-แม่ทะ ต.ชมพู อ.เมือง จ.ลำปาง 52000"; 
$dorm_phone = "โทร. 063-528-9198 , 093-132-1069";
$invoice_number = "INV-" . date("Ym", strtotime($record['record_date'])) . "-" . str_pad($record['id'], 4, '0', STR_PAD_LEFT);

// จัดการวันที่ภาษาไทย
$thai_month_names = ["", "มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน", "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม"];
$billing_time = strtotime($record['record_date']);
$billing_date = date("j", $billing_time) . " " . $thai_month_names[(int)date("n", $billing_time)] . " " . (date("Y", $billing_time) + 543);

$dateObj = DateTime::createFromFormat('!Y-m-d', $record["billing_month"]);
$billing_month_thai = $thai_month_names[(int)$dateObj->format('n')] . ' ' . ($dateObj->format('Y') + 543);

$html = '
<!DOCTYPE html>
<html>
<head>
<style>
    body { font-family: "thsarabun"; font-size: 16pt; line-height: 1.35; color: #000; }
    .container { width: 100%; }
    .header { text-align: center; margin-bottom: 18px; }
    .header h1 { margin: 0; font-size: 28pt; font-weight: bold; }
    .header h2 { margin: 4px 0; font-size: 22pt; font-weight: bold; }
    .header p { margin: 0; font-size: 13pt; }
    .details { width: 100%; margin-top: 16px; border-collapse: collapse; }
    .details td { padding: 3px 0; font-size: 16pt; }
    .label { font-weight: bold; }
    .text-right { text-align: right; }
    .items-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    .items-table th { border: 1px solid #000; padding: 7px; background-color: #f2f2f2; font-weight: bold; }
    .items-table td { border: 1px solid #000; padding: 8px 10px; font-size: 15pt; }
    .details-row { font-size: 13pt; color: #444; display: block; margin-top: 2px; }
    .amount { text-align: right; vertical-align: middle; }
    .total-row td { background-color: #f7f7f7; font-weight: bold; font-size: 17pt; }
    .footer { margin-top: 25px; text-align: center; font-size: 13pt; }
    .payment-note { font-weight: bold; }
    .qr-code { text-align: center; margin-top: 15px; }
    .qr-code img { width: 120px; height: 120px; }
</style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="images/cat.png" style="max-height: 60px; margin-bottom: 5px;">
            <h1>ใบแจ้งหนี้</h1>
            <h2>'.htmlspecialchars($dorm_name).'</h2>
            <p>'.htmlspecialchars($dorm_address).'</p>
            <p>'.htmlspecialchars($dorm_phone).'</p>
        </div>

        <table class="details">
            <tr>
                <td style="width: 55%;"><span class="label">เลขที่:</span> '.htmlspecialchars($invoice_number).'</td>
                <td class="text-right"><span class="label">วันที่:</span> '.$billing_date.'</td>
            </tr>
            <tr>
                <td><span class="label">สำหรับห้อง:</span> '.htmlspecialchars($record['room_number']).'</td>
                <td class="text-right"><span class="label">รอบบิลเดือน:</span> '.$billing_month_thai.'</td>
            </tr>
            <tr>
                <td colspan="2"><span class="label">ผู้เข้าพัก:</span> '.htmlspecialchars($display_name).'</td>
            </tr>
        </table>

        <table class="items-table">
            <thead>
                <tr>
                    <th>รายการ</th>
                    <th style="width: 160px;">จำนวนเงิน (บาท)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>ค่าเช่าห้องพัก</td>
                    <td class="amount">'.number_format($record['room_rent'], 2).'</td>
                </tr>
                <tr>
                    <td>ค่าน้ำประปา (เหมาจ่าย '.htmlspecialchars($record['num_people']).' คน)</td>
                    <td class="amount">'.number_format($record['water_cost'], 2).'</td>
                </tr>
                <tr>
                    <td>
                        ค่าไฟฟ้าประจำเดือน
                        <span class="details-row">
                            (เลขมิเตอร์: '.htmlspecialchars($record['elec_new']).' - '.htmlspecialchars($record['elec_prev']).'
                            = '.htmlspecialchars($record['elec_units']).' หน่วย * 8 บาท)
                        </span>
                    </td>
                    <td class="amount">'.number_format($record['elec_cost'], 2).'</td>
                </tr>
                <tr class="total-row">
                    <td class="text-right">ยอดรวมสุทธิที่ต้องชำระ</td>
                    <td class="amount">'.number_format($record['total_cost'], 2).'</td>
                </tr>
            </tbody>
        </table>

        <div class="footer">
            <p class="payment-note">ช่องทางการชำระเงิน: ธนาคารกรุงศรีอยุธยา | ชื่อบัญชี: เกรียงศักดิ์ คำฟู | เลขที่บัญชี: 439-1148-541</p>
            <p>* กรุณาชำระภายในวันที่ 5 ของเดือนถัดไป ขอขอบพระคุณที่ใช้บริการ *</p>
        </div>

        <div class="qr-code">
            <img src="images/my_qr.png">
            <p>สแกนเพื่อชำระเงิน</p>
        </div>
    </div>
</body>
</html>';

$mpdf = new \Mpdf\Mpdf([
    'fontDir' => array_merge((new Mpdf\Config\ConfigVariables())->getDefaults()['fontDir'], [__DIR__ . '/custom_fonts']),
    'fontdata' => (new Mpdf\Config\FontVariables())->getDefaults()['fontdata'] + [
        'thsarabun' => [
            'R' => 'THSarabunNew.ttf',
            'I' => 'THSarabunNew Italic.ttf',
            'B' => 'THSarabunNew Bold.ttf',
            'BI'=> 'THSarabunNew BoldItalic.ttf',
        ]
    ],
    'default_font' => 'thsarabun',
    'margin_left' => 15,
    'margin_right' => 15,
    'margin_top' => 10,
    'margin_bottom' => 10,
    'autoScriptToLang' => true,
    'autoLangToFont' => true
]);

$mpdf->WriteHTML($html);
$mpdf->Output("Invoice-".$record['room_number'].".pdf", "I");
>>>>>>> b3c7638653082b907eb612c49ef346ef3806ad14
exit();