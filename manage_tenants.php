<?php 
ob_start(); 
include 'db_connect.php'; 
include 'check_session.php'; 
include 'header.php'; 

if ($_SESSION['role'] !== 'admin') { die("Access Denied!"); }

// 🌟 1. สร้างรายชื่อห้องพักทั้งหมด (Master Data 20 ห้อง)
$all_rooms = [];
for ($i = 101; $i <= 120; $i++) {
    $all_rooms[] = (string)$i;
}

// 🌟 2. ดึงเลขห้องที่มีคนพักอยู่แล้วในปัจจุบันเพื่อเอามาคัดออก
$occupied_rooms = [];
$check_occ = $conn->query("SELECT username FROM users WHERE role = 'tenant'");
while($row = $check_occ->fetch_assoc()) {
    $occupied_rooms[] = $row['username'];
}

// 🌟 3. คำนวณหาห้องที่ยังว่าง (Available = All - Occupied)
$available_rooms = array_diff($all_rooms, $occupied_rooms);

function generate_password($length = 6) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyz';
    $password = '';
    $max = strlen($characters) - 1;
    for ($i = 0; $i < $length; $i++) {
        $password .= $characters[random_int(0, $max)];
    }
    return $password;
}

// 🔥 4. ส่วนของการเพิ่มผู้ใช้ใหม่
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_tenant'])) {
    $room_number = $_POST['room_number'];
    $full_name = $_POST['full_name'];

    // ตรวจสอบความปลอดภัยอีกชั้นว่าห้องยังว่างจริงไหม
    if (in_array($room_number, $occupied_rooms)) {
        $_SESSION['error_message'] = "❌ ไม่สามารถเพิ่มได้: เลขห้อง <strong>$room_number</strong> มีคนเข้าพักแล้ว";
    } else {
        $password = generate_password(); 
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // 🚩 [Fix] กำหนดเลขมิเตอร์น้ำ-ไฟอัตโนมัติตามเลขห้อง
        $water_meter = "WTR-" . $room_number;
        $electric_meter = "ELE-" . $room_number;

        $stmt = $conn->prepare("INSERT INTO users (username, full_name, password, role, plain_password, water_meter, electric_meter) VALUES (?, ?, ?, 'tenant', ?, ?, ?)");
        $stmt->bind_param("ssssss", $room_number, $full_name, $hashed_password, $password, $water_meter, $electric_meter); 
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "✅ สร้างบัญชีห้อง <strong>" . htmlspecialchars($room_number) . "</strong> สำเร็จ! รหัสผ่านคือ: <strong class='fs-5 text-primary'>" . $password . "</strong>";
        } else {
            $_SESSION['error_message'] = "เกิดข้อผิดพลาดทางเทคนิค ไม่สามารถเพิ่มผู้ใช้ได้";
        }
        $stmt->close();
    }
    header("Location: manage_tenants.php");
    exit();
}

// 5. ส่วนของการลบผู้ใช้
if (isset($_GET['delete_id'])) {
    $id_to_delete = $_GET['delete_id'];
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'tenant'");
    $stmt->bind_param("i", $id_to_delete);
    $stmt->execute();
    $stmt->close();
    $_SESSION['success_message'] = "ลบบัญชีสำเร็จแล้ว";
    header("Location: manage_tenants.php");
    exit();
}

// 6. ส่วนของการรีเซ็ตรหัสผ่าน
if (isset($_GET['reset_id'])) {
    $id_to_reset = $_GET['reset_id'];
    $new_password = generate_password();
    $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("UPDATE users SET password = ?, plain_password = ? WHERE id = ? AND role = 'tenant'");
    $stmt->bind_param("ssi", $new_hashed_password, $new_password, $id_to_reset);
    $stmt->execute();
    
    $user_stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
    $user_stmt->bind_param("i", $id_to_reset);
    $user_stmt->execute();
    $user_result = $user_stmt->get_result();
    $user = $user_result->fetch_assoc();

    $_SESSION['success_message'] = "รีเซ็ตรหัสผ่านห้อง <strong>" . htmlspecialchars($user['username']) . "</strong> สำเร็จ! รหัสผ่านใหม่คือ: <strong class='fs-5 text-primary'>" . $new_password . "</strong>";
    $stmt->close();
    $user_stmt->close();
    header("Location: manage_tenants.php");
    exit();
}

