<?php
include 'header.php'; 
include 'check_session.php'; 
include 'db_connect.php'; 

// 1. ดึง ID จาก URL
if (!isset($_GET['id'])) {
    die("ไม่พบรหัสรายการ");
}
$id = $_GET['id'];

// 2. ดึงข้อมูลบิล "ปัจจุบัน"
$record = null;
$stmt = $conn->prepare(
    "SELECT b.*, u.full_name 
     FROM billing_records AS b
     LEFT JOIN users AS u ON b.room_number = u.username
     WHERE b.id = ?"
);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("ไม่พบข้อมูลสำหรับรายการนี้");
}
$record = $result->fetch_assoc();
$stmt->close(); 

// 3. ดึงข้อมูลย้อนหลัง 6 เดือน (เอาเฉพาะค่าไฟ)
$room_number_for_chart = $record['room_number'];
$chart_data = [];
$stmt_chart = $conn->prepare(
    "SELECT billing_month, elec_cost 
     FROM billing_records 
     WHERE room_number = ? 
     ORDER BY billing_month DESC 
     LIMIT 6"
);
$stmt_chart->bind_param("s", $room_number_for_chart);
$stmt_chart->execute();
$result_chart = $stmt_chart->get_result();
while($row = $result_chart->fetch_assoc()) {
    $chart_data[] = $row;
}
$stmt_chart->close();
$conn->close(); 

// 4. เตรียมข้อมูลสำหรับส่งให้ JavaScript
$chart_data = array_reverse($chart_data); 
$chart_labels = [];
$chart_elec_data = []; 

$thai_month_short = ["", "ม.ค.", "ก.พ.", "มี.ค.", "เม.ย.", "พ.ค.", "มิ.ย.", "ก.ค.", "ส.ค.", "ก.ย.", "ต.ค.", "พ.ย.", "ธ.ค."];
foreach ($chart_data as $data) {
    $dateObj = new DateTime($data['billing_month']);
    $chart_labels[] = $thai_month_short[(int)$dateObj->format('n')] . ' ' . substr($dateObj->format('Y') + 543, -2);
    $chart_elec_data[] = $data['elec_cost']; 
}

// 5. เตรียมข้อมูลสำหรับแสดงผล
$thai_month_names = ["", "มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน", "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม"];
$billing_month_thai = '';
if(isset($record["billing_month"])) {
    $dateObj = DateTime::createFromFormat('!Y-m-d', $record["billing_month"]);
    $billing_month_thai = $thai_month_names[(int)$dateObj->format('n')] . ' ' . ($dateObj->format('Y') + 543);
}
$record_date_thai = date('d/m/Y H:i', strtotime($record['record_date']));

// (สันนิษฐานว่าคุณมีค่าคงที่เหล่านี้ใน config หรือ header.php)
if (!defined('ELECTRICITY_RATE_PER_UNIT')) define('ELECTRICITY_RATE_PER_UNIT', 8); 
if (!defined('WATER_RATE_PER_PERSON')) define('WATER_RATE_PER_PERSON', 100); 

$elec_rate = ELECTRICITY_RATE_PER_UNIT; 
$water_rate = WATER_RATE_PER_PERSON;
?>
<title>รายละเอียดบิล ห้อง <?php echo htmlspecialchars($record['room_number']); ?></title>

