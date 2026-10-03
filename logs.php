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
    <style>
        body { background-color: #f8fafc; color: #1e293b; }
        
        .card-custom { border: none; border-radius: 1.25rem; box-shadow: 0 4px 15px rgba(0,0,0,0.02); transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); background: #ffffff; }
        .log-item { background: #ffffff; border: 1px solid #f1f5f9; padding: 1.25rem; border-radius: 1rem; margin-bottom: 1rem; transition: all 0.2s ease; box-shadow: 0 2px 10px rgba(0,0,0,0.01); }
        .log-item:hover { box-shadow: 0 6px 20px rgba(0,0,0,0.05); border-color: #e2e8f0; transform: translateY(-2px); }
        
        .badge-filter { padding: 0.5rem 1rem; border-radius: 0.75rem; font-weight: 600; cursor: pointer; transition: all 0.2s; text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem; }
        .badge-filter.active { box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        
        /* Filter Colors */
        .btn-outline-primary { color: #3b82f6; border-color: #bfdbfe; background: #eff6ff; }
        .btn-outline-primary:hover, .btn-primary { background: #3b82f6; color: white; border-color: #3b82f6; }
        
        .btn-outline-success { color: #10b981; border-color: #a7f3d0; background: #ecfdf5; }
        .btn-outline-success:hover, .btn-success { background: #10b981; color: white; border-color: #10b981; }
        
        .btn-outline-warning { color: #f59e0b; border-color: #fde68a; background: #fffbeb; }
        .btn-outline-warning:hover, .btn-warning { background: #f59e0b; color: white; border-color: #f59e0b; }
        
        .btn-outline-danger { color: #ef4444; border-color: #fecaca; background: #fef2f2; }
        .btn-outline-danger:hover, .btn-danger { background: #ef4444; color: white; border-color: #ef4444; }
        
        .btn-outline-info { color: #0ea5e9; border-color: #bae6fd; background: #f0f9ff; }
        .btn-outline-info:hover, .btn-info { background: #0ea5e9; color: white; border-color: #0ea5e9; }
        
        /* Log Badges */
        .badge-log { padding: 0.4rem 0.8rem; border-radius: 0.5rem; font-weight: 600; font-size: 0.75rem; letter-spacing: 0.5px; }
    </style>
</head>
<body>
    <div class="app-container">
        <?php include 'sidebar.php'; ?>

        <main class="main-content">
            <div class="mb-4 animate-fade-up">
                <div class="d-flex align-items-center gap-3 mb-2">
                    <div class="bg-secondary bg-opacity-10 text-dark rounded-3 d-flex justify-content-center align-items-center" style="width: 48px; height: 48px; font-size: 1.5rem;">
                        <i class="bi bi-clock-history"></i>
                    </div>
                    <div>
                        <h2 class="fw-bold text-dark m-0">ประวัติกิจกรรมระบบ (System Logs)</h2>
                        <p class="text-secondary m-0">แสดงประวัติการทำงานย้อนหลัง 100 รายการล่าสุด พร้อมระบุผู้ใช้งาน</p>
                    </div>
                </div>
            </div>

            <!-- ตัวกรอง -->
            <div class="card card-custom p-3 mb-4 d-flex flex-row gap-2 flex-wrap align-items-center border border-light animate-fade-up" style="animation-delay: 0.1s;">
                <span class="text-secondary small fw-bold text-uppercase me-2"><i class="bi bi-funnel-fill me-1"></i> ตัวกรองกิจกรรม:</span>
                <a href="logs.php?filter=ALL" class="badge-filter <?= $filter === 'ALL' ? 'btn-primary' : 'btn-outline-primary' ?>">ทั้งหมด</a>
                <a href="logs.php?filter=CREATE_PROJECT" class="badge-filter <?= $filter === 'CREATE_PROJECT' ? 'btn-success' : 'btn-outline-success' ?>"><i class="bi bi-plus-circle-fill"></i> การสร้าง</a>
                <a href="logs.php?filter=UPDATE_PROJECT" class="badge-filter <?= $filter === 'UPDATE_PROJECT' ? 'btn-warning' : 'btn-outline-warning' ?>"><i class="bi bi-pencil-fill"></i> การแก้ไข</a>
                <a href="logs.php?filter=DELETE_PROJECT" class="badge-filter <?= $filter === 'DELETE_PROJECT' ? 'btn-danger' : 'btn-outline-danger' ?>"><i class="bi bi-trash-fill"></i> การลบ</a>
                <a href="logs.php?filter=TRANSFER_EQUIPMENT" class="badge-filter <?= $filter === 'TRANSFER_EQUIPMENT' ? 'btn-info' : 'btn-outline-info' ?>"><i class="bi bi-arrow-left-right"></i> การโยกย้าย</a>
            </div>

            <!-- รายการ Log -->
            <div class="card card-custom p-4 bg-light border border-light animate-fade-up" style="animation-delay: 0.2s;">
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while($log = $result->fetch_assoc()): ?>
                        <?php 
                            $badge_class = 'bg-secondary bg-opacity-10 text-secondary border-secondary';
                            $badge_label = $log['action_type'];
                            $icon = 'bi-info-circle-fill';

                            switch ($log['action_type']) {
                                case 'CREATE_PROJECT':
                                    $badge_class = 'bg-success bg-opacity-10 text-success border border-success border-opacity-25';
                                    $badge_label = 'สร้างข้อมูล';
                                    $icon = 'bi-plus-circle-fill';
                                    break;
                                case 'UPDATE_PROJECT':
                                    $badge_class = 'bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25';
                                    $badge_label = 'แก้ไขข้อมูล';
                                    $icon = 'bi-pencil-fill';
                                    break;
                                case 'DELETE_PROJECT':
                                    $badge_class = 'bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25';
                                    $badge_label = 'ลบข้อมูล';
                                    $icon = 'bi-trash-fill';
                                    break;
                                case 'TRANSFER_EQUIPMENT':
                                    $badge_class = 'bg-info bg-opacity-10 text-info border border-info border-opacity-25';
                                    $badge_label = 'โยกย้ายอุปกรณ์';
                                    $icon = 'bi-arrow-left-right';
                                    break;
                            }
                        ?>
                        <div class="log-item">
                            <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom border-light">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge badge-log <?= $badge_class ?>"><i class="bi <?= $icon ?> me-1"></i> <?= $badge_label ?></span>
                                    <?php if ($log['project_name']): ?>
                                        <span class="badge bg-white text-dark border shadow-sm fw-bold">ตู้: <?= htmlspecialchars($log['project_name']) ?></span>
                                    <?php endif; ?>
                                </div>
                                <span class="badge bg-light text-secondary border px-2 py-1"><i class="bi bi-calendar3 me-1"></i> <?= date('d M Y, H:i', strtotime($log['created_at'])) ?></span>
                            </div>
                            
                            <!-- ดึงชื่อผู้ใช้งานออกมาไฮไลท์จาก Description (ถ้ามีรูปแบบ [username]) -->
                            <?php 
                                $desc = htmlspecialchars($log['description']);
                                // ค้นหาคำว่า [admin] หรือ [username] เพื่อไฮไลท์
                                $desc = preg_replace('/\[(.*?)\]/', '<span class="badge bg-dark text-white me-1"><i class="bi bi-person-circle me-1"></i>$1</span>', $desc);
                            ?>
                            <div class="text-dark fw-bold" style="font-size: 0.95rem; line-height: 1.6;">
                                <?= $desc ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="text-center py-5">
                        <div class="bg-white rounded-circle d-inline-flex justify-content-center align-items-center mb-3 shadow-sm border border-light" style="width: 80px; height: 80px;">
                            <i class="bi bi-journal-x text-secondary fs-1 opacity-50"></i>
                        </div>
                        <h6 class="fw-bold text-secondary m-0">ไม่พบประวัติกิจกรรมในหมวดหมู่นี้</h6>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/bootstrap.bundle.min.js"></script>
</body>
</html>