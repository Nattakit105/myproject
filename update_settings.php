<<<<<<< HEAD
<?php
include 'check_session.php';
include 'db_connect.php';

// ตรวจสอบว่าเป็น Admin
if ($_SESSION['role'] !== 'admin') { 
    die("Access Denied!"); 
}

// ตรวจสอบว่าเป็น POST Request
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 1. รับค่าและ Validate (ป้องกันค่าติดลบ หรือค่าว่าง)
    $water_rate = filter_var($_POST['water_rate'], FILTER_VALIDATE_FLOAT);
    $elec_rate = filter_var($_POST['elec_rate'], FILTER_VALIDATE_FLOAT);

    if ($water_rate === false || $water_rate < 0 || $elec_rate === false || $elec_rate < 0) {
        $_SESSION['error_message'] = "ค่าที่กรอกไม่ถูกต้อง กรุณาลองอีกครั้ง";
        header("Location: settings.php");
        exit();
    }

    // 2. อัปเดตค่าน้ำ (ใช้ Prepared Statement)
    $stmt_water = $conn->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'WATER_RATE_PER_PERSON'");
    $stmt_water->bind_param("s", $water_rate); // "s" เพราะใน DB เราตั้งเป็น VARCHAR
    $stmt_water->execute();
    $stmt_water->close();

    // 3. อัปเดตค่าไฟ (ใช้ Prepared Statement)
    $stmt_elec = $conn->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'ELECTRICITY_RATE_PER_UNIT'");
    $stmt_elec->bind_param("s", $elec_rate);
    $stmt_elec->execute();
    $stmt_elec->close();

    $conn->close();

    // 4. แจ้งเตือนและส่งกลับ
    $_SESSION['success_message'] = "บันทึกค่าใหม่สำเร็จ!";
    header("Location: settings.php");
    exit();

} else {
    // ถ้าไม่ใช่ POST ให้เด้งกลับ
    header("Location: settings.php");
    exit();
}
=======
<?php
include 'check_session.php';
include 'db_connect.php';

if ($_SESSION['role'] !== 'admin') {
    die("Access Denied!");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $water_rate = filter_var($_POST['water_rate'], FILTER_VALIDATE_FLOAT);
    $elec_rate  = filter_var($_POST['elec_rate'],  FILTER_VALIDATE_FLOAT);

    if ($water_rate === false || $water_rate < 0 || $elec_rate === false || $elec_rate < 0) {
        $_SESSION['error_message'] = "ค่าที่กรอกไม่ถูกต้อง กรุณาลองอีกครั้ง";
        header("Location: settings.php");
        exit();
    }

    $stmt = $conn->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'WATER_RATE_PER_PERSON'");
    $stmt->bind_param("s", $water_rate);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'ELECTRICITY_RATE_PER_UNIT'");
    $stmt->bind_param("s", $elec_rate);
    $stmt->execute();
    $stmt->close();

    // OpenRouter settings (upsert)
    $openrouter_key = trim($_POST['openrouter_api_key'] ?? '');
    $allowed_models = [
        'baidu/qianfan-ocr-fast:free',
        'google/gemma-4-31b-it:free',
        'google/gemma-4-26b-a4b-it:free',
        'tencent/hy3-preview:free',
        'nvidia/nemotron-3-nano-omni-30b-a3b-reasoning:free',
    ];
    $openrouter_model = in_array($_POST['openrouter_model'] ?? '', $allowed_models)
        ? $_POST['openrouter_model']
        : 'google/gemma-4-31b-it:free';

    foreach (['OPENROUTER_API_KEY' => $openrouter_key, 'OPENROUTER_MODEL' => $openrouter_model] as $key => $value) {
        $chk = $conn->prepare("SELECT id FROM settings WHERE setting_key = ?");
        $chk->bind_param("s", $key);
        $chk->execute();
        $chk->store_result();
        if ($chk->num_rows > 0) {
            $chk->close();
            $upd = $conn->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
            $upd->bind_param("ss", $value, $key);
            $upd->execute();
            $upd->close();
        } else {
            $chk->close();
            $ins = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)");
            $ins->bind_param("ss", $key, $value);
            $ins->execute();
            $ins->close();
        }
    }

    $conn->close();

    $_SESSION['success_message'] = "บันทึกค่าใหม่สำเร็จ!";
    header("Location: settings.php");
    exit();

} else {
    header("Location: settings.php");
    exit();
}
>>>>>>> b3c7638653082b907eb612c49ef346ef3806ad14
?>