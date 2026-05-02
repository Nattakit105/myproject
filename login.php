<?php session_start(); ?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ | ปลายฟ้ารีสอร์ท</title>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    
    <style>
        :root {
            --bg-color: #0f172a;      /* พื้นหลังเข้ม Slate 900 */
            --card-bg: #1e293b;       /* พื้นหลังการ์ด Slate 800 */
            --input-bg: #334155;      /* พื้นหลังช่องกรอก Slate 700 */
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --primary-blue: linear-gradient(90deg, #3b82f6 0%, #2563eb 100%);
        }

        body { 
            font-family: 'Kanit', sans-serif;
            display: flex; 
            align-items: center; 
            justify-content: center; 
            min-height: 100vh; 
            background-color: var(--bg-color);
            margin: 0;
            color: var(--text-main);
        }

        /* 📦 การ์ดล็อคอิน */
        .login-card { 
            max-width: 440px; 
            width: 90%; 
            border: none;
            border-radius: 2rem;
            background-color: var(--card-bg);
            padding: 3.5rem 2.5rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            transition: transform 0.3s ease;
        }

        .login-card:hover {
            transform: translateY(-5px);
        }

        /* 🔵 วงกลมโลโก้ด้านบน */
        .brand-icon-wrapper {
            width: 80px;
            height: 80px;
            background: var(--primary-blue);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            box-shadow: 0 0 25px rgba(37, 99, 235, 0.4);
        }
        .brand-icon-wrapper i { font-size: 2.5rem; color: #fff; }

        .card-title { font-weight: 500; font-size: 1.7rem; margin-bottom: 0.2rem; color: #fff; }
        .card-subtitle { color: var(--text-muted); font-size: 0.95rem; margin-bottom: 2.5rem; }

        /* ⌨️ ส่วนของ Input */
        .form-label { font-size: 0.85rem; color: #cbd5e1; margin-bottom: 0.5rem; font-weight: 500; }
        
        .input-group-custom {
            position: relative;
            margin-bottom: 1.25rem;
        }

        .input-group-custom i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            z-index: 10;
        }

        .form-control {
            background-color: var(--input-bg);
            border: 1px solid #475569;
            border-radius: 12px;
            padding: 0.85rem 1rem 0.85rem 2.8rem;
            color: #fff;
            transition: 0.3s;
        }

        .form-control::placeholder { color: #64748b; }

        .form-control:focus {
            background-color: var(--input-bg);
            border-color: #3b82f6;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.2);
            color: #fff;
        }

        /* 🚀 ปุ่มล็อคอิน */
        .btn-login {
            background: var(--primary-blue);
            border: none;
            padding: 0.9rem;
            border-radius: 12px;
            font-weight: 500;
            font-size: 1.1rem;
            color: #fff;
            margin-top: 1rem;
            transition: 0.3s;
        }

        .btn-login:hover {
            opacity: 0.9;
            box-shadow: 0 10px 20px rgba(37, 99, 235, 0.3);
        }

        /* ⚠️ การแจ้งเตือน Error */
        .alert-custom {
            background-color: rgba(239, 68, 68, 0.15);
            color: #f87171;
            padding: 0.8rem;
            border-radius: 12px;
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
            border: 1px solid rgba(239, 68, 68, 0.2);
            display: flex;
            align-items: center;
        }

        .version-info {
            position: fixed;
            bottom: 20px;
            text-align: center;
            width: 100%;
            color: #475569;
            font-size: 0.8rem;
        }
    </style>
</head>
<body>

    <div class="card login-card text-center shadow">
        <div class="brand-icon-wrapper">
            <i class="bi bi-house-door-fill"></i>
        </div>
        
        <h2 class="card-title">Sign in</h2>
        <p class="card-subtitle">ระบบจัดการบ้านปลายฟ้ารีสอร์ท</p>

        <?php if(isset($_GET['error'])): ?>
            <div class="alert-custom">
                <i class="bi bi-exclamation-circle-fill me-2"></i>
                ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง
            </div>
        <?php endif; ?>

        <form action="auth.php" method="post">
            <div class="text-start input-group-custom">
                <label class="form-label">Username</label>
                <i class="bi bi-person"></i>
                <input type="text" class="form-control" name="username" placeholder="ระบุชื่อผู้ใช้งาน" required autofocus>
            </div>

            <div class="text-start input-group-custom">
                <label class="form-label">Password</label>
                <i class="bi bi-lock"></i>
                <input type="password" class="form-control" name="password" placeholder="ระบุรหัสผ่าน" required>
            </div>

            <div class="d-grid">
                <button type="submit" class="btn btn-login">
                    เข้าสู่ระบบ <i class="bi bi-arrow-right-short ms-1"></i>
                </button>
            </div>
        </form>
    </div>

    <div class="version-info">
        <div>Baan Prai Fah Management System v1.2.0</div>
        <div><?php echo date("d F Y"); ?></div>
    </div>

</body>
</html>