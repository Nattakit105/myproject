<?php
require_once __DIR__ . '/vendor/autoload.php';
include 'db_connect.php';
include 'check_session.php';

if ($_SESSION['role'] !== 'admin') { die("Access Denied!"); }

$selected_month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');
$start_date     = $selected_month . '-01';
$end_date       = date("Y-m-t", strtotime($start_date));

$thai_month_names = ["", "มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน", "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม"];
$p = explode('-', $selected_month);
$month_thai = $thai_month_names[(int)$p[1]] . ' ' . ((int)$p[0] + 543);

$summary = ['total_units' => 0, 'elec_income' => 0, 'water_income' => 0, 'total_income' => 0, 'room_count' => 0];
$details = [];

$stmt = $conn->prepare("SELECT b.room_number, b.tenant_name, u.full_name AS renter_name,
                               b.elec_prev, b.elec_new, b.elec_units, b.elec_cost, b.water_cost, b.room_rent, b.total_cost, b.status
                        FROM billing_records b
                        LEFT JOIN users u ON b.room_number = u.username
                        WHERE b.billing_month BETWEEN ? AND ?
                        ORDER BY CAST(b.room_number AS UNSIGNED) ASC");
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $details[] = $row;
    $summary['total_units']  += $row['elec_units'];
    $summary['elec_income']  += $row['elec_cost'];
    $summary['water_income'] += $row['water_cost'];
    $summary['total_income'] += $row['total_cost'];
    $summary['room_count']++;
}
$stmt->close();
$conn->close();

$dorm_name    = "บ้านปรายฟ้ารีสอร์ท";
$dorm_address = "567 ม.9 ถ.ลำปาง-แม่ทะ ต.ชมพู อ.เมือง จ.ลำปาง 52000";
$dorm_phone   = "โทร. 063-528-9198 , 093-132-1069";
$print_date   = date("j") . " " . $thai_month_names[(int)date("n")] . " " . (date("Y") + 543);

$rows_html = '';
$i = 1;
foreach ($details as $row) {
    $name       = !empty($row['tenant_name']) ? $row['tenant_name'] : ($row['renter_name'] ?? '-');
    $paid_badge = ($row['status'] === 'paid') ? '<span style="color:#15803d;font-weight:bold;">ชำระแล้ว</span>' : '<span style="color:#b91c1c;">ค้างชำระ</span>';
    $rows_html .= '<tr>
        <td style="text-align:center;">' . $i++ . '</td>
        <td style="text-align:center;font-weight:bold;">' . htmlspecialchars($row['room_number']) . '</td>
        <td>' . htmlspecialchars($name) . '</td>
        <td style="text-align:center;">' . htmlspecialchars($row['elec_prev']) . ' - ' . htmlspecialchars($row['elec_new']) . '</td>
        <td style="text-align:center;">' . number_format($row['elec_units'], 1) . '</td>
        <td style="text-align:right;">' . number_format($row['elec_cost'], 2) . '</td>
        <td style="text-align:right;">' . number_format($row['water_cost'], 2) . '</td>
        <td style="text-align:right;">' . number_format($row['room_rent'], 2) . '</td>
        <td style="text-align:right;font-weight:bold;">' . number_format($row['total_cost'], 2) . '</td>
        <td style="text-align:center;">' . $paid_badge . '</td>
    </tr>';
}

