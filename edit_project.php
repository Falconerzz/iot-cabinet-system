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

$master_res = $conn->query("SELECT * FROM master_equipments ORDER BY equipment_name ASC");
$masters = [];
while($m = $master_res->fetch_assoc()) {
    $masters[] = $m['equipment_name'];
}

$control_tpls = $conn->query("SELECT * FROM equipment_templates WHERE cabinet_type = 'control_iot' ORDER BY id DESC");
$dc_tpls = $conn->query("SELECT * FROM equipment_templates WHERE cabinet_type = 'dc_iot' ORDER BY id DESC");

$tpl_items_map = [];
$items_query = $conn->query("SELECT * FROM template_items");
while ($row = $items_query->fetch_assoc()) {
    $tpl_items_map[$row['template_id']][] = $row['equipment_name'];
}
?>
<!DOCTYPE html>
<html lang="th" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แก้ไขโครงการ - <?= htmlspecialchars($project['project_name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://unpkg.com/html5-qrcode"></script>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="app-container">
        <?php include 'sidebar.php'; ?>

        <main class="main-content">
            <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom border-secondary">
                <h2 class="fw-bold text-white m-0"><i class="bi bi-pencil-square text-warning me-2"></i>แก้ไขข้อมูลโครงการ: <?= htmlspecialchars($project['project_name']) ?></h2>
                <a href="view_project.php?id=<?= $id ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> ยกเลิก</a>
            </div>

            <form action="update_project.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="project_id" value="<?= $id ?>">

                <div class="card card-custom p-4 mb-4">
                    <h4 class="text-white fw-bold mb-3">ข้อมูลทั่วไปของโครงการ</h4>
                    <div class="mb-3">
                        <label class="form-label text-secondary">ชื่อโครงการ *</label>
                        <input type="text" name="project_name" value="<?= htmlspecialchars($project['project_name']) ?>" class="form-control bg-dark text-white border-secondary" required>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label text-secondary">ID ตู้ Control IoT *</label>
                            <input type="text" name="control_iot_id" value="<?= htmlspecialchars($project['control_iot_id']) ?>" class="form-control bg-dark text-white border-secondary" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-secondary">ID ตู้ DC-IoT *</label>
                            <input type="text" name="dc_iot_id" value="<?= htmlspecialchars($project['dc_iot_id']) ?>" class="form-control bg-dark text-white border-secondary" required>
                        </div>
                    </div>
                </div>

                <div class="card card-custom p-4 mb-4">
                    <h4 class="text-primary border-bottom border-secondary pb-2 mb-3">รายละเอียดตู้ Control IoT</h4>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label text-secondary">Reverse SSH Name</label>
                            <input type="text" name="reverse_ssh_name" value="<?= htmlspecialchars($project['reverse_ssh_name'] ?? '') ?>" class="form-control bg-dark text-white border-secondary">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-secondary">Gateway Name</label>
                            <input type="text" name="gateway_name" value="<?= htmlspecialchars($project['gateway_name'] ?? '') ?>" class="form-control bg-dark text-white border-secondary">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-secondary">Gateway Code</label>
                            <input type="text" name="gateway_code" value="<?= htmlspecialchars($project['gateway_code'] ?? '') ?>" class="form-control bg-dark text-white border-secondary">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-secondary">Installation Date</label>
                            <input type="date" name="installation_date" value="<?= htmlspecialchars($project['installation_date'] ?? '') ?>" class="form-control bg-dark text-white border-secondary">
                        </div>
                    </div>

                    <h5 class="text-warning mt-4 mb-3"><i class="bi bi-card-checklist me-2"></i>Checklist การเตรียมตู้ Control IoT</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label text-secondary">เสาอากาศ</label>
                            <select name="has_antenna" class="form-select bg-dark text-white border-secondary">
                                <option value="0" <?= $project['has_antenna'] == 0 ? 'selected' : '' ?>>ยังไม่ได้ใส่</option>
                                <option value="1" <?= $project['has_antenna'] == 1 ? 'selected' : '' ?>>ใส่เรียบร้อยแล้ว</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-secondary">การตั้งค่ากล้อง</label>
                            <select name="camera_setup_status" class="form-select bg-dark text-white border-secondary">
                                <option value="pending" <?= $project['camera_setup_status'] === 'pending' ? 'selected' : '' ?>>ยังไม่ได้ตั้งค่า</option>
                                <option value="completed" <?= $project['camera_setup_status'] === 'completed' ? 'selected' : '' ?>>ตั้งค่าเรียบร้อยแล้ว</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-secondary">ซิมการ์ด</label>
                            <select name="sim_status" class="form-select bg-dark text-white border-secondary" onchange="toggleGroup(this.value, 'sim_num_group')">
                                <option value="not_installed" <?= $project['sim_status'] === 'not_installed' ? 'selected' : '' ?>>ยังไม่ได้ใส่</option>
                                <option value="installed" <?= $project['sim_status'] === 'installed' ? 'selected' : '' ?>>ใส่เรียบร้อยแล้ว</option>
                            </select>
                        </div>
                        <div class="col-md-6" id="sim_num_group" style="display:<?= $project['sim_status'] === 'installed' ? 'block' : 'none' ?>;">
                            <label class="form-label text-secondary">เบอร์โทรศัพท์ซิม</label>
                            <input type="text" name="sim_number" value="<?= htmlspecialchars($project['sim_number'] ?? '') ?>" class="form-control bg-dark text-white border-secondary">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-secondary">SD Card</label>
                            <select name="sd_status" class="form-select bg-dark text-white border-secondary" onchange="toggleGroup(this.value, 'sd_cap_group')">
                                <option value="not_installed" <?= $project['sd_status'] === 'not_installed' ? 'selected' : '' ?>>ยังไม่ได้ใส่</option>
                                <option value="installed" <?= $project['sd_status'] === 'installed' ? 'selected' : '' ?>>ใส่เรียบร้อยแล้ว</option>
                            </select>
                        </div>
                        <div class="col-md-6" id="sd_cap_group" style="display:<?= $project['sd_status'] === 'installed' ? 'block' : 'none' ?>;">
                            <label class="form-label text-secondary">ความจุ SD Card (GB)</label>
                            <input type="text" name="sd_capacity_gb" value="<?= htmlspecialchars($project['sd_capacity_gb'] ?? '') ?>" class="form-control bg-dark text-white border-secondary">
                        </div>
                    </div>
                </div>

                <!-- รายการอุปกรณ์ Control -->
                <div class="card card-custom p-4 mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="text-primary m-0">อุปกรณ์ภายในตู้ Control IoT</h4>
                        <div class="d-flex align-items-center gap-2">
                            <label class="small text-secondary m-0">แทนที่ด้วยแม่แบบ:</label>
                            <select onchange="loadTemplateItems('control_eq_list', 'control', this.value)" class="form-select bg-dark text-white border-secondary form-select-sm">
                                <option value="">-- เลือกแพตเทิร์น --</option>
                                <?php while($tpl = $control_tpls->fetch_assoc()): ?>
                                    <option value="<?= $tpl['id'] ?>"><?= htmlspecialchars($tpl['template_name']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    <div id="control_eq_list">
                        <?php foreach ($control_items as $eq): ?>
                            <?php $row_id = 'row_exist_control_' . $eq['id']; ?>
                            <div class="row g-2 align-items-start mb-3 p-2 bg-dark rounded border border-secondary" id="<?= $row_id ?>">
                                <input type="hidden" name="control_id[]" value="<?= $eq['id'] ?>">
                                <div class="col-md-4">
                                    <label class="small text-secondary">ชื่ออุปกรณ์</label>
                                    <select name="control_name[]" class="form-select select2-eq-name" required>
                                        <option value="<?= htmlspecialchars($eq['equipment_name']) ?>" selected><?= htmlspecialchars($eq['equipment_name']) ?></option>
                                        <?php foreach($masters as $m): ?>
                                            <?php if($m !== $eq['equipment_name']): ?>
                                                <option value="<?= htmlspecialchars($m) ?>"><?= htmlspecialchars($m) ?></option>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="small text-secondary">Serial Number *</label>
                                    <div class="input-group">
                                        <input type="text" id="sn_<?= $row_id ?>" name="control_serial[]" value="<?= htmlspecialchars($eq['serial_number']) ?>" class="form-control bg-dark text-white border-secondary" required>
                                        <button type="button" class="btn btn-info" onclick="openCamera('sn_<?= $row_id ?>')">📷 สแกน</button>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <label class="small text-secondary">รูปยืนยัน S/N</label>
                                    <?php if ($eq['photo_path']): ?>
                                        <small class="text-info d-block mb-1"><i class="bi bi-image me-1"></i>มีรูปเดิมอยู่แล้ว</small>
                                    <?php endif; ?>
                                    <input type="file" id="file_<?= $row_id ?>" name="control_photo[]" class="form-control bg-dark text-white border-secondary" accept="image/*" onchange="scanImageFile(this, 'sn_<?= $row_id ?>')">
                                </div>
                                <div class="col-md-1 d-flex align-items-end">
                                    <button type="button" class="btn btn-danger w-100" onclick="this.parentElement.parentElement.remove()">ลบ</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="btn btn-outline-secondary mt-2" onclick="addEquipmentRow('control_eq_list', 'control')">+ เพิ่มอุปกรณ์ Control</button>
                </div>

                <!-- รายการอุปกรณ์ DC -->
                <div class="card card-custom p-4 mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="text-info m-0">อุปกรณ์ภายในตู้ DC-IoT</h4>
                        <div class="d-flex align-items-center gap-2">
                            <label class="small text-secondary m-0">แทนที่ด้วยแม่แบบ:</label>
                            <select onchange="loadTemplateItems('dc_eq_list', 'dc', this.value)" class="form-select bg-dark text-white border-secondary form-select-sm">
                                <option value="">-- เลือกแพตเทิร์น --</option>
                                <?php while($tpl = $dc_tpls->fetch_assoc()): ?>
                                    <option value="<?= $tpl['id'] ?>"><?= htmlspecialchars($tpl['template_name']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    <div id="dc_eq_list">
                        <?php foreach ($dc_items as $eq): ?>
                            <?php $row_id = 'row_exist_dc_' . $eq['id']; ?>
                            <div class="row g-2 align-items-start mb-3 p-2 bg-dark rounded border border-secondary" id="<?= $row_id ?>">
                                <input type="hidden" name="dc_id[]" value="<?= $eq['id'] ?>">
                                <div class="col-md-4">
                                    <label class="small text-secondary">ชื่ออุปกรณ์</label>
                                    <select name="dc_name[]" class="form-select select2-eq-name" required>
                                        <option value="<?= htmlspecialchars($eq['equipment_name']) ?>" selected><?= htmlspecialchars($eq['equipment_name']) ?></option>
                                        <?php foreach($masters as $m): ?>
                                            <?php if($m !== $eq['equipment_name']): ?>
                                                <option value="<?= htmlspecialchars($m) ?>"><?= htmlspecialchars($m) ?></option>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="small text-secondary">Serial Number *</label>
                                    <div class="input-group">
                                        <input type="text" id="sn_<?= $row_id ?>" name="dc_serial[]" value="<?= htmlspecialchars($eq['serial_number']) ?>" class="form-control bg-dark text-white border-secondary" required>
                                        <button type="button" class="btn btn-info" onclick="openCamera('sn_<?= $row_id ?>')">📷 สแกน</button>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <label class="small text-secondary">รูปยืนยัน S/N</label>
                                    <?php if ($eq['photo_path']): ?>
                                        <small class="text-info d-block mb-1"><i class="bi bi-image me-1"></i>มีรูปเดิมอยู่แล้ว</small>
                                    <?php endif; ?>
                                    <input type="file" id="file_<?= $row_id ?>" name="dc_photo[]" class="form-control bg-dark text-white border-secondary" accept="image/*" onchange="scanImageFile(this, 'sn_<?= $row_id ?>')">
                                </div>
                                <div class="col-md-1 d-flex align-items-end">
                                    <button type="button" class="btn btn-danger w-100" onclick="this.parentElement.parentElement.remove()">ลบ</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="btn btn-outline-secondary mt-2" onclick="addEquipmentRow('dc_eq_list', 'dc')">+ เพิ่มอุปกรณ์ DC</button>
                </div>

                <button type="submit" class="btn btn-warning btn-lg w-100 py-3 fw-bold"><i class="bi bi-save me-2"></i>อัปเดตข้อมูลโครงการทั้งหมด</button>
            </form>
        </main>
    </div>

    <!-- Modal สแกนบาร์โค้ด -->
    <div id="scannerModal" class="modal fade" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content card-custom text-white p-3">
                <h5 class="fw-bold text-info"><i class="bi bi-camera me-2"></i>สแกน Barcode จากกล้อง</h5>
                <div id="reader"></div>
                <button type="button" class="btn btn-danger mt-3" onclick="closeScanner()">ปิดกล้อง</button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/bootstrap.bundle.min.js"></script>
    <script>
        const masterEquipments = <?= json_encode($masters) ?>;
        const templateItemsData = <?= json_encode($tpl_items_map) ?>;

        let currentActiveInput = null;
        let html5QrCode = null;

        function toggleGroup(value, groupId) {
            document.getElementById(groupId).style.display = (value === 'installed') ? 'block' : 'none';
        }

        $(document).ready(function() {
            $('.select2-eq-name').select2({
                theme: 'bootstrap-5',
                width: '100%',
                tags: true,
                placeholder: '🔍 พิมพ์เพื่อค้นหาหรือระบุชื่อ...'
            });
        });

        function loadTemplateItems(containerId, type, templateId) {
            const container = document.getElementById(containerId);
            container.innerHTML = '';
            if (!templateId || !templateItemsData[templateId]) {
                addEquipmentRow(containerId, type);
                return;
            }
            templateItemsData[templateId].forEach(eqName => {
                addEquipmentRow(containerId, type, eqName);
            });
        }

        function addEquipmentRow(containerId, type, defaultName = '') {
            const container = document.getElementById(containerId);
            const rowId = 'row_' + Date.now() + '_' + Math.floor(Math.random() * 1000);
            const row = document.createElement('div');
            row.className = 'row g-2 align-items-start mb-3 p-2 bg-dark rounded border border-secondary';
            row.id = rowId;

            let masterOptions = '<option value="">🔍 พิมพ์ค้นหา/เลือกชื่ออุปกรณ์...</option>';
            masterEquipments.forEach(m => {
                const selected = (m === defaultName) ? 'selected' : '';
                masterOptions += `<option value="${m}" ${selected}>${m}</option>`;
            });

            row.innerHTML = `
                <input type="hidden" name="${type}_id[]" value="new">
                <div class="col-md-4">
                    <label class="small text-secondary">ชื่ออุปกรณ์</label>
                    <select name="${type}_name[]" class="form-select select2-eq-name" required>
                        ${masterOptions}
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="small text-secondary">Serial Number *</label>
                    <div class="input-group">
                        <input type="text" id="sn_${rowId}" name="${type}_serial[]" class="form-control bg-dark text-white border-secondary" placeholder="S/N" required>
                        <button type="button" class="btn btn-info" onclick="openCamera('sn_${rowId}')">📷 สแกน</button>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="small text-secondary">รูปยืนยัน S/N</label>
                    <input type="file" id="file_${rowId}" name="${type}_photo[]" class="form-control bg-dark text-white border-secondary" accept="image/*" onchange="scanImageFile(this, 'sn_${rowId}')">
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button type="button" class="btn btn-danger w-100" onclick="this.parentElement.parentElement.remove()">ลบ</button>
                </div>
            `;
            container.appendChild(row);

            $(`#${rowId} .select2-eq-name`).select2({
                theme: 'bootstrap-5',
                width: '100%',
                tags: true,
                placeholder: '🔍 พิมพ์เพื่อค้นหาหรือระบุชื่อ...'
            });
        }

        function scanImageFile(fileInput, targetInputId) {
            if (fileInput.files.length === 0) return;
            const imageFile = fileInput.files[0];
            const html5QrCodeScanner = new Html5Qrcode("reader");

            html5QrCodeScanner.scanFile(imageFile, true)
                .then(qrCodeMessage => {
                    document.getElementById(targetInputId).value = qrCodeMessage;
                })
                .catch(err => { console.log("ไม่พบ Barcode: ", err); });
        }

        function openCamera(targetInputId) {
            currentActiveInput = targetInputId;
            const modal = new bootstrap.Modal(document.getElementById('scannerModal'));
            modal.show();

            if (!html5QrCode) {
                html5QrCode = new Html5Qrcode("reader");
            }

            html5QrCode.start(
                { facingMode: "environment" },
                { fps: 10, qrbox: { width: 250, height: 150 } },
                (decodedText) => {
                    document.getElementById(currentActiveInput).value = decodedText;
                    closeScanner();
                },
                (errorMessage) => { }
            ).catch(err => {
                alert("ไม่สามารถเปิดกล้องได้: " + err);
                closeScanner();
            });
        }

        function closeScanner() {
            const scannerModalEl = document.getElementById('scannerModal');
            const modal = bootstrap.Modal.getInstance(scannerModalEl);
            if (modal) modal.hide();

            if (html5QrCode && html5QrCode.isScanning) {
                html5QrCode.stop();
            }
        }
    </script>
</body>
</html>

<!-- 11/8/2569 05:07 -->