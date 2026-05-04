<?php 
ob_start(); 
session_start();
include 'db_connect.php'; 
include 'check_session.php';
include 'header.php';  

if ($_SESSION['role'] !== 'admin') { die("Access Denied!"); }

// 🇹🇭 1. เตรียมชุดข้อมูลเดือนภาษาไทย
$thai_months = ["01" => "มกราคม", "02" => "กุมภาพันธ์", "03" => "มีนาคม", "04" => "เมษายน", "05" => "พฤษภาคม", "06" => "มิถุนายน", "07" => "กรกฎาคม", "08" => "สิงหาคม", "09" => "กันยายน", "10" => "ตุลาคม", "11" => "พฤศจิกายน", "12" => "ธันวาคม"];

// 2. ดึงรายการเดือน
$month_sql = "SELECT DISTINCT DATE_FORMAT(billing_month, '%Y-%m') AS month_value FROM billing_records ORDER BY month_value DESC";
$month_result = $conn->query($month_sql);
$available_months = [];
while($row = $month_result->fetch_assoc()) { $available_months[] = $row['month_value']; }
$selected_month = (isset($_GET['month'])) ? $_GET['month'] : ($available_months[0] ?? date('Y-m'));

// 3. เตรียมโครงสร้างห้องพัก
$all_rooms = [];
$room_res = $conn->query("SELECT room_number FROM rooms ORDER BY CAST(room_number AS UNSIGNED) ASC");
while($r = $room_res->fetch_assoc()) {
    $all_rooms[$r['room_number']] = ['display_name' => null, 'is_snapshot' => false, 'bill_data' => null];
}

// 4. ดึงข้อมูล Snapshot
$start_date = $selected_month . '-01';
$end_date = date("Y-m-t", strtotime($start_date));
$stmt = $conn->prepare("SELECT id, room_number, tenant_name, total_cost, payment_date FROM billing_records WHERE billing_month BETWEEN ? AND ?");
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$bill_res = $stmt->get_result();
while($b = $bill_res->fetch_assoc()) {
    if (isset($all_rooms[$b['room_number']])) {
        $all_rooms[$b['room_number']]['bill_data'] = $b;
        $all_rooms[$b['room_number']]['display_name'] = $b['tenant_name'];
        $all_rooms[$b['room_number']]['is_snapshot'] = true;
    }
}
$stmt->close();

// 5. ดึงชื่อปัจจุบัน (Fallback)
$tenant_res = $conn->query("SELECT username, full_name FROM users WHERE role = 'tenant'");
while($t = $tenant_res->fetch_assoc()) {
    if (isset($all_rooms[$t['username']]) && !$all_rooms[$t['username']]['is_snapshot']) {
        $all_rooms[$t['username']]['display_name'] = $t['full_name'];
    }
}

// 6. คำนวณสถิติ
$paid_count = 0; $unpaid_count = 0; $nobill_count = 0; $empty_count = 0; $occupied_count = 0;
foreach($all_rooms as $room) {
    if ($room['display_name']) {
        $occupied_count++; // 🚩 เพิ่มสถิติห้องที่มีผู้เช่า
        if ($room['bill_data']) {
            if (!empty($room['bill_data']['payment_date'])) $paid_count++; else $unpaid_count++;
        } else { $nobill_count++; }
    } else { $empty_count++; }
}
?>