<div class="container mt-4" style="max-width: 700px;">
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h1 class="h4 mb-0">รายละเอียดบิลค่าหอพัก</h1>
            <p class="mb-0">
                ห้อง <strong><?php echo htmlspecialchars($record['room_number']); ?></strong> 
                (คุณ: <strong><?php echo htmlspecialchars($record['full_name']); ?></strong>)
            </p>
            <p class="mb-0">
                รอบบิล: <strong><?php echo $billing_month_thai; ?></strong>
            </p>
        </div>
        <div class="card-body p-4">
            <div class="text-center mb-4"><h2 class="h5 mb-2">สถานะบิล</h2><?php if ($record['payment_date'] !== NULL): ?><span class="badge bg-success fs-6 rounded-pill px-3 py-2"><i class="bi bi-check-circle-fill"></i> ชำระแล้ว (วันที่ <?php echo date('d/m/Y', strtotime($record['payment_date'])); ?>)</span><?php else: ?><span class="badge bg-danger fs-6 rounded-pill px-3 py-2"><i class="bi bi-x-circle-fill"></i> ยังไม่ชำระ</span><?php endif; ?></div>
            
            <ul class="list-group list-group-flush">
                <li class="list-group-item d-flex justify-content-between align-items-center"><span>💰 ค่าห้อง</span><span class="fw-bold fs-5"><?php echo number_format($record['room_rent'], 2); ?> บาท</span></li>
                <li class="list-group-item"><div class="d-flex justify-content-between align-items-center"><span>💧 ค่าน้ำ</span><span class="fw-bold fs-5"><?php echo number_format($record['water_cost'], 2); ?> บาท</span></div><small class="text-muted d-block mt-1">(เหมาจ่าย <?php echo htmlspecialchars($record['num_people']); ?> คน, คนละ <?php echo number_format($water_rate, 2); ?> บาท)</small></li>
                <li class="list-group-item"><div class="d-flex justify-content-between align-items-center"><span>⚡️ ค่าไฟ</span><span class="fw-bold fs-5"><?php echo number_format($record['elec_cost'], 2); ?> บาท</span></div><small class="text-muted d-block mt-1">(ใช้ไป <?php echo htmlspecialchars($record['elec_units']); ?> หน่วย, หน่วยละ <?php echo number_format($elec_rate, 2); ?> บาท)</small><small class="text-muted d-block">เลขมิเตอร์: <?php echo htmlspecialchars($record['elec_new']); ?> - <?php echo htmlspecialchars($record['elec_prev']); ?></small></li>
                
                <?php 
                // [แก้ไข] แก้ไข Path รูปภาพ (สันนิษฐานว่ารูปอยู่ใน 'uploads/')
                $image_display_path = 'uploads/' . $record['elec_image_path'];
                if ($record['elec_image_path'] && file_exists($image_display_path)): 
                ?>
                    <li class="list-group-item text-center">
                        <label class="form-label d-block mb-2">รูปมิเตอร์ไฟฟ้า:</label>
                        <img src="<?php echo htmlspecialchars($image_display_path); ?>" class="img-fluid border rounded p-1" style="max-height: 250px; cursor: pointer;" alt="รูปมิเตอร์ไฟฟ้า" onclick="window.open(this.src, '_blank');">
                        <small class="d-block text-muted mt-1">(คลิกที่รูปเพื่อดูภาพขนาดใหญ่)</small>
                    </li>
                <?php endif; ?>
                
                <li class="list-group-item d-flex justify-content-between align-items-center bg-light p-3"><strong class="h5 mb-0">ยอดรวมสุทธิ</strong><strong class="h4 mb-0 text-danger"><?php echo number_format($record['total_cost'], 2); ?> บาท</strong></li>

                <?php if ($_SESSION['role'] == 'admin' && $record['payment_date'] === NULL): ?>
                    <li class="list-group-item text-center p-3">
                        <form action="mark_as_paid.php" method="POST" onsubmit="return confirm('ยืนยันว่าได้รับชำระบิลนี้แล้ว ?');">
                            <input type="hidden" name="bill_id" value="<?php echo $id; // ใช้ $id จากด้านบนสุดของไฟล์ ?>">
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="bi bi-check-circle-fill"></i> ยืนยันการชำระเงิน
                            </button>
                        </form>
                    </li>
                <?php endif; ?>
                </ul>
        </div>
    </div>

    <div class="card shadow-sm mt-4">
        <div class="card-body">
            <h5 class="card-title text-center">📊 สรุปค่าไฟย้อนหลัง 6 เดือน</h5>
            <canvas id="usageChart"></canvas>
        </div>
    </div>

    <div class="text-center my-4"><a href="javascript:history.back()" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> กลับไปหน้ารายการ</a></div>
    <div class="text-muted text-center small mt-3">(ออกบิลเมื่อ: <?php echo $record_date_thai; ?> น.)</div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const labels = <?php echo json_encode($chart_labels); ?>;
    const elecData = <?php echo json_encode($chart_elec_data); ?>; 

    if (labels && labels.length > 0) {
        const ctx = document.getElementById('usageChart').getContext('2d');
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: '⚡️ ค่าไฟ',
                        data: elecData,
                        borderColor: '#ffc107',
                        backgroundColor: 'rgba(255, 193, 7, 0.1)',
                        fill: true,
                        tension: 0.1
                    }
                ]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: { display: true, text: 'ยอดเงิน (บาท)' }
                    },
                    x: {
                        title: { display: true, text: 'เดือน' }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) { label += ': '; }
                                if (context.parsed.y !== null) {
                                    label += new Intl.NumberFormat('th-TH', { style: 'currency', currency: 'THB' }).format(context.parsed.y);
                                }
                                return label;
                            }
                        }
                    }
                }
            }
        });
    } else {
        const chartCard = document.getElementById('usageChart').closest('.card');
        if(chartCard) {
            chartCard.style.display = 'none';
        }
    }
});
</script>

<?php include 'footer.php'; ?>