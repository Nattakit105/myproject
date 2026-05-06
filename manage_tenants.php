<?php 
ob_start(); 
include 'db_connect.php'; 
include 'check_session.php'; 
include 'header.php'; 

if ($_SESSION['role'] !== 'admin') { die("Access Denied!"); }

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function generate_password($length = 6) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyz';
    $password = '';
    $max = strlen($characters) - 1;
    for ($i = 0; $i < $length; $i++) {
        $password .= $characters[random_int(0, $max)];
    }
    return $password;
}

// --- 1. เตรียมข้อมูล Master Data และห้องว่าง ---
$all_rooms = [];
for ($i = 101; $i <= 120; $i++) {
    $all_rooms[] = (string)$i;
}

$tenants = [];
$occupied_rooms = [];
// แก้ไข Query ให้ดึงค่าใหม่มาด้วย
$result = $conn->query("SELECT id, username, full_name, plain_password, occupant_count, meter_no FROM users WHERE role = 'tenant' ORDER BY CAST(username AS UNSIGNED) ASC");
while($row = $result->fetch_assoc()) {
    $tenants[] = $row;
    $occupied_rooms[] = $row['username'];
}

$available_rooms = array_diff($all_rooms, $occupied_rooms);

// --- 2. การจัดการข้อมูล (POST/GET) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_tenant'])) {
    $room_number = $_POST['room_number'];
    $full_name = $_POST['full_name'];
    $occupant_count = $_POST['occupant_count']; // รับค่าจำนวนคนพัก
    $meter_no = $_POST['meter_no']; // รับค่าเลขมิเตอร์

    if (in_array($room_number, $occupied_rooms)) {
        $_SESSION['error_message'] = "❌ ห้อง $room_number มีคนพักแล้ว";
    } else {
        $password = generate_password(); 
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $water_meter = "WTR-" . $room_number;
        $electric_meter = "ELE-" . $room_number;

        // แก้ไข INSERT ให้บันทึกค่าใหม่ลงไป
        $stmt = $conn->prepare("INSERT INTO users (username, full_name, password, role, plain_password, water_meter, electric_meter, occupant_count, meter_no) VALUES (?, ?, ?, 'tenant', ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssis", $room_number, $full_name, $hashed_password, $password, $water_meter, $electric_meter, $occupant_count, $meter_no); 
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "✅ เพิ่มห้อง $room_number สำเร็จ! รหัสผ่าน: $password";
        }
        $stmt->close();
    }
    header("Location: manage_tenants.php");
    exit();
}

// ... (ส่วนลบข้อมูลและรีเซ็ตรหัสผ่านเหมือนเดิม) ...
if (isset($_GET['delete_id'])) {
    $id = filter_input(INPUT_GET, 'delete_id', FILTER_VALIDATE_INT);
    if ($id) {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'tenant'");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        $_SESSION['success_message'] = "ลบบัญชีสำเร็จ";
    }
    header("Location: manage_tenants.php"); exit();
}

