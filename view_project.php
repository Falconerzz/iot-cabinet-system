<?php
require_once 'config.php';
check_login();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$stmt = $conn->prepare("SELECT * FROM projects WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$project = $stmt->get_result()->fetch_assoc();

if (!$project) {
    die("ไม่พบข้อมูลโครงการ");
}

$eq_stmt = $conn->prepare("SELECT * FROM equipments WHERE project_id = ?");
$eq_stmt->bind_param("i", $id);
$eq_stmt->execute();
$equipments = $eq_stmt->get_result();

$control_items = [];
$dc_items = [];
while ($item = $equipments->fetch_assoc()) {
    if ($item['cabinet_type'] === 'control_iot') {
        $control_items[] = $item;
    } else {
        $dc_items[] = $item;
    }
}

$logs_stmt = $conn->prepare("SELECT * FROM activity_logs WHERE project_id = ? ORDER BY id DESC LIMIT 15");
$logs_stmt->bind_param("i", $id);
$logs_stmt->execute();
$logs = $logs_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="th" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายละเอียดโครงการ - <?= htmlspecialchars($project['project_name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        .img-preview { width: 50px; height: 50px; object-fit: cover; border-radius: 6px; cursor: pointer; border: 1px solid #2e3446; }
        .img-preview:hover { transform: scale(1.1); transition: 0.2s; }
    </style>
</head>
<body>
    <div class="app-container">
        <?php include 'sidebar.php'; ?>

        <main class="main-content">
            <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom border-secondary">
                <h2 class="fw-bold text-white m-0">โครงการ: <?= htmlspecialchars($project['project_name']) ?></h2>
                <div class="d-flex gap-2">
                    <a href="edit_project.php?id=<?= $id ?>" class="btn btn-warning"><i class="bi bi-pencil-square me-1"></i> แก้ไข</a>
                    <form action="update_project.php" method="POST" onsubmit="return confirm('ยืนยันลบโครงการนี้ใช่หรือไม่?');">
                        <input type="hidden" name="project_id" value="<?= $id ?>">
                        <input type="hidden" name="action" value="delete">
                        <button type="submit" class="btn btn-danger"><i class="bi bi-trash me-1"></i> ลบ</button>
                    </form>
                </div>
            </div>

            <!-- ข้อมูลเฉพาะ และ Checklist ตู้ Control -->
            <div class="card card-custom p-4 mb-4">
                <div class="row g-4">
                    <div class="col-md-6 border-end border-secondary">
                        <span class="badge bg-primary bg-opacity-25 text-primary border border-primary px-2 py-1 mb-2">ตู้ Control IoT</span>
                        <h5>ID ตู้: <strong class="text-info"><?= htmlspecialchars($project['control_iot_id']) ?></strong></h5>
                        <div class="small text-secondary mt-2">Reverse SSH: <span class="text-white"><?= htmlspecialchars($project['reverse_ssh_name'] ?: '-') ?></span></div>
                        <div class="small text-secondary">Gateway: <span class="text-white"><?= htmlspecialchars($project['gateway_name'] ?: '-') ?> (<?= htmlspecialchars($project['gateway_code'] ?: '-') ?>)</span></div>
                    </div>
                    <div class="col-md-6">
                        <span class="badge bg-info bg-opacity-25 text-info border border-info px-2 py-1 mb-2">ตู้ DC-IoT</span>
                        <h5>ID ตู้: <strong class="text-info"><?= htmlspecialchars($project['dc_iot_id']) ?></strong></h5>
                        <div class="small text-secondary mt-2">วันที่ติดตั้ง: <span class="text-white"><?= htmlspecialchars($project['installation_date'] ?: '-') ?></span></div>
                    </div>
                </div>

                <hr class="border-secondary my-3">

                <!-- แสดงผล Checklist ตู้ Control -->
                <h5 class="text-warning mb-3"><i class="bi bi-card-checklist me-2"></i>Checklist การเตรียมตู้ Control IoT</h5>
                <div class="row g-3 small">
                    <div class="col-md-3">
                        <strong class="text-secondary">เสาอากาศ:</strong> 
                        <span><?= $project['has_antenna'] ? '✅ ใส่แล้ว' : '❌ ยังไม่ใส่' ?></span>
                    </div>
                    <div class="col-md-3">
                        <strong class="text-secondary">การตั้งค่ากล้อง:</strong> 
                        <span><?= $project['camera_setup_status'] === 'completed' ? '✅ ตั้งค่าแล้ว' : '❌ ยังไม่ตั้งค่า' ?></span>
                    </div>
                    <div class="col-md-3">
                        <strong class="text-secondary">ซิมการ์ด:</strong> 
                        <span><?= $project['sim_status'] === 'installed' ? '✅ ใส่แล้ว (เบอร์: '.htmlspecialchars($project['sim_number']).')' : '❌ ยังไม่ใส่' ?></span>
                    </div>
                    <div class="col-md-3">
                        <strong class="text-secondary">SD Card:</strong> 
                        <span><?= $project['sd_status'] === 'installed' ? '✅ ใส่แล้ว (ความจุ: '.htmlspecialchars($project['sd_capacity_gb']).')' : '❌ ยังไม่ใส่' ?></span>
                    </div>
                </div>
            </div>

            <!-- รายการอุปกรณ์ Control และ DC -->
            <div class="row g-4 mb-4">
                <div class="col-xl-6 col-lg-12">
                    <div class="card card-custom p-3 h-100">
                        <h4 class="text-primary border-bottom border-secondary pb-2 mb-3"><i class="bi bi-box-seam me-2"></i>อุปกรณ์ในตู้ Control IoT</h4>
                        <div class="table-responsive">
                            <table class="table table-dark table-hover align-middle border-secondary">
                                <thead>
                                    <tr>
                                        <th>ชื่ออุปกรณ์</th>
                                        <th>Serial Number</th>
                                        <th>รูปยืนยัน S/N</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($control_items) > 0): ?>
                                        <?php foreach($control_items as $eq): ?>
                                            <tr>
                                                <td class="fw-bold text-white"><?= htmlspecialchars($eq['equipment_name']) ?></td>
                                                <td class="text-info fw-bold"><?= htmlspecialchars($eq['serial_number']) ?></td>
                                                <td>
                                                    <?php if ($eq['photo_path']): ?>
                                                        <img src="<?= $eq['photo_path'] ?>" class="img-preview" alt="SN Photo" onclick="zoomImage('<?= $eq['photo_path'] ?>', '<?= htmlspecialchars($eq['equipment_name']) ?>')">
                                                    <?php else: ?>
                                                        <span class="text-secondary small">ไม่มีรูปถ่าย</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="3" class="text-center text-secondary py-3">ไม่มีรายการอุปกรณ์</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-xl-6 col-lg-12">
                    <div class="card card-custom p-3 h-100">
                        <h4 class="text-info border-bottom border-secondary pb-2 mb-3"><i class="bi bi-box-seam me-2"></i>อุปกรณ์ในตู้ DC-IoT</h4>
                        <div class="table-responsive">
                            <table class="table table-dark table-hover align-middle border-secondary">
                                <thead>
                                    <tr>
                                        <th>ชื่ออุปกรณ์</th>
                                        <th>Serial Number</th>
                                        <th>รูปยืนยัน S/N</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($dc_items) > 0): ?>
                                        <?php foreach($dc_items as $eq): ?>
                                            <tr>
                                                <td class="fw-bold text-white"><?= htmlspecialchars($eq['equipment_name']) ?></td>
                                                <td class="text-info fw-bold"><?= htmlspecialchars($eq['serial_number']) ?></td>
                                                <td>
                                                    <?php if ($eq['photo_path']): ?>
                                                        <img src="<?= $eq['photo_path'] ?>" class="img-preview" alt="SN Photo" onclick="zoomImage('<?= $eq['photo_path'] ?>', '<?= htmlspecialchars($eq['equipment_name']) ?>')">
                                                    <?php else: ?>
                                                        <span class="text-secondary small">ไม่มีรูปถ่าย</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="3" class="text-center text-secondary py-3">ไม่มีรายการอุปกรณ์</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ประวัติ Logs -->
            <div class="card card-custom p-3">
                <h5 class="fw-bold text-white mb-3"><i class="bi bi-journal-text me-2"></i>ประวัติกิจกรรมของโครงการนี้</h5>
                <?php if ($logs->num_rows > 0): ?>
                    <?php while($l = $logs->fetch_assoc()): ?>
                        <div class="p-2 mb-2 bg-dark rounded border border-secondary small">
                            <span class="text-secondary me-2"><?= $l['created_at'] ?></span>
                            <span class="text-white"><?= htmlspecialchars($l['description']) ?></span>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="text-secondary small m-0">ยังไม่มีประวัติกิจกรรม</p>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Modal ขยายรูปภาพ -->
    <div class="modal fade" id="imageModal" tabindex="-1" onclick="closeImageModal(event)">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content card-custom text-white text-center p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 id="zoomTitle" class="m-0 text-info fw-bold"></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="p-2 bg-dark rounded mb-3">
                    <img id="zoomedImg" src="" style="max-width: 100%; max-height: 70vh; object-fit: contain;" alt="Zoomed S/N">
                </div>
                <div class="d-flex justify-content-center">
                    <a id="downloadBtn" href="" download class="btn btn-success"><i class="bi bi-download me-1"></i> ดาวน์โหลดรูปภาพ</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/bootstrap.bundle.min.js"></script>
    <script>
        function zoomImage(src, name) {
            document.getElementById('zoomedImg').src = src;
            document.getElementById('zoomTitle').innerText = 'รูปถ่ายหลักฐาน S/N - ' + name;
            document.getElementById('downloadBtn').href = src;
            document.getElementById('downloadBtn').download = 'SN_' + name + '.jpg';
            const imgModal = new bootstrap.Modal(document.getElementById('imageModal'));
            imgModal.show();
        }

        function closeImageModal(event) {
            if (event.target.id === 'imageModal') {
                const modalEl = document.getElementById('imageModal');
                const modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) modal.hide();
            }
        }
    </script>
</body>
</html>