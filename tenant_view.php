<?php
include 'header.php'; 
include 'check_session.php';
include 'db_connect.php';

// 1. ตรวจสอบว่าเป็นผู้เข้าพัก
if ($_SESSION['role'] !== 'tenant') {
    die("Access Denied!");
}

// 2. ดึงเลขห้องจาก session
$room_number = $_SESSION['username'];
$records = [];
$available_years = [];

// 3. รายชื่อเดือนภาษาไทย
$thai_month_names = [
    "", "มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน",
    "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม"
];

// 4. รับค่าปีที่เลือกจาก dropdown
$selected_year = isset($_GET['year']) ? trim($_GET['year']) : '';

// กันค่ามั่ว
if ($selected_year !== '' && !preg_match('/^\d{4}$/', $selected_year)) {
    $selected_year = '';
}

// 5. ดึงปีทั้งหมดของห้องนี้มาไว้ทำ dropdown
$stmt_years = $conn->prepare(
    "SELECT DISTINCT YEAR(billing_month) AS year_num
     FROM billing_records
     WHERE room_number = ?
     ORDER BY year_num DESC"
);
$stmt_years->bind_param("s", $room_number);
$stmt_years->execute();
$result_years = $stmt_years->get_result();

while ($row_year = $result_years->fetch_assoc()) {
    $available_years[] = $row_year['year_num'];
}
$stmt_years->close();

// ถ้าปีที่ส่งมาไม่มีอยู่จริงในข้อมูล ให้ล้างค่า
if ($selected_year !== '' && !in_array((int)$selected_year, array_map('intval', $available_years), true)) {
    $selected_year = '';
}

// 6. ดึงข้อมูลบิล โดยกรองตามปีถ้ามีการเลือก
if ($selected_year !== '') {
    $stmt = $conn->prepare(
        "SELECT id, billing_month, total_cost, payment_date
         FROM billing_records
         WHERE room_number = ? AND YEAR(billing_month) = ?
         ORDER BY billing_month DESC"
    );
    $stmt->bind_param("si", $room_number, $selected_year);
} else {
    $stmt = $conn->prepare(
        "SELECT id, billing_month, total_cost, payment_date
         FROM billing_records
         WHERE room_number = ?
         ORDER BY billing_month DESC"
    );
    $stmt->bind_param("s", $room_number);
}

$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $records[] = $row;
}

$stmt->close();
$conn->close();
?>

<title>รายละเอียดค่าใช้จ่ายห้อง <?php echo htmlspecialchars($room_number); ?></title>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h1 class="h3 mb-0">ประวัติค่าใช้จ่าย ห้อง <?php echo htmlspecialchars($room_number); ?></h1>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            
            <!-- ตัวกรองปี -->
            <form method="GET" class="row g-3 align-items-end mb-3">
                <div class="col-md-4 col-lg-3">
                    <label for="year" class="form-label fw-bold">กรองตามปี</label>
                    <select name="year" id="year" class="form-select" onchange="this.form.submit()">
                        <option value="">ทั้งหมด</option>
                        <?php foreach ($available_years as $year): ?>
                            <option value="<?php echo $year; ?>" <?php echo ($selected_year == $year) ? 'selected' : ''; ?>>
                                <?php echo ($year + 543); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-auto">
                    <button type="submit" class="btn btn-primary">กรองข้อมูล</button>
                    <a href="tenant_view.php" class="btn btn-outline-secondary">ล้างตัวกรอง</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>สำหรับเดือน</th>
                            <th class="text-end">ยอดรวม (บาท)</th>
                            <th class="text-center">สถานะ</th>
                            <th class="text-center">ใบแจ้งหนี้</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($records)): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted p-3">
                                    ยังไม่มีข้อมูลค่าใช้จ่าย<?php echo ($selected_year !== '') ? 'ในปี ' . ($selected_year + 543) : ''; ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($records as $row): ?>
                                <tr>
                                    <td>
                                        <?php
                                            $dateObj = DateTime::createFromFormat('!Y-m-d', $row["billing_month"]);
                                            echo $thai_month_names[(int)$dateObj->format('n')] . ' ' . ($dateObj->format('Y') + 543);
                                        ?>
                                    </td>

                                    <td class="text-end fw-bold">
                                        <?php echo number_format($row["total_cost"], 2); ?>
                                    </td>

                                    <td class="text-center">
                                        <?php if ($row['payment_date'] !== NULL): ?>
                                            <span class="badge bg-success">ชำระแล้ว</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">ยังไม่ชำระ</span>
                                        <?php endif; ?>
                                    </td>

                                    <td class="text-center">
                                        <a href="generate_invoice.php?id=<?php echo $row['id']; ?>" class="btn btn-primary btn-sm" target="_blank">
                                            <i class="bi bi-printer"></i> ดูใบแจ้งหนี้
                                        </a>
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

<?php include 'footer.php'; ?>