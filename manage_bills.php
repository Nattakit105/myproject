<?php 
include 'header.php'; 
include 'check_session.php'; 
include 'db_connect.php'; 

if ($_SESSION['role'] !== 'admin') { die("Access Denied!"); }

// 🇹🇭 1. เตรียมข้อมูลภาษาไทย
$thai_months = ["01" => "มกราคม", "02" => "กุมภาพันธ์", "03" => "มีนาคม", "04" => "เมษายน", "05" => "พฤษภาคม", "06" => "มิถุนายน", "07" => "กรกฎาคม", "08" => "สิงหาคม", "09" => "กันยายน", "10" => "ตุลาคม", "11" => "พฤศจิกายน", "12" => "ธันวาคม"];

$selected_year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
$selected_month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');

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
?>

<style>
    body { background-color: #f1f5f9; font-family: 'Kanit', sans-serif; }
    .card-modern { border: none; border-radius: 1rem; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
    .month-grid { display: grid; grid-template-columns: repeat(6, 1fr); gap: 10px; }
    @media (max-width: 768px) { .month-grid { grid-template-columns: repeat(3, 1fr); } }
    .month-item { padding: 12px 5px; border-radius: 0.75rem; text-align: center; border: 1px solid #e2e8f0; background: white; color: #64748b; transition: 0.2s; text-decoration: none; font-size: 0.8rem; }
    .month-item.active { background: #3b82f6; color: white; border-color: #3b82f6; font-weight: bold; }
    .month-item.has-data { border-bottom: 3px solid #10b981; }
    
    .badge-status { border-radius: 2rem; padding: 0.4rem 1rem; font-size: 0.75rem; font-weight: bold; display: inline-block; }
    .bg-paid { background: #dcfce7; color: #15803d; }
    .bg-pending { background: #fee2e2; color: #b91c1c; }
    
    .text-payment-info { font-size: 0.75rem; color: #64748b; line-height: 1.2; }
    .section-title { color: #1e293b; font-weight: 700; border-left: 5px solid #3b82f6; padding-left: 15px; }
</style>

<div class="container-fluid mt-4 px-md-4">
    <div class="d-flex flex-wrap justify-content-between align-items-end mb-3 gap-3">
        <div>
            <h4 class="section-title mb-1">บันทึกการชำระเงินและติดตามสถานะ</h4>
            <p class="text-muted small mb-0 ms-4">จัดการประวัติ ยืนยันการรับชำระ และตรวจสอบเวลาเข้าจ่ายเงิน</p>
        </div>
        <div class="text-end">
            <h6 class="fw-bold mb-2 text-secondary">ประจำปี พ.ศ. <?php echo $selected_year+543; ?></h6>
            <div class="btn-group shadow-sm">
                <a href="?year=<?php echo $selected_year-1; ?>&month=<?php echo $selected_month; ?>" class="btn btn-sm btn-white bg-white border"><i class="bi bi-chevron-left"></i></a>
                <a href="?year=<?php echo $selected_year+1; ?>&month=<?php echo $selected_month; ?>" class="btn btn-sm btn-white bg-white border"><i class="bi bi-chevron-right"></i></a>
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
                <table class="table align-middle mb-0">
                    <thead class="bg-light">
                        <tr class="text-muted small">
                            <th class="ps-4 py-3">ห้อง</th>
                            <th>ผู้เช่า (ประวัติในบิล)</th>
                            <th class="text-end">ยอดสุทธิ</th>
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
                            ?>
                            <tr>
                                <td class="ps-4 fw-bold h5 mb-0 text-primary"><?php echo htmlspecialchars($row["room_number"]); ?></td>
                                <td>
                                    <div class="fw-bold"><?php echo htmlspecialchars($display_name); ?></div>
                                    <div class="text-muted" style="font-size: 0.7rem;">ID: #<?php echo $row['id']; ?></div>
                                </td>
                                <td class="text-end fw-bold text-dark">฿<?php echo number_format($row["total_cost"], 2); ?></td>
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
                                            <?php echo date('H:i', strtotime($row['payment_date'])); ?> น.
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
                                        <form action="mark_paid.php" method="POST" class="d-inline">
                                            <input type="hidden" name="record_id" value="<?php echo $row['id']; ?>">
                                            <input type="hidden" name="month" value="<?php echo $selected_month; ?>">
                                            <button type="submit" class="btn btn-sm btn-success rounded-pill px-3 shadow-sm" style="font-size: 0.7rem;">ยืนยันการชำระ</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center pe-4">
                                    <div class="d-flex justify-content-center gap-1">
                                        <a href="generate_invoice.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-light border text-primary" title="พิมพ์ใบเสร็จ" target="_blank"><i class="bi bi-printer"></i></a>
                                        <a href="edit.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-light border text-warning" title="แก้ไขบิล"><i class="bi bi-pencil-square"></i></a>
                                        <form action="delete.php" method="POST" class="d-inline" onsubmit="return confirm('ลบบิลถาวร?')">
                                            <input type="hidden" name="id" value="<?php echo $row['id']; ?>"><input type="hidden" name="month" value="<?php echo $selected_month; ?>">
                                            <button type="submit" class="btn btn-sm btn-light border text-danger" title="ลบ"><i class="bi bi-trash"></i></button>
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

<?php $conn->close(); include 'footer.php'; ?>