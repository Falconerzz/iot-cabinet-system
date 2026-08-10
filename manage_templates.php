<?php
require_once 'config.php';
check_login();

$master_res = $conn->query("SELECT * FROM master_equipments ORDER BY equipment_name ASC");
$masters = [];
while($m = $master_res->fetch_assoc()) {
    $masters[] = $m['equipment_name'];
}

$templates = $conn->query("
    SELECT t.*, COUNT(i.id) as item_count 
    FROM equipment_templates t 
    LEFT JOIN template_items i ON t.id = i.template_id 
    GROUP BY t.id 
    ORDER BY t.id DESC
");
?>
<!DOCTYPE html>
<html lang="th" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการแม่แบบอุปกรณ์ - IoT Cabinet System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="app-container">
        <?php include 'sidebar.php'; ?>

        <main class="main-content">
            <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom border-secondary">
                <div>
                    <h2 class="fw-bold text-white m-0"><i class="bi bi-layers-fill text-warning me-2"></i>จัดการแม่แบบอุปกรณ์ (Templates)</h2>
                    <small class="text-secondary">สร้างและบันทึกชุดอุปกรณ์มาตรฐานล่วงหน้าเพื่อความสะดวกในการเพิ่มโครงการ</small>
                </div>
            </div>

            <div class="card card-custom p-4 mb-4">
                <h5 class="fw-bold text-info mb-3"><i class="bi bi-plus-square me-2"></i>สร้างแม่แบบอุปกรณ์ใหม่</h5>
                <form action="save_template.php" method="POST">
                    <input type="hidden" name="action" value="create">
                    <div class="row g-3 mb-3">
                        <div class="col-md-8">
                            <label class="form-label text-secondary">ตั้งชื่อแพตเทิร์น / แม่แบบ *</label>
                            <input type="text" name="template_name" class="form-control bg-dark text-white border-secondary" required placeholder="เช่น ชุดอุปกรณ์ Standard Type A">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-secondary">สำหรับประเภทตู้ *</label>
                            <select name="cabinet_type" class="form-select bg-dark text-white border-secondary" required>
                                <option value="control_iot">ตู้ Control IoT</option>
                                <option value="dc_iot">ตู้ DC-IoT</option>
                            </select>
                        </div>
                    </div>

                    <h6 class="fw-bold text-warning mb-2"><i class="bi bi-list-check me-1"></i>รายชื่ออุปกรณ์ในแม่แบบนี้</h6>
                    <div id="template_item_list"></div>
                    
                    <button type="button" class="btn btn-outline-secondary btn-sm mb-3" onclick="addTemplateItemRow()">+ เพิ่มอุปกรณ์ในแม่แบบ</button>
                    <button type="submit" class="btn btn-primary w-100 py-2 fw-bold"><i class="bi bi-save me-1"></i>บันทึกแพตเทิร์นแม่แบบนี้</button>
                </form>
            </div>

            <!-- รายการแม่แบบ -->
            <div class="card card-custom p-4">
                <h5 class="fw-bold text-white mb-3"><i class="bi bi-list-stars me-2"></i>แม่แบบอุปกรณ์ทั้งหมดในระบบ</h5>
                <?php if ($templates->num_rows > 0): ?>
                    <?php while($tpl = $templates->fetch_assoc()): ?>
                        <div class="d-flex justify-content-between align-items-center p-3 mb-2 bg-dark rounded border border-secondary">
                            <div>
                                <strong class="text-white fs-5 me-2"><?= htmlspecialchars($tpl['template_name']) ?></strong>
                                <span class="badge <?= $tpl['cabinet_type'] === 'control_iot' ? 'bg-primary' : 'bg-info' ?>">
                                    <?= $tpl['cabinet_type'] === 'control_iot' ? 'ตู้ Control' : 'ตู้ DC' ?>
                                </span>
                                <small class="text-secondary ms-2">(<?= $tpl['item_count'] ?> รายการ)</small>
                            </div>
                            <form action="save_template.php" method="POST" onsubmit="return confirm('ยืนยันลบแม่แบบนี้?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="template_id" value="<?= $tpl['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i> ลบ</button>
                            </form>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="text-secondary small text-center my-3">ยังไม่มีแม่แบบอุปกรณ์ในระบบ</p>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/bootstrap.bundle.min.js"></script>
    <script>
        const masterEquipments = <?= json_encode($masters) ?>;

        function addTemplateItemRow(defaultName = '') {
            const container = document.getElementById('template_item_list');
            const rowId = 'tpl_item_' + Date.now() + '_' + Math.floor(Math.random() * 1000);
            const div = document.createElement('div');
            div.className = 'row g-2 align-items-center mb-2';
            div.id = rowId;

            let masterOptions = '<option value="">🔍 พิมพ์ค้นหา/เลือกชื่ออุปกรณ์...</option>';
            masterEquipments.forEach(m => {
                const selected = (m === defaultName) ? 'selected' : '';
                masterOptions += `<option value="${m}" ${selected}>${m}</option>`;
            });

            div.innerHTML = `
                <div class="col-md-10">
                    <select name="equipment_name[]" class="form-select select2-tpl-eq" required>
                        ${masterOptions}
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-danger w-100" onclick="this.parentElement.parentElement.remove()">ลบ</button>
                </div>
            `;
            container.appendChild(div);

            $(`#${rowId} .select2-tpl-eq`).select2({
                theme: 'bootstrap-5',
                width: '100%',
                tags: true,
                placeholder: '🔍 พิมพ์เพื่อค้นหาหรือระบุชื่อ...'
            });
        }

        window.onload = function() {
            addTemplateItemRow();
            addTemplateItemRow();
        };
    </script>
</body>
</html>

<!-- 11/8/2569 05:07 -->