<?php
require_once 'config.php';
check_login();

$result = $conn->query("SELECT * FROM projects ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="th" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายการโครงการ - IoT Cabinet System</title>
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
                    <h2 class="fw-bold m-0 text-white"><i class="bi bi-grid-fill text-primary me-2"></i>รายการโครงการประกอบตู้ IoT</h2>
                    <small class="text-secondary">แสดงโครงการทั้งหมดในระบบ</small>
                </div>
                <a href="add_project.php" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> เพิ่มโครงการใหม่</a>
            </div>

            <?php if ($result->num_rows > 0): ?>
                <div class="row g-4">
                    <?php while($row = $result->fetch_assoc()): ?>
                        <div class="col-xl-6 col-lg-12">
                            <div class="card card-custom p-3 h-100 shadow-sm">
                                <div class="d-flex justify-content-between align-items-center mb-3 border-bottom border-secondary pb-2">
                                    <h4 class="m-0 text-white fw-bold">โครงการ: <?= htmlspecialchars($row['project_name']) ?></h4>
                                    <a href="view_project.php?id=<?= $row['id'] ?>" class="btn btn-outline-info btn-sm"><i class="bi bi-eye me-1"></i> ดูรายละเอียด</a>
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-6 border-end border-secondary">
                                        <span class="badge bg-primary bg-opacity-25 text-primary border border-primary px-2 py-1">ตู้ Control IoT</span>
                                        <p class="mt-2 mb-0 fs-5 fw-bold text-info">ID: <?= htmlspecialchars($row['control_iot_id']) ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <span class="badge bg-info bg-opacity-25 text-info border border-info px-2 py-1">ตู้ DC-IoT</span>
                                        <p class="mt-2 mb-0 fs-5 fw-bold text-info">ID: <?= htmlspecialchars($row['dc_iot_id']) ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="card card-custom p-5 text-center text-secondary">
                    <i class="bi bi-folder-x display-4 mb-2"></i>
                    <p class="m-0">ยังไม่มีข้อมูลโครงการ คลิกที่ปุ่ม "+ เพิ่มโครงการใหม่" เพื่อเริ่มสร้าง</p>
                </div>
            <?php endif; ?>
        </main>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/bootstrap.bundle.min.js"></script>
</body>
</html>