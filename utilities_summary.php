<?php 
// 1. จัดการระบบ Session และ Buffer (ต้องอยู่บรรทัดแรกสุด)
ob_start(); 
session_start();

// 2. ลำดับการเรียกไฟล์: เชื่อมต่อ DB -> เช็คสิทธิ์ -> วาด Header
include 'db_connect.php'; 
include 'check_session.php'; 
include 'header.php'; 

if ($_SESSION['role'] !== 'admin') { die("Access Denied!"); }

// 🇹🇭 เตรียมชุดข้อมูลเดือนภาษาไทย
$thai_months = ["01" => "มกราคม", "02" => "กุมภาพันธ์", "03" => "มีนาคม", "04" => "เมษายน", "05" => "พฤษภาคม", "06" => "มิถุนายน", "07" => "กรกฎาคม", "08" => "สิงหาคม", "09" => "กันยายน", "10" => "ตุลาคม", "11" => "พฤศจิกายน", "12" => "ธันวาคม"];
$thai_short = ["01" => "ม.ค.", "02" => "ก.พ.", "03" => "มี.ค.", "04" => "เม.ย.", "05" => "พ.ค.", "06" => "มิ.ย.", "07" => "ก.ค.", "08" => "ส.ค.", "09" => "ก.ย.", "10" => "ต.ค.", "11" => "พ.ย.", "12" => "ธ.ค."];

// 🚩 แก้ไข SQL: เปลี่ยน ORDER BY ให้ใช้ Alias (month_value) เพื่อเลี่ยง Fatal Error
$month_sql = "SELECT DISTINCT DATE_FORMAT(billing_month, '%Y-%m') AS month_value 
              FROM billing_records 
              ORDER BY month_value DESC"; 
$month_result = $conn->query($month_sql);
$available_months = [];
while($row = $month_result->fetch_assoc()) { $available_months[] = $row['month_value']; }

$selected_month = isset($_GET['month']) ? $_GET['month'] : (!empty($available_months) ? $available_months[0] : date('Y-m'));

// ดึงข้อมูลสรุปประจำเดือน
$summary = ['total_units' => 0, 'elec_income' => 0, 'water_income' => 0, 'room_count' => 0];
$details = [];
if ($selected_month) {
    $start_date = $selected_month . '-01';
    $end_date = date("Y-m-t", strtotime($start_date));
    
    $sql_monthly = "SELECT b.room_number, b.tenant_name, u.full_name AS renter_name, b.elec_units, b.elec_cost, b.water_cost 
                    FROM billing_records b
                    LEFT JOIN users u ON b.room_number = u.username
                    WHERE b.billing_month BETWEEN ? AND ?
                    ORDER BY CAST(b.room_number AS UNSIGNED) ASC";
    
    $stmt = $conn->prepare($sql_monthly);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $res = $stmt->get_result();
    while($row = $res->fetch_assoc()){
        $details[] = $row;
        $summary['total_units'] += $row['elec_units'];
        $summary['elec_income'] += $row['elec_cost'];
        $summary['water_income'] += $row['water_cost'];
        $summary['room_count']++;
    }
}

// ข้อมูลกราฟภาพรวมหอพัก
$graph_labels = []; $graph_elec = []; $graph_water = [];
$sql_trend = "SELECT DATE_FORMAT(billing_month, '%Y-%m') as mk, SUM(elec_cost) as te, SUM(water_cost) as tw 
              FROM billing_records GROUP BY mk ORDER BY mk ASC LIMIT 12";
$res_trend = $conn->query($sql_trend);
while($tr = $res_trend->fetch_assoc()){
    $p = explode('-', $tr['mk']);
    $graph_labels[] = $thai_short[$p[1]] . " " . (substr($p[0] + 543, 2));
    $graph_elec[] = $tr['te'];
    $graph_water[] = $tr['tw'];
}

