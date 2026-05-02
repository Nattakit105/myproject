<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ปรับให้แอดมินเริ่มต้นที่หน้า Dashboard
$home_url = 'login.php'; 
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        $home_url = 'dashboard.php';
    } else {
        $home_url = 'tenant_view.php'; 
    }
}

$current_page = basename($_SERVER['PHP_SELF']); 
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ปรายฟ้ารีสอร์ท | Management System</title>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        :root {
            --dark-bg: #0f172a;       /* พื้นหลัง Slate 900 */
            --card-bg: #1e293b;       /* พื้นหลัง Navbar/Card Slate 800 */
            --input-bg: #334155;      /* พื้นหลัง Input Slate 700 */
            --primary-blue: #3b82f6;  /* สีน้ำเงินหลัก */
            --text-main: #f8fafc;     /* ตัวหนังสือขาว */
            --text-muted: #94a3b8;    /* ตัวหนังสือเทา */
        }

        body { 
            font-family: 'Kanit', sans-serif; 
            background-color: var(--dark-bg); 
            color: var(--text-main);
            margin: 0;
            min-height: 100vh;
        }

        /* ตกแต่ง Navbar ให้ดูพรีเมียม */
        .navbar { 
            background-color: var(--card-bg) !important; 
            border-bottom: 1px solid rgba(255,255,255,0.05);
            padding: 0.7rem 0;
        }
        .navbar-brand { font-weight: 600; font-size: 1.25rem; letter-spacing: 0.5px; }
        .navbar-brand img { max-height: 38px; margin-right: 10px; border-radius: 6px; }

        .nav-link { 
            font-size: 0.9rem; 
            color: var(--text-muted) !important;
            padding: 0.6rem 1rem !important;
            transition: 0.3s;
            border-radius: 10px;
            margin: 0 2px;
        }
        .nav-link i { margin-right: 5px; font-size: 1.1rem; }
        .nav-link:hover { 
            color: var(--primary-blue) !important; 
            background: rgba(255,255,255,0.03);
        }
        .nav-link.active { 
            color: #fff !important; 
            background: var(--primary-blue) !important;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }

        .btn-logout { 
            color: #fca5a5 !important; 
            font-weight: 500; 
            border-radius: 10px;
        }
        .btn-logout:hover { background: rgba(239, 68, 68, 0.1) !important; }

        /* สไตล์ Card กลางที่ทุกหน้าจะใช้ร่วมกัน */
        .card { 
            background-color: var(--card-bg); 
            border: none; 
            border-radius: 1.25rem; 
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);
            color: var(--text-main);
        }

        /* ตกแต่ง Table ให้เป็น Dark Theme */
        .table { color: var(--text-main); }
        .table-light { --bs-table-bg: #334155; --bs-table-color: #fff; border-color: #475569; }
        
        /* ตกแต่ง Scrollbar (แถมให้เพื่อความสวย) */
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: var(--dark-bg); }
        ::-webkit-scrollbar-thumb { background: #334155; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #475569; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark shadow-sm sticky-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="<?php echo $home_url; ?>">
                <img src="images/cat.png" alt="Logo"> 
                <span>ปรายฟ้ารีสอร์ท</span>
            </a>
            
            <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true): ?>
                <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#main-nav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                
                <div class="collapse navbar-collapse" id="main-nav">
                    <ul class="navbar-nav ms-auto align-items-center">
                        
                        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>" href="dashboard.php">
                                    <i class="bi bi-speedometer2"></i> แดชบอร์ด
                                </a>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link <?php echo ($current_page == 'index.php') ? 'active' : ''; ?>" href="index.php">
                                    <i class="bi bi-grid-3x3-gap"></i> จัดการห้องพัก
                                </a>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link <?php echo ($current_page == 'create_bill.php') ? 'active' : ''; ?>" href="create_bill.php">
                                    <i class="bi bi-pencil-square"></i> บันทึกบิล
                                </a>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link <?php echo ($current_page == 'manage_bills.php') ? 'active' : ''; ?>" href="manage_bills.php">
                                    <i class="bi bi-receipt"></i> ชำระเงิน
                                </a>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link <?php echo ($current_page == 'utilities_summary.php') ? 'active' : ''; ?>" href="utilities_summary.php">
                                    <i class="bi bi-lightning-water"></i> สาธารณูปโภค
                                </a>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link <?php echo ($current_page == 'manage_tenants.php') ? 'active' : ''; ?>" href="manage_tenants.php">
                                    <i class="bi bi-people"></i> ผู้เข้าพัก
                                </a>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link <?php echo ($current_page == 'settings.php') ? 'active' : ''; ?>" href="settings.php">
                                    <i class="bi bi-gear"></i> ตั้งค่า
                                </a>
                            </li>
                        <?php endif; ?>

                        <li class="nav-item ms-lg-2">
                            <a class="nav-link btn-logout" href="logout.php">
                                <i class="bi bi-box-arrow-right"></i> ออกจากระบบ
                            </a>
                        </li>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    </nav>