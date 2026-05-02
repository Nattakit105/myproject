<?php 
include 'header.php'; 
include 'check_session.php'; 
include 'db_connect.php'; 

if ($_SESSION['role'] !== 'admin') { die("Access Denied!"); }

// --- 1. ดึงสถิติพื้นฐาน ---
$total_rooms = $conn->query("SELECT COUNT(*) as total FROM rooms")->fetch_assoc()['total'];
$occupied_rooms = $conn->query("SELECT COUNT(DISTINCT username) as total FROM users WHERE role = 'tenant'")->fetch_assoc()['total'];
$occupancy_rate = ($total_rooms > 0) ? ($occupied_rooms / $total_rooms) * 100 : 0;

// รายได้เดือนปัจจุบัน (เฉพาะที่จ่ายแล้ว)
$current_month = date('Y-m');
$revenue_res = $conn->query("SELECT SUM(total_cost) as total FROM billing_records WHERE billing_month LIKE '$current_month%' AND (status = 'paid' OR payment_date IS NOT NULL)");
$monthly_revenue = $revenue_res->fetch_assoc()['total'] ?? 0;

// ยอดค้างชำระทั้งหมด (ที่ยังไม่ได้จ่าย)
$unpaid_res = $conn->query("SELECT SUM(total_cost) as total FROM billing_records WHERE status != 'paid' AND payment_date IS NULL");
$total_unpaid = $unpaid_res->fetch_assoc()['total'] ?? 0;

// --- 2. เตรียมข้อมูลกราฟ 6 เดือนย้อนหลัง ---
$chart_labels = [];
$chart_data = [];
for ($i = 5; $i >= 0; $i--) {
    $m = date('Y-m', strtotime("-$i months"));
    $chart_labels[] = date('M', strtotime("-$i months"));
    $rev = $conn->query("SELECT SUM(total_cost) as total FROM billing_records WHERE billing_month LIKE '$m%' AND (status = 'paid' OR payment_date IS NOT NULL)")->fetch_assoc()['total'] ?? 0;
    $chart_data[] = $rev;
}
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
    body { background-color: #0f172a; color: #f8fafc; font-family: 'Kanit', sans-serif; }
    .stat-card { background: #1e293b; border: none; border-radius: 1.25rem; padding: 1.5rem; transition: 0.3s; }
    .stat-card:hover { transform: translateY(-5px); background: #26334d; }
    .text-muted-custom { color: #94a3b8; font-size: 0.85rem; }
    .chart-container { background: #1e293b; border-radius: 1.5rem; padding: 2rem; }
    .btn-quick { background: #334155; color: #fff; border: none; border-radius: 12px; padding: 12px; text-align: left; transition: 0.2s; }
    .btn-quick:hover { background: #475569; padding-left: 20px; }
</style>

<div class="container-fluid mt-4 px-md-5">
    <div class="mb-4">
        <h2 class="fw-bold">แดชบอร์ด</h2>
        <p class="text-muted-custom">ภาพรวมการเงินและสถานะห้องพักของบ้านปลายฟ้ารีสอร์ท</p>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="text-muted-custom mb-1">ห้องทั้งหมด</div>
                <h2 class="fw-bold mb-0"><?php echo $total_rooms; ?></h2>
                <div class="small text-info mt-2"><i class="bi bi-door-open"></i> พร้อมให้บริการ</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="text-muted-custom mb-1">อัตราการเข้าพัก</div>
                <h2 class="fw-bold mb-0"><?php echo number_format($occupancy_rate, 1); ?>%</h2>
                <div class="progress mt-2" style="height: 6px;">
                    <div class="progress-bar bg-success" style="width: <?php echo $occupancy_rate; ?>%"></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card border-start border-success border-4">
                <div class="text-muted-custom mb-1">รายได้ที่เก็บได้เดือนนี้</div>
                <h2 class="fw-bold mb-0 text-success">฿<?php echo number_format($monthly_revenue, 2); ?></h2>
                <div class="text-muted-custom small mt-1">อ้างอิงจากบิลที่ยืนยันจ่ายแล้ว</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card border-start border-danger border-4">
                <div class="text-muted-custom mb-1">ยอดค้างชำระรวม</div>
                <h2 class="fw-bold mb-0 text-danger">฿<?php echo number_format($total_unpaid, 2); ?></h2>
                <div class="text-muted-custom small mt-1">รวมบิลค้างจ่ายทุกเดือน</div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="chart-container shadow-sm">
                <h6 class="fw-bold mb-4"><i class="bi bi-graph-up-arrow me-2"></i>วิเคราะห์รายได้ย้อนหลัง 6 เดือน</h6>
                <canvas id="revChart" height="280"></canvas>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="chart-container">
                <h6 class="fw-bold mb-4">จัดการงานด่วน</h6>
                <div class="d-grid gap-2">
                    <a href="create_bill.php" class="btn-quick text-decoration-none d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-pencil-square me-2 text-warning"></i> จดมิเตอร์/ออกบิล</span>
                        <i class="bi bi-chevron-right small"></i>
                    </a>
                    <a href="manage_bills.php" class="btn-quick text-decoration-none d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-cash-stack me-2 text-success"></i> รับชำระเงิน</span>
                        <i class="bi bi-chevron-right small"></i>
                    </a>
                    <a href="manage_tenants.php" class="btn-quick text-decoration-none d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-person-plus me-2 text-info"></i> เพิ่มผู้เช่าใหม่</span>
                        <i class="bi bi-chevron-right small"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const ctx = document.getElementById('revChart').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($chart_labels); ?>,
        datasets: [{
            label: 'รายได้สุทธิ',
            data: <?php echo json_encode($chart_data); ?>,
            borderColor: '#3b82f6',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            borderWidth: 3,
            fill: true,
            tension: 0.4
        }]
    },
    options: {
        plugins: { legend: { display: false } },
        scales: {
            y: { ticks: { color: '#94a3b8' }, grid: { color: 'rgba(255,255,255,0.05)' } },
            x: { ticks: { color: '#94a3b8' }, grid: { display: false } }
        }
    }
});
</script>