<style>
    html[data-theme="dark"] body { color: #ffffff; }
    html[data-theme="light"] body { color: #0f172a; }

    /* 🏹 ส่วนเลือกเดือน (Pill) */
    .filter-pill { 
        background: rgba(255, 255, 255, 0.1); 
        border: 1px solid rgba(255, 255, 255, 0.3); 
        backdrop-filter: blur(10px);
    }
    .form-select-custom { 
        background: transparent; color: #ffffff !important; border: none; font-weight: bold; cursor: pointer;
        -webkit-appearance: none; -moz-appearance: none; appearance: none;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23ffffff' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e");
        background-repeat: no-repeat; background-position: right 0.75rem center; background-size: 16px 12px; 
        padding-right: 2.5rem !important;
    }
    .form-select-custom option { background-color: #1e293b; color: #fff; }
    html[data-theme="light"] .filter-pill {
        background: #ffffff;
        border-color: #cbd5e1;
    }
    html[data-theme="light"] .filter-pill label,
    html[data-theme="light"] .form-select-custom {
        color: #0f172a !important;
    }
    html[data-theme="light"] .form-select-custom {
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%230f172a' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e");
    }
    html[data-theme="light"] .form-select-custom option { background-color: #ffffff; color: #0f172a; }

    /* 🏠 การ์ดห้องพัก */
    .room-card { border-radius: 1.5rem; transition: 0.3s; border: 4px solid transparent !important; }
    .room-card:hover { transform: translateY(-8px); box-shadow: 0 15px 30px rgba(0,0,0,0.5); }

    /* ⚪ สีห้องว่าง (ขาว/ดำ) | 🌑 สีห้องมีคน (เทาเข้ม) */
    .bg-empty-white { background-color: #ffffff !important; }
    .bg-empty-white h5, .bg-empty-white span, .bg-empty-white i, .bg-empty-white div { color: #0f172a !important; }
    .bg-occupied-dark { background-color: #1e293b !important; }
    .bg-occupied-dark h5, .bg-occupied-dark span, .bg-occupied-dark i, .bg-occupied-dark div { color: #ffffff !important; }
    html[data-theme="light"] .room-card.bg-occupied-dark,
    html[data-theme="dark"] .room-card.bg-occupied-dark { background-color: #1e293b !important; }
    html[data-theme="light"] .room-card.bg-empty-white,
    html[data-theme="dark"] .room-card.bg-empty-white { background-color: #ffffff !important; }

    /* สีกรอบสถานะบิล */
    .border-success { border-color: #00c781 !important; }
    .border-danger { border-color: #ff3b4f !important; }
    .border-warning { border-color: #ff9f00 !important; }
    .border-empty { border-color: #cbd5e1 !important; }
    html[data-theme="dark"] .border-empty { border-color: #94a3b8 !important; }

    /* 💡 กล่องสถิติพิเศษ */
    .bg-occupied-grey { background-color: #1e293b !important; } /* สีเทาเข้มแบบเดิม */
    .bg-occupied-grey h6, .bg-occupied-grey span { color: #ffffff !important; }
    .row.g-3.mb-4 .bg-success h6,
    .row.g-3.mb-4 .bg-success span,
    .row.g-3.mb-4 .bg-danger h6,
    .row.g-3.mb-4 .bg-danger span,
    .row.g-3.mb-4 .bg-primary h6,
    .row.g-3.mb-4 .bg-primary span { color: #ffffff !important; }
    .row.g-3.mb-4 .bg-warning h6,
    .row.g-3.mb-4 .bg-warning span { color: #0f172a !important; }
    html[data-theme="light"] .card.bg-white.bg-opacity-10 { background-color: #ffffff !important; }
    html[data-theme="light"] .card.bg-white.bg-opacity-10 span { color: #0f172a !important; }
    .room-legend-card {
        background: #ffffff !important;
        color: #0f172a !important;
        border-radius: 1rem !important;
        box-shadow: 0 10px 24px -16px rgba(15,23,42,0.45);
    }
    .room-legend {
        gap: 1rem 1.35rem !important;
        font-size: 1.18rem;
        line-height: 1.2;
    }
    .legend-item {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        color: #0f172a !important;
        white-space: nowrap;
    }
    .legend-swatch {
        width: 28px;
        height: 28px;
        display: inline-block;
        border-radius: 8px;
        flex: 0 0 28px;
    }
    .legend-dot {
        font-size: 1.65rem;
        line-height: 1;
    }
    .legend-occupied { background: #1e293b; border: 3px solid #64748b; }
    .legend-empty { background: #ffffff; border: 3px solid #64748b; }
    html[data-theme="dark"] .room-legend-card {
        background: #1e293b !important;
        color: #f8fafc !important;
        border: 1px solid #334155;
    }
    html[data-theme="light"] .room-legend-card { background: #ffffff !important; color: #0f172a !important; }
    html[data-theme="dark"] .room-legend-card .legend-item,
    html[data-theme="dark"] .room-legend-card .legend-item span:not(.legend-dot):not(.legend-swatch) {
        color: #f8fafc !important;
    }
    html[data-theme="dark"] .room-legend-card .legend-empty { border-color: #e2e8f0; }
    html[data-theme="dark"] .room-legend-card .text-success { color: #22c55e !important; }
    html[data-theme="dark"] .room-legend-card .text-danger { color: #ef4444 !important; }
    html[data-theme="dark"] .room-legend-card .text-warning { color: #f59e0b !important; }
    html[data-theme="light"] .room-legend-card .text-success { color: #15803d !important; }
    html[data-theme="light"] .room-legend-card .text-danger { color: #dc2626 !important; }
    html[data-theme="light"] .room-legend-card .text-warning { color: #b45309 !important; }
</style>

<div class="container-fluid mt-4 px-md-5">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <h1 class="h3 mb-0 fw-bold"><i class="bi bi-grid-3x3-gap-fill text-primary me-2"></i>จัดการห้องพัก</h1>
        
        <form action="index.php" method="GET" class="d-flex filter-pill p-2 rounded-pill px-3 align-items-center shadow-sm">
            <label class="me-2 mb-0 small fw-bold">ประจำเดือน:</label>
            <select name="month" class="form-select-custom form-select-sm" onchange="this.form.submit()">
                <?php foreach ($available_months as $m): 
                    $p = explode('-', $m); $y_th = $p[0]+543; $m_th = $thai_months[$p[1]]; ?>
                    <option value="<?php echo $m; ?>" <?php echo ($selected_month == $m) ? 'selected' : ''; ?>>
                        <?php echo "$m_th $y_th"; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <div class="row g-3 mb-4 text-center">
        <div class="col-md-2 col-4"><div class="card border-0 bg-success bg-opacity-75"><div class="card-body p-2"><h6>ชำระแล้ว</h6><span class="h3 fw-bold"><?php echo $paid_count; ?></span></div></div></div>
        <div class="col-md-2 col-4"><div class="card border-0 bg-danger bg-opacity-75"><div class="card-body p-2"><h6>ยังไม่ชำระ</h6><span class="h3 fw-bold"><?php echo $unpaid_count; ?></span></div></div></div>
        <div class="col-md-2 col-4"><div class="card border-0 bg-warning bg-opacity-75"><div class="card-body p-2"><h6 class="text-dark">รอสร้างบิล</h6><span class="h3 fw-bold text-dark"><?php echo $nobill_count; ?></span></div></div></div>
        
        <div class="col-md-2 col-4"><div class="card border-0 bg-occupied-grey"><div class="card-body p-2"><h6>ห้องผู้เช่า</h6><span class="h3 fw-bold"><?php echo $occupied_count; ?></span></div></div></div>
        
        <div class="col-md-2 col-4"><div class="card border-0 bg-empty-white"><div class="card-body p-2"><h6 class="text-dark">ห้องว่าง</h6><span class="h3 fw-bold text-dark"><?php echo $empty_count; ?></span></div></div></div>
        
        <div class="col-md-2 col-4"><div class="card border-0 bg-primary bg-opacity-75"><div class="card-body p-2"><h6>รวมทั้งหมด</h6><span class="h3 fw-bold"><?php echo count($all_rooms); ?></span></div></div></div>
    </div>

    <div class="card room-legend-card border-0 mb-4">
        <div class="card-body py-2 text-center">
            <div class="room-legend d-flex flex-wrap justify-content-center fw-bold">
                <span class="legend-item"><span class="legend-swatch legend-occupied"></span>เทา: มีผู้เช่า</span>
                <span class="legend-item"><span class="legend-swatch legend-empty"></span>ขาว: ห้องว่าง</span>
                <span class="legend-item"><span class="legend-dot text-success">●</span>จ่ายแล้ว</span>
                <span class="legend-item"><span class="legend-dot text-danger">●</span>ค้างชำระ</span>
                <span class="legend-item"><span class="legend-dot text-warning">●</span>รอสร้างบิล</span>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-5">
        <?php foreach ($all_rooms as $room_num => $data): 
            $has_someone = !empty($data['display_name']);
            $bill = $data['bill_data'];

            $bg_class = $has_someone ? 'bg-occupied-dark' : 'bg-empty-white';
            $border_color = 'border-empty'; 
            if ($has_someone) {
                if ($bill) {
                    $border_color = !empty($bill['payment_date']) ? 'border-success' : 'border-danger';
                } else {
                    $border_color = 'border-warning';
                }
            }

            $link = $has_someone 
                ? ($bill ? "manage_bills.php?month=$selected_month" : "create_bill.php?room=$room_num")
                : "manage_tenants.php?add_room=$room_num";
        ?>
        <div class="col-6 col-md-4 col-lg-2">
            <a href="<?php echo $link; ?>" class="text-decoration-none">
                <div class="card h-100 room-card border <?php echo $bg_class; ?> <?php echo $border_color; ?> border-3 text-center py-4 shadow-sm">
                    <div class="card-body p-1">
                        <i class="bi bi-door-closed-fill fs-3 mb-2"></i>
                        <h5 class="fw-bold mb-1"><?php echo $room_num; ?></h5>
                        <div class="small text-truncate mt-1 px-2">
                            <?php if ($has_someone): ?>
                                <span class="fw-bold"><?php echo htmlspecialchars($data['display_name']); ?></span>
                            <?php else: ?>
                                <span>ว่าง</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php $conn->close(); include 'footer.php'; ?>
