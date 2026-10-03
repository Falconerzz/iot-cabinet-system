<?php
require_once 'config.php'; check_login();

$user_action = isset($_SESSION['username']) ? $_SESSION['username'] : 'admin';
$success_msg = '';
$error_msg = '';

// ==========================================
// 🌟 1. ระบบจัดการเมื่อกดปุ่ม "ยืนยันการโยกย้าย" (รองรับสลับ และ โยกย้ายปกติ)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $eq_id = intval($_POST['equipment_id']);
    $from_pid = intval($_POST['from_project_id']);
    $to_pid = intval($_POST['to_project_id']);

    if ($eq_id > 0 && $from_pid > 0 && $to_pid > 0 && $from_pid !== $to_pid) {
        
        // ดึงข้อมูลชื่อและ S/N
        $eq_info = $conn->query("SELECT equipment_name, serial_number FROM equipments WHERE id=$eq_id")->fetch_assoc();
        $from_info = $conn->query("SELECT cabinet_id, project_name FROM projects WHERE id=$from_pid")->fetch_assoc();
        $to_info = $conn->query("SELECT cabinet_id, project_name FROM projects WHERE id=$to_pid")->fetch_assoc();

        if ($eq_info && $from_info && $to_info) {
            $eq_name = $eq_info['equipment_name'];
            $sn1 = $eq_info['serial_number'] ? $eq_info['serial_number'] : '-';
            $eq_name_esc = $conn->real_escape_string($eq_name);
            
            // 🌟 ตรวจสอบว่าตู้ปลายทางมีอุปกรณ์ "ชื่อเดียวกัน" นี้อยู่แล้วหรือไม่?
            $check_target = $conn->query("SELECT id, serial_number FROM equipments WHERE project_id=$to_pid AND equipment_name='$eq_name_esc'");

            if ($check_target && $check_target->num_rows > 0) {
                // ==============================================
                // กรณีที่ 1: ตู้ปลายทางมีของชิ้นนี้อยู่แล้ว -> สลับกัน (SWAP)
                // ==============================================
                $target_eq = $check_target->fetch_assoc();
                $target_eq_id = $target_eq['id'];
                $sn2 = $target_eq['serial_number'] ? $target_eq['serial_number'] : '-';

                // ย้ายของเดิม(ตู้ A) ไปตู้ใหม่(ตู้ B)
                $conn->query("UPDATE equipments SET project_id = $to_pid WHERE id = $eq_id");
                // ย้ายของใหม่(ตู้ B) กลับมาตู้เดิม(ตู้ A)
                $conn->query("UPDATE equipments SET project_id = $from_pid WHERE id = $target_eq_id");

                // บันทึก Log ตู้ต้นทาง
                $log_from = "[$user_action] สลับอุปกรณ์ {$eq_name} (S/N: $sn1) ไปยังตู้ {$to_info['cabinet_id']} และได้รับ (S/N: $sn2) เข้ามาแทนที่";
                $conn->query("INSERT INTO activity_logs (project_id, action_type, description) VALUES ($from_pid, 'TRANSFER_EQUIPMENT', '$log_from')");

                // บันทึก Log ตู้ปลายทาง
                $log_to = "[$user_action] สลับอุปกรณ์ {$eq_name} (S/N: $sn2) กลับไปยังตู้ {$from_info['cabinet_id']} และได้รับ (S/N: $sn1) เข้ามาแทนที่";
                $conn->query("INSERT INTO activity_logs (project_id, action_type, description) VALUES ($to_pid, 'TRANSFER_EQUIPMENT', '$log_to')");

                $success_msg = "สลับอุปกรณ์ {$eq_name} ระหว่างตู้สำเร็จเรียบร้อยแล้ว!";

            } else {
                // ==============================================
                // กรณีที่ 2: ตู้ปลายทางยังไม่มีของชิ้นนี้ -> โยกย้ายปกติ (MOVE)
                // ==============================================
                $conn->query("UPDATE equipments SET project_id = $to_pid WHERE id = $eq_id");

                // บันทึก Log ตู้ต้นทาง (เอาออก)
                $log_from = "[$user_action] ถอดอุปกรณ์ {$eq_name} (S/N: $sn1) ออกไปยังตู้ {$to_info['cabinet_id']}";
                $conn->query("INSERT INTO activity_logs (project_id, action_type, description) VALUES ($from_pid, 'TRANSFER_EQUIPMENT', '$log_from')");
                
                // บันทึก Log ตู้ปลายทาง (ใส่เข้า)
                $log_to = "[$user_action] นำอุปกรณ์ {$eq_name} (S/N: $sn1) เข้าสู่ตู้ {$to_info['cabinet_id']} (ย้ายมาจากตู้ {$from_info['cabinet_id']})";
                $conn->query("INSERT INTO activity_logs (project_id, action_type, description) VALUES ($to_pid, 'TRANSFER_EQUIPMENT', '$log_to')");

                $success_msg = "โยกย้ายอุปกรณ์ {$eq_name} สำเร็จเรียบร้อยแล้ว!";
            }

        } else {
            $error_msg = "เกิดข้อผิดพลาดในการดึงข้อมูล!";
        }
    } else {
        $error_msg = "กรุณาเลือกตู้ต้นทาง, อุปกรณ์ และตู้ปลายทางให้ถูกต้อง (ห้ามซ้ำกัน)";
    }
}

