<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "iot_cabinet_db";

$conn = new mysqli($host, $user, $pass, $dbname);
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ตรวจสอบการเข้าสู่ระบบ
function check_login() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }
}

// ทำความสะอาดชื่ออุปกรณ์
function clean_equipment_name($name) {
    $name = trim($name);
    return preg_replace('/\s+/', ' ', $name);
}

// บันทึก Log ประวัติการทำงาน
function log_activity($conn, $project_id, $action_type, $description) {
    $stmt = $conn->prepare("INSERT INTO activity_logs (project_id, action_type, description) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $project_id, $action_type, $description);
    $stmt->execute();
    $stmt->close();
}
?>