$html = '
<!DOCTYPE html>
<html>
<head>
<style>
    body { font-family: "thsarabun"; font-size: 14pt; line-height: 1.4; color: #000; }
    .header { text-align: center; margin-bottom: 15px; border-bottom: 2px solid #000; padding-bottom: 10px; }
    .header h1 { margin: 0; font-size: 18pt; font-weight: bold; }
    .header h2 { margin: 2px 0; font-size: 14pt; font-weight: bold; }
    .header p { margin: 1px 0; font-size: 10pt; }
    .summary-box { width: 100%; border-collapse: collapse; margin: 8px 0; }
    .summary-box td { border: 1px solid #ddd; padding: 6px 10px; font-size: 11pt; }
    .summary-box .label { background: #f1f5f9; font-weight: bold; color: #475569; width: 22%; font-size: 10pt; }
    .summary-box .value { font-size: 12pt; font-weight: bold; color: #1a56db; width: 28%; }
    .items-table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 12pt; }
    .items-table th { border: 1px solid #000; padding: 6px 4px; background: #1e293b; color: #fff; font-weight: bold; text-align: center; font-size: 11pt; }
    .items-table td { border: 1px solid #aaa; padding: 6px 4px; }
    .items-table tr:nth-child(even) td { background: #f8fafc; }
    .total-row td { background: #f0f9ff; font-weight: bold; font-size: 13pt; border-top: 2px solid #000; }
    .footer { margin-top: 20px; font-size: 11pt; color: #555; text-align: right; }
    .section-title { font-size: 15pt; font-weight: bold; margin: 15px 0 5px 0; border-left: 4px solid #1a56db; padding-left: 8px; }
</style>
</head>
<body>
    <div class="header">
        <img src="' . __DIR__ . '/images/cat.png" style="max-height: 45px; margin-bottom: 3px;">
        <h1>' . htmlspecialchars($dorm_name) . '</h1>
        <h2>สรุปสาธารณูปโภคประจำเดือน ' . $month_thai . '</h2>
        <p>' . htmlspecialchars($dorm_address) . '</p>
        <p>' . htmlspecialchars($dorm_phone) . '</p>
    </div>

    <div class="section-title">สรุปรายรับรวม</div>
    <table class="summary-box">
        <tr>
            <td class="label">จำนวนบิล</td>
            <td class="value">' . $summary['room_count'] . ' ห้อง</td>
            <td class="label">หน่วยไฟรวม</td>
            <td class="value">' . number_format($summary['total_units'], 1) . ' หน่วย</td>
        </tr>
        <tr>
            <td class="label">รายรับค่าไฟ</td>
            <td class="value">฿' . number_format($summary['elec_income'], 2) . '</td>
            <td class="label">รายรับค่าน้ำ</td>
            <td class="value">฿' . number_format($summary['water_income'], 2) . '</td>
        </tr>
        <tr style="background:#f0fdf4;">
            <td colspan="3" style="text-align:right;font-weight:bold;font-size:11pt;color:#374151;border:1px solid #ddd;padding:6px 10px;">รายรับรวมทั้งหมด</td>
            <td style="font-size:14pt;font-weight:bold;color:#15803d;border:1px solid #ddd;padding:6px 10px;">฿' . number_format($summary['total_income'], 2) . '</td>
        </tr>
    </table>

    <div class="section-title">รายละเอียดรายห้อง</div>
    <table class="items-table">
        <thead>
            <tr>
                <th>#</th>
                <th>ห้อง</th>
                <th>ชื่อผู้เช่า</th>
                <th>มิเตอร์เดิม - ใหม่</th>
                <th>หน่วย</th>
                <th>ค่าไฟ (บาท)</th>
                <th>ค่าน้ำ (บาท)</th>
                <th>ค่าเช่า (บาท)</th>
                <th>รวม (บาท)</th>
                <th>สถานะ</th>
            </tr>
        </thead>
        <tbody>
            ' . $rows_html . '
            <tr class="total-row">
                <td colspan="4" style="text-align:right;">รวมทั้งหมด</td>
                <td style="text-align:center;">' . number_format($summary['total_units'], 1) . '</td>
                <td style="text-align:right;">฿' . number_format($summary['elec_income'], 2) . '</td>
                <td style="text-align:right;">฿' . number_format($summary['water_income'], 2) . '</td>
                <td></td>
                <td style="text-align:right;">฿' . number_format($summary['total_income'], 2) . '</td>
                <td></td>
            </tr>
        </tbody>
    </table>

    <div class="footer">พิมพ์เมื่อวันที่ ' . $print_date . '</div>
</body>
</html>';

$mpdf_tmp = '/tmp/mpdf';
if (!is_dir($mpdf_tmp)) mkdir($mpdf_tmp, 0777, true);

$mpdf = new \Mpdf\Mpdf([
    'tempDir'   => $mpdf_tmp,
    'fontDir'   => array_merge((new Mpdf\Config\ConfigVariables())->getDefaults()['fontDir'], [__DIR__ . '/custom_fonts']),
    'fontdata'  => (new Mpdf\Config\FontVariables())->getDefaults()['fontdata'] + [
        'thsarabun' => [
            'R'  => 'THSarabunNew.ttf',
            'I'  => 'THSarabunNew Italic.ttf',
            'B'  => 'THSarabunNew Bold.ttf',
            'BI' => 'THSarabunNew BoldItalic.ttf',
        ]
    ],
    'default_font'     => 'thsarabun',
    'margin_left'      => 10,
    'margin_right'     => 10,
    'margin_top'       => 10,
    'margin_bottom'    => 10,
    'orientation'      => 'L',
    'autoScriptToLang' => true,
    'autoLangToFont'   => true
]);

$mpdf->WriteHTML($html);
$mpdf->Output("Utilities-" . $selected_month . ".pdf", "I");
exit();