// ดึงรายชื่อห้องทั้งหมดสำหรับตัวเลือกกราฟรายห้อง
$rooms_list = [];
$room_q = $conn->query("SELECT DISTINCT room_number FROM rooms ORDER BY CAST(room_number AS UNSIGNED) ASC");
while($rm = $room_q->fetch_assoc()){ $rooms_list[] = $rm['room_number']; }
?>

<!-- ส่วนแสดงผล HTML และ CSS (สไตล์ Gritty Dark) -->
<style>
    body { background-color: #0f172a; color: #fff; font-family: 'Kanit', sans-serif; }
    .card-stat { border: none; border-radius: 1rem; transition: 0.3s; }
    .card-dark { background: #1e293b; border: none; border-radius: 1.2rem; }
    .table-modern thead { background-color: #1e293b; color: #94a3b8; }
    .table-modern td { color: #f8fafc; border-color: rgba(255,255,255,0.05); }
    .form-select-dark { background: #334155; color: white; border: 1px solid #475569; }
</style>

<div class="container-fluid mt-4 px-md-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <h1 class="h3 mb-0 text-primary fw-bold"><i class="bi bi-bar-chart-line-fill me-2"></i>สรุปสาธารณูปโภค</h1>
        <form action="" method="GET" class="d-flex align-items-center bg-dark p-2 rounded shadow-sm border border-secondary">
            <label class="me-2 mb-0 small fw-bold text-white">รอบเดือน:</label>
            <select name="month" class="form-select form-select-sm border-0 fw-bold bg-transparent text-white" onchange="this.form.submit()">
                <?php foreach ($available_months as $m): 
                    $p = explode('-', $m); $m_th = $thai_months[$p[1]]; $y_th = $p[0] + 543; ?>
                    <option value="<?php echo $m; ?>" <?php echo ($selected_month == $m) ? 'selected' : ''; ?> class="text-dark"><?php echo "$m_th $y_th"; ?></option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <!-- แถบสถิติ -->
    <div class="row g-3 mb-4 text-center">
        <div class="col-md-3 col-6"><div class="card card-stat shadow-sm bg-warning text-dark"><div class="card-body"><h6>ไฟรวม (หน่วย)</h6><span class="h3 fw-bold"><?php echo number_format($summary['total_units'], 1); ?></span></div></div></div>
        <div class="col-md-3 col-6"><div class="card card-stat shadow-sm bg-success text-white"><div class="card-body"><h6>รายรับค่าไฟ</h6><span class="h3 fw-bold">฿<?php echo number_format($summary['elec_income'], 2); ?></span></div></div></div>
        <div class="col-md-3 col-6"><div class="card card-stat shadow-sm bg-info text-white"><div class="card-body"><h6>รายรับค่าน้ำ</h6><span class="h3 fw-bold">฿<?php echo number_format($summary['water_income'], 2); ?></span></div></div></div>
        <div class="col-md-3 col-6"><div class="card card-stat shadow-sm bg-secondary text-white"><div class="card-body"><h6>จำนวนบิล</h6><span class="h3 fw-bold"><?php echo $summary['room_count']; ?></span></div></div></div>
    </div>

    <!-- กราฟเทรนด์ภาพรวม -->
    <div class="card card-dark shadow-sm mb-4">
        <div class="card-body py-4">
            <h6 class="fw-bold mb-4 text-white"><i class="bi bi-graph-up me-2 text-warning"></i>สถิติรายรับรวมสะสมรายเดือน (หอพัก)</h6>
            <div style="height: 300px;"><canvas id="trendChart"></canvas></div>
        </div>
    </div>

    <!-- กราฟรายห้อง (แบบเส้น 3 ข้อมูล) -->
    <div class="card card-dark shadow-sm mb-4 border border-primary">
        <div class="card-body py-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h6 class="fw-bold mb-0 text-white"><i class="bi bi-house-door-fill me-2 text-primary"></i>แนวโน้มค่าใช้จ่ายรายห้อง (ย้อนหลัง 12 เดือน)</h6>
                <div style="width: 180px;">
                    <select id="roomSelector" class="form-select form-select-sm form-select-dark" onchange="loadRoomChart(this.value)">
                        <?php foreach($rooms_list as $rm): ?>
                            <option value="<?= $rm ?>"><?= "ห้อง " . $rm ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div style="height: 350px;"><canvas id="roomChart"></canvas></div>
        </div>
    </div>

    <!-- ตารางรายละเอียด -->
    <div class="card card-dark shadow-sm overflow-hidden mb-5">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 table-modern">
                    <thead>
                        <tr><th class="ps-4">ห้อง</th><th>ชื่อผู้เช่า (ประวัติ)</th><th class="text-center">หน่วยไฟ</th><th class="text-end">ค่าไฟ</th><th class="text-end pe-4">ค่าน้ำ</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($details as $row): 
                            $display_name = !empty($row['tenant_name']) ? $row['tenant_name'] : ($row['renter_name'] ?? '-'); ?>
                        <tr>
                            <td class="ps-4 fw-bold text-primary"><?php echo $row['room_number']; ?></td>
                            <td>
                                <div class="fw-bold text-dark"><?php echo htmlspecialchars($display_name); ?></div>
                            </td>
                            <td class="text-center text-dark"><?php echo number_format($row['elec_units'], 1); ?></td>
                            <td class="text-end text-danger fw-bold ">฿<?php echo number_format($row['elec_cost'], 2); ?></td>
                            <td class="text-end text-primary fw-bold pe-4 ">฿<?php echo number_format($row['water_cost'], 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// --- กราฟภาพรวม ---
const ctxTrend = document.getElementById('trendChart').getContext('2d');
new Chart(ctxTrend, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($graph_labels); ?>,
        datasets: [
            { label: 'ค่าไฟ (บาท)', data: <?php echo json_encode($graph_elec); ?>, borderColor: '#ffc107', tension: 0.3, fill: true, backgroundColor: 'rgba(255, 193, 7, 0.05)' },
            { label: 'ค่าน้ำ (บาท)', data: <?php echo json_encode($graph_water); ?>, borderColor: '#0dcaf0', tension: 0.3, fill: true, backgroundColor: 'rgba(13, 202, 240, 0.05)' }
        ]
    },
    options: { 
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { labels: { color: '#fff', font: { family: 'Kanit' } } } },
        scales: { 
            y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#94a3b8' } },
            x: { grid: { display: false }, ticks: { color: '#94a3b8' } }
        }
    }
});

// --- กราฟรายห้อง ---
let myRoomChart;
function loadRoomChart(room) {
    fetch(`get_room_stats.php?room=${room}`)
        .then(res => res.json())
        .then(data => {
            const ctxRoom = document.getElementById('roomChart').getContext('2d');
            if (myRoomChart) { myRoomChart.destroy(); }
            myRoomChart = new Chart(ctxRoom, {
                type: 'line',
                data: {
                    labels: data.labels,
                    datasets: [
                        { label: 'ค่าไฟ', data: data.elec_cost, borderColor: '#ffc107', borderWidth: 3, tension: 0.4, pointRadius: 4 },
                        { label: 'ค่าน้ำ', data: data.water_cost, borderColor: '#0dcaf0', borderWidth: 3, tension: 0.4, pointRadius: 4 },
                        { label: 'ค่าเช่า', data: data.room_rent, borderColor: '#a855f7', borderWidth: 3, tension: 0.4, pointRadius: 4 }
                    ]
                },
                options: { 
                    responsive: true, maintainAspectRatio: false,
                    plugins: { legend: { labels: { color: '#fff' } } },
                    scales: { y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#94a3b8' } }, x: { ticks: { color: '#94a3b8' } } }
                }
            });
        });
}

window.onload = () => {
    const defaultRoom = document.getElementById('roomSelector').value;
    if(defaultRoom) loadRoomChart(defaultRoom);
};
</script>

<?php $conn->close(); include 'footer.php'; ?>