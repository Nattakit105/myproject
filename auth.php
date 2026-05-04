<?php
session_start();
include 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, password, role FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        if (password_verify($password, $user['password'])) {
            // รหัสผ่านถูกต้อง สร้าง session
            $_SESSION['loggedin'] = true;
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $username;
            $_SESSION['role'] = $user['role'];

            // ตรวจสอบ role แล้วเปลี่ยนหน้า
            if ($user['role'] === 'admin') {
                header("Location: index.php"); 
            } else {
                header("Location: tenant_view.php"); 
            }
            exit(); // จบการทำงานทันที
        }
    }
    
    // หาก username หรือ password ไม่ถูก ให้กลับไปหน้า login
    header("Location: login.php?error=1");
    exit();
}
>>>>>>> b3c7638653082b907eb612c49ef346ef3806ad14
// ตรวจสอบให้แน่ใจว่าไม่มีช่องว่างหรืออักขระใดๆ หลังโค้ดนี้