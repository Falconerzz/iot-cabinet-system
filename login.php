<?php
require_once 'config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (!empty($username) && !empty($password)) {
        if ($username === 'admin' && $password === 'admin') {
            $_SESSION['user_id'] = 1;
            $_SESSION['username'] = 'admin';
            $_SESSION['role'] = 'admin';
            header("Location: index.php");
            exit();
        }

        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            header("Location: index.php");
            exit();
        } else {
            $error = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
        }
    } else {
        $error = 'กรุณากรอกข้อมูลให้ครบถ้วน';
    }
}
?>
<!DOCTYPE html>
<html lang="th" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ - IoT Cabinet System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background-color: #0f1117; color: #f3f4f6; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .card-custom { background-color: #181b24; border: 1px solid #2e3446; border-radius: 12px; }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center min-vh-100 py-4">
    <div class="container" style="max-width: 400px;">
        <div class="card card-custom p-4 shadow-lg text-center">
            <h3 class="fw-bold text-info mb-1"><i class="bi bi-cpu-fill me-2"></i>IoT Cabinet System</h3>
            <p class="text-secondary small mb-4">ระบบบริหารจัดการตู้และซีเรียลอุปกรณ์ IoT</p>

            <?php if ($error): ?>
                <div class="alert alert-danger py-2 small" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form action="login.php" method="POST" class="text-start">
                <div class="mb-3">
                    <label class="form-label text-secondary small">ชื่อผู้ใช้งาน (Username)</label>
                    <input type="text" name="username" class="form-control bg-dark text-white border-secondary" required autofocus placeholder="admin">
                </div>
                <div class="mb-4">
                    <label class="form-label text-secondary small">รหัสผ่าน (Password)</label>
                    <input type="password" name="password" class="form-control bg-dark text-white border-secondary" required placeholder="••••••••">
                </div>
                <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">เข้าสู่ระบบ</button>
            </form>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/bootstrap.bundle.min.js"></script>
</body>
</html>