// 7. ดึงข้อมูลแสดงผลในตาราง
$tenants = [];
$result = $conn->query("SELECT id, username, full_name, plain_password FROM users WHERE role = 'tenant' ORDER BY CAST(username AS UNSIGNED) ASC");
while($row = $result->fetch_assoc()) {
    $tenants[] = $row;
}
$conn->close();
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 text-primary fw-bold"><i class="bi bi-people-fill me-2"></i>จัดการบัญชีผู้เข้าพัก</h1>
    </div>

    <!-- ส่วนแสดงข้อความแจ้งเตือน -->
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class='alert alert-success border-0 shadow-sm alert-dismissible fade show'>
            <?php echo $_SESSION['success_message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class='alert alert-danger border-0 shadow-sm alert-dismissible fade show'>
            <?php echo $_SESSION['error_message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <div class="card shadow-sm mb-4 border-0">
        <div class="card-header bg-success text-white fw-bold">
            <i class="bi bi-person-plus-fill me-1"></i> เพิ่มบัญชีผู้เข้าพัก (เฉพาะห้องว่าง)
        </div>
        <div class="card-body bg-light">
            <form method="POST" action="manage_tenants.php" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label fw-bold">เลือกเลขห้อง</label>
                    <!-- 🌟 เปลี่ยนเป็น Dropdown List -->
                    <select name="room_number" class="form-select shadow-sm" required>
                        <option value="" selected disabled>-- เลือกห้องพัก --</option>
                        <?php if (empty($available_rooms)): ?>
                            <option disabled>ห้องพักเต็มทุกห้องแล้ว</option>
                        <?php else: ?>
                            <?php foreach ($available_rooms as $room): ?>
                                <option value="<?php echo $room; ?>">ห้อง <?php echo $room; ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="col-md-5">
                    <label for="full_name" class="form-label fw-bold">ชื่อ-นามสกุล ผู้เข้าพัก</label>
                    <input type="text" class="form-control shadow-sm" name="full_name" id="full_name" placeholder="ระบุชื่อจริง" required>
                </div>
                <div class="col-md-3">
                    <button type="submit" name="add_tenant" class="btn btn-success w-100 fw-bold shadow-sm">
                        <i class="bi bi-plus-lg me-1"></i> สร้างบัญชี
                    </button>
                </div>
                <div class="col-12">
                     <p class="form-text mb-0 text-muted small"><i class="bi bi-info-circle me-1"></i>รหัสผ่านและเลขมิเตอร์จะถูกสร้างให้อัตโนมัติ</p>
                </div>
            </form>
        </div>
    </div>

    <!-- ตารางแสดงรายชื่อ -->
    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th class="ps-4">Username (เลขห้อง)</th>
                            <th>ชื่อผู้เข้าพัก</th>
                            <th>รหัสผ่าน (ที่แสดง)</th>
                            <th class="text-center pe-4">การจัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($tenants)): ?>
                            <tr><td colspan="4" class="text-center text-muted py-5">ยังไม่มีบัญชีผู้เข้าพักในระบบ</td></tr>
                        <?php else: ?>
                            <?php foreach ($tenants as $tenant): ?>
                            <tr>
                                <td class="ps-4"><strong><?php echo htmlspecialchars($tenant['username']); ?></strong></td>
                                <td><?php echo htmlspecialchars($tenant['full_name'] ?: '(ไม่มีชื่อ)'); ?></td>
                                <td>
                                    <?php if (!empty($tenant['plain_password'])): ?>
                                        <code class="fs-6 fw-bold text-danger bg-light px-2 py-1 rounded">
                                            <?php echo htmlspecialchars($tenant['plain_password']); ?>
                                        </code>
                                    <?php else: ?>
                                        <span class="text-muted small">ต้องรีเซ็ตรหัสผ่าน</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center pe-4">
                                    <div class="btn-group shadow-sm">
                                        <a href="manage_tenants.php?reset_id=<?php echo $tenant['id']; ?>" 
                                           class="btn btn-outline-info btn-sm" 
                                           onclick="return confirm('ยืนยันการรีเซ็ตรหัสผ่านใหม่?')">
                                             <i class="bi bi-key-fill"></i> รีเซ็ต
                                        </a>
                                        <a href="manage_tenants.php?delete_id=<?php echo $tenant['id']; ?>" 
                                           class="btn btn-outline-danger btn-sm" 
                                           onclick="return confirm('⚠️ ยืนยันการลบบัญชีห้อง <?php echo $tenant['username']; ?>?')">
                                             <i class="bi bi-trash"></i> ลบ
                                        </a>
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

<?php 
include 'footer.php'; 
ob_end_flush(); 
?>