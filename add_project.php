<?php
require_once 'config.php';
check_login();

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
    <title>เพิ่มโครงการใหม่ - IoT Cabinet System</title>
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
                <h2 class="fw-bold text-white m-0"><i class="bi bi-folder-plus text-primary me-2"></i>เพิ่มข้อมูลประกอบตู้ IoT คู่ใหม่</h2>
            </div>

            <form action="save_project.php" method="POST" enctype="multipart/form-data">
                <!-- ข้อมูลโครงการ -->
                <div class="card card-custom p-4 mb-4">
                    <h4 class="text-white fw-bold mb-3">ข้อมูลทั่วไปของโครงการ</h4>
                    <div class="mb-3">
                        <label class="form-label text-secondary">ชื่อโครงการ *</label>
                        <input type="text" name="project_name" class="form-control bg-dark text-white border-secondary" required placeholder="เช่น โครงการ Smart Factory Phase 1">
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label text-secondary">ID ตู้ Control IoT *</label>
                            <input type="text" name="control_iot_id" class="form-control bg-dark text-white border-secondary" required placeholder="เช่น CTRL-001">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-secondary">ID ตู้ DC-IoT *</label>
                            <input type="text" name="dc_iot_id" class="form-control bg-dark text-white border-secondary" required placeholder="เช่น DC-001">
                        </div>
                    </div>
                </div>

                <!-- รายละเอียดตู้ Control + Checklist -->
                <div class="card card-custom p-4 mb-4">
                    <h4 class="text-primary border-bottom border-secondary pb-2 mb-3"><i class="bi bi-sliders me-2"></i>รายละเอียดตู้ Control IoT</h4>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label text-secondary">Reverse SSH Name</label>
                            <input type="text" name="reverse_ssh_name" class="form-control bg-dark text-white border-secondary" placeholder="เช่น ssh-control-01">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-secondary">Gateway Name</label>
                            <input type="text" name="gateway_name" class="form-control bg-dark text-white border-secondary" placeholder="เช่น GW-RPI-01">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-secondary">Gateway Code</label>
                            <input type="text" name="gateway_code" class="form-control bg-dark text-white border-secondary" placeholder="เช่น GWC-9982">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-secondary">Installation Date (วันที่ติดตั้ง)</label>
                            <input type="date" name="installation_date" class="form-control bg-dark text-white border-secondary">
                        </div>
                    </div>

                    <h5 class="text-warning mt-4 mb-3"><i class="bi bi-card-checklist me-2"></i>Checklist การเตรียมตู้ Control IoT</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label text-secondary">เสาอากาศ</label>
                            <select name="has_antenna" class="form-select bg-dark text-white border-secondary">
                                <option value="0">ยังไม่ได้ใส่</option>
                                <option value="1">ใส่เรียบร้อยแล้ว</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-secondary">การตั้งค่ากล้อง</label>
                            <select name="camera_setup_status" class="form-select bg-dark text-white border-secondary">
                                <option value="pending">ยังไม่ได้ตั้งค่า</option>
                                <option value="completed">ตั้งค่าเรียบร้อยแล้ว</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-secondary">ซิมการ์ด</label>
                            <select name="sim_status" class="form-select bg-dark text-white border-secondary" onchange="toggleGroup(this.value, 'sim_num_group')">
                                <option value="not_installed">ยังไม่ได้ใส่</option>
                                <option value="installed">ใส่เรียบร้อยแล้ว</option>
                            </select>
                        </div>
                        <div class="col-md-6" id="sim_num_group" style="display:none;">
                            <label class="form-label text-secondary">เบอร์โทรศัพท์ซิม</label>
                            <input type="text" name="sim_number" class="form-control bg-dark text-white border-secondary" placeholder="เช่น 0812345678">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-secondary">SD Card</label>
                            <select name="sd_status" class="form-select bg-dark text-white border-secondary" onchange="toggleGroup(this.value, 'sd_cap_group')">
                                <option value="not_installed">ยังไม่ได้ใส่</option>
                                <option value="installed">ใส่เรียบร้อยแล้ว</option>
                            </select>
                        </div>
                        <div class="col-md-6" id="sd_cap_group" style="display:none;">
                            <label class="form-label text-secondary">ความจุ SD Card (GB)</label>
                            <input type="text" name="sd_capacity_gb" class="form-control bg-dark text-white border-secondary" placeholder="เช่น 32GB, 64GB">
                        </div>
                    </div>
                </div>

                <!-- อุปกรณ์ตู้ Control -->
                <div class="card card-custom p-4 mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="text-primary m-0"><i class="bi bi-box-seam me-2"></i>อุปกรณ์ภายในตู้ Control IoT</h4>
                        <div class="d-flex align-items-center gap-2">
                            <label class="small text-secondary m-0">โหลดแม่แบบ:</label>
                            <select onchange="loadTemplateItems('control_eq_list', 'control', this.value)" class="form-select bg-dark text-white border-secondary form-select-sm">
                                <option value="">-- เลือกแพตเทิร์น --</option>
                                <?php while($tpl = $control_tpls->fetch_assoc()): ?>
                                    <option value="<?= $tpl['id'] ?>"><?= htmlspecialchars($tpl['template_name']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    <div id="control_eq_list"></div>
                    <button type="button" class="btn btn-outline-secondary mt-2" onclick="addEquipmentRow('control_eq_list', 'control')">+ เพิ่มอุปกรณ์ Control</button>
                </div>

                <!-- อุปกรณ์ตู้ DC -->
                <div class="card card-custom p-4 mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="text-info m-0"><i class="bi bi-box-seam me-2"></i>อุปกรณ์ภายในตู้ DC-IoT</h4>
                        <div class="d-flex align-items-center gap-2">
                            <label class="small text-secondary m-0">โหลดแม่แบบ:</label>
                            <select onchange="loadTemplateItems('dc_eq_list', 'dc', this.value)" class="form-select bg-dark text-white border-secondary form-select-sm">
                                <option value="">-- เลือกแพตเทิร์น --</option>
                                <?php while($tpl = $dc_tpls->fetch_assoc()): ?>
                                    <option value="<?= $tpl['id'] ?>"><?= htmlspecialchars($tpl['template_name']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    <div id="dc_eq_list"></div>
                    <button type="button" class="btn btn-outline-secondary mt-2" onclick="addEquipmentRow('dc_eq_list', 'dc')">+ เพิ่มอุปกรณ์ DC</button>
                </div>

                <button type="submit" class="btn btn-primary btn-lg w-100 py-3 fw-bold"><i class="bi bi-save me-2"></i>บันทึกข้อมูลโครงการทั้งหมด</button>
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

        window.onload = function() {
            addEquipmentRow('control_eq_list', 'control');
            addEquipmentRow('dc_eq_list', 'dc');
        };
    </script>
</body>
</html>