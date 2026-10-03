<?php
require_once 'config.php'; check_login();
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$stmt = $conn->prepare("SELECT * FROM projects WHERE id = ?"); $stmt->bind_param("i", $id); $stmt->execute();
$project = $stmt->get_result()->fetch_assoc();
if (!$project) { die("ไม่พบข้อมูลโครงการ"); }

$eq_stmt = $conn->prepare("SELECT * FROM equipments WHERE project_id = ?"); $eq_stmt->bind_param("i", $id); $eq_stmt->execute();
$equipments = $eq_stmt->get_result();
$control_items = []; $dc_items = [];
while ($item = $equipments->fetch_assoc()) { if ($item['cabinet_type'] === 'control_iot') { $control_items[] = $item; } else { $dc_items[] = $item; } }

$control_tpls = $conn->query("SELECT * FROM equipment_templates WHERE cabinet_type = 'control_iot' ORDER BY id DESC");
$dc_tpls = $conn->query("SELECT * FROM equipment_templates WHERE cabinet_type = 'dc_iot' ORDER BY id DESC");
$tpl_items_map = []; $items_query = $conn->query("SELECT * FROM template_items");
while ($row = $items_query->fetch_assoc()) { $tpl_items_map[$row['template_id']][] = ['name' => $row['equipment_name'], 'req_config' => $row['requires_config']]; }
$existing_tags = $project['project_tags'] ? array_map('trim', explode(',', $project['project_tags'])) : [];
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แก้ไขโครงการ - <?= htmlspecialchars($project['project_name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/bootstrap.bundle.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://unpkg.com/html5-qrcode"></script>
    <link rel="stylesheet" href="style.css">
    <style>
        body { background-color: #f8fafc; color: #334155; }
        .card-clean { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); margin-bottom: 1.5rem; }
        .card-header-clean { background: transparent; border-bottom: 1px solid #f1f5f9; padding: 1.25rem 1.5rem; font-weight: 700; color: #0f172a; display: flex; justify-content: space-between; align-items: center;}
        .form-label { font-size: 0.85rem; font-weight: 600; color: #64748b; margin-bottom: 0.4rem; }
        .form-control, .form-select { border-radius: 8px; border: 1px solid #cbd5e1; padding: 0.6rem 1rem; font-size: 0.95rem; transition: all 0.3s; }
        .form-control:focus, .form-select:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
        .pulse-marker { width: 20px; height: 20px; border-radius: 50%; background-color: #f59e0b; border: 3px solid #fff; box-shadow: 0 0 0 rgba(245, 158, 11, 0.4); animation: pulse-warn 2s infinite; }
        @keyframes pulse-warn { 0%{box-shadow:0 0 0 0 rgba(245,158,11,0.7);}70%{box-shadow:0 0 0 10px rgba(245,158,11,0);}100%{box-shadow:0 0 0 0 rgba(245,158,11,0);} }
        .table-clean th { background: #f8fafc; color: #64748b; font-size: 0.85rem; font-weight: 600; border-bottom: 1px solid #e2e8f0; }
        .table-clean td { vertical-align: middle; border-bottom: 1px solid #f1f5f9; }
        .row-err { border-left: 4px solid #ef4444; background: #fef2f2; }
        .img-preview { object-fit: cover; border-radius: 6px; cursor: zoom-in; border: 1px solid #e2e8f0; transition: all 0.2s ease;}
        .img-preview:hover { transform: scale(1.1); box-shadow: 0 4px 10px rgba(0,0,0,0.1); border-color: #3b82f6;}
    </style>
</head>
<body>
    <div class="app-container">
        <?php include 'sidebar.php'; ?>
        <main class="main-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3 class="fw-bold text-dark m-0">แก้ไขข้อมูล: <?= htmlspecialchars($project['project_name']) ?></h3>
                    <p class="text-secondary small mt-1">อัปเดตข้อมูลหรือแก้ไข Serial Number อุปกรณ์ในตู้</p>
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <form action="update_project.php" method="POST" onsubmit="return confirm('⚠️ ยืนยันการลบโครงการนี้?\n\nข้อมูลตู้ อุปกรณ์ และประวัติทั้งหมดในโครงการนี้จะถูกลบอย่างถาวรและไม่สามารถกู้คืนได้!');" class="m-0">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="project_id" value="<?= $id ?>">
                        <button type="submit" class="btn btn-outline-danger border-0 bg-danger bg-opacity-10 rounded-pill px-4 fw-bold"><i class="bi bi-trash3-fill me-2"></i>ลบโครงการ</button>
                    </form>
                    <a href="view_project.php?id=<?= $id ?>" class="btn btn-light border px-4 rounded-pill fw-bold"><i class="bi bi-arrow-left me-1"></i> กลับ</a>
                </div>
            </div>

            <form action="update_project.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="project_id" value="<?= $id ?>">

                <div class="card-clean">
                    <div class="card-header-clean"><div class="d-flex align-items-center"><i class="bi bi-info-circle me-2 text-primary"></i> ข้อมูลโครงการ</div></div>
                    <div class="p-4">
                        <div class="row g-4">
                            <div class="col-md-6"><label class="form-label">ชื่อโครงการ *</label><input type="text" name="project_name" value="<?= htmlspecialchars($project['project_name']) ?>" class="form-control" required></div>
                            <div class="col-md-6"><label class="form-label">รหัสตู้ (Cabinet ID) *</label><input type="text" name="cabinet_id" value="<?= htmlspecialchars($project['cabinet_id']) ?>" class="form-control" required></div>
                            <div class="col-md-6"><label class="form-label">แท็ก (Tags)</label><select name="project_tags[]" class="form-select select2-tags" multiple="multiple"><?php foreach($existing_tags as $tag): ?><option value="<?= htmlspecialchars($tag) ?>" selected><?= htmlspecialchars($tag) ?></option><?php endforeach; ?></select></div>
                            <div class="col-md-6"><label class="form-label">ภูมิภาค</label><select name="region" class="form-select"><option value="ภาคเหนือ" <?= $project['region']=='ภาคเหนือ'?'selected':'' ?>>ภาคเหนือ</option><option value="ภาคอีสาน" <?= $project['region']=='ภาคอีสาน'?'selected':'' ?>>ภาคตะวันออกเฉียงเหนือ</option><option value="ภาคกลาง" <?= $project['region']=='ภาคกลาง'?'selected':'' ?>>ภาคกลาง</option><option value="ภาคใต้" <?= $project['region']=='ภาคใต้'?'selected':'' ?>>ภาคใต้</option></select></div>
                            <div class="col-12"><label class="form-label">หมายเหตุ / โน๊ตเพิ่มเติม (ไม่บังคับ)</label><textarea name="project_notes" class="form-control" rows="2"><?= htmlspecialchars($project['project_notes'] ?? '') ?></textarea></div>
                        </div>
                    </div>
                </div>

                <div class="card-clean">
                    <div class="card-header-clean"><div class="d-flex align-items-center"><i class="bi bi-geo-alt me-2 text-danger"></i> สถานที่ตั้งและพิกัด GPS</div></div>
                    <div class="p-4">
                        <div class="row g-4">
                            <div class="col-lg-7">
                                <label class="form-label">ปักหมุดบนแผนที่</label>
                                <div id="pickerMap" style="height: 380px; border-radius: 8px; border: 1px solid #cbd5e1; z-index: 1;"></div>
                            </div>
                            <div class="col-lg-5">
                                <div class="row g-3">
                                    <div class="col-12"><label class="form-label">Latitude (ละติจูด)</label><input type="text" id="latInput" name="latitude" value="<?= htmlspecialchars($project['latitude'] ?? '') ?>" class="form-control fw-bold text-primary"></div>
                                    <div class="col-12"><label class="form-label">Longitude (ลองจิจูด)</label><input type="text" id="lngInput" name="longitude" value="<?= htmlspecialchars($project['longitude'] ?? '') ?>" class="form-control fw-bold text-primary"></div>
                                    <div class="col-12"><hr class="my-2 border-light"></div>
                                    <div class="col-12"><label class="form-label">ที่อยู่ติดตั้งโดยละเอียด *</label><input type="text" name="address" value="<?= htmlspecialchars($project['address'] ?? '') ?>" class="form-control" required></div>
                                    <div class="col-12"><label class="form-label">จังหวัด *</label><select name="province" id="province" class="form-select select2-searchable" data-val="<?= htmlspecialchars($project['province'] ?? '') ?>" required><option value="">กำลังโหลด...</option></select></div>
                                    <div class="col-md-6"><label class="form-label">อำเภอ *</label><select name="amphure" id="amphoe" class="form-select select2-searchable" data-val="<?= htmlspecialchars($project['amphure'] ?? '') ?>" required disabled><option value="">เลือกอำเภอ</option></select></div>
                                    <div class="col-md-6"><label class="form-label">ตำบล *</label><select name="tambon" id="district" class="form-select select2-searchable" data-val="<?= htmlspecialchars($project['tambon'] ?? '') ?>" required disabled><option value="">เลือกตำบล</option></select></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-4 mb-4">
                    <div class="col-lg-6">
                        <div class="card-clean h-100 mb-0">
                            <div class="card-header-clean"><div class="d-flex align-items-center"><i class="bi bi-gear me-2 text-secondary"></i> การตั้งค่าและลงโปรแกรม</div></div>
                            <div class="p-4">
                                <div class="form-check form-switch mb-4">
                                    <input class="form-check-input" type="checkbox" id="isProgrammedToggle" name="is_programmed" value="1" <?= $project['is_programmed'] ? 'checked' : '' ?>>
                                    <label class="form-check-label fw-bold text-dark ms-2" for="isProgrammedToggle">ลงโปรแกรมระบบเรียบร้อยแล้ว</label>
                                </div>
                                <div id="programDetailsBox" style="display:<?= $project['is_programmed'] ? 'block' : 'none' ?>;">
                                    <div class="row g-3">
                                        <div class="col-md-4"><label class="form-label">ผู้ลงโปรแกรม</label><input type="text" name="programmer_name" value="<?= htmlspecialchars($project['programmer_name'] ?? '') ?>" class="form-control"></div>
                                        <div class="col-md-4"><label class="form-label">Gateway Name</label><input type="text" name="gateway_name" value="<?= htmlspecialchars($project['gateway_name'] ?? '') ?>" class="form-control"></div>
                                        <div class="col-md-4"><label class="form-label">Gateway Code</label><input type="text" name="gateway_code" value="<?= htmlspecialchars($project['gateway_code'] ?? '') ?>" class="form-control"></div>
                                    </div>
                                </div>
                                <hr class="my-4 border-light">
                                <div class="row g-3">
                                    <div class="col-sm-6"><label class="form-label">วันที่ติดตั้ง</label><input type="date" name="installation_date" value="<?= htmlspecialchars($project['installation_date'] ?? '') ?>" class="form-control"></div>
                                    <div class="col-sm-6"><label class="form-label">สถานะกล้องวงจรปิด</label><select name="camera_setup_status" class="form-select"><option value="pending" <?= $project['camera_setup_status']=='pending'?'selected':'' ?>>ยังไม่ตั้งค่า</option><option value="completed" <?= $project['camera_setup_status']=='completed'?'selected':'' ?>>ตั้งค่าเรียบร้อย</option></select></div>
                                    <div class="col-12">
                                        <div class="form-check form-switch mt-2">
                                            <input class="form-check-input" type="checkbox" id="isDeliveredToggle" name="is_delivered" value="1" <?= $project['is_delivered'] ? 'checked' : '' ?>>
                                            <label class="form-check-label fw-bold text-success ms-2" for="isDeliveredToggle">ตู้พร้อมใช้งาน ส่งงานแล้ว</label>
                                        </div>
                                        <div id="deliveryDetailsBox" class="mt-3" style="display:<?= $project['is_delivered'] ? 'block' : 'none' ?>;">
                                            <label class="form-label">รูปถ่ายการส่งงาน (อัปโหลดเพื่อเปลี่ยนใหม่)</label>
                                            <?php if ($project['delivery_photo_path']): ?>
                                                <div class="d-flex align-items-center gap-2 mb-2">
                                                    <img src="<?= $project['delivery_photo_path'] ?>" class="img-preview" style="width: 40px; height: 40px;" onclick="zoomImage('<?= $project['delivery_photo_path'] ?>', 'รูปส่งงาน')">
                                                    <span class="small text-success fw-bold"><i class="bi bi-check-circle-fill"></i> มีรูปแล้ว</span>
                                                </div>
                                            <?php endif; ?>
                                            <input type="file" name="delivery_photo" class="form-control" accept="image/*">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="card-clean h-100 mb-0">
                            <div class="card-header-clean"><div class="d-flex align-items-center"><i class="bi bi-hdd me-2 text-secondary"></i> อุปกรณ์เสริม</div></div>
                            <div class="p-4">
                                <div class="mb-3"><label class="form-label">เสาอากาศ</label><select name="has_antenna" class="form-select"><option value="0" <?= $project['has_antenna']==0?'selected':'' ?>>ยังไม่ได้ติดตั้ง</option><option value="1" <?= $project['has_antenna']==1?'selected':'' ?>>ติดตั้งเรียบร้อยแล้ว</option></select></div>
                                <div class="mb-3"><label class="form-label">สถานะซิมการ์ด</label><select name="sim_status" class="form-select" onchange="document.getElementById('simBox').style.display=(this.value==='installed')?'block':'none'"><option value="not_installed" <?= $project['sim_status']=='not_installed'?'selected':'' ?>>ยังไม่มีซิม</option><option value="installed" <?= $project['sim_status']=='installed'?'selected':'' ?>>ใส่ซิมเรียบร้อยแล้ว</option></select></div>
                                <div id="simBox" class="bg-light p-3 rounded-3 mb-3 border border-light" style="display:<?= $project['sim_status']=='installed'?'block':'none' ?>;">
                                    <div class="row g-2">
                                        <div class="col-sm-6"><label class="form-label">เบอร์โทรศัพท์</label><input type="text" name="sim_number" value="<?= htmlspecialchars($project['sim_number'] ?? '') ?>" class="form-control"></div>
                                        <div class="col-sm-6"><label class="form-label">Serial Number</label><input type="text" name="sim_serial" value="<?= htmlspecialchars($project['sim_serial'] ?? '') ?>" class="form-control"></div>
                                        <div class="col-12 mt-2">
                                            <label class="form-label">รูปซองซิม (อัปโหลดเพื่อเปลี่ยนใหม่)</label>
                                            <?php if ($project['sim_photo_path']): ?>
                                                <div class="d-flex align-items-center gap-2 mb-2">
                                                    <img src="<?= $project['sim_photo_path'] ?>" class="img-preview" style="width: 40px; height: 40px;" onclick="zoomImage('<?= $project['sim_photo_path'] ?>', 'รูปซองซิม')">
                                                    <span class="small text-success fw-bold"><i class="bi bi-check-circle-fill"></i> มีรูปแล้ว</span>
                                                </div>
                                            <?php endif; ?>
                                            <input type="file" name="sim_photo" class="form-control" accept="image/*">
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-2"><label class="form-label">สถานะ SD Card</label><select name="sd_status" class="form-select" onchange="document.getElementById('sdBox').style.display=(this.value==='installed')?'block':'none'"><option value="not_installed" <?= $project['sd_status']=='not_installed'?'selected':'' ?>>ยังไม่มี SD Card</option><option value="installed" <?= $project['sd_status']=='installed'?'selected':'' ?>>ใส่เรียบร้อยแล้ว</option></select></div>
                                <div id="sdBox" style="display:<?= $project['sd_status']=='installed'?'block':'none' ?>;"><input type="text" name="sd_capacity_gb" value="<?= htmlspecialchars($project['sd_capacity_gb'] ?? '') ?>" class="form-control mt-2" placeholder="ความจุ (เช่น 64GB)"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-clean">
                    <div class="card-header-clean d-flex justify-content-between">
                        <div><i class="bi bi-list-ul me-2"></i> รายการอุปกรณ์ตู้ Control IoT</div>
                        <select onchange="loadTemplateItems('control_eq_list', 'control', this.value)" class="form-select form-select-sm" style="width:200px; font-weight:normal;"><option value="">-- โหลดทับจากแม่แบบ --</option><?php while($tpl = $control_tpls->fetch_assoc()): ?><option value="<?= $tpl['id'] ?>"><?= htmlspecialchars($tpl['template_name']) ?></option><?php endwhile; ?></select>
                    </div>
                    <div class="p-0 table-responsive border-bottom-0">
                        <table class="table table-clean m-0">
                            <thead><tr><th class="ps-4">ชื่ออุปกรณ์</th><th width="30%">Serial Number</th><th width="20%">รูปถ่าย (สแกน S/N)</th><th width="15%">การตั้งค่า</th><th width="5%">ลบ</th></tr></thead>
                            <tbody id="control_eq_list">
                                <?php foreach ($control_items as $eq): $row_id = 'c_'.$eq['id']; $is_err = (empty(trim($eq['serial_number']))||trim($eq['serial_number'])==='-') || ($eq['requires_config']==1 && $eq['is_configured']==0); ?>
                                    <tr id="<?= $row_id ?>" class="<?= $is_err ? 'row-err' : '' ?>">
                                        <td class="ps-4"><input type="hidden" name="control_id[]" value="<?= $eq['id'] ?>"><input type="hidden" name="control_req_config[]" value="<?= $eq['requires_config'] ?>"><input type="text" class="form-control-plaintext fw-bold" name="control_name[]" value="<?= htmlspecialchars($eq['equipment_name']) ?>" readonly></td>
                                        <td><div class="input-group"><input type="text" id="sn_<?= $row_id ?>" class="form-control form-control-sm" name="control_serial[]" value="<?= htmlspecialchars($eq['serial_number']) ?>"><button type="button" class="btn btn-primary btn-sm rounded-end" onclick="openCamera('sn_<?= $row_id ?>')"><i class="bi bi-qr-code-scan"></i></button></div></td>
                                        <td>
                                            <?php if($eq['photo_path']): ?>
                                                <div class="d-flex align-items-center gap-2 mb-1">
                                                    <img src="<?= $eq['photo_path'] ?>" class="img-preview" style="width: 30px; height: 30px;" onclick="zoomImage('<?= $eq['photo_path'] ?>', '<?= htmlspecialchars($eq['equipment_name']) ?>')">
                                                    <span class="text-success small fw-bold"><i class="bi bi-check-circle-fill"></i> มีรูปแล้ว</span>
                                                </div>
                                            <?php endif; ?>
                                            <input type="file" class="form-control form-control-sm" name="control_photo[]" accept="image/*" onchange="scanImageFile(this, 'sn_<?= $row_id ?>')" title="อัปโหลดเพื่อเปลี่ยนรูปใหม่">
                                        </td>
                                        <td><?php if($eq['requires_config']==1): ?><select name="control_configured[]" class="form-select form-select-sm"><option value="0" <?= $eq['is_configured']==0?'selected':'' ?>>รอตั้งค่า</option><option value="1" <?= $eq['is_configured']==1?'selected':'' ?>>ตั้งค่าแล้ว</option></select><?php else: ?><span class="text-secondary small">ไม่ต้องตั้งค่า</span><input type="hidden" name="control_configured[]" value="0"><?php endif; ?></td>
                                        <td><button type="button" class="btn btn-sm text-danger" onclick="this.parentElement.parentElement.remove()"><i class="bi bi-trash"></i></button></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card-clean mb-5">
                    <div class="card-header-clean d-flex justify-content-between">
                        <div><i class="bi bi-list-ul me-2"></i> รายการอุปกรณ์ตู้ DC-IoT</div>
                        <select onchange="loadTemplateItems('dc_eq_list', 'dc', this.value)" class="form-select form-select-sm" style="width:200px; font-weight:normal;"><option value="">-- โหลดทับจากแม่แบบ --</option><?php while($tpl = $dc_tpls->fetch_assoc()): ?><option value="<?= $tpl['id'] ?>"><?= htmlspecialchars($tpl['template_name']) ?></option><?php endwhile; ?></select>
                    </div>
                    <div class="p-0 table-responsive border-bottom-0">
                        <table class="table table-clean m-0">
                            <thead><tr><th class="ps-4">ชื่ออุปกรณ์</th><th width="30%">Serial Number</th><th width="20%">รูปถ่าย (สแกน S/N)</th><th width="15%">การตั้งค่า</th><th width="5%">ลบ</th></tr></thead>
                            <tbody id="dc_eq_list">
                                <?php foreach ($dc_items as $eq): $row_id = 'd_'.$eq['id']; $is_err = (empty(trim($eq['serial_number']))||trim($eq['serial_number'])==='-') || ($eq['requires_config']==1 && $eq['is_configured']==0); ?>
                                    <tr id="<?= $row_id ?>" class="<?= $is_err ? 'row-err' : '' ?>">
                                        <td class="ps-4"><input type="hidden" name="dc_id[]" value="<?= $eq['id'] ?>"><input type="hidden" name="dc_req_config[]" value="<?= $eq['requires_config'] ?>"><input type="text" class="form-control-plaintext fw-bold" name="dc_name[]" value="<?= htmlspecialchars($eq['equipment_name']) ?>" readonly></td>
                                        <td><div class="input-group"><input type="text" id="sn_<?= $row_id ?>" class="form-control form-control-sm" name="dc_serial[]" value="<?= htmlspecialchars($eq['serial_number']) ?>"><button type="button" class="btn btn-primary btn-sm rounded-end" onclick="openCamera('sn_<?= $row_id ?>')"><i class="bi bi-qr-code-scan"></i></button></div></td>
                                        <td>
                                            <?php if($eq['photo_path']): ?>
                                                <div class="d-flex align-items-center gap-2 mb-1">
                                                    <img src="<?= $eq['photo_path'] ?>" class="img-preview" style="width: 30px; height: 30px;" onclick="zoomImage('<?= $eq['photo_path'] ?>', '<?= htmlspecialchars($eq['equipment_name']) ?>')">
                                                    <span class="text-success small fw-bold"><i class="bi bi-check-circle-fill"></i> มีรูปแล้ว</span>
                                                </div>
                                            <?php endif; ?>
                                            <input type="file" class="form-control form-control-sm" name="dc_photo[]" accept="image/*" onchange="scanImageFile(this, 'sn_<?= $row_id ?>')" title="อัปโหลดเพื่อเปลี่ยนรูปใหม่">
                                        </td>
                                        <td><?php if($eq['requires_config']==1): ?><select name="dc_configured[]" class="form-select form-select-sm"><option value="0" <?= $eq['is_configured']==0?'selected':'' ?>>รอตั้งค่า</option><option value="1" <?= $eq['is_configured']==1?'selected':'' ?>>ตั้งค่าแล้ว</option></select><?php else: ?><span class="text-secondary small">ไม่ต้องตั้งค่า</span><input type="hidden" name="dc_configured[]" value="0"><?php endif; ?></td>
                                        <td><button type="button" class="btn btn-sm text-danger" onclick="this.parentElement.parentElement.remove()"><i class="bi bi-trash"></i></button></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="text-end mb-5"><button type="submit" class="btn btn-warning text-white px-5 py-2 fw-bold"><i class="bi bi-cloud-arrow-up me-2"></i> อัปเดตข้อมูลตู้</button></div>
            </form>
        </main>
    </div>

    <!-- Modal Scanner -->
    <div id="scannerModal" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.6); z-index:2000; justify-content:center; align-items:center;">
        <div style="background:#fff; padding:2rem; border-radius:1rem; text-align:center;">
            <h5 class="fw-bold mb-3">สแกน Barcode S/N</h5>
            <div id="reader" style="width:300px; border-radius:10px; overflow:hidden;"></div>
            <button type="button" class="btn btn-danger mt-3 px-4 rounded-pill" onclick="closeScanner()">ปิดกล้อง</button>
        </div>
    </div>
    
    <!-- Modal รูปภาพ -->
    <div id="imageModal" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(15, 23, 42, 0.85); z-index:2000; flex-direction:column; justify-content:center; align-items:center; backdrop-filter: blur(5px);">
        <div style="position:absolute; top:20px; right:20px; display:flex; gap:10px; z-index:2010; background:rgba(255,255,255,0.95); padding:12px; border-radius:16px; box-shadow: 0 10px 30px rgba(0,0,0,0.3);">
            <button class="btn btn-light border rounded-3 fw-bold shadow-sm px-3" onclick="zoomImg(0.25)" title="ซูมเข้า"><i class="bi bi-zoom-in me-1"></i> ซูมเข้า</button>
            <button class="btn btn-light border rounded-3 fw-bold shadow-sm px-3" onclick="zoomImg(-0.25)" title="ซูมออก"><i class="bi bi-zoom-out me-1"></i> ซูมออก</button>
            <button class="btn btn-light border rounded-3 fw-bold shadow-sm px-3" onclick="resetZoom()" title="พอดีจอ"><i class="bi bi-aspect-ratio me-1"></i> ขนาดปกติ</button>
            <div class="vr mx-1"></div>
            <button class="btn btn-danger rounded-3 fw-bold shadow-sm px-3" onclick="closeImageModal(true)" title="ปิดหน้าต่าง"><i class="bi bi-x-lg me-1"></i> ปิด</button>
        </div>
        <h5 id="zoomTitle" class="text-white fw-bold m-0" style="position:absolute; top:30px; left:30px; z-index:2010; text-shadow: 2px 2px 6px rgba(0,0,0,0.8);"></h5>
        <div id="imgContainer" style="width:100%; height:100%; overflow:hidden; display:flex; justify-content:center; align-items:center;" onclick="if(event.target.id === 'imgContainer') closeImageModal(true)">
            <img id="zoomedImg" src="" style="max-width:90%; max-height:90vh; transform-origin: center center; cursor: zoom-in; box-shadow: 0 10px 40px rgba(0,0,0,0.4);" draggable="false">
        </div>
    </div>

    <script>
        const templateItemsData = <?= json_encode($tpl_items_map) ?>; let currentInput = null; let html5QrCode = null;
        
        function loadTemplateItems(tbodyId, prefix, tid) { 
            let tb = document.getElementById(tbodyId); tb.innerHTML = ''; 
            if (!tid || !templateItemsData[tid]) return; 
            
            let idx = 1000;
            templateItemsData[tid].forEach(item => { 
                let conf = item.req_config == 1 ? `<select class="form-select form-select-sm border-warning fw-bold text-dark" name="${prefix}_configured[${idx}]"><option value="0" class="text-danger">รอตั้งค่า</option><option value="1" class="text-success">ตั้งค่าแล้ว</option></select>` : `<span class="text-secondary small">ไม่ต้องตั้งค่า</span><input type="hidden" name="${prefix}_configured[${idx}]" value="0">`;
                let html = `<tr>
                    <td class="ps-4"><input type="hidden" name="${prefix}_id[]" value="new"><input type="text" class="form-control-plaintext fw-bold" name="${prefix}_name[]" value="${item.name}" readonly><input type="hidden" name="${prefix}_req_config[]" value="${item.req_config}"></td>
                    <td><div class="input-group"><input type="text" id="sn_${prefix}_${idx}" class="form-control form-control-sm" name="${prefix}_serial[]" placeholder="S/N"><button type="button" class="btn btn-primary btn-sm rounded-end" onclick="openCamera('sn_${prefix}_${idx}')"><i class="bi bi-qr-code-scan"></i></button></div></td>
                    <td><input type="file" class="form-control form-control-sm" name="${prefix}_photo[]" accept="image/*" onchange="scanImageFile(this, 'sn_${prefix}_${idx}')"></td>
                    <td>${conf}</td>
                    <td><button type="button" class="btn btn-sm text-danger" onclick="this.parentElement.parentElement.remove()"><i class="bi bi-trash"></i></button></td>
                </tr>`;
                $(tb).append(html); idx++;
            }); 
        }

        $(document).ready(function() {
            $('.select2-searchable').select2({ theme: 'bootstrap-5', width: '100\%' });$('.select2-tags').select2({ theme: 'bootstrap-5', tags: true, tokenSeparators: [',', ' '] });

            $.when($.getJSON('https://cdn.jsdelivr.net/gh/thailand-geography-data/thailand-geography-json@main/src/provinces.json'), $.getJSON('https://cdn.jsdelivr.net/gh/thailand-geography-data/thailand-geography-json@main/src/districts.json'), $.getJSON('https://cdn.jsdelivr.net/gh/thailand-geography-data/thailand-geography-json@main/src/subdistricts.json')).done(function(p, a, t) {
                window.provinces = p[0].sort((a,b)=>a.provinceNameTh.localeCompare(b.provinceNameTh)); window.amphures = a[0]; window.tambons = t[0];
                let opts = '<option value="">เลือกจังหวัด</option>'; window.provinces.forEach(x => opts+=`<option value="${x.provinceNameTh}" data-id="${x.provinceCode}">${x.provinceNameTh}</option>`);
                $('#province').html(opts); if ($('#province').data('val')) { $('#province').val($('#province').data('val')).trigger('change'); }
            });
            $('#province').change(function() { let id=$(this).find(':selected').data('id'); $('#amphoe').empty().append('<option value="">เลือกอำเภอ</option>').prop('disabled',true); $('#district').empty().append('<option value="">เลือกตำบล</option>').prop('disabled',true); if(id){ let opts='<option value="">เลือกอำเภอ</option>'; window.amphures.filter(x=>x.provinceCode===id).forEach(x=>opts+=`<option value="${x.districtNameTh}" data-id="${x.districtCode}">${x.districtNameTh}</option>`); $('#amphoe').html(opts).prop('disabled',false); if ($('#amphoe').data('val')) { $('#amphoe').val($('#amphoe').data('val')).trigger('change'); $('#amphoe').data('val', ''); } } });
            $('#amphoe').change(function() { let id=$(this).find(':selected').data('id');$('#district').empty().append('<option value="">เลือกตำบล</option>').prop('disabled',true); if(id){ let opts='<option value="">เลือกตำบล</option>'; window.tambons.filter(x=>x.districtCode===id).forEach(x=>opts+=`<option value="${x.subdistrictNameTh}" data-id="${x.subdistrictCode}">${x.subdistrictNameTh}</option>`); $('#district').html(opts).prop('disabled',false); if ($('#district').data('val')) { $('#district').val($('#district').data('val')).trigger('change'); $('#district').data('val', ''); } } });
        });

        let initLat = <?= $project['latitude'] ?$project['latitude'] : 13.5000 ?>; let initLng = <?= $project['longitude'] ?$project['longitude'] : 100.9925 ?>;
        const map = L.map('pickerMap').setView([initLat, initLng], <?= ($project['latitude'] &&$project['longitude']) ? 12 : 6 ?>); 
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
        const icon = L.divIcon({ className: 'pulse-marker', iconSize: [20, 20], iconAnchor: [10, 10] }); 
        let marker = L.marker([initLat, initLng], { icon: icon, draggable: true }).addTo(map);
        marker.on('dragend', function(e) { let p = marker.getLatLng(); $('#latInput').val(p.lat.toFixed(8)); $('#lngInput').val(p.lng.toFixed(8)); map.panTo(p); });
        $('#latInput, #lngInput').on('input', function() { let lat=parseFloat($('#latInput').val()), lng=parseFloat($('#lngInput').val()); if(!isNaN(lat)&&!isNaN(lng)){ let p=new L.LatLng(lat,lng); marker.setLatLng(p); map.setView(p,12); } });

        $('#isProgrammedToggle').change(function() { $('#programDetailsBox').slideToggle(this.checked); }); $('#isDeliveredToggle').change(function() { $('#deliveryDetailsBox').slideToggle(this.checked); });
        
        function openCamera(id) { 
            currentInput = id; $('#scannerModal').css('display','flex'); 
            if(!html5QrCode) html5QrCode = new Html5Qrcode("reader"); 
            html5QrCode.start({ facingMode: "environment" }, { fps: 10, qrbox: 250 }, (text) => { 
                let cleanText = text.trim();
                if(cleanText.startsWith('http://') || cleanText.startsWith('https://')) {
                    alert('⚠️ สแกนติดลิงก์เว็บไซต์ โปรดสแกนให้ตรง Barcode S/N');
                    return; 
                }
                $('#'+currentInput).val(cleanText); 
                closeScanner(); 
            }, (err) => {}).catch(()=>{}); 
        }
        function closeScanner() { $('#scannerModal').hide(); if(html5QrCode && html5QrCode.isScanning) html5QrCode.stop(); }

        // 🌟 อัปเกรดระบบอ่านรูปภาพ รองรับตัวอักษรผสมตัวเลข (เช่น YBE12546)
        function scanImageFile(fileInput, targetInputId) { 
            if (fileInput.files.length === 0) return; 
            
            let targetEl = document.getElementById(targetInputId);
            let originalBg = targetEl.style.backgroundColor;
            
            targetEl.value = "";
            targetEl.placeholder = "กำลังแกะรหัส S/N...";
            targetEl.style.backgroundColor = "#fffbeb"; 

            if(!html5QrCode) html5QrCode = new Html5Qrcode("reader");
            
            html5QrCode.scanFile(fileInput.files[0], true)
                .then(qrCodeMessage => { 
                    let text = qrCodeMessage.trim();
                    
                    if(text.startsWith('http://') || text.startsWith('https://')) {
                        targetEl.value = ""; 
                        targetEl.placeholder = "สแกนติดเว็บ โปรดพิมพ์เอง";
                        targetEl.style.backgroundColor = "#fee2e2"; 
                        setTimeout(() => { 
                            targetEl.style.backgroundColor = originalBg; 
                            targetEl.placeholder = "S/N";
                        }, 3000);
                        return;
                    }
                    
                    targetEl.value = text; 
                    targetEl.style.backgroundColor = "#dcfce7"; 
                    setTimeout(() => { targetEl.style.backgroundColor = originalBg; }, 2000);
                })
                .catch(err => { 
                    targetEl.value = ""; 
                    targetEl.placeholder = "อ่านไม่ออก โปรดพิมพ์เอง";
                    targetEl.style.backgroundColor = "#fee2e2"; 
                    setTimeout(() => { 
                        targetEl.style.backgroundColor = originalBg; 
                        targetEl.placeholder = "S/N";
                    }, 3000);
                }); 
        }
        
        let currentScale = 1; let currentX = 0; let currentY = 0; let isDragging = false; let startX, startY;
        const imgEl = document.getElementById('zoomedImg'); const container = document.getElementById('imgContainer');

        function zoomImage(src, name) { 
            imgEl.src = src; document.getElementById('zoomTitle').innerText = name; 
            document.getElementById('imageModal').style.display = 'flex'; resetZoom(); 
        }
        function closeImageModal(force = false) { if(force) { document.getElementById('imageModal').style.display = 'none'; resetZoom(); } }
        function zoomImg(step) { currentScale += step; if (currentScale < 0.25) currentScale = 0.25; if (currentScale > 4) currentScale = 4; applyZoom(true); }
        function resetZoom() { currentScale = 1; currentX = 0; currentY = 0; applyZoom(true); }

        function applyZoom(smooth = true) {
            imgEl.style.transition = smooth ? 'transform 0.3s cubic-bezier(0.25, 0.8, 0.25, 1)' : 'none';
            imgEl.style.transform = `translate(${currentX}px, ${currentY}px) scale(${currentScale})`;
            if (currentScale > 1) { imgEl.style.maxWidth = 'none'; imgEl.style.maxHeight = 'none'; imgEl.style.cursor = isDragging ? 'grabbing' : 'grab'; } 
            else { imgEl.style.maxWidth = '90%'; imgEl.style.maxHeight = '90vh'; imgEl.style.cursor = 'zoom-in'; currentX = 0; currentY = 0; if(smooth) imgEl.style.transform = `translate(0px, 0px) scale(1)`; }
        }

        container.addEventListener('wheel', function(e) { e.preventDefault(); if (e.deltaY < 0) { zoomImg(0.15); } else { zoomImg(-0.15); } });
        imgEl.addEventListener('mousedown', function(e) { if (currentScale > 1) { e.preventDefault(); isDragging = true; startX = e.clientX - currentX; startY = e.clientY - currentY; applyZoom(false); } });
        window.addEventListener('mousemove', function(e) { if (!isDragging) return; currentX = e.clientX - startX; currentY = e.clientY - startY; applyZoom(false); });
        window.addEventListener('mouseup', function() { if (isDragging) { isDragging = false; applyZoom(false); } });
    </script>
</body>
</html>