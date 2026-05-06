<?php 
include 'header.php'; 
include 'check_session.php'; 
include 'db_connect.php'; 

if ($_SESSION['role'] !== 'admin') { die("Access Denied!"); }

// 🇹🇭 1. เตรียมข้อมูลภาษาไทย
$thai_months = ["01" => "มกราคม", "02" => "กุมภาพันธ์", "03" => "มีนาคม", "04" => "เมษายน", "05" => "พฤษภาคม", "06" => "มิถุนายน", "07" => "กรกฎาคม", "08" => "สิงหาคม", "09" => "กันยายน", "10" => "ตุลาคม", "11" => "พฤศจิกายน", "12" => "ธันวาคม"];

$selected_year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
$requested_month = isset($_GET['month']) && preg_match('/^\d{4}-\d{2}$/', $_GET['month'])
    ? $_GET['month']
    : date('Y-m');
$selected_month_num = substr($requested_month, 5, 2);
$selected_month = sprintf('%04d-%02d', $selected_year, (int)$selected_month_num);

// 2. ดึงข้อมูลเดือนที่มีการบันทึกบิลไว้
$active_months = [];
$check_sql = "SELECT DISTINCT DATE_FORMAT(billing_month, '%Y-%m') as m_val FROM billing_records WHERE YEAR(billing_month) = ?";
$stmt_check = $conn->prepare($check_sql);
if ($stmt_check) {
    $stmt_check->bind_param("i", $selected_year);
    $stmt_check->execute();
    $res_check = $stmt_check->get_result();
    while($row = $res_check->fetch_assoc()) { $active_months[] = $row['m_val']; }
    $stmt_check->close();
}

// 3. ดึงข้อมูลบิลรายห้อง (แสดงทุกบิล ไม่มีการทับซ้อน)
$records = [];
$start_date = $selected_month . '-01';
$end_date = date("Y-m-t", strtotime($start_date));

$sql = "SELECT b.*, u.full_name AS renter_name 
        FROM billing_records AS b
        LEFT JOIN users AS u ON b.room_number = u.username
        WHERE b.billing_month BETWEEN ? AND ? 
        ORDER BY b.payment_date DESC, CAST(b.room_number AS UNSIGNED) ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();
while($row = $result->fetch_assoc()) { $records[] = $row; }
$stmt->close();

function meter_image_url($path) {
    $path = trim((string)$path);
    if ($path === '') {
        return '';
    }

    $path = str_replace('\\', '/', $path);
    if (preg_match('#^(https?://|/)#', $path)) {
        return $path;
    }

    return strpos($path, '/') !== false ? $path : 'uploads/' . $path;
}
?>

