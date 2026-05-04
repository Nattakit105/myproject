<?php
session_start();
require_once __DIR__ . '/vendor/autoload.php';
include 'db_connect.php';
include 'check_session.php';

if ($_SESSION['role'] !== 'admin') {
    die('Access Denied!');
}

$selected_month = $_GET['month'] ?? date('Y-m');
if (!preg_match('/^\d{4}-\d{2}$/', $selected_month)) {
    die('Invalid month');
}

$start_date = $selected_month . '-01';
$end_date = date('Y-m-t', strtotime($start_date));

$sql = "SELECT b.*, u.full_name AS renter_name
        FROM billing_records AS b
        LEFT JOIN users AS u ON b.room_number = u.username
        WHERE b.billing_month BETWEEN ? AND ?
        ORDER BY CAST(b.room_number AS UNSIGNED) ASC, b.id ASC";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die('SQL Error: ' . $conn->error);
}

$stmt->bind_param('ss', $start_date, $end_date);
$stmt->execute();
$records = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();

if (empty($records)) {
    die('ไม่พบบิลในรอบเดือนนี้');
}

$thai_month_names = [
    '', 'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน',
    'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'
];

$date_obj = DateTime::createFromFormat('!Y-m-d', $start_date);
$billing_month_thai = $thai_month_names[(int)$date_obj->format('n')] . ' ' . ($date_obj->format('Y') + 543);
$dorm_name = 'บ้านปรายฟ้ารีสอร์ท';
$dorm_address = '567 ม.9 ถ.ลำปาง-แม่ทะ ต.ชมพู อ.เมือง จ.ลำปาง 52000';
$dorm_phone = 'โทร. 063-528-9198 , 093-132-1069';
$logo_path = __DIR__ . '/images/cat.png';
$qr_path = __DIR__ . '/images/my_qr.png';

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function invoice_page(array $record, array $thai_month_names, string $billing_month_thai, string $dorm_name, string $dorm_address, string $dorm_phone, string $logo_path, string $qr_path): string {
    $display_name = !empty($record['tenant_name']) ? $record['tenant_name'] : ($record['renter_name'] ?? '(ย้ายออกแล้ว)');
    $record_time = strtotime($record['record_date'] ?? 'now');
    $billing_date = date('j', $record_time) . ' ' . $thai_month_names[(int)date('n', $record_time)] . ' ' . (date('Y', $record_time) + 543);
    $invoice_number = 'INV-' . date('Ym', $record_time) . '-' . str_pad((string)$record['id'], 4, '0', STR_PAD_LEFT);

    return '
    <div class="invoice-page">
        <div class="header">
            <img src="' . h($logo_path) . '" style="max-height: 60px; margin-bottom: 5px;">
            <h1>ใบแจ้งหนี้</h1>
            <h2>' . h($dorm_name) . '</h2>
            <p>' . h($dorm_address) . '</p>
            <p>' . h($dorm_phone) . '</p>
        </div>

        <table class="details">
            <tr>
                <td style="width: 55%;"><span class="label">เลขที่:</span> ' . h($invoice_number) . '</td>
                <td class="text-right"><span class="label">วันที่:</span> ' . h($billing_date) . '</td>
            </tr>
            <tr>
                <td><span class="label">สำหรับห้อง:</span> ' . h($record['room_number']) . '</td>
                <td class="text-right"><span class="label">รอบบิลเดือน:</span> ' . h($billing_month_thai) . '</td>
            </tr>
            <tr>
                <td colspan="2"><span class="label">ผู้เข้าพัก:</span> ' . h($display_name) . '</td>
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
                    <td class="amount">' . number_format((float)$record['room_rent'], 2) . '</td>
                </tr>
                <tr>
                    <td>ค่าน้ำประปา (เหมาจ่าย ' . h($record['num_people']) . ' คน)</td>
                    <td class="amount">' . number_format((float)$record['water_cost'], 2) . '</td>
                </tr>
                <tr>
                    <td>
                        ค่าไฟฟ้าประจำเดือน
                        <span class="details-row">
                            (เลขมิเตอร์: ' . h($record['elec_new']) . ' - ' . h($record['elec_prev']) . '
                            = ' . h($record['elec_units']) . ' หน่วย)
                        </span>
                    </td>
                    <td class="amount">' . number_format((float)$record['elec_cost'], 2) . '</td>
                </tr>
                <tr class="total-row">
                    <td class="text-right">ยอดรวมสุทธิที่ต้องชำระ</td>
                    <td class="amount">' . number_format((float)$record['total_cost'], 2) . '</td>
                </tr>
            </tbody>
        </table>

        <div class="footer">
            <p class="payment-note">ช่องทางการชำระเงิน: ธนาคารกรุงศรีอยุธยา | ชื่อบัญชี: เกรียงศักดิ์ คำฟู | เลขที่บัญชี: 439-1148-541</p>
            <p>* กรุณาชำระภายในวันที่ 5 ของเดือนถัดไป ขอขอบพระคุณที่ใช้บริการ *</p>
        </div>

        <div class="qr-code">
            <img src="' . h($qr_path) . '">
            <p>สแกนเพื่อชำระเงิน</p>
        </div>
    </div>';
}

$stylesheet = '
body { font-family: "thsarabun"; font-size: 16pt; line-height: 1.35; color: #000; }
.invoice-page { width: 100%; page-break-after: always; }
.invoice-page:last-child { page-break-after: auto; }
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
';

$mpdf_tmp = '/tmp/mpdf';
if (!is_dir($mpdf_tmp)) {
    mkdir($mpdf_tmp, 0777, true);
}

$mpdf = new \Mpdf\Mpdf([
    'tempDir' => $mpdf_tmp,
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

$html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>' . $stylesheet . '</style></head><body>';
foreach ($records as $record) {
    $html .= invoice_page($record, $thai_month_names, $billing_month_thai, $dorm_name, $dorm_address, $dorm_phone, $logo_path, $qr_path);
}
$html .= '</body></html>';

$mpdf->WriteHTML($html);
$mpdf->Output('Invoices-' . $selected_month . '.pdf', 'I');
exit();
