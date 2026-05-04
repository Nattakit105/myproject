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
    <script>
        (function() {
            const savedTheme = localStorage.getItem('appTheme') || 'dark';
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();
    </script>

    <style>
        :root {
            --dark-bg: #0f172a;       /* พื้นหลัง Slate 900 */
            --card-bg: #1e293b;       /* พื้นหลัง Navbar/Card Slate 800 */
            --input-bg: #334155;      /* พื้นหลัง Input Slate 700 */
            --primary-blue: #3b82f6;  /* สีน้ำเงินหลัก */
            --text-main: #f8fafc;     /* ตัวหนังสือขาว */
            --text-muted: #94a3b8;    /* ตัวหนังสือเทา */
            --border-soft: rgba(255,255,255,0.08);
            --surface-soft: rgba(255,255,255,0.03);
            --shadow-soft: 0 10px 15px -3px rgba(0, 0, 0, 0.3);
        }

        html[data-theme="light"] {
            --dark-bg: #f4f7fb;
            --card-bg: #ffffff;
            --input-bg: #eef2f7;
            --primary-blue: #2563eb;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --border-soft: rgba(15,23,42,0.12);
            --surface-soft: rgba(15,23,42,0.04);
            --shadow-soft: 0 10px 25px -12px rgba(15, 23, 42, 0.28);
        }

        body { 
            font-family: 'Kanit', sans-serif; 
            background-color: var(--dark-bg); 
            color: var(--text-main);
            margin: 0;
            min-height: 100vh;
        }
        html[data-theme="dark"] body { background-color: var(--dark-bg) !important; color: var(--text-main) !important; }
        html[data-theme="light"] body { background-color: var(--dark-bg) !important; color: var(--text-main) !important; }

        /* ตกแต่ง Navbar ให้ดูพรีเมียม */
        .navbar { 
            background-color: var(--card-bg) !important; 
            border-bottom: 1px solid var(--border-soft);
            padding: 0.7rem 0;
        }
        .navbar-brand { font-weight: 600; font-size: 1.25rem; letter-spacing: 0.5px; }
        .brand-logo {
            width: 42px;
            height: 42px;
            margin-right: 10px;
            border-radius: 50%;
            object-fit: cover;
            background: #ffffff;
            border: 1px solid rgba(15, 23, 42, 0.16);
            box-shadow: 0 8px 18px -12px rgba(0, 0, 0, 0.55);
            flex: 0 0 42px;
        }
        .navbar-brand, .navbar-brand:hover { color: var(--text-main) !important; }
        .navbar-toggler { color: var(--text-main); }
        .navbar-toggler-icon { filter: none; }
        html[data-theme="light"] .navbar-toggler-icon { filter: invert(1) grayscale(1); }

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
            background: var(--surface-soft);
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
            border: none; 
            border-radius: 1.25rem; 
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);
        }
        .card:not(.bg-primary):not(.bg-success):not(.bg-danger):not(.bg-warning):not(.bg-info):not(.bg-secondary):not(.bg-light):not(.bg-dark):not(.bg-white):not(.bg-occupied-dark):not(.bg-empty-white):not(.bg-occupied-grey) {
            background-color: var(--card-bg);
            color: var(--text-main);
        }
        html[data-theme="dark"] .card:not(.bg-primary):not(.bg-success):not(.bg-danger):not(.bg-warning):not(.bg-info):not(.bg-secondary):not(.bg-light):not(.bg-dark):not(.bg-white):not(.bg-occupied-dark):not(.bg-empty-white):not(.bg-occupied-grey),
        html[data-theme="dark"] .card-dark { background-color: var(--card-bg) !important; color: var(--text-main) !important; }
        html[data-theme="light"] .card:not(.bg-primary):not(.bg-success):not(.bg-danger):not(.bg-warning):not(.bg-info):not(.bg-secondary):not(.bg-light):not(.bg-dark):not(.bg-white):not(.bg-occupied-dark):not(.bg-empty-white):not(.bg-occupied-grey),
        html[data-theme="light"] .card-dark { background-color: var(--card-bg) !important; color: var(--text-main) !important; box-shadow: var(--shadow-soft) !important; }
        html[data-theme="light"] .card-header:not(.bg-primary):not(.bg-success):not(.bg-danger):not(.bg-warning):not(.bg-info):not(.bg-secondary) {
            background-color: #f8fafc !important;
            color: var(--text-main) !important;
        }

        /* ตกแต่ง Table ให้เป็น Dark Theme */
        .table { color: var(--text-main); }
        .table-light { --bs-table-bg: #334155; --bs-table-color: #fff; border-color: #475569; }
        html[data-theme="light"] .table { color: var(--text-main) !important; }
        html[data-theme="light"] .table-light,
        html[data-theme="light"] .table-modern thead {
            --bs-table-bg: #e2e8f0;
            --bs-table-color: #0f172a;
            background-color: #e2e8f0 !important;
            color: #0f172a !important;
        }
        html[data-theme="light"] .table-modern td { color: #0f172a !important; border-color: rgba(15,23,42,0.08) !important; }
        html[data-theme="light"] .bg-dark { background-color: #ffffff !important; color: #0f172a !important; }
        html[data-theme="light"] .bg-light { background-color: #eef2f7 !important; color: #0f172a !important; }
        html[data-theme="light"] .text-dark { color: #0f172a !important; }
        html[data-theme="light"] .card-dark .text-white,
        html[data-theme="light"] .utility-month-form .text-white { color: #0f172a !important; }
        html[data-theme="light"] .text-muted,
        html[data-theme="light"] footer { color: #64748b !important; }
        html[data-theme="light"] .form-control,
        html[data-theme="light"] .form-select,
        html[data-theme="light"] .form-select-dark,
        html[data-theme="light"] .utility-month-select {
            background-color: #ffffff !important;
            color: #0f172a !important;
            border-color: #cbd5e1 !important;
        }
        html[data-theme="light"] .form-control::placeholder { color: #94a3b8; }
        html[data-theme="light"] .list-group-item { background-color: #ffffff !important; color: #0f172a !important; }
        html[data-theme="dark"] .card:not(.bg-warning):not(.bg-empty-white) .text-dark,
        html[data-theme="dark"] .table .text-dark,
        html[data-theme="dark"] .list-group-item .text-dark { color: #f8fafc !important; }
        html[data-theme="dark"] .bg-warning .text-dark,
        html[data-theme="dark"] .bg-empty-white .text-dark { color: #0f172a !important; }
        html[data-theme="dark"] .bg-light:not(.badge):not(.btn) { background-color: #334155 !important; color: #f8fafc !important; }
        html[data-theme="dark"] .bg-light .text-dark,
        html[data-theme="dark"] .bg-light label,
        html[data-theme="dark"] .bg-light h1,
        html[data-theme="dark"] .bg-light h2,
        html[data-theme="dark"] .bg-light h3,
        html[data-theme="dark"] .bg-light h4,
        html[data-theme="dark"] .bg-light h5,
        html[data-theme="dark"] .bg-light h6,
        html[data-theme="dark"] .bg-light p,
        html[data-theme="dark"] .bg-light span:not(.badge) { color: #f8fafc !important; }
        html[data-theme="dark"] .form-control,
        html[data-theme="dark"] .form-select,
        html[data-theme="dark"] .form-select-dark,
        html[data-theme="dark"] .utility-month-select {
            background-color: #0f172a !important;
            color: #f8fafc !important;
            border-color: #475569 !important;
        }
        html[data-theme="dark"] .form-control::placeholder { color: #94a3b8; }
        html[data-theme="dark"] .list-group-item { background-color: #1e293b !important; color: #f8fafc !important; border-color: #334155 !important; }
        html[data-theme="dark"] .table > :not(caption) > * > * { color: inherit; }
        .app-footer { color: var(--text-muted) !important; }
        html[data-theme="dark"] .app-footer { color: #cbd5e1 !important; }
        html[data-theme="light"] .app-footer { color: #64748b !important; }

        .theme-toggle-btn {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            border: 1px solid var(--border-soft);
            background: var(--surface-soft);
            color: var(--text-main);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: 0.2s ease;
        }
        .theme-toggle-btn:hover {
            color: var(--primary-blue);
            border-color: rgba(59, 130, 246, 0.45);
            background: rgba(59, 130, 246, 0.1);
        }

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
                <img class="brand-logo" src="images/cat.png" alt="โลโก้ปรายฟ้ารีสอร์ท">
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
                            <button type="button" class="theme-toggle-btn" id="themeToggle" title="สลับโหมดมืด/สว่าง" aria-label="สลับโหมดมืด/สว่าง">
                                <i class="bi bi-sun-fill" id="themeToggleIcon"></i>
                            </button>
                        </li>

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