<style>
    html[data-theme="dark"] body { background-color: #0f172a !important; color: #f8fafc !important; font-family: 'Kanit', sans-serif; }
    html[data-theme="light"] body { background-color: #f1f5f9 !important; color: #0f172a !important; font-family: 'Kanit', sans-serif; }
    .card-modern { border: none; border-radius: 1rem; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
    html[data-theme="dark"] .card-modern { background: #1e293b !important; color: #f8fafc !important; }
    html[data-theme="light"] .card-modern { background: #ffffff !important; color: #0f172a !important; }
    .month-grid { display: grid; grid-template-columns: repeat(6, 1fr); gap: 10px; }
    @media (max-width: 768px) { .month-grid { grid-template-columns: repeat(3, 1fr); } }
    .month-item { padding: 12px 5px; border-radius: 0.75rem; text-align: center; border: 1px solid #e2e8f0; background: white; color: #64748b; transition: 0.2s ease; text-decoration: none; font-size: 0.8rem; }
    html[data-theme="dark"] .month-item { background: #0f172a; border-color: #334155; color: #cbd5e1; }
    html[data-theme="light"] .month-item { background: #ffffff; border-color: #e2e8f0; color: #475569; }
    .month-item:hover,
    .month-item:focus {
        background: #2563eb;
        color: #ffffff;
        border-color: #60a5fa;
        box-shadow: 0 10px 22px -14px rgba(37, 99, 235, 0.9);
        transform: translateY(-2px);
        outline: none;
    }
    .month-item.active {
        background: #3b82f6;
        color: white;
        border-color: #93c5fd;
        font-weight: bold;
        box-shadow: 0 0 0 2px rgba(147, 197, 253, 0.45), 0 12px 24px -16px rgba(37, 99, 235, 0.95);
    }
    .month-item.active:hover,
    .month-item.active:focus {
        background: #1d4ed8;
        border-color: #bfdbfe;
        color: #ffffff;
    }
    .month-item.has-data { border-bottom: 3px solid #10b981; }
    
    .badge-status { border-radius: 2rem; padding: 0.4rem 1rem; font-size: 0.75rem; font-weight: bold; display: inline-block; }
    .bg-paid { background: #dcfce7; color: #15803d; }
    .bg-pending { background: #fee2e2; color: #b91c1c; }
    
    .text-payment-info { font-size: 0.75rem; color: #64748b; line-height: 1.2; }
    .section-title { color: var(--text-main); font-weight: 700; border-left: 5px solid #3b82f6; padding-left: 15px; }
    .page-subtitle { color: var(--text-muted) !important; }
    html[data-theme="dark"] .page-subtitle { color: #cbd5e1 !important; }
    html[data-theme="light"] .page-subtitle { color: #64748b !important; }
    html[data-theme="dark"] .table { color: #f8fafc !important; }
    html[data-theme="dark"] .billing-table thead th { background: #ffffff !important; color: #64748b !important; border-color: #d9e2ec !important; }
    html[data-theme="dark"] .billing-table tbody td { background: #ffffff !important; color: #0f172a !important; border-color: #d9e2ec !important; }
    html[data-theme="dark"] .billing-table .text-dark { color: #0f172a !important; }
    html[data-theme="dark"] .table .text-muted,
    html[data-theme="dark"] .text-payment-info { color: #64748b !important; }
    html[data-theme="light"] .table { color: #0f172a !important; }
    html[data-theme="light"] .billing-table thead th { background: #ffffff !important; color: #64748b !important; border-color: #d9e2ec !important; }
    html[data-theme="light"] .billing-table tbody td { background: #ffffff !important; color: #0f172a !important; border-color: #d9e2ec !important; }
    .billing-table .fw-bold:not(.text-primary):not(.text-success):not(.text-danger):not(.text-warning) { color: #0f172a !important; }
    .billing-table .badge-status { color: inherit !important; }
    .billing-table .bg-paid { background: #dcfce7 !important; color: #15803d !important; }
    .billing-table .bg-pending { background: #fee2e2 !important; color: #b91c1c !important; }
    html[data-theme="light"] .btn-white,
    html[data-theme="light"] .btn-light { background: #ffffff !important; color: #0f172a !important; }
    html[data-theme="dark"] .btn-white,
    html[data-theme="dark"] .btn-light { background: #334155 !important; color: #f8fafc !important; border-color: #475569 !important; }
    html[data-theme="dark"] .btn-light.text-primary,
    html[data-theme="dark"] .billing-table .btn-light.text-primary,
    html[data-theme="light"] .billing-table .btn-light.text-primary { color: #2563eb !important; border-color: #cbd5e1 !important; }
    html[data-theme="dark"] .btn-light.text-warning,
    html[data-theme="dark"] .billing-table .btn-light.text-warning,
    html[data-theme="light"] .billing-table .btn-light.text-warning { color: #ca8a04 !important; border-color: #cbd5e1 !important; }
    html[data-theme="dark"] .btn-light.text-danger,
    html[data-theme="dark"] .billing-table .btn-light.text-danger,
    html[data-theme="light"] .billing-table .btn-light.text-danger { color: #dc2626 !important; border-color: #cbd5e1 !important; }
    html[data-theme="dark"] .billing-table .btn-light:hover,
    html[data-theme="light"] .billing-table .btn-light:hover {
        background: #eef6ff !important;
        border-color: #93c5fd !important;
    }
    .billing-table .tool-btn {
        width: 30px;
        height: 30px;
        padding: 0;
        background: #ffffff !important;
        border: 1px solid #cbd5e1 !important;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    .billing-table .tool-btn:hover {
        background: #eef6ff !important;
        border-color: #93c5fd !important;
    }
    .billing-table .tool-btn-print,
    .billing-table .tool-btn-print i { color: #2563eb !important; }
    .billing-table .tool-btn-edit,
    .billing-table .tool-btn-edit i { color: #ca8a04 !important; }
    .billing-table .tool-btn-image,
    .billing-table .tool-btn-image i { color: #16a34a !important; }
    .billing-table .tool-btn-delete,
    .billing-table .tool-btn-delete i { color: #dc2626 !important; }
    .billing-table .tool-btn:disabled {
        opacity: 0.38;
        cursor: not-allowed;
    }
    .meter-image-modal .modal-content {
        background: #ffffff;
        color: #0f172a;
        border: 0;
        border-radius: 1rem;
    }
    .meter-image-preview {
        max-height: 72vh;
        width: 100%;
        object-fit: contain;
        background: #f8fafc;
    }
    .meter-meta {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 10px;
        margin-bottom: 12px;
    }
    .meter-meta-item {
        background: #f8fafc;
        border: 1px solid #d9e2ec;
        border-radius: 10px;
        padding: 8px 10px;
    }
    .meter-meta-label {
        color: #64748b;
        display: block;
        font-size: 0.72rem;
        font-weight: 700;
        margin-bottom: 2px;
    }
    .meter-meta-value {
        color: #0f172a;
        font-weight: 800;
    }
    @media (max-width: 767.98px) {
        .meter-meta { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    .bill-breakdown {
        min-width: 230px;
        max-width: 300px;
        color: #0f172a;
        font-size: 0.78rem;
        line-height: 1.35;
    }
    .bill-breakdown-row {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 12px;
        padding: 2px 0;
    }
    .bill-breakdown-label {
        color: #475569;
        text-align: left;
    }
    .bill-breakdown-value {
        color: #0f172a;
        font-weight: 700;
        white-space: nowrap;
        text-align: right;
    }
    .bill-breakdown-total {
        margin-top: 4px;
        padding-top: 5px;
        border-top: 1px solid #d9e2ec;
    }
    .bill-breakdown-total .bill-breakdown-label,
    .bill-breakdown-total .bill-breakdown-value {
        color: #0f172a;
        font-size: 0.9rem;
        font-weight: 800;
    }
    .bill-header-actions { justify-content: flex-end; }
    .print-all-btn {
        min-height: 44px;
        border-radius: 999px;
        border-color: #64748b;
        color: #f8fafc;
        background: transparent;
    }
    .print-all-btn:hover {
        background: #2563eb;
        border-color: #2563eb;
        color: #ffffff;
    }
    html[data-theme="light"] .print-all-btn {
        background: #ffffff;
        color: #0f172a;
        border-color: #cbd5e1;
    }
    html[data-theme="light"] .print-all-btn:hover {
        background: #2563eb;
        border-color: #2563eb;
        color: #ffffff;
    }
    .bill-year-nav {
        min-height: 44px;
        background: #1e293b;
        border: 1px solid #64748b;
        color: #f8fafc;
    }
    .bill-year-label {
        white-space: nowrap;
        line-height: 1;
        font-weight: 700;
        color: inherit;
    }
    .bill-year-btn {
        width: 34px;
        height: 30px;
        border-radius: 8px;
        border: 1px solid rgba(148, 163, 184, 0.55);
        color: #f8fafc;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        transition: 0.2s ease;
    }
    .bill-year-btn:hover {
        background: #2563eb;
        border-color: #2563eb;
        color: #ffffff;
    }
    html[data-theme="light"] .bill-year-nav {
        background: #ffffff;
        border-color: #cbd5e1;
        color: #0f172a;
    }
    html[data-theme="light"] .bill-year-btn {
        color: #0f172a;
        border-color: #cbd5e1;
    }
    html[data-theme="light"] .bill-year-btn:hover {
        color: #ffffff;
        background: #2563eb;
        border-color: #2563eb;
    }
    @media (max-width: 575.98px) {
        .bill-header-actions { width: 100%; justify-content: stretch; }
        .bill-year-nav,
        .print-all-btn { width: 100%; justify-content: center; }
    }
</style>

<div class="container-fluid mt-4 px-md-4">
    <div class="d-flex flex-wrap justify-content-between align-items-end mb-3 gap-3">
        <div>
            <h4 class="section-title mb-1">บันทึกการชำระเงินและติดตามสถานะ</h4>
            <p class="page-subtitle small mb-0 ms-4">จัดการประวัติ ยืนยันการรับชำระ และตรวจสอบเวลาเข้าจ่ายเงิน</p>
        </div>
        <div class="bill-header-actions d-flex align-items-center gap-2 flex-wrap">
            <?php if (!empty($records)): ?>
                <a href="generate_invoices.php?month=<?php echo urlencode($selected_month); ?>" target="_blank" class="print-all-btn btn btn-outline-light d-inline-flex align-items-center px-3 fw-bold">
                    <i class="bi bi-printer-fill me-1"></i> พิมพ์ทั้งหมด
                </a>
            <?php endif; ?>
            <div class="bill-year-nav d-flex align-items-center gap-2 rounded-pill shadow-sm px-3 py-2">
                <span class="bill-year-label small">ประจำปี พ.ศ. <?php echo $selected_year+543; ?></span>
                <a href="?year=<?php echo $selected_year-1; ?>&month=<?php echo ($selected_year-1) . '-' . $selected_month_num; ?>" class="bill-year-btn" title="ปีก่อนหน้า" aria-label="ปีก่อนหน้า"><i class="bi bi-chevron-left"></i></a>
                <a href="?year=<?php echo $selected_year+1; ?>&month=<?php echo ($selected_year+1) . '-' . $selected_month_num; ?>" class="bill-year-btn" title="ปีถัดไป" aria-label="ปีถัดไป"><i class="bi bi-chevron-right"></i></a>
            </div>
        </div>
    </div>

    <div class="card card-modern mb-4">
        <div class="card-body">
            <div class="month-grid">
                <?php foreach ($thai_months as $num => $name): 
                    $m_val = $selected_year . "-" . $num;
                    $is_active = ($selected_month == $m_val);
                    $has_data = in_array($m_val, $active_months);
                ?>
                    <a href="?month=<?php echo $m_val; ?>&year=<?php echo $selected_year; ?>" 
                       class="month-item <?php echo $is_active ? 'active' : ''; ?> <?php echo $has_data ? 'has-data' : ''; ?>">
                        <?php echo $name; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="card card-modern overflow-hidden">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table align-middle mb-0 billing-table">
                    <thead class="bg-light">
                        <tr class="text-muted small">
                            <th class="ps-4 py-3">ห้อง</th>
                            <th>ผู้เช่า (ประวัติในบิล)</th>
                            <th>รายการ</th>
                            <th class="text-center">สถานะบิล</th>
                            <th class="text-center">ข้อมูลการชำระ</th>
                            <th class="text-center">การจัดการ</th>
                            <th class="text-center pe-4">เครื่องมือ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($records)): ?>
                            <tr><td colspan="7" class="text-center py-5 text-muted">ไม่พบข้อมูลบันทึกบิลในรอบเดือนนี้</td></tr>
                        <?php else: ?>
                            <?php foreach ($records as $row): 
                                $display_name = !empty($row['tenant_name']) ? $row['tenant_name'] : ($row['renter_name'] ?? '(ย้ายออกแล้ว)');
                                $is_paid = ($row['status'] == 'paid' || !empty($row['payment_date']));
                                $meter_image = meter_image_url($row['elec_image_path'] ?? '');
                                $bill_month_label = '';
                                if (!empty($row['billing_month'])) {
                                    $bill_time = strtotime($row['billing_month']);
                                    $bill_month_num = date('m', $bill_time);
                                    $bill_month_label = ($thai_months[$bill_month_num] ?? date('F', $bill_time)) . ' ' . ((int)date('Y', $bill_time) + 543);
                                }
                            ?>
                            <tr>
                                <td class="ps-4 fw-bold h5 mb-0 text-primary"><?php echo htmlspecialchars($row["room_number"]); ?></td>
                                <td>
                                    <div class="fw-bold"><?php echo htmlspecialchars($display_name); ?></div>
                                    <div class="text-muted" style="font-size: 0.7rem;">ID: #<?php echo $row['id']; ?></div>
                                </td>
                                <td>
                                    <div class="bill-breakdown">
                                        <div class="bill-breakdown-row">
                                            <span class="bill-breakdown-label">ค่าเช่าห้องพัก</span>
                                            <span class="bill-breakdown-value">฿<?php echo number_format($row["room_rent"], 2); ?></span>
                                        </div>
                                        <div class="bill-breakdown-row">
                                            <span class="bill-breakdown-label">ค่าน้ำประปา (เหมาจ่าย <?php echo (int)$row["num_people"]; ?> คน)</span>
                                            <span class="bill-breakdown-value">฿<?php echo number_format($row["water_cost"], 2); ?></span>
                                        </div>
                                        <div class="bill-breakdown-row">
                                            <span class="bill-breakdown-label">ค่าไฟฟ้าประจำเดือน (<?php echo number_format($row["elec_units"], 1); ?> หน่วย)</span>
                                            <span class="bill-breakdown-value">฿<?php echo number_format($row["elec_cost"], 2); ?></span>
                                        </div>
                                        <div class="bill-breakdown-row bill-breakdown-total">
                                            <span class="bill-breakdown-label">ยอดสุทธิ</span>
                                            <span class="bill-breakdown-value">฿<?php echo number_format($row["total_cost"], 2); ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="badge-status <?php echo $is_paid ? 'bg-paid' : 'bg-pending'; ?>">
                                        <?php echo $is_paid ? 'ชำระแล้ว' : 'ค้างชำระ'; ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <?php if ($is_paid): ?>
                                        <div class="text-payment-info">
                                            <i class="bi bi-clock-history text-success"></i><br>
                                            <strong><?php echo date('d/m/Y', strtotime($row['payment_date'])); ?></strong><br>
                                            <?php echo date('H:i', strtotime($row['record_date'])); ?> น.
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted small">- รอชำระ -</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($is_paid): ?>
                                        <form action="cancel_payment.php" method="POST" class="d-inline" onsubmit="return confirm('ยกเลิกสถานะจ่ายเงิน?')">
                                            <input type="hidden" name="record_id" value="<?php echo $row['id']; ?>">
                                            <input type="hidden" name="month" value="<?php echo $selected_month; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-3 shadow-sm" style="font-size: 0.7rem;">ยกเลิกจ่าย</button>
                                        </form>
                                    <?php else: ?>
                                        <form action="mark_paid.php" method="POST" class="d-inline" onsubmit="return confirm('ยืนยันการรับชำระเงินห้อง <?php echo htmlspecialchars($row['room_number']); ?>?')">
                                            <input type="hidden" name="record_id" value="<?php echo $row['id']; ?>">
                                            <input type="hidden" name="month" value="<?php echo $selected_month; ?>">
                                            <button type="submit" class="btn btn-sm btn-success rounded-pill px-3 shadow-sm" style="font-size: 0.7rem;">ยืนยันการชำระ</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center pe-4">
                                    <div class="d-flex justify-content-center gap-1">
                                        <a href="generate_invoice.php?id=<?php echo $row['id']; ?>" class="btn btn-sm tool-btn tool-btn-print" title="พิมพ์ใบเสร็จ" target="_blank"><i class="bi bi-printer"></i></a>
                                        <a href="edit.php?id=<?php echo $row['id']; ?>" class="btn btn-sm tool-btn tool-btn-edit" title="แก้ไขบิล"><i class="bi bi-pencil-square"></i></a>
                                        <?php if ($meter_image !== ''): ?>
                                            <button type="button" class="btn btn-sm tool-btn tool-btn-image js-meter-image" title="ดูรูปมิเตอร์" data-image-url="<?php echo htmlspecialchars($meter_image, ENT_QUOTES, 'UTF-8'); ?>" data-room="<?php echo htmlspecialchars($row['room_number'], ENT_QUOTES, 'UTF-8'); ?>" data-tenant="<?php echo htmlspecialchars($display_name, ENT_QUOTES, 'UTF-8'); ?>" data-bill-id="<?php echo htmlspecialchars($row['id'], ENT_QUOTES, 'UTF-8'); ?>" data-bill-month="<?php echo htmlspecialchars($bill_month_label, ENT_QUOTES, 'UTF-8'); ?>">
                                                <i class="bi bi-image"></i>
                                            </button>
                                        <?php else: ?>
                                            <button type="button" class="btn btn-sm tool-btn tool-btn-image" title="ไม่มีรูปมิเตอร์" disabled>
                                                <i class="bi bi-image"></i>
                                            </button>
                                        <?php endif; ?>
                                        <form action="delete.php" method="POST" class="d-inline" onsubmit="return confirm('ลบบิลถาวร?')">
                                            <input type="hidden" name="id" value="<?php echo $row['id']; ?>"><input type="hidden" name="month" value="<?php echo $selected_month; ?>">
                                            <button type="submit" class="btn btn-sm tool-btn tool-btn-delete" title="ลบ"><i class="bi bi-trash"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade meter-image-modal" id="meterImageModal" tabindex="-1" aria-labelledby="meterImageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="meterImageModalLabel">รูปมิเตอร์</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="ปิด"></button>
            </div>
            <div class="modal-body p-3">
                <div class="meter-meta">
                    <div class="meter-meta-item">
                        <span class="meter-meta-label">ห้อง</span>
                        <span class="meter-meta-value" id="meterMetaRoom">-</span>
                    </div>
                    <div class="meter-meta-item">
                        <span class="meter-meta-label">ชื่อผู้เช่า</span>
                        <span class="meter-meta-value" id="meterMetaTenant">-</span>
                    </div>
                    <div class="meter-meta-item">
                        <span class="meter-meta-label">Bill ID</span>
                        <span class="meter-meta-value" id="meterMetaBillId">-</span>
                    </div>
                    <div class="meter-meta-item">
                        <span class="meter-meta-label">เดือน / ปี</span>
                        <span class="meter-meta-value" id="meterMetaMonth">-</span>
                    </div>
                </div>
                <img id="meterImagePreview" class="meter-image-preview rounded border" src="" alt="รูปมิเตอร์">
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modalElement = document.getElementById('meterImageModal');
    const modalTitle = document.getElementById('meterImageModalLabel');
    const modalImage = document.getElementById('meterImagePreview');
    const metaRoom = document.getElementById('meterMetaRoom');
    const metaTenant = document.getElementById('meterMetaTenant');
    const metaBillId = document.getElementById('meterMetaBillId');
    const metaMonth = document.getElementById('meterMetaMonth');
    if (!modalElement || !modalImage || typeof bootstrap === 'undefined') return;

    const meterModal = new bootstrap.Modal(modalElement);
    document.querySelectorAll('.js-meter-image').forEach(function(button) {
        button.addEventListener('click', function() {
            const imageUrl = this.getAttribute('data-image-url') || '';
            const room = this.getAttribute('data-room') || '';
            const tenant = this.getAttribute('data-tenant') || '-';
            const billId = this.getAttribute('data-bill-id') || '-';
            const billMonth = this.getAttribute('data-bill-month') || '-';
            modalTitle.textContent = room ? 'รูปมิเตอร์ห้อง ' + room : 'รูปมิเตอร์';
            if (metaRoom) metaRoom.textContent = room || '-';
            if (metaTenant) metaTenant.textContent = tenant;
            if (metaBillId) metaBillId.textContent = '#' + billId;
            if (metaMonth) metaMonth.textContent = billMonth;
            modalImage.src = imageUrl;
            meterModal.show();
        });
    });

    modalElement.addEventListener('hidden.bs.modal', function() {
        modalImage.src = '';
        if (metaRoom) metaRoom.textContent = '-';
        if (metaTenant) metaTenant.textContent = '-';
        if (metaBillId) metaBillId.textContent = '-';
        if (metaMonth) metaMonth.textContent = '-';
    });
});
</script>

<?php $conn->close(); include 'footer.php'; ?>
