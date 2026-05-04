<?php
session_start();
// ล้างค่า session ทั้งหมด
session_unset();
session_destroy();
// กลับไปยังหน้า login
header("Location: login.php");
exit();
<<<<<<< HEAD
>>>>>>> b3c7638653082b907eb612c49ef346ef3806ad14
=======
>>>>>>> ba37e4e6dda8110a0f8318feeb3d84c507f67045
?>