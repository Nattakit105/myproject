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
                <hr class="my-4">
                <h6 class="fw-bold mb-3">🤖 ตั้งค่า AI อ่านมิเตอร์ (OpenRouter)</h6>
                <div class="mb-3">
                    <label class="form-label"><strong>🔑 OpenRouter API Key</strong></label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="openrouter_api_key" name="openrouter_api_key"
                               value="<?php echo htmlspecialchars(defined('OPENROUTER_API_KEY') ? OPENROUTER_API_KEY : ''); ?>"
                               placeholder="sk-or-v1-...">
                        <button class="btn btn-outline-secondary" type="button"
                                onclick="var f=document.getElementById('openrouter_api_key');f.type=f.type==='password'?'text':'password'">
                            👁
                        </button>
                    </div>
                    <div class="form-text">ดู API Key ได้ที่ openrouter.ai/keys (ฟรี)</div>
                </div>
                <div class="mb-3">
                    <label class="form-label"><strong>🧠 Model ที่ใช้อ่านมิเตอร์</strong></label>
                    <select class="form-select" name="openrouter_model">
                        <?php
                        $cur_model = defined('OPENROUTER_MODEL') ? OPENROUTER_MODEL : 'google/gemma-4-31b-it:free';
                        $models = [
                            'baidu/qianfan-ocr-fast:free'                       => '🆓 Baidu Qianfan OCR Fast (แนะนำ — OCR โดยเฉพาะ)',
                            'google/gemma-4-31b-it:free'                         => '🆓 Google Gemma 4 31B',
                            'google/gemma-4-26b-a4b-it:free'                     => '🆓 Google Gemma 4 26B',
                            'tencent/hy3-preview:free'                           => '🆓 Tencent Hy3 Preview',
                            'nvidia/nemotron-3-nano-omni-30b-a3b-reasoning:free' => '🆓 NVIDIA Nemotron 3 Nano Omni',
                        ];
                        foreach ($models as $val => $label): ?>
                        <option value="<?php echo $val; ?>" <?php echo ($cur_model === $val) ? 'selected' : ''; ?>>
                            <?php echo $label; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn btn-success btn-lg">บันทึกการเปลี่ยนแปลง</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php include 'footer.php'; ?>