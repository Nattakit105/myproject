<?php
include 'header.php'; 
include 'check_session.php'; 
include 'db_connect.php';

if ($_SESSION['role'] !== 'admin') { die("Access Denied!"); }

// 🔥 [เพิ่ม] รับค่าเลขห้องที่ส่งมาจากหน้า Dashboard (ถ้ามี)
$prefill_room = isset($_GET['room']) ? htmlspecialchars($_GET['room']) : '';

$tenant_rooms = [];
$result = $conn->query("SELECT username FROM users WHERE role = 'tenant' ORDER BY username ASC");
if ($result) {
    while($row = $result->fetch_assoc()){ $tenant_rooms[] = $row['username']; }
}
$conn->close();
?>

<div class="container mt-4" style="max-width: 700px;">
    <div class="card shadow border-0 rounded-3">
        <div class="card-header bg-primary text-white text-center py-3">
            <h1 class="h4 mb-0"><i class="bi bi-calculator me-2"></i>บันทึกบิลใหม่</h1>
        </div>
        <div class="card-body p-4">
            <form action="calculate.php" method="post" enctype="multipart/form-data" id="meterUploadForm">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">เลือกห้องพัก</label>
                        <select class="form-select" id="room_number" name="room_number" required>
                            <option value="">-- เลือกห้อง --</option>
                            <?php foreach ($tenant_rooms as $room): ?>
                                <option value="<?php echo htmlspecialchars($room); ?>" <?php echo ($prefill_room == $room) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($room); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">จำนวนคนพัก</label>
                        <input type="number" class="form-control" name="num_people" value="1" min="1" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">ประจำเดือน</label>
                        <input type="month" class="form-control" name="billing_month" value="<?php echo date('Y-m'); ?>" required>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold text-primary">💰 ค่าเช่าห้องพื้นฐาน (บาท)</label>
                    <input type="number" step="0.01" class="form-control bg-light" name="room_rent" value="2500.00" required>
                </div>

                <div class="bg-light p-3 rounded border mb-4 shadow-sm">
                    <h6 class="text-danger fw-bold mb-3 border-bottom pb-2"><i class="bi bi-lightning-charge-fill"></i> ข้อมูลมิเตอร์ไฟฟ้า</h6>
                    <div class="mb-3">
                        <label class="form-label text-dark">เลขมิเตอร์ครั้งก่อน <strong>(แก้ไขได้หากไม่ถูกต้อง)</strong></label>
                        <input type="number" step="0.1" class="form-control border-danger" id="elec_prev" name="elec_prev" value="0" required>
                        <div id="status" class="mt-1 fw-bold" style="font-size: 0.85rem;"></div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label  text-dark">อัปโหลดรูปมิเตอร์ไฟฟ้าปัจจุบัน</label>
                        <input class="form-control border-primary" type="file" name="elec_image" accept="image/*" required>
                    </div>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-primary btn-lg shadow fw-bold" id="calculateBtn">คำนวณค่าห้อง</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="processingOverlay" class="position-fixed top-0 start-0 w-100 h-100 d-none" style="background: rgba(15, 23, 42, 0.78); z-index: 2000;">
    <div class="d-flex align-items-center justify-content-center h-100 px-3">
        <div class="bg-white rounded-3 shadow-lg p-4 w-100" style="max-width: 460px;">
            <div class="d-flex align-items-center gap-3 mb-3">
                <div class="spinner-border text-primary" role="status" aria-hidden="true"></div>
                <div>
                    <h2 class="h5 mb-1 fw-bold text-primary">กำลังประมวลผลรูปมิเตอร์</h2>
                    <div class="text-muted small">อัปโหลดรูปและอ่านตัวเลขด้วยระบบ OCR/AI</div>
                </div>
            </div>
            <div class="progress" style="height: 10px;">
                <div id="processingBar" class="progress-bar progress-bar-striped progress-bar-animated" style="width: 20%;"></div>
            </div>
            <div id="processingText" class="small text-secondary mt-3">กำลังเตรียมข้อมูล...</div>
        </div>
    </div>
</div>

<script>
// ฟังก์ชันดึงข้อมูลมิเตอร์และค่าเช่า
function fetchRoomInfo(room) {
    const meterInput = document.getElementById('elec_prev');
    const rentInput = document.getElementsByName('room_rent')[0];
    const status = document.getElementById('status');
    
    if(!room) { 
        meterInput.value = 0; 
        status.textContent = ''; 
        return; 
    }

    status.textContent = '⌛ กำลังดึงข้อมูล...';
    status.className = 'text-muted small';
    
    fetch(`get_previous_meter.php?room=${room}`)
        .then(res => res.json())
        .then(data => {
            meterInput.value = data.previous_meter;
            if(rentInput && data.room_price) {
                rentInput.value = parseFloat(data.room_price).toFixed(2);
            }
            status.textContent = '✅ ดึงข้อมูลสำเร็จ';
            status.className = 'text-success small';
        })
        .catch(err => {
            status.textContent = '❌ ดึงข้อมูลไม่สำเร็จ';
            status.className = 'text-danger small';
        });
}

// ตรวจสอบเมื่อมีการเปลี่ยนห้อง
document.getElementById('room_number').addEventListener('change', function() {
    fetchRoomInfo(this.value);
});

// 🔥 [เพิ่ม] ถ้ามีการส่งค่าห้องมาจากหน้า Dashboard ให้รันฟังก์ชันดึงข้อมูลทันที
window.addEventListener('DOMContentLoaded', (event) => {
    const prefilledRoom = document.getElementById('room_number').value;
    if(prefilledRoom) {
        fetchRoomInfo(prefilledRoom);
    }
});

document.getElementById('meterUploadForm').addEventListener('submit', function() {
    const overlay = document.getElementById('processingOverlay');
    const bar = document.getElementById('processingBar');
    const text = document.getElementById('processingText');
    const btn = document.getElementById('calculateBtn');
    const steps = [
        { width: 35, text: 'กำลังอัปโหลดรูปมิเตอร์...' },
        { width: 62, text: 'กำลังอ่านตัวเลขจากรูป...' },
        { width: 84, text: 'กำลังเตรียมหน้าตรวจสอบผลลัพธ์...' }
    ];
    let index = 0;

    overlay.classList.remove('d-none');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>กำลังคำนวณ...';

    window.setInterval(function() {
        const step = steps[Math.min(index, steps.length - 1)];
        bar.style.width = step.width + '%';
        text.textContent = step.text;
        index++;
    }, 900);
});

</script>

<?php include 'footer.php'; ?>
