<?php 
ob_start(); 
include 'db_connect.php'; 
include 'check_session.php'; 
include 'header.php'; 

if ($_SESSION['role'] !== 'admin') { die("Access Denied!"); }

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

// ดึงข้อมูลผู้เช่าปัจจุบันเพื่อหาห้องที่ไม่ว่าง
$tenants = [];
$occupied_rooms = [];
$result = $conn->query("SELECT id, username, full_name, plain_password FROM users WHERE role = 'tenant' ORDER BY CAST(username AS UNSIGNED) ASC");
while($row = $result->fetch_assoc()) {
    $tenants[] = $row;
    $occupied_rooms[] = $row['username'];
}

// กรองเหลือเฉพาะห้องที่ยังว่าง
$available_rooms = array_diff($all_rooms, $occupied_rooms);


// --- 2. การจัดการข้อมูล (POST/GET) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_tenant'])) {
    $room_number = $_POST['room_number'];
    $full_name = $_POST['full_name'];

    if (in_array($room_number, $occupied_rooms)) {
        $_SESSION['error_message'] = "❌ ห้อง $room_number มีคนพักแล้ว";
    } else {
        $password = generate_password(); 
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $water_meter = "WTR-" . $room_number;
        $electric_meter = "ELE-" . $room_number;

        $stmt = $conn->prepare("INSERT INTO users (username, full_name, password, role, plain_password, water_meter, electric_meter) VALUES (?, ?, ?, 'tenant', ?, ?, ?)");
        $stmt->bind_param("ssssss", $room_number, $full_name, $hashed_password, $password, $water_meter, $electric_meter); 
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "✅ เพิ่มห้อง $room_number สำเร็จ! รหัสผ่าน: $password";
        }
        $stmt->close();
    }
    header("Location: manage_tenants.php");
    exit();
}

// ส่วนลบข้อมูล
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];
    $conn->query("DELETE FROM users WHERE id = $id AND role = 'tenant'");
    $_SESSION['success_message'] = "ลบบัญชีสำเร็จ";
    header("Location: manage_tenants.php"); exit();
}

// ส่วนรีเซ็ตรหัสผ่าน
if (isset($_GET['reset_id'])) {
    $id = $_GET['reset_id'];
    $new_pw = generate_password();
    $hashed = password_hash($new_pw, PASSWORD_DEFAULT);
    $conn->query("UPDATE users SET password = '$hashed', plain_password = '$new_pw' WHERE id = $id");
    $_SESSION['success_message'] = "รีเซ็ตรหัสผ่านใหม่สำเร็จ: $new_pw";
    header("Location: manage_tenants.php"); exit();
}
?>

<div class="container mt-4">
    <h1 class="h3 text-primary fw-bold mb-4"><i class="bi bi-people-fill me-2"></i>จัดการบัญชีผู้เข้าพัก</h1>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class='alert alert-success border-0 shadow-sm alert-dismissible fade show'>
            <?php echo $_SESSION['success_message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <!-- ฟอร์มเพิ่มผู้เข้าพัก (Dropdown) -->
    <div class="card shadow-sm mb-4 border-0">
        <div class="card-header bg-success text-white fw-bold">
            <i class="bi bi-person-plus-fill me-1"></i> เพิ่มบัญชีผู้เข้าพัก (เฉพาะห้องว่าง)
        </div>
        <div class="card-body bg-light">
            <form method="POST" action="manage_tenants.php" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label fw-bold">เลือกเลขห้อง</label>
                    <select name="room_number" class="form-select shadow-sm" required>
                        <option value="" selected disabled>-- เลือกห้องพัก --</option>
                        <?php foreach ($available_rooms as $room): ?>
                            <option value="<?php echo $room; ?>">ห้อง <?php echo $room; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label fw-bold">ชื่อ-นามสกุล</label>
                    <input type="text" class="form-control shadow-sm" name="full_name" placeholder="ระบุชื่อจริง" required>
                </div>
                <div class="col-md-3">
                    <button type="submit" name="add_tenant" class="btn btn-success w-100 fw-bold shadow-sm">
                        <i class="bi bi-plus-lg me-1"></i> สร้างบัญชี
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ตารางแสดงรายชื่อ -->
    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th class="ps-4">เลขห้อง</th>
                        <th>ชื่อผู้เข้าพัก</th>
                        <th>รหัสผ่าน</th>
                        <th class="text-center pe-4">การจัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tenants as $tenant): ?>
                    <tr>
                        <td class="ps-4"><strong><?php echo $tenant['username']; ?></strong></td>
                        <td><?php echo $tenant['full_name']; ?></td>
                        <td><code class="text-danger fw-bold"><?php echo $tenant['plain_password']; ?></code></td>
                        <td class="text-center pe-4">
                            <div class="btn-group">
                                <a href="?reset_id=<?php echo $tenant['id']; ?>" class="btn btn-outline-info btn-sm" onclick="return confirm('รีเซ็ตรหัสผ่าน?')">รีเซ็ต</a>
                                <a href="?delete_id=<?php echo $tenant['id']; ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('ลบบัญชี?')">ลบ</a>
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