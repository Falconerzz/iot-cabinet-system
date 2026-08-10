<?php
require_once 'config.php';
check_login();

$msg = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $eq_name = clean_equipment_name($_POST['equipment_name'] ?? '');
        if (!empty($eq_name)) {
            $stmt = $conn->prepare("INSERT INTO master_equipments (equipment_name) VALUES (?)");
            $stmt->bind_param("s", $eq_name);
            if ($stmt->execute()) {
                $msg = "เพิ่มชื่ออุปกรณ์ '{$eq_name}' เข้าสู่พจนานุกรมสำเร็จ";
            } else {
                $error = "ไม่สามารถเพิ่มได้ อาจมีชื่อนี้ในระบบแล้ว";
            }
            $stmt->close();
        }
    } elseif ($action === 'delete') {
        $id = intval($_POST['id']);
        $stmt = $conn->prepare("DELETE FROM master_equipments WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        $msg = "ลบชื่ออุปกรณ์เรียบร้อยแล้ว";
    }
}

$masters = $conn->query("SELECT * FROM master_equipments ORDER BY equipment_name ASC");
?>
<!DOCTYPE html>
<html lang="th" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>พจนานุกรมชื่ออุปกรณ์มาตรฐาน - IoT Cabinet System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="app-container">
        <?php include 'sidebar.php'; ?>

        <main class="main-content">
            <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom border-secondary">
                <div>
                    <h2 class="fw-bold text-white m-0"><i class="bi bi-card-checklist text-info me-2"></i>พจนานุกรมชื่ออุปกรณ์มาตรฐาน</h2>
                    <small class="text-secondary">กำหนดชื่ออุปกรณ์มาตรฐาน (รองรับตัวอักษรเล็ก/ใหญ่/ตัวเลข) เพื่อใช้ใน Dropdown และการค้นหา</small>
                </div>
            </div>

            <?php if ($msg): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($msg) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card card-custom p-4 mb-4">
                <h5 class="fw-bold text-info mb-3"><i class="bi bi-plus-circle me-2"></i>เพิ่มชื่ออุปกรณ์ใหม่ในพจนานุกรม</h5>
                <form action="manage_master_equipments.php" method="POST" class="row g-2">
                    <input type="hidden" name="action" value="add">
                    <div class="col-md-9">
                        <input type="text" name="equipment_name" class="form-control bg-dark text-white border-secondary" placeholder="เช่น IRT, irt_v2, Power Supply 24V" required>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary w-100"><i class="bi bi-save me-1"></i> บันทึกชื่อ</button>
                    </div>
                </form>
            </div>

            <div class="card card-custom p-4">
                <h5 class="fw-bold text-white mb-3"><i class="bi bi-list-ul me-2"></i>รายการชื่ออุปกรณ์มาตรฐานในระบบ</h5>
                <div class="table-responsive">
                    <table class="table table-dark table-hover border-secondary align-middle">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>ชื่ออุปกรณ์มาตรฐาน (คำตรงตัว)</th>
                                <th class="text-end">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($masters->num_rows > 0): ?>
                                <?php $i=1; while($row = $masters->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= $i++ ?></td>
                                        <td class="fw-bold text-info"><?= htmlspecialchars($row['equipment_name']) ?></td>
                                        <td class="text-end">
                                            <form action="manage_master_equipments.php" method="POST" onsubmit="return confirm('ยืนยันลบชื่ออุปกรณ์นี้ออกจากพจนานุกรม?');" style="display:inline;">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i> ลบ</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="3" class="text-center text-secondary py-3">ยังไม่มีข้อมูลในพจนานุกรม</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/bootstrap.bundle.min.js"></script>
</body>
</html>