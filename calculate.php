<?php
include 'header.php'; 
include 'check_session.php'; 
include 'db_connect.php'; 

if ($_SESSION['role'] !== 'admin') { die("Access Denied!"); }

$ELEC_RATE = 8.00; 
$WATER_RATE_PER_PERSON = 100.00;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['elec_image'])) {
    
    $room_number       = $_POST['room_number'];
    $billing_month_str = $_POST['billing_month'];
    $room_rent         = (float)$_POST['room_rent'];
    $num_people        = (int)$_POST['num_people'];
    $elec_prev         = trim($_POST['elec_prev']);

    // --- 1. จัดการการอัปโหลดรูปภาพ ---
    $upload_dir = __DIR__ . '/uploads/';
    if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
    $image_name = 'meter_' . $room_number . '_' . $billing_month_str . '_' . time() . '.png';
    $image_path = $upload_dir . $image_name;
    move_uploaded_file($_FILES['elec_image']['tmp_name'], $image_path);

    // --- 2. Python OCR ---
    $python_executable  = "C:\\Users\\User\\AppData\\Local\\Programs\\Python\\Python310\\python.exe";
    $python_script_path = __DIR__ . '\\detecnum_new.py';
    $tesseract_path     = "C:\\Program Files\\Tesseract-OCR\\tesseract.exe";

    $command = "$python_executable " . escapeshellarg($python_script_path) . " " . escapeshellarg($image_path) . " --tesseract " . escapeshellarg($tesseract_path);
    $output  = shell_exec($command . " 2>&1");

    preg_match_all('/\d{4,6}/', $output, $matches);
    $elec_new_ocr = !empty($matches[0]) ? end($matches[0]) : "0000";
    if (strlen($elec_new_ocr) > 4) $elec_new_ocr = substr($elec_new_ocr, 0, 4);

    // --- 3. เตรียม OpenRouter AI (client-side) ---
    $openrouter_key   = defined('OPENROUTER_API_KEY') ? OPENROUTER_API_KEY : '';
    $openrouter_model = defined('OPENROUTER_MODEL')   ? OPENROUTER_MODEL   : 'google/gemma-4-31b-it:free';
    if ($openrouter_key) {
        $img_info     = getimagesize($image_path);
        $image_mime   = $img_info['mime'] ?? 'image/jpeg';
        $image_base64 = base64_encode(file_get_contents($image_path));
    }
    ?>

    <div class="container mt-4" style="max-width: 850px;">
        <div class="card shadow border-primary rounded-3">
            <div class="card-header bg-primary text-white text-center py-3">
                <h1 class="h4 mb-0"><i class="bi bi-shield-check me-2"></i>ตรวจสอบและยืนยันข้อมูล (ห้อง <?php echo $room_number; ?>)</h1>
            </div>
            <div class="card-body p-4">
                <form action="save_bill.php" method="POST" id="billForm">
                    <div class="row g-4">
                        <div class="col-md-5 text-center border-end">
                            <h6 class="text-secondary fw-bold mb-3">รูปถ่ายมิเตอร์จริง</h6>
                            <a href="uploads/<?php echo $image_name; ?>" target="_blank">
                                <img src="uploads/<?php echo $image_name; ?>" class="img-fluid rounded border shadow-sm mb-2" style="max-height: 300px; object-fit: contain;">
                            </a>
                            <p class="small text-primary fw-bold mt-2">หากค่าไม่ถูกต้องสามารถแก้ไขได้ทางด้านขวา</p>

                            <?php if ($openrouter_key): ?>
                            <div class="mt-3">
                                <div id="ai-loading" class="text-center text-muted small py-1">
                                    <span class="spinner-border spinner-border-sm me-1" role="status"></span>
                                    กำลังอ่านมิเตอร์ด้วย AI...
                                </div>
                                <div id="ai-result" class="d-none">
                                    <div class="mb-2">
                                        <label class="form-label small text-muted mb-1">📋 หมายเลขมิเตอร์ (NO.)</label>
                                        <input type="text" class="form-control form-control-sm bg-light" id="ai-meter-no" readonly>
                                    </div>
                                    <div>
                                        <label class="form-label small text-muted mb-1">⚡ ค่าที่อ่านได้ (หน่วย)</label>
                                        <input type="text" class="form-control fw-bold text-danger border-danger" id="ai-meter-val" readonly>
                                    </div>
                                </div>
                                <div id="ai-error" class="alert alert-warning mt-2 py-2 mb-0 d-none small"></div>
                            </div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-7">
                            <h6 class="text-primary fw-bold mb-3"><i class="bi bi-pencil-square"></i> แก้ไขตัวเลขให้ถูกต้อง</h6>
                            
                            <div id="validation_alert" class="alert alert-danger d-none mb-3 shadow-sm small">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i> <strong>ผิดพลาด!</strong> เลขใหม่ห้ามน้อยกว่าเลขเก่า
                            </div>

                            <div class="mb-3">
                                <label class="form-label small text-secondary">1. เลขมิเตอร์ครั้งก่อน</label>
                                <input type="text" class="form-control fw-bold bg-light" id="val_prev" name="elec_prev" value="<?php echo $elec_prev; ?>" maxlength="4" oninput="reCalculate()">
                            </div>
                            <div class="mb-3">
                                <label class="form-label small text-danger fw-bold">2. เลขมิเตอร์ปัจจุบัน (ที่คำนวณได้)</label>
                                <input type="text" class="form-control form-control-lg border-danger fw-bold text-danger" id="val_new" name="elec_new_confirmed" value="<?php echo $elec_new_ocr; ?>" maxlength="4" oninput="reCalculate()" style="background-color: #fff8f8;">
                                <div class="form-text mt-1 text-muted small">ตรวจสอบเลข 4 หลักจากรูปภาพทางซ้ายมือ</div>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="bg-light p-4 rounded-3 border mb-4 shadow-sm">
                        <h5 class="text-center text-dark mb-4"><i class="bi bi-calculator me-2"></i>ประมาณการยอดเงิน</h5>
                        <table class="table table-borderless mb-0">
                            <tr class="border-bottom">
                                <td class="py-2 text-secondary">ค่าเช่าห้องพัก:</td>
                                <td class="py-2 text-end fw-bold"><?php echo number_format($room_rent, 2); ?> บาท</td>
                            </tr>
                            <tr class="border-bottom">
                                <td class="py-2 text-secondary">ค่าน้ำ (เหมาจ่าย <?php echo $num_people; ?> คน):</td>
                                <td class="py-2 text-end fw-bold"><?php echo number_format($num_people * $WATER_RATE_PER_PERSON, 2); ?> บาท</td>
                            </tr>
                            <tr class="border-bottom">
                                <td class="py-2 text-secondary">
                                    ค่าไฟฟ้า: <span id="formula_units" class="text-dark fw-bold"></span> 
                                    <div class="small text-muted" id="formula_math"></div>
                                </td>
                                <td class="py-2 text-end text-danger fw-bold">+ <span id="preview_elec_cost">0.00</span> บาท</td>
                            </tr>
                            <tr>
                                <td class="pt-4 fw-bold h5 text-primary">ยอดรวมสุทธิ:</td>
                                <td class="pt-4 text-end fw-bold text-primary h3"><span id="preview_total">0.00</span></td>
                            </tr>
                        </table>
                    </div>

                    <input type="hidden" name="room_number" value="<?php echo htmlspecialchars($room_number); ?>">
                    <input type="hidden" name="billing_month" value="<?php echo htmlspecialchars($billing_month_str); ?>">
                    <input type="hidden" name="room_rent" value="<?php echo $room_rent; ?>">
                    <input type="hidden" name="num_people" value="<?php echo $num_people; ?>">
                    <input type="hidden" name="image_name" value="<?php echo htmlspecialchars($image_name); ?>">

                    <div class="d-grid gap-3">
                        <button type="submit" id="submit_btn" class="btn btn-success btn-lg shadow-sm py-3 fw-bold">
                            <i class="bi bi-save me-2"></i>ยืนยันและบันทึกบิล
                        </button>
                        <a href="create_bill.php" class="btn btn-link text-secondary text-decoration-none">ยกเลิกและกลับไปหน้าเดิม</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    function reCalculate() {
        const prevStr = document.getElementById('val_prev').value;
        const nextStr = document.getElementById('val_new').value;
        
        const prev = parseFloat(prevStr) || 0;
        const next = parseFloat(nextStr) || 0;
        
        const rent = <?php echo $room_rent; ?>;
        const water = <?php echo ($num_people * $WATER_RATE_PER_PERSON); ?>;
        const rate = <?php echo $ELEC_RATE; ?>;
        
        const alertBox = document.getElementById('validation_alert');
        const submitBtn = document.getElementById('submit_btn');

        if (next < prev) {
            alertBox.classList.remove('d-none');
            submitBtn.disabled = true;
            document.getElementById('formula_units').textContent = "(ผิดพลาด)";
            document.getElementById('preview_total').textContent = "รอแก้ไขข้อมูล";
        } else {
            alertBox.classList.add('d-none');
            submitBtn.disabled = false;

            const units = next - prev;
            const elecCost = units * rate;
            const total = rent + water + elecCost;

            // 🔥 [เพิ่ม] การแสดงผลที่มาของตัวเลขแบบ Dynamic
            document.getElementById('formula_units').textContent = `(${next} - ${prev} = ${units.toFixed(1)} หน่วย)`;
            document.getElementById('formula_math').textContent = `(${units.toFixed(1)} หน่วย x ${rate.toFixed(2)} บาท)`;
            
            document.getElementById('preview_elec_cost').textContent = elecCost.toLocaleString(undefined, {minimumFractionDigits: 2});
            document.getElementById('preview_total').textContent = total.toLocaleString('th-TH', { style: 'currency', currency: 'THB' });
        }
    }
    <?php if ($openrouter_key): ?>
    (function() {
        const apiKey      = <?php echo json_encode($openrouter_key); ?>;
        const model       = <?php echo json_encode($openrouter_model); ?>;
        const base64Image = <?php echo json_encode($image_base64); ?>;
        const mimeType    = <?php echo json_encode($image_mime); ?>;
        const prompt = `คุณเป็น AI ที่เชี่ยวชาญในการอ่านมิเตอร์ไฟฟ้า\n\nจากรูปภาพนี้ กรุณาระบุ:\n1. หมายเลขมิเตอร์ (Meter No.) — ตัวเลขที่อยู่หลัง NO. ด้านล่างมิเตอร์\n2. ช่องตัวเลขบนหน้าปัด โดยแยกเป็น 2 ส่วน:\n   - white_digits: ตัวเลขในช่องพื้นขาว/ดำปกติ (อ่านจากซ้ายไปขวา ต่อกันไม่มีช่องว่าง)\n   - colored_digits: ตัวเลขในช่องที่มีกรอบหรือพื้นหลังสีแดงหรือสีน้ำเงิน\n\nตอบในรูปแบบ JSON เท่านั้น ไม่ต้องมีข้อความอื่น:\n{\n  "meter_no": "<หมายเลขมิเตอร์หรือ null>",\n  "white_digits": "<ตัวเลขในช่องพื้นขาว/ดำ เช่น 7090>",\n  "colored_digits": "<ตัวเลขในช่องสีแดง/น้ำเงิน เช่น 9>",\n  "confidence": "<high|medium|low>"\n}`;

        async function readMeterWithAI() {
            const loadingDiv = document.getElementById('ai-loading');
            const resultDiv  = document.getElementById('ai-result');
            const errorDiv   = document.getElementById('ai-error');

            try {
                const res = await fetch('https://openrouter.ai/api/v1/chat/completions', {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${apiKey}`,
                        'Content-Type': 'application/json',
                        'HTTP-Referer': window.location.href,
                        'X-Title': 'Dormitory Meter Reader'
                    },
                    body: JSON.stringify({
                        model: model,
                        messages: [{
                            role: 'user',
                            content: [
                                { type: 'image_url', image_url: { url: `data:${mimeType};base64,${base64Image}` } },
                                { type: 'text', text: prompt }
                            ]
                        }],
                        max_tokens: 512,
                        temperature: 0
                    })
                });

                if (!res.ok) {
                    const err = await res.json().catch(() => ({}));
                    throw new Error(err?.error?.message || `HTTP ${res.status}`);
                }

                const data    = await res.json();
                const content = data.choices?.[0]?.message?.content || '';
                const jsonMatch = content.match(/\{[\s\S]*\}/);
                if (!jsonMatch) throw new Error('ไม่พบข้อมูล JSON ในคำตอบ');

                const parsed  = JSON.parse(jsonMatch[0]);
                const reading = parsed.white_digits ?? parsed.meter_reading ?? null;
                const value   = (reading != null) ? String(parseInt(reading, 10)) : null;

                if (value && value !== 'NaN') {
                    document.getElementById('ai-meter-no').value  = parsed.meter_no || '—';
                    document.getElementById('ai-meter-val').value = value;
                    document.getElementById('val_new').value = value;
                    loadingDiv.classList.add('d-none');
                    resultDiv.classList.remove('d-none');
                    reCalculate();
                } else {
                    throw new Error('อ่านค่าไม่ได้ กรุณาตรวจสอบรูปภาพ');
                }
            } catch(e) {
                loadingDiv.classList.add('d-none');
                errorDiv.textContent = '⚠️ AI: ' + e.message;
                errorDiv.classList.remove('d-none');
            }
        }

        window.addEventListener('load', readMeterWithAI);
    })();
    <?php endif; ?>

    window.onload = reCalculate;
    </script>
    <?php
} else {
    header("Location: create_bill.php");
    exit;
}
include 'footer.php'; 
?>