if (isset($_GET['reset_id'])) {
    $id = filter_input(INPUT_GET, 'reset_id', FILTER_VALIDATE_INT);
    if ($id) {
        $new_pw = generate_password();
        $hashed = password_hash($new_pw, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ?, plain_password = ? WHERE id = ? AND role = 'tenant'");
        $stmt->bind_param("ssi", $hashed, $new_pw, $id);
        $stmt->execute();
        $stmt->close();
        $_SESSION['success_message'] = "รีเซ็ตรหัสผ่านใหม่สำเร็จ: $new_pw";
    }
    header("Location: manage_tenants.php"); exit();
}
?>

<style>
    /* สไตล์เดิมของคุณ */
    .tenant-table-card { background: #ffffff !important; color: #0f172a !important; }
    .tenant-table { --bs-table-bg: #ffffff; --bs-table-color: #0f172a; --bs-table-border-color: #d9e2ec; color: #0f172a !important; }
    .tenant-table thead th { background: #1f2933 !important; color: #ffffff !important; border-color: #1f2933 !important; }
    .tenant-table tbody td { background: #ffffff !important; color: #0f172a !important; border-color: #d9e2ec !important; }
    .tenant-table tbody strong { color: #0f172a !important; }
    .tenant-table code { color: #dc2626 !important; }
    html[data-theme="dark"] .tenant-form-body { background: #334155 !important; color: #f8fafc !important; }
    html[data-theme="light"] .tenant-form-body { background: #eef2f7 !important; color: #0f172a !important; }
</style>

<div class="container mt-4">
    <h1 class="h3 text-primary fw-bold mb-4"><i class="bi bi-people-fill me-2"></i>จัดการบัญชีผู้เข้าพัก</h1>

    <!-- แสดง Alert (เหมือนเดิม) -->
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class='alert alert-success border-0 shadow-sm alert-dismissible fade show'>
            <?php echo e($_SESSION['success_message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <!-- ฟอร์มเพิ่มผู้เข้าพัก (แก้ไขใหม่) -->
    <div class="card shadow-sm mb-4 border-0">
        <div class="card-header bg-success text-white fw-bold">
            <i class="bi bi-person-plus-fill me-1"></i> เพิ่มบัญชีผู้เข้าพัก (เฉพาะห้องว่าง)
        </div>
        <div class="card-body bg-light tenant-form-body">
            <form method="POST" action="manage_tenants.php" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label fw-bold">เลือกเลขห้อง</label>
                    <select name="room_number" class="form-select shadow-sm" required>
                        <option value="" selected disabled>-- เลือกห้อง --</option>
                        <?php foreach ($available_rooms as $room): ?>
                            <option value="<?php echo e($room); ?>">ห้อง <?php echo e($room); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">ชื่อ-นามสกุล</label>
                    <input type="text" class="form-control shadow-sm" name="full_name" placeholder="ระบุชื่อจริง" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">จำนวนคนพัก</label>
                    <input type="number" class="form-control shadow-sm" name="occupant_count" value="1" min="1" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">เลขมิเตอร์ (S/N)</label>
                    <input type="text" class="form-control shadow-sm" name="meter_no" placeholder="เช่น 12345" required>
                </div>
                <div class="col-12 mt-3">
                    <button type="submit" name="add_tenant" class="btn btn-success w-100 fw-bold shadow-sm">
                        <i class="bi bi-plus-lg me-1"></i> สร้างบัญชีและผูกมิเตอร์
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ตารางแสดงรายชื่อ (เพิ่มคอลัมน์ใหม่) -->
    <div class="card shadow-sm border-0 tenant-table-card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 tenant-table">
                <thead class="table-dark">
                    <tr>
                        <th class="ps-4">ห้อง</th>
                        <th>ชื่อผู้เข้าพัก</th>
                        <th class="text-center">คนพัก</th>
                        <th>เลขมิเตอร์</th>
                        <th>รหัสผ่าน</th>
                        <th class="text-center pe-4">การจัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tenants as $tenant): ?>
                    <tr>
                        <td class="ps-4"><strong><?php echo e($tenant['username']); ?></strong></td>
                        <td><?php echo e($tenant['full_name']); ?></td>
                        <td class="text-center"><?php echo e($tenant['occupant_count']); ?></td>
                        <td><span class="badge bg-secondary"><?php echo e($tenant['meter_no']); ?></span></td>
                        <td><code class="text-danger fw-bold"><?php echo e($tenant['plain_password']); ?></code></td>
                        <td class="text-center pe-4">
                            <div class="btn-group">
                                <a href="?reset_id=<?php echo (int) $tenant['id']; ?>" class="btn btn-outline-info btn-sm" onclick="return confirm('รีเซ็ตรหัสผ่าน?')">รีเซ็ต</a>
                                <a href="?delete_id=<?php echo (int) $tenant['id']; ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('ลบบัญชี?')">ลบ</a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'footer.php'; ob_end_flush(); ?>