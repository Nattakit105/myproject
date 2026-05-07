<?php
include 'header.php'; 
include 'check_session.php'; 
include 'db_connect.php';

if ($_SESSION['role'] !== 'admin') { die("Access Denied!"); }

$prefill_room = isset($_GET['room']) ? htmlspecialchars($_GET['room']) : '';

$tenant_rooms = [];
$result = $conn->query("SELECT username FROM users WHERE role = 'tenant' ORDER BY username ASC");
if ($result) {
    while($row = $result->fetch_assoc()){ $tenant_rooms[] = $row['username']; }
}
$conn->close();
?>

<div class="container mt-4" style="max-width: 700px;">
    <div class="card shadow border-0 rounded-3 text-dark">
        <div class="card-header bg-primary text-white text-center py-3">
            <h1 class="h4 mb-0"><i class="bi bi-cpu-fill me-2"></i>บันทึกบิลด้วยระบบ AI OCR</h1>
        </div>
        <div class="card-body p-4 bg-white">
            <form action="calculate.php" method="post" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">เลขห้อง</label>
                        <select class="form-select" id="room_number" name="room_number" required>
                            <option value="">-- เลือกห้อง --</option>
                            <?php foreach ($tenant_rooms as $room): ?>
                                <option value="<?= htmlspecialchars($room); ?>" <?= ($prefill_room == $room) ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($room); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">คนพัก</label>
                        <input type="number" class="form-control" name="num_people" id="num_people" value="1" readonly>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">ประจำเดือน</label>
                        <input type="month" class="form-control" name="billing_month" value="<?= date('Y-m'); ?>">
                    </div>
                </div>

                <div class="bg-light p-3 rounded border mb-4 shadow-sm">
                    <h6 class="text-danger fw-bold mb-3 border-bottom pb-2">🔋 ข้อมูลมิเตอร์ไฟฟ้า</h6>
                    
                    <div class="mb-3">
                        <label class="form-label">เลือกรูปมิเตอร์เพื่อสแกน</label>
                        <input class="form-control border-primary" type="file" id="elec_image_input" accept="image/*">
                        <button type="button" class="btn btn-info btn-sm mt-2 w-100 fw-bold" id="btn_scan_ai">
                            <i class="bi bi-search"></i> เริ่มสแกนตัวเลขด้วย AI
                        </button>
                    </div>

                    <div class="row">
                        <div class="col-6">
                            <label class="form-label small">เลขครั้งก่อน</label>
                            <input type="number" class="form-control bg-secondary text-white" id="elec_prev" name="elec_prev" readonly>
                        </div>
                        <div class="col-6">
                            <label class="form-label small text-primary fw-bold">เลขครั้งนี้ (AI อ่านให้)</label>
                            <input type="number" step="0.1" class="form-control border-primary fw-bold" id="elec_current" name="elec_current" required>
                        </div>
                    </div>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-primary btn-lg shadow fw-bold">คำนวณและออกบิล</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="processingOverlay" class="position-fixed top-0 start-0 w-100 h-100 d-none" style="background: rgba(0,0,0,0.8); z-index: 9999;">
    <div class="d-flex flex-column align-items-center justify-content-center h-100 text-white">
        <div class="spinner-border text-primary mb-3" role="status"></div>
        <h5 id="process_text">AI กำลังอ่านตัวเลขจากรูป...</h5>
    </div>
</div>

<script>
// ระบบดึงข้อมูลห้องอัตโนมัติ
document.getElementById('room_number').addEventListener('change', function() {
    const room = this.value;
    if(!room) return;
    fetch(`get_previous_meter.php?room=${room}`)
        .then(res => res.json())
        .then(data => {
            document.getElementById('elec_prev').value = data.previous_meter;
            document.getElementById('num_people').value = data.occupant_count || 1;
        });
});

// ระบบ AI OCR
document.getElementById('btn_scan_ai').addEventListener('click', function() {
    const fileInput = document.getElementById('elec_image_input');
    if (fileInput.files.length === 0) { alert('กรุณาเลือกรูปก่อนครับ ตะวัน'); return; }

    const overlay = document.getElementById('processingOverlay');
    overlay.classList.remove('d-none');

    const formData = new FormData();
    formData.append('image', fileInput.files[0]);

    fetch('process_ocr.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
        overlay.classList.add('d-none');
        if(data.success) {
            document.getElementById('elec_current').value = data.detected_number;
            alert('สแกนสำเร็จ! ได้ค่า: ' + data.detected_number);
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(err => {
        overlay.classList.add('d-none');
        alert('ไม่สามารถเชื่อมต่อระบบ OCR ได้');
    });
});
</script>