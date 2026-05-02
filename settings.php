<?php
include 'header.php'; // (ต้องแก้ header.php ให้มีลิงก์มาหน้านี้ด้วย)
include 'check_session.php'; 
include 'db_connect.php'; // (ไฟล์นี้จะ define() ค่าปัจจุบันมาให้เรา)

// ตรวจสอบว่าเป็น Admin เท่านั้น
if ($_SESSION['role'] !== 'admin') { 
    die("Access Denied!"); 
}
?>
<title>ตั้งค่าระบบ</title>

<div class="container mt-4" style="max-width: 600px;">
    <div class="card shadow-sm">
        <div class="card-header">
            <h1 class="h4 mb-0">ตั้งค่าระบบ (ค่าน้ำ / ค่าไฟ)</h1>
        </div>
        <div class="card-body">
            
            <?php 
            if (isset($_SESSION['success_message'])) {
                echo "<div class='alert alert-success'>" . $_SESSION['success_message'] . "</div>";
                unset($_SESSION['success_message']);
            }
            if (isset($_SESSION['error_message'])) {
                echo "<div class='alert alert-danger'>" . $_SESSION['error_message'] . "</div>";
                unset($_SESSION['error_message']);
            }
            ?>
            
            <form action="update_settings.php" method="post">
                <div class="mb-3">
                    <label for="water_rate" class="form-label"><strong>💧 ค่าน้ำ (เหมาต่อคน / บาท)</strong></label>
                    <input type="number" step="0.01" class="form-control" id="water_rate" name="water_rate" 
                           value="<?php echo htmlspecialchars(WATER_RATE_PER_PERSON); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="elec_rate" class="form-label"><strong>⚡️ ค่าไฟ (ต่อหน่วย / บาท)</strong></label>
                    <input type="number" step="0.01" class="form-control" id="elec_rate" name="elec_rate" 
                           value="<?php echo htmlspecialchars(ELECTRICITY_RATE_PER_UNIT); ?>" required>
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn btn-success btn-lg">บันทึกการเปลี่ยนแปลง</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php include 'footer.php'; ?>