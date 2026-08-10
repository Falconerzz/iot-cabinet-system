<?php
require_once 'config.php';
check_login();

$filter = $_GET['filter'] ?? 'ALL';
$where_clause = "";

if ($filter !== 'ALL') {
    $where_clause = " WHERE l.action_type = '" . $conn->real_escape_string($filter) . "' ";
}

$sql = "SELECT l.*, p.project_name FROM activity_logs l LEFT JOIN projects p ON l.project_id = p.id {$where_clause} ORDER BY l.id DESC LIMIT 100";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="th" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ประวัติ Logs - IoT Cabinet System</title>
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
                    <h2 class="fw-bold text-white m-0"><i class="bi bi-journal-text text-info me-2"></i>ประวัติกิจกรรมและ Log ทั้งหมด</h2>
                    <small class="text-secondary">แสดงประวัติย้อนหลัง 100 รายการล่าสุด</small>
                </div>
            </div>

            <!-- ตัวกรอง -->
            <div class="card card-custom p-3 mb-4 d-flex flex-row gap-2 flex-wrap align-items-center">
                <span class="text-secondary small fw-bold">ตัวกรองกิจกรรม:</span>
                <a href="logs.php?filter=ALL" class="btn btn-sm <?= $filter === 'ALL' ? 'btn-primary' : 'btn-outline-secondary' ?>">ทั้งหมด</a>
                <a href="logs.php?filter=CREATE_PROJECT" class="btn btn-sm <?= $filter === 'CREATE_PROJECT' ? 'btn-success' : 'btn-outline-success' ?>">🟢 การสร้าง</a>
                <a href="logs.php?filter=UPDATE_PROJECT" class="btn btn-sm <?= $filter === 'UPDATE_PROJECT' ? 'btn-warning' : 'btn-outline-warning' ?>">🟡 การแก้ไข</a>
                <a href="logs.php?filter=DELETE_PROJECT" class="btn btn-sm <?= $filter === 'DELETE_PROJECT' ? 'btn-danger' : 'btn-outline-danger' ?>">🔴 การลบ</a>
                <a href="logs.php?filter=TRANSFER_EQUIPMENT" class="btn btn-sm <?= $filter === 'TRANSFER_EQUIPMENT' ? 'btn-info' : 'btn-outline-info' ?>">🔵 การโยกย้าย/สลับ</a>
            </div>

            <div class="card card-custom p-4">
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while($log = $result->fetch_assoc()): ?>
                        <?php 
                            $badge_class = 'bg-secondary';
                            $badge_label = $log['action_type'];

                            switch ($log['action_type']) {
                                case 'CREATE_PROJECT':
                                    $badge_class = 'bg-success';
                                    $badge_label = '🟢 สร้างโครงการ';
                                    break;
                                case 'UPDATE_PROJECT':
                                    $badge_class = 'bg-warning text-dark';
                                    $badge_label = '🟡 แก้ไขข้อมูล';
                                    break;
                                case 'DELETE_PROJECT':
                                    $badge_class = 'bg-danger';
                                    $badge_label = '🔴 ลบโครงการ';
                                    break;
                                case 'TRANSFER_EQUIPMENT':
                                    $badge_class = 'bg-info text-dark';
                                    $badge_label = '🔵 โยกย้าย/สลับ';
                                    break;
                            }
                        ?>
                        <div class="p-3 mb-2 bg-dark rounded border border-secondary">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <div>
                                    <span class="badge <?= $badge_class ?> me-2"><?= $badge_label ?></span>
                                    <?php if ($log['project_name']): ?>
                                        <strong class="text-info">[โครงการ: <?= htmlspecialchars($log['project_name']) ?>]</strong>
                                    <?php endif; ?>
                                </div>
                                <small class="text-secondary"><?= $log['created_at'] ?></small>
                            </div>
                            <div class="text-white small mt-1"><?= htmlspecialchars($log['description']) ?></div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="text-center text-secondary my-4">ไม่พบประวัติการทำงานในหมวดหมู่นี้</p>
                <?php endif; ?>
            </div>
        </main>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/bootstrap.bundle.min.js"></script>
</body>
</html>