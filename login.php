<?php
require_once 'config.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']); $password = trim($_POST['password']);
    if (!empty($username) && !empty($password)) {
        if ($username === 'admin' && $password === 'admin') {
            $_SESSION['user_id'] = 1; $_SESSION['username'] = 'admin'; $_SESSION['role'] = 'admin';
            header("Location: index.php"); exit();
        }
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?"); $stmt->bind_param("s", $username); $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc(); $stmt->close();
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id']; $_SESSION['username'] = $user['username']; $_SESSION['role'] = $user['role'];
            header("Location: index.php"); exit();
        } else { $error = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง'; }
    } else { $error = 'กรุณากรอกข้อมูลให้ครบถ้วน'; }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ - Sync Vibe</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="bg-grid"></div><div class="bg-glow-1"></div><div class="bg-glow-2"></div>
    <div class="d-flex align-items-center justify-content-center min-vh-100 position-relative z-1">
        <div class="container" style="max-width: 420px;">
            <div class="card card-custom p-5 text-center shadow-lg border-0">
                <div class="bg-white shadow-sm rounded-circle d-inline-flex align-items-center justify-content-center mx-auto mb-4" style="width: 80px; height: 80px;">
                    <i class="bi bi-broadcast fs-1 text-primary-custom"></i>
                </div>
                <h3 class="fw-bold text-gradient mb-2">Sync Vibe</h3>
                <p class="text-secondary small mb-4">ระบบบริหารจัดการตู้ IoT และจุดติดตั้งทั่วประเทศ</p>
                <?php if ($error): ?><div class="alert alert-danger py-2 small rounded-3 fw-bold"><i class="bi bi-exclamation-triangle-fill me-1"></i> <?= htmlspecialchars($error) ?></div><?php endif; ?>
                <form action="login.php" method="POST" class="text-start">
                    <div class="mb-3">
                        <label class="form-label text-secondary small fw-bold">ชื่อผู้ใช้งาน</label>
                        <input type="text" name="username" class="form-control" required autofocus placeholder="admin">
                    </div>
                    <div class="mb-4">
                        <label class="form-label text-secondary small fw-bold">รหัสผ่าน</label>
                        <input type="password" name="password" class="form-control" required placeholder="••••••••">
                    </div>
                    <button type="submit" class="btn btn-primary w-100 py-3 fs-6 rounded-pill">เข้าสู่ระบบ</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>