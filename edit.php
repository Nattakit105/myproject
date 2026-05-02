<?php
include 'header.php'; 
include 'check_session.php'; 
include 'db_connect.php';

// ตรวจสอบว่าเป็น Admin เท่านั้น
if ($_SESSION['role'] !== 'admin') {
    die("Access Denied!");
}

$record = null;
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    // *** แก้ไข: เพิ่ม elec_image_path ใน SELECT ***
    $stmt = $conn->prepare("SELECT * FROM billing_records WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $record = $result->fetch_assoc();
    }
    $stmt->close();
}

if ($record === null) {
    header("Location: index.php?status=notfound");
    exit();
}
?>
<title>แก้ไขข้อมูลบิล</title>

<div class="container mt-4">
    <div class="card shadow-sm">
        <div class="card-header bg-warning">
            <h1 class="h4 mb-0 text-center text-dark">แก้ไขข้อมูลบิล ห้อง <?php echo htmlspecialchars($record['room_number']); ?></h1>
        </div>
        <div class="card-body">
            <form action="update.php" method="post" enctype="multipart/form-data"> <input type="hidden" name="id" value="<?php echo $record['id']; ?>">
                <input type="hidden" name="old_elec_image_path" value="<?php echo htmlspecialchars($record['elec_image_path']); ?>"> <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="room_number" class="form-label"><strong>หมายเลขห้อง</strong></label>
                        <input type="text" class="form-control" name="room_number" value="<?php echo htmlspecialchars($record['room_number']); ?>" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="num_people" class="form-label"><strong>จำนวนคน</strong></label>
                        <input type="number" class="form-control" name="num_people" value="<?php echo $record['num_people']; ?>" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="billing_month" class="form-label"><strong>สำหรับเดือน</strong></label>
                        <input type="month" class="form-control" name="billing_month" value="<?php echo date('Y-m', strtotime($record['billing_month'])); ?>" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="room_rent" class="form-label"><strong>💰 ค่าห้อง</strong></label>
                    <input type="number" step="0.01" class="form-control" name="room_rent" value="<?php echo $record['room_rent']; ?>" required>
                </div>
                <hr>
                <div class="row justify-content-center">
                    <div class="col-md-8">
                        <h2 class="h5 mb-3 text-center">⚡️ ค่าไฟ</h2>
                        <div class="row">
                            <div class="col-6 mb-3">
                                <label for="elec_prev" class="form-label">เลขมิเตอร์ครั้งก่อน</label>
                                <input type="number" class="form-control" name="elec_prev" value="<?php echo $record['elec_prev']; ?>" required>
                            </div>
                            <div class="col-6 mb-3">
                                <label for="elec_new" class="form-label">เลขมิเตอร์ครั้งนี้</label>
                                <input type="number" class="form-control" name="elec_new" value="<?php echo $record['elec_new']; ?>" required>
                            </div>
                        </div>
                        
                        <?php if ($record['elec_image_path'] && file_exists($record['elec_image_path'])): ?>
                            <div class="mb-3 text-center">
                                <label class="form-label d-block">รูปมิเตอร์ไฟฟ้าเดิม:</label>
                                <img src="<?php echo htmlspecialchars($record['elec_image_path']); ?>" 
                                     class="img-fluid border rounded p-1" 
                                     style="max-height: 150px; cursor: pointer;" 
                                     alt="รูปมิเตอร์ไฟฟ้าเดิม"
                                     onclick="window.open(this.src, '_blank');">
                                <small class="d-block text-muted mt-1">(คลิกที่รูปเพื่อดูภาพขนาดใหญ่)</small>
                            </div>
                        <?php endif; ?>

                        <div class="mb-3">
                            <label for="elec_image" class="form-label">อัปโหลดรูปมิเตอร์ไฟฟ้าใหม่ (ถ้าต้องการเปลี่ยน)</label>
                            <input class="form-control" type="file" id="elec_image" name="elec_image" accept="image/*">
                            <small class="form-text text-muted">รองรับไฟล์ JPG, PNG. ขนาดไม่เกิน 2MB</small>
                        </div>

                    </div>
                </div>
                <div class="d-grid mt-3">
                    <button type="submit" class="btn btn-success btn-lg">บันทึกการแก้ไข</button>
                </div>
                
                <div class="text-center mt-3">
                    <a href="manage_bills.php?month=<?php echo date('Y-m', strtotime($record['billing_month'])); ?>" class="btn btn-outline-danger">ยกเลิก</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>