// ==========================================
// 🌟 2. ดึงข้อมูลเตรียมไว้แสดงใน Dropdown
// ==========================================
$projects = $conn->query("SELECT id, project_name, cabinet_id FROM projects ORDER BY id DESC")->fetch_all(MYSQLI_ASSOC);
$equipments = $conn->query("SELECT id, project_id, equipment_name, serial_number FROM equipments ORDER BY equipment_name ASC")->fetch_all(MYSQLI_ASSOC);

$eq_json = json_encode($equipments, JSON_UNESCAPED_UNICODE);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>โยกย้ายอุปกรณ์ - Sync Vibe</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <style>
        body { background-color: #f8fafc; color: #1e293b; }
        
        .transfer-wrapper { position: relative; padding: 2rem 0; }
        .process-line { 
            position: absolute; top: 50%; left: 15%; right: 15%; height: 3px; 
            background-image: linear-gradient(to right, #cbd5e1 50%, transparent 50%); 
            background-size: 15px 3px; background-repeat: repeat-x; z-index: 0; transform: translateY(-50%); opacity: 0.5;
        }

        .step-card { 
            background: #ffffff; border-radius: 1.5rem; padding: 2.5rem 2rem; text-align: center;
            position: relative; z-index: 1; border: 1px solid #f1f5f9; box-shadow: 0 10px 30px rgba(0,0,0,0.03); 
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); height: 100%; display: flex; flex-direction: column;
        }
        .step-card:hover { transform: translateY(-5px); box-shadow: 0 20px 40px rgba(0,0,0,0.06); }
        
        .icon-wrapper { 
            width: 72px; height: 72px; border-radius: 1.25rem; display: flex; justify-content: center; align-items: center; 
            font-size: 2rem; margin: 0 auto 1.5rem auto; box-shadow: 0 8px 16px rgba(0,0,0,0.06); transition: transform 0.3s ease;
        }
        .step-card:hover .icon-wrapper { transform: scale(1.05) rotate(-3deg); }

        .icon-source { background: linear-gradient(135deg, #e9d5ff 0%, #c084fc 100%); color: #4c1d95; }
        .icon-equip { background: linear-gradient(135deg, #bfdbfe 0%, #93c5fd 100%); color: #1e3a8a; }
        .icon-target { background: linear-gradient(135deg, #bbf7d0 0%, #86efac 100%); color: #14532d; }

        .bg-purple { background-color: #a855f7 !important; }
        .text-purple { color: #7e22ce !important; }
        .border-purple { border-color: #d8b4fe !important; }

        .step-title { font-size: 1.1rem; font-weight: 700; color: #334155; margin-bottom: 0.5rem; }
        .step-desc { font-size: 0.85rem; color: #64748b; margin-bottom: 1.5rem; }
        .step-badge { font-size: 0.75rem; font-weight: 800; letter-spacing: 1px; padding: 0.4rem 0.8rem; border-radius: 20px; position: absolute; top: -12px; left: 50%; transform: translateX(-50%); box-shadow: 0 4px 6px rgba(0,0,0,0.05); }

        .form-select-modern { 
            background-color: #f8fafc; border: 2px solid #e2e8f0; border-radius: 1rem; padding: 0.8rem 1rem;
            font-size: 0.95rem; font-weight: 600; color: #1e293b; cursor: pointer; transition: all 0.2s ease;
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.02); text-align: left; margin-top: auto; 
        }
        .form-select-modern:focus { border-color: #3b82f6; background-color: #ffffff; box-shadow: 0 0 0 4px rgba(59,130,246,0.1); outline: none; }
        .form-select-modern:disabled { background-color: #f1f5f9; color: #94a3b8; cursor: not-allowed; border-color: #e2e8f0; }

        .btn-submit-modern { 
            background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; border: none; border-radius: 1rem; 
            font-weight: 700; padding: 1rem 2rem; font-size: 1.1rem; box-shadow: 0 10px 20px rgba(16,185,129,0.2); transition: all 0.3s; 
        }
        .btn-submit-modern:hover:not(:disabled) { transform: translateY(-3px); box-shadow: 0 15px 25px rgba(16,185,129,0.3); }
        .btn-submit-modern:disabled { background: #cbd5e1; box-shadow: none; transform: none; color: #64748b; }

        .arrow-mobile { font-size: 2rem; color: #cbd5e1; text-align: center; margin: -10px 0; z-index: 0; }

        .custom-modal-backdrop { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); z-index: 1050; display: flex; align-items: center; justify-content: center; }
        .custom-modal-dialog { background: #ffffff; border-radius: 1.5rem; width: 90%; max-width: 600px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); overflow: hidden; }
        .summary-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 1rem; padding: 1.25rem; margin-bottom: 1rem; }
    </style>
</head>
<body>
    <div class="app-container">
        <?php include 'sidebar.php'; ?>
        <main class="main-content">
            
            <div class="mb-4 pb-3 border-bottom border-light animate-fade-up">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-info bg-opacity-10 text-info rounded-4 d-flex justify-content-center align-items-center shadow-sm" style="width: 55px; height: 55px; font-size: 1.8rem;">
                        <i class="bi bi-arrow-left-right"></i>
                    </div>
                    <div>
                        <h2 class="fw-bold text-dark m-0">ระบบโยกย้ายและสลับอุปกรณ์</h2>
                        <p class="text-secondary m-0 mt-1">ย้ายอุปกรณ์ข้ามตู้โครงการ หากตู้ปลายทางมีของอยู่แล้วระบบจะทำการ <b>สลับกัน (Swap)</b> อัตโนมัติ</p>
                    </div>
                </div>
            </div>

            <?php if($success_msg): ?>
                <div class="alert alert-success d-flex align-items-center border-0 shadow-sm rounded-4 fw-bold p-3 mb-4 animate-fade-up" role="alert">
                    <div class="bg-white text-success rounded-circle d-flex justify-content-center align-items-center me-3" style="width: 32px; height: 32px;"><i class="bi bi-check-lg"></i></div>
                    <div><?= $success_msg ?></div>
                    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if($error_msg): ?>
                <div class="alert alert-danger d-flex align-items-center border-0 shadow-sm rounded-4 fw-bold p-3 mb-4 animate-fade-up" role="alert">
                    <div class="bg-white text-danger rounded-circle d-flex justify-content-center align-items-center me-3" style="width: 32px; height: 32px;"><i class="bi bi-exclamation-triangle-fill"></i></div>
                    <div><?= $error_msg ?></div>
                    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <form action="" method="POST" id="transferForm">
                <div class="transfer-wrapper animate-fade-up" style="animation-delay: 0.1s;">
                    <div class="d-none d-lg-block process-line"></div>
                    <div class="row g-4 position-relative">
                        
                        <!-- 🟣 STEP 1: ตู้ต้นทาง -->
                        <div class="col-lg-4">
                            <div class="step-card" style="border-color: #f3e8ff;">
                                <span class="badge bg-purple text-white step-badge">STEP 1</span>
                                <div class="icon-wrapper icon-source"><i class="bi bi-box-arrow-up"></i></div>
                                <div class="step-title">1. ตู้ต้นทาง</div>
                                <div class="step-desc">เลือกตู้ที่คุณต้องการนำอุปกรณ์ออก</div>
                                
                                <select class="form-select form-select-modern border-purple" name="from_project_id" id="from_project_id" required>
                                    <option value="" selected disabled>-- เลือกตู้ต้นทาง --</option>
                                    <?php foreach($projects as$p): ?>
                                        <option value="<?= $p['id'] ?>">ID: <?= htmlspecialchars($p['cabinet_id']) ?> (<?= htmlspecialchars($p['project_name']) ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="d-block d-lg-none arrow-mobile"><i class="bi bi-arrow-down"></i></div>

                        <!-- 🔵 STEP 2: เลือกอุปกรณ์ -->
                        <div class="col-lg-4">
                            <div class="step-card" style="border-color: #bfdbfe;">
                                <span class="badge bg-primary text-white step-badge">STEP 2</span>
                                <div class="icon-wrapper icon-equip"><i class="bi bi-hdd-network"></i></div>
                                <div class="step-title">2. อุปกรณ์</div>
                                <div class="step-desc">ชิ้นส่วนอุปกรณ์ที่ต้องการดำเนินการ</div>
                                
                                <select class="form-select form-select-modern border-primary border-opacity-25" name="equipment_id" id="equipment_id" required disabled>
                                    <option value="" selected disabled>-- กรุณาเลือกตู้ต้นทางก่อน --</option>
                                </select>
                            </div>
                        </div>

                        <div class="d-block d-lg-none arrow-mobile"><i class="bi bi-arrow-down"></i></div>

                        <!-- 🟢 STEP 3: ตู้ปลายทาง -->
                        <div class="col-lg-4">
                            <div class="step-card" style="border-color: #bbf7d0;">
                                <span class="badge bg-success text-white step-badge">STEP 3</span>
                                <div class="icon-wrapper icon-target"><i class="bi bi-box-arrow-in-down"></i></div>
                                <div class="step-title">3. ตู้ปลายทาง</div>
                                <div class="step-desc">เลือกตู้ที่ต้องการนำอุปกรณ์ชิ้นนี้ไปใส่</div>
                                
                                <select class="form-select form-select-modern border-success border-opacity-25" name="to_project_id" id="to_project_id" required disabled>
                                    <option value="" selected disabled>-- เลือกตู้ปลายทาง --</option>
                                    <?php foreach($projects as$p): ?>
                                        <option value="<?= $p['id'] ?>">ID: <?= htmlspecialchars($p['cabinet_id']) ?> (<?= htmlspecialchars($p['project_name']) ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- ปุ่มตรวจสอบความถูกต้อง -->
                <div class="text-center mt-5 mb-3 animate-fade-up" style="animation-delay: 0.2s;">
                    <button type="button" class="btn btn-submit-modern px-5" id="previewBtn" disabled>
                        <i class="bi bi-search me-2"></i> ตรวจสอบและยืนยัน
                    </button>
                    <p class="text-secondary small mt-3"><i class="bi bi-info-circle me-1"></i> ระบบจะวิเคราะห์และสลับอุปกรณ์ให้โดยอัตโนมัติหากตู้ปลายทางมีของชิ้นนั้นอยู่แล้ว</p>
                </div>
            </form>

        </main>
    </div>

    <!-- 🌟 Modal สรุปผลการโยกย้าย (Confirmation) -->
    <div id="confirmModal" class="custom-modal-backdrop" style="display: none;">
        <div class="custom-modal-dialog animate-fade-up">
            <div class="bg-light p-4 border-bottom d-flex justify-content-between align-items-center">
                <h5 class="fw-bold text-dark m-0"><i class="bi bi-clipboard-data text-success me-2"></i>สรุปรายการดำเนินการ</h5>
                <button type="button" class="btn-close" onclick="closeConfirmModal()"></button>
            </div>
            
            <div class="p-4 bg-white">
                <div class="summary-box">
                    <div class="row align-items-center text-center">
                        <div class="col-5">
                            <div class="text-secondary small fw-bold text-uppercase mb-1">ตู้ต้นทาง</div>
                            <div class="text-purple fw-bold" id="summaryFrom"></div>
                        </div>
                        <div class="col-2 text-secondary fs-4" id="actionIcon"><i class="bi bi-arrow-right"></i></div>
                        <div class="col-5">
                            <div class="text-secondary small fw-bold text-uppercase mb-1">ตู้ปลายทาง</div>
                            <div class="text-success fw-bold" id="summaryTo"></div>
                        </div>
                    </div>
                </div>

                <div class="summary-box border-primary bg-primary bg-opacity-10 d-flex align-items-center gap-3">
                    <div class="bg-white text-primary rounded-circle d-flex justify-content-center align-items-center shadow-sm" style="width: 45px; height: 45px; font-size: 1.5rem;"><i class="bi bi-cpu"></i></div>
                    <div>
                        <div class="text-secondary small fw-bold text-uppercase">อุปกรณ์ที่กำลังดำเนินการ</div>
                        <div class="fw-bold text-dark fs-6" id="summaryEq"></div>
                    </div>
                </div>

                <!-- ส่วนตรวจสอบอุปกรณ์ปลายทางอัตโนมัติ -->
                <div id="destCheckStatus">
                    <!-- ข้อมูลจะถูกแทรกที่นี่โดย JS -->
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                    <button type="button" class="btn btn-light rounded-pill px-4 fw-bold text-secondary" onclick="closeConfirmModal()">ยกเลิก</button>
                    <button type="button" class="btn btn-success rounded-pill px-4 fw-bold shadow-sm" onclick="submitTransfer()">
                        <i class="bi bi-check-circle me-1"></i> ยืนยันข้อมูลถูกต้อง
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const allEquipments = <?= $eq_json ?>;

        $(document).ready(function() {
            const $fromProject =$('#from_project_id');
            const $equipment =$('#equipment_id');
            const $toProject =$('#to_project_id');
            const $previewBtn =$('#previewBtn');

            // 1. เมื่อเลือกตู้ต้นทาง
            $fromProject.on('change', function() {
                const selectedProjectId = $(this).val();$equipment.empty().append('<option value="" selected disabled>-- เลือกอุปกรณ์ --</option>');
                
                let hasEquip = false;
                allEquipments.forEach(function(eq) {
                    if (eq.project_id == selectedProjectId) {
                        const snText = eq.serial_number ? ` (S/N: ${eq.serial_number})` : '';
                        $equipment.append(`<option value="${eq.id}" data-name="${eq.equipment_name}">${eq.equipment_name}${snText}</option>`);
                        hasEquip = true;
                    }
                });

                if(hasEquip) {
                    $equipment.prop('disabled', false);$equipment.find('option:first').text('-- เลือกชิ้นส่วนที่ต้องการ --'); 
                } else {
                    $equipment.empty().append('<option value="" selected disabled>ตู้นี้ไม่มีอุปกรณ์ให้ย้าย</option>');
                    $equipment.prop('disabled', true);
                }

                $toProject.prop('disabled', false);
                $toProject.find('option').show();$toProject.find(`option[value="${selectedProjectId}"]`).hide(); 
                
                if($toProject.val() == selectedProjectId) {$toProject.val('');
                }
                
                checkSubmitStatus();
            });

            // 2. ตรวจสอบปุ่มยืนยัน
            $equipment.on('change', checkSubmitStatus);$toProject.on('change', checkSubmitStatus);

            function checkSubmitStatus() {
                if($fromProject.val() &&$equipment.val() && $toProject.val()) {$previewBtn.prop('disabled', false);
                } else {
                    $previewBtn.prop('disabled', true);
                }
            }

            // 3. เมื่อกดปุ่มตรวจสอบและยืนยัน
            $previewBtn.on('click', function(e) {
                e.preventDefault();
                
                let fromText = $('#from_project_id option:selected').text();
                let eqText = $('#equipment_id option:selected').text();
                let eqName = $('#equipment_id option:selected').data('name'); 
                let toText = $('#to_project_id option:selected').text();
                let toId = $('#to_project_id').val();
                
                $('#summaryFrom').text(fromText.split(' (')[0]); 
                $('#summaryEq').text(eqText);
                $('#summaryTo').text(toText.split(' (')[0]);

                // 🌟 ระบบวิเคราะห์ (Move or Swap)
                let destEquips = allEquipments.filter(eq => eq.project_id == toId);
                let duplicateEq = destEquips.find(eq => eq.equipment_name === eqName);
                
                let statusHtml = '<h6 class="fw-bold text-dark mt-4 mb-2">สรุปการดำเนินการอัตโนมัติ:</h6>';
                
                if(duplicateEq) {
                    // กรณี "สลับ" อุปกรณ์ (SWAP)
                    let snText = duplicateEq.serial_number ? duplicateEq.serial_number : 'ไม่มี S/N';
                    $('#actionIcon').html('<i class="bi bi-arrow-left-right text-warning"></i>');
                    
                    statusHtml += `<div class="alert alert-warning border border-warning shadow-sm mb-0">
                        <i class="bi bi-arrow-left-right me-1 fs-5 text-warning align-middle"></i> 
                        <b>ระบบตรวจพบอุปกรณ์ชนิดเดียวกันในตู้ปลายทาง</b><br>
                        <div class="mt-2 text-dark">
                            ตู้ปลายทางมี <b>${eqName}</b> อยู่แล้ว (S/N: ${snText}) <br>
                            👉 <u>ระบบจะทำการ "สลับ (Swap)" อุปกรณ์ทั้ง 2 ชิ้นนี้เข้าหากันแทนการย้ายปกติ</u>
                        </div>
                    </div>`;
                } else {
                    // กรณี "ย้าย" ปกติ (MOVE)
                    $('#actionIcon').html('<i class="bi bi-arrow-right text-success"></i>');
                    
                    statusHtml += `<div class="alert alert-success border border-success shadow-sm mb-0">
                        <i class="bi bi-arrow-right-circle-fill me-1 fs-5 text-success align-middle"></i> 
                        <b>ระบบจะทำการ "โยกย้าย" ปกติ</b><br>
                        <div class="mt-1 text-dark">
                            ตู้ปลายทางยังไม่มี ${eqName} ระบบจะย้ายอุปกรณ์ชิ้นนี้เข้าไปติดตั้งใหม่ และตู้ต้นทางจะไม่มีอุปกรณ์ชิ้นนี้
                        </div>
                    </div>`;
                }

                $('#destCheckStatus').html(statusHtml);
                $('#confirmModal').fadeIn(200).css('display', 'flex');
            });
        });

        function closeConfirmModal() {
            $('#confirmModal').fadeOut(200);
        }

        function submitTransfer() {
            $('#confirmModal').fadeOut(100, function() {
                $('#transferForm').submit();
            });
        }
    </script>
</body>
</html>