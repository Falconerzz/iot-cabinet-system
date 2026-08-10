<?php
require_once 'config.php';
check_login();

$projects_res = $conn->query("SELECT id, project_name, control_iot_id, dc_iot_id FROM projects ORDER BY project_name ASC");
$projects = [];
while($p = $projects_res->fetch_assoc()) {
    $projects[] = $p;
}

$eq_res = $conn->query("
    SELECT e.*, p.project_name 
    FROM equipments e 
    JOIN projects p ON e.project_id = p.id 
    ORDER BY p.project_name ASC, e.equipment_name ASC
");
$equipments = [];
while($eq = $eq_res->fetch_assoc()) {
    $equipments[] = $eq;
}
?>
<!DOCTYPE html>
<html lang="th" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบโยกย้าย / สลับอุปกรณ์</title>
    <!-- โหลด Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- โหลด Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <!-- โหลด jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- โหลด Select2 CSS & JS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <link rel="stylesheet" href="style.css">
    <style>
        .transfer-arrow { font-size: 2.5rem; color: #06b6d4; }
        .swap-arrow { font-size: 2.5rem; color: #f59e0b; }
        
        /* Custom Modal Backdrop Overlay ไร้บั๊กการโหลดสคริปต์ */
        .custom-modal-backdrop {
            position: fixed;
            top: 0; left: 0; width: 100vw; height: 100vh;
            background: rgba(0,0,0,0.85);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 2000;
        }
        .custom-modal-dialog {
            background: #181b24;
            border: 1px solid #2e3446;
            border-radius: 12px;
            max-width: 500px;
            width: 90%;
            padding: 24px;
            color: #fff;
            box-shadow: 0 10px 25px rgba(0,0,0,0.5);
        }
    </style>
</head>
<body>
    <div class="app-container">
        <?php include 'sidebar.php'; ?>

        <main class="main-content">
            <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom border-secondary">
                <div>
                    <h2 class="fw-bold m-0 text-white"><i class="bi bi-arrow-left-right text-success me-2"></i>ระบบโยกย้าย & สลับอุปกรณ์</h2>
                    <small class="text-secondary">จัดการย้ายตู้หรือสลับสับเปลี่ยนอุปกรณ์ระหว่างโครงการอย่างสะดวกรวดเร็ว</small>
                </div>
            </div>

            <form id="transferForm" action="save_transfer.php" method="POST">
                <div class="row g-4">
                    <!-- ฝั่งต้นทาง (Side A) -->
                    <div class="col-xl-5 col-lg-12">
                        <div class="card card-custom h-100 p-4 shadow-sm border-primary">
                            <h5 class="fw-bold text-primary mb-3 pb-2 border-bottom border-secondary"><i class="bi bi-box-seam me-2"></i>1. ฝั่งต้นทาง (โครงการ A)</h5>
                            
                            <div class="mb-3">
                                <label class="form-label text-secondary">ค้นหาและเลือกอุปกรณ์ต้นทาง *</label>
                                <select id="source_eq_id" name="source_eq_id" class="form-select select2-searchable" required>
                                    <option value="">🔍 พิมพ์ค้นหา (ชื่ออุปกรณ์ / S/N / โครงการ)...</option>
                                    <?php foreach($equipments as $eq): ?>
                                        <option value="<?= $eq['id'] ?>" 
                                                data-name="<?= htmlspecialchars($eq['equipment_name']) ?>"
                                                data-sn="<?= htmlspecialchars($eq['serial_number']) ?>"
                                                data-proj-id="<?= $eq['project_id'] ?>"
                                                data-proj-name="<?= htmlspecialchars($eq['project_name']) ?>"
                                                data-cab-type="<?= $eq['cabinet_type'] ?>">
                                            [<?= htmlspecialchars($eq['project_name']) ?>] <?= htmlspecialchars($eq['equipment_name']) ?> (S/N: <?= htmlspecialchars($eq['serial_number']) ?>) - <?= $eq['cabinet_type'] === 'control_iot' ? 'ตู้ Control' : 'ตู้ DC' ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div id="source_info_box" class="p-3 rounded bg-dark border border-secondary mt-3" style="display:none;">
                                <div class="small text-secondary mb-1 fw-bold">รายละเอียดต้นทาง:</div>
                                <div class="fw-bold text-info fs-5" id="src_name_disp"></div>
                                <div class="small mt-1">Serial Number: <span id="src_sn_disp" class="text-warning fw-bold"></span></div>
                                <div class="small">โครงการต้นทาง: <span id="src_proj_disp" class="text-white"></span></div>
                                <div class="small">ตู้: <span id="src_cab_disp" class="text-info"></span></div>
                            </div>
                        </div>
                    </div>

                    <!-- สถานะโหมด -->
                    <div class="col-xl-2 col-lg-12 d-flex flex-column justify-content-center align-items-center text-center">
                        <div id="mode_icon_box" class="my-2">
                            <i id="mode_icon" class="bi bi-arrow-right-circle-fill transfer-arrow"></i>
                        </div>
                        <span id="mode_badge" class="badge bg-info text-dark px-3 py-2 fw-bold">ย้ายอุปกรณ์</span>
                    </div>

                    <!-- ฝั่งปลายทาง (Side B) -->
                    <div class="col-xl-5 col-lg-12">
                        <div class="card card-custom h-100 p-4 shadow-sm border-info">
                            <h5 class="fw-bold text-info mb-3 pb-2 border-bottom border-secondary"><i class="bi bi-box-arrow-in-down-right me-2"></i>2. ฝั่งปลายทาง (โครงการ B)</h5>

                            <div class="mb-3">
                                <label class="form-label text-secondary">ตู้ปลายทาง (ตรงกับตู้ต้นทาง)</label>
                                <input type="text" id="target_cabinet_disp" class="form-control bg-dark text-info border-secondary fw-bold" value="-- ตรงกับตู้ต้นทาง --" readonly>
                                <input type="hidden" id="target_cabinet_type" name="target_cabinet_type" value="">
                            </div>

                            <div class="mb-3">
                                <label class="form-label text-secondary">ค้นหาโครงการปลายทาง * (ตัดโครงการ A ออก)</label>
                                <select id="target_project_id" name="target_project_id" class="form-select select2-searchable" required>
                                    <option value="">🔍 พิมพ์ค้นหาโครงการปลายทาง...</option>
                                    <?php foreach($projects as $p): ?>
                                        <option value="<?= $p['id'] ?>" data-name="<?= htmlspecialchars($p['project_name']) ?>">
                                            <?= htmlspecialchars($p['project_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div id="target_info_box" class="p-3 rounded bg-dark border border-secondary mb-3" style="display:none;">
                                <div class="small text-secondary mb-1 fw-bold">รายละเอียดปลายทาง:</div>
                                <div class="fw-bold text-success fs-5" id="tgt_proj_disp"></div>
                                <div class="small">ตู้ปลายทาง: <span id="tgt_cab_disp" class="text-info"></span></div>
                                <div class="small mt-1" id="tgt_eq_status"></div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label text-secondary">อุปกรณ์สลับคู่ (เฉพาะชื่อตรงกัน):</label>
                                <select id="target_eq_id" name="target_eq_id" class="form-select select2-searchable">
                                    <option value="NONE">-- ไม่สลับ (ย้ายเข้าเป็นชิ้นใหม่) --</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="button" class="btn btn-success btn-lg w-100 py-3 fw-bold shadow" onclick="showConfirmModal()">
                        <i class="bi bi-check-circle-fill me-2"></i> ตรวจสอบและยืนยันรายการ
                    </button>
                </div>
            </form>
        </main>
    </div>

    <!-- Custom Pop-up Modal ยืนยันข้อมูล (เปิดได้ 100% ไร้ข้อผิดพลาด) -->
    <div id="confirmModalBox" class="custom-modal-backdrop">
        <div class="custom-modal-dialog">
            <div class="d-flex justify-content-between align-items-center pb-2 mb-3 border-bottom border-secondary">
                <h5 class="fw-bold text-info m-0"><i class="bi bi-shield-check me-2"></i>ยืนยันการโยกย้าย / สลับอุปกรณ์</h5>
                <button type="button" class="btn-close btn-close-white" onclick="closeConfirmModal()"></button>
            </div>
            <div id="summaryContent" class="p-3 bg-dark rounded border border-secondary mb-3"></div>
            <div class="alert alert-warning py-2 mb-3 small">
                <i class="bi bi-exclamation-triangle-fill me-1"></i> กรุณาตรวจสอบความถูกต้องก่อนยืนยัน ระบบจะทำการบันทึกประวัติ (Log) ทันที
            </div>
            <div class="d-flex gap-2 justify-content-end">
                <button type="button" class="btn btn-outline-secondary" onclick="closeConfirmModal()">ยกเลิก / แก้ไข</button>
                <button type="button" class="btn btn-success px-4" onclick="submitTransfer()"><i class="bi bi-save me-1"></i> ยืนยันบันทึกข้อมูล</button>
            </div>
        </div>
    </div>

    <script>
        const allProjects = <?= json_encode($projects) ?>;
        const allEquipments = <?= json_encode($equipments) ?>;

        $(document).ready(function() {
            $('.select2-searchable').select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: '🔍 พิมพ์เพื่อค้นหาข้อมูล...'
            });

            $('#source_eq_id').on('change', function() { onSourceChange(); });
            $('#target_project_id').on('change', function() { onTargetChange(); });
            $('#target_eq_id').on('change', function() { onTargetEqChange(); });
        });

        function onSourceChange() {
            const srcOpt = $('#source_eq_id').find(':selected');
            const infoBox = document.getElementById('source_info_box');

            if (!srcOpt.val()) {
                infoBox.style.display = 'none';
                updateTargetProjectsList(null);
                return;
            }

            const srcProjId = srcOpt.attr('data-proj-id');
            const srcCabType = srcOpt.attr('data-cab-type');
            const srcEqName = srcOpt.attr('data-name');
            const srcSN = srcOpt.attr('data-sn');
            const srcProjName = srcOpt.attr('data-proj-name');

            document.getElementById('src_name_disp').innerText = srcEqName;
            document.getElementById('src_sn_disp').innerText = srcSN;
            document.getElementById('src_proj_disp').innerText = srcProjName;
            document.getElementById('src_cab_disp').innerText = srcCabType === 'control_iot' ? 'ตู้ Control IoT' : 'ตู้ DC-IoT';
            infoBox.style.display = 'block';

            document.getElementById('target_cabinet_type').value = srcCabType;
            document.getElementById('target_cabinet_disp').value = srcCabType === 'control_iot' ? 'ตู้ Control IoT (อัตโนมัติ)' : 'ตู้ DC-IoT (อัตโนมัติ)';

            updateTargetProjectsList(srcProjId);
        }

        function updateTargetProjectsList(sourceProjId) {
            const targetProjSelect = $('#target_project_id');
            const currentVal = targetProjSelect.val();

            targetProjSelect.empty();
            targetProjSelect.append(new Option('🔍 พิมพ์ค้นหาโครงการปลายทาง...', ''));

            allProjects.forEach(p => {
                if (!sourceProjId || p.id != sourceProjId) {
                    const opt = new Option(p.project_name, p.id);
                    $(opt).attr('data-name', p.project_name);
                    targetProjSelect.append(opt);
                }
            });

            if (currentVal && currentVal != sourceProjId) {
                targetProjSelect.val(currentVal);
            } else {
                targetProjSelect.val('');
            }

            targetProjSelect.trigger('change');
        }

        function onTargetChange() {
            const srcOpt = $('#source_eq_id').find(':selected');
            const tgtProjOpt = $('#target_project_id').find(':selected');
            const targetInfoBox = document.getElementById('target_info_box');

            if (!tgtProjOpt.val() || !srcOpt.val()) {
                targetInfoBox.style.display = 'none';
                updateTargetEquipmentsList();
                return;
            }

            const srcEqName = srcOpt.attr('data-name');
            const srcCabType = srcOpt.attr('data-cab-type');

            const tgtProjName = tgtProjOpt.attr('data-name');
            const tgtProjId = tgtProjOpt.val();

            const cabNameLabel = srcCabType === 'control_iot' ? 'ตู้ Control IoT' : 'ตู้ DC-IoT';

            document.getElementById('tgt_proj_disp').innerText = tgtProjName;
            document.getElementById('tgt_cab_disp').innerText = cabNameLabel;

            const existingMatch = allEquipments.find(eq => 
                eq.project_id == tgtProjId && 
                eq.cabinet_type == srcCabType && 
                eq.equipment_name.trim() === srcEqName.trim()
            );

            if (existingMatch) {
                document.getElementById('tgt_eq_status').innerHTML = `<span class="text-warning fw-bold"><i class="bi bi-exclamation-triangle me-1"></i> พบอุปกรณ์ ${existingMatch.equipment_name} (S/N: ${existingMatch.serial_number}) ในตู้ปลายทางแล้ว</span>`;
            } else {
                document.getElementById('tgt_eq_status').innerHTML = `<span class="text-success"><i class="bi bi-check-circle me-1"></i> ตู้ปลายทางยังไม่มีอุปกรณ์ ${srcEqName} (สามารถย้ายเข้าเป็นชิ้นใหม่ได้)</span>`;
            }

            targetInfoBox.style.display = 'block';
            updateTargetEquipmentsList();
        }

        function updateTargetEquipmentsList() {
            const srcOpt = $('#source_eq_id').find(':selected');
            const tgtProjOpt = $('#target_project_id').find(':selected');
            const targetEqSelect = $('#target_eq_id');

            targetEqSelect.empty();
            
            const defaultOpt = document.createElement('option');
            defaultOpt.value = 'NONE';
            defaultOpt.innerText = '-- ไม่สลับ (ย้ายเข้าเป็นชิ้นใหม่) --';
            targetEqSelect.append(defaultOpt);

            if (!srcOpt.val() || !tgtProjOpt.val()) {
                targetEqSelect.trigger('change');
                return;
            }

            const srcEqName = srcOpt.attr('data-name');
            const srcCabType = srcOpt.attr('data-cab-type');
            const targetProjId = tgtProjOpt.val();

            const matchedEqs = allEquipments.filter(eq => 
                eq.project_id == targetProjId && 
                eq.cabinet_type == srcCabType && 
                eq.equipment_name.trim() === srcEqName.trim()
            );

            matchedEqs.forEach(eq => {
                const optText = `🔄 สลับกับ: ${eq.equipment_name} (S/N: ${eq.serial_number})`;
                const newOpt = document.createElement('option');
                newOpt.value = eq.id;
                newOpt.innerText = optText;
                newOpt.setAttribute('data-name', eq.equipment_name);
                newOpt.setAttribute('data-sn', eq.serial_number);
                targetEqSelect.append(newOpt);
            });

            targetEqSelect.trigger('change');
            onTargetEqChange();
        }

        function onTargetEqChange() {
            const targetEqVal = $('#target_eq_id').val();
            const icon = document.getElementById('mode_icon');
            const badge = document.getElementById('mode_badge');

            if (targetEqVal && targetEqVal !== 'NONE') {
                icon.className = 'bi bi-arrow-left-right swap-arrow';
                badge.className = 'badge bg-warning text-dark px-3 py-2 fw-bold';
                badge.innerText = '🔄 โหมดสลับอุปกรณ์คู่ออโต้';
            } else {
                icon.className = 'bi bi-arrow-right-circle-fill transfer-arrow';
                badge.className = 'badge bg-info text-dark px-3 py-2 fw-bold';
                badge.innerText = '➡️ โหมดโยกย้ายอุปกรณ์';
            }
        }

        function showConfirmModal() {
            const srcOpt = $('#source_eq_id').find(':selected');
            const targetProjOpt = $('#target_project_id').find(':selected');
            const targetEqOpt = $('#target_eq_id').find(':selected');

            if (!srcOpt.val() || !targetProjOpt.val()) {
                alert('กรุณาเลือกอุปกรณ์ต้นทางและโครงการปลายทางให้ครบถ้วนก่อนครับ');
                return;
            }

            const srcName = srcOpt.attr('data-name');
            const srcSN = srcOpt.attr('data-sn');
            const srcProj = srcOpt.attr('data-proj-name');
            const srcCab = srcOpt.attr('data-cab-type') === 'control_iot' ? 'ตู้ Control IoT' : 'ตู้ DC-IoT';

            const targetProjName = targetProjOpt.attr('data-name');

            let html = '';

            if (targetEqOpt.val() && targetEqOpt.val() !== 'NONE') {
                const tgtName = targetEqOpt.attr('data-name');
                const tgtSN = targetEqOpt.attr('data-sn');

                html = `
                    <div class="text-warning fw-bold mb-2"><i class="bi bi-arrow-left-right me-1"></i> ยืนยันการสลับอุปกรณ์คู่อัตโนมัติ:</div>
                    <div class="p-2 mb-2 rounded bg-secondary bg-opacity-25 border border-warning">
                        <strong>ชิ้นที่ 1 (ย้าย A -> B):</strong> ${srcName} (S/N: ${srcSN})<br>
                        <small class="text-secondary">ย้ายจาก: ${srcProj} [${srcCab}] ➡️ ${targetProjName} [${srcCab}]</small>
                    </div>
                    <div class="p-2 rounded bg-secondary bg-opacity-25 border border-warning">
                        <strong>ชิ้นที่ 2 (สลับ B -> A):</strong> ${tgtName} (S/N: ${tgtSN})<br>
                        <small class="text-secondary">สลับไปใส่ที่: ${srcProj} [${srcCab}] ⬅️ ${targetProjName} [${srcCab}]</small>
                    </div>
                `;
            } else {
                html = `
                    <div class="text-info fw-bold mb-2"><i class="bi bi-arrow-right-circle me-1"></i> ยืนยันการย้ายอุปกรณ์:</div>
                    <p class="mb-1"><strong>อุปกรณ์:</strong> <span class="text-info">${srcName}</span> (S/N: ${srcSN})</p>
                    <p class="mb-1"><strong>🔴 ต้นทาง:</strong> ${srcProj} [${srcCab}]</p>
                    <p class="mb-0"><strong>🟢 ปลายทาง:</strong> ${targetProjName} [${srcCab}]</p>
                `;
            }

            document.getElementById('summaryContent').innerHTML = html;
            
            document.getElementById('confirmModalBox').style.display = 'flex';
        }

        function closeConfirmModal() {
            document.getElementById('confirmModalBox').style.display = 'none';
        }

        function submitTransfer() {
            document.getElementById('transferForm').submit();
        }
    </script>
</body>
</html>

<!-- 11/8/2569 05:07 -->