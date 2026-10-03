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

function formatPhoneNumber($phone) { $phone = preg_replace('/[^0-9]/', '', $phone); if (strlen($phone) === 10) { return preg_replace("/^(\d{3})(\d{3})(\d{4})$/", "$1-$2-$3", $phone); } return $phone; }

$cabinet_id_esc = $conn->real_escape_string($project['cabinet_id']);
$transfer_logs = []; $log_query = "SELECT * FROM activity_logs WHERE (project_id = $id OR description LIKE '%$cabinet_id_esc%') AND (action_type LIKE '%TRANSFER%' OR description LIKE '%โยกย้าย%') ORDER BY created_at DESC";
$log_res = $conn->query($log_query); if ($log_res) { while ($l = $log_res->fetch_assoc()) { $transfer_logs[] = $l; } }
$total_equipments = count($control_items) + count($dc_items);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายละเอียดตู้ - <?= htmlspecialchars($project['project_name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <link rel="stylesheet" href="style.css">
    <style>
        body { background-color: #f8fafc; color: #334155; }
        .card-clean { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); margin-bottom: 1.5rem; overflow: hidden;}
        .icon-circle { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0; }
        .info-label { font-size: 0.75rem; color: #64748b; font-weight: 600; text-transform: uppercase; margin-bottom: 2px; }
        .info-value { font-size: 1rem; color: #0f172a; font-weight: 700; }
        .table-clean th { background: #f8fafc; color: #64748b; font-size: 0.85rem; font-weight: 600; border-bottom: 1px solid #e2e8f0; padding: 1rem; }
        .table-clean td { vertical-align: middle; border-bottom: 1px solid #f1f5f9; padding: 1rem; }
        .row-err { background-color: #fef2f2 !important; border-left: 4px solid #ef4444 !important; }
        
        .img-preview { width: 50px; height: 50px; object-fit: cover; border-radius: 8px; cursor: zoom-in; border: 1px solid #e2e8f0; transition: all 0.2s ease;}
        .img-preview:hover { transform: scale(1.1); box-shadow: 0 4px 10px rgba(0,0,0,0.1); border-color: #3b82f6;}
        
        .timeline-item { padding-left: 1.5rem; position: relative; margin-bottom: 1.5rem; border-left: 2px solid #e2e8f0; }
        .timeline-item::before { content: ''; position: absolute; left: -6px; top: 0; width: 10px; height: 10px; border-radius: 50%; background: #cbd5e1; }
        
        #viewMap { width: 100%; height: 300px; min-height: 280px; border-radius: 12px; border: 1px solid #cbd5e1; z-index: 1; }
        .pulse-marker { width: 20px; height: 20px; border-radius: 50%; background-color: #ef4444; border: 3px solid #fff; box-shadow: 0 0 0 rgba(239, 68, 68, 0.4); animation: pulse-red 2s infinite; }
        @keyframes pulse-red { 0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); } 70% { box-shadow: 0 0 0 10px rgba(239, 68, 68, 0); } 100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); } }
    </style>
</head>
<body>
    <div class="app-container">
        <?php include 'sidebar.php'; ?>
        <main class="main-content">
            <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3 border-light animate-fade-up">
                <div>
                    <h3 class="fw-bold text-dark mb-2"><?= htmlspecialchars($project['project_name']) ?></h3>
                    <div class="d-flex gap-2 align-items-center">
                        <span class="badge bg-primary rounded-pill px-3 py-1">ID: <?= htmlspecialchars($project['cabinet_id']) ?></span>
                        <span class="badge bg-light border text-secondary rounded-pill px-3 py-1"><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($project['region'] ?: 'ไม่ระบุ') ?></span>
                        <?php if($project['project_tags']): ?>
                            <div class="vr mx-1" style="height: 15px; opacity: 0.2;"></div>
                            <?php foreach(explode(',', $project['project_tags']) as $tag): ?>
                                <span class="badge bg-light text-secondary px-2 py-1 rounded-pill">#<?= htmlspecialchars(trim($tag)) ?></span>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <div>
                    <a href="edit_project.php?id=<?= $id ?>" class="btn btn-dark rounded-pill px-4 shadow-sm fw-bold"><i class="bi bi-pencil-square me-2"></i> แก้ไขข้อมูล</a>
                </div>
            </div>

            <?php if (!empty(trim($project['project_notes']))): ?>
            <div class="card-clean p-4 border-start border-warning border-4 animate-fade-up" style="animation-delay:0.1s; background-color: #fffbeb;">
                <div class="d-flex align-items-start gap-3">
                    <i class="bi bi-journal-text text-warning fs-4"></i>
                    <div>
                        <div class="fw-bold text-dark mb-1">หมายเหตุ / โน๊ตจากผู้ติดตั้ง</div>
                        <div class="text-secondary small" style="line-height: 1.6;"><?= nl2br(htmlspecialchars($project['project_notes'])) ?></div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div class="row g-4 mb-4 animate-fade-up" style="animation-delay:0.15s;">
                <div class="col-lg-4">
                    <div class="card-clean p-4 h-100">
                        <div class="d-flex align-items-center gap-3 mb-4">
                            <div class="icon-circle bg-primary bg-opacity-10 text-primary"><i class="bi bi-router"></i></div>
                            <div><h6 class="fw-bold m-0">เครือข่าย</h6></div>
                        </div>
                        <div class="d-flex flex-column gap-3">
                            <div><div class="info-label">Gateway Code</div><div class="info-value text-primary"><?= htmlspecialchars($project['gateway_code'] ?: '-') ?></div></div>
                            <div><div class="info-label">Gateway Name</div><div class="info-value"><?= htmlspecialchars($project['gateway_name'] ?: '-') ?></div></div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <div class="card-clean p-4 h-100">
                        <div class="d-flex align-items-center gap-3 mb-4">
                            <div class="icon-circle <?= $project['is_programmed'] ? 'bg-success bg-opacity-10 text-success' : 'bg-secondary bg-opacity-10 text-secondary' ?>"><i class="bi bi-code-slash"></i></div>
                            <div><h6 class="fw-bold m-0">ระบบและการตั้งค่า</h6></div>
                        </div>
                        <div class="mb-3">
                            <span class="badge <?= $project['is_programmed'] ? 'bg-success' : 'bg-secondary' ?> rounded-pill mb-2"><?= $project['is_programmed'] ? 'ลงโปรแกรมแล้ว' : 'ยังไม่ลงโปรแกรม' ?></span>
                        </div>
                        <div class="row g-2 mt-auto">
                            <div class="col-6"><div class="info-label">ติดตั้งโปรแกรมเมื่อ</div><div class="info-value fs-6"><?= $project['installation_date'] ? date('d M Y', strtotime($project['installation_date'])) : '-' ?></div></div>
                            <div class="col-6"><div class="info-label">ผู้รับผิดชอบ</div><div class="info-value fs-6"><?= htmlspecialchars($project['programmer_name'] ?: '-') ?></div></div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <div class="card-clean p-4 h-100">
                        <div class="d-flex align-items-center gap-3 mb-4">
                            <div class="icon-circle <?= $project['is_delivered'] ? 'bg-success bg-opacity-10 text-success' : 'bg-warning bg-opacity-10 text-warning' ?>"><i class="bi <?= $project['is_delivered'] ? 'bi-check2-all' : 'bi-hourglass' ?>"></i></div>
                            <div><h6 class="fw-bold m-0">สถานะการส่งมอบ</h6></div>
                        </div>
                        <?php if($project['is_delivered']): ?>
                            <div class="info-label text-success">ตู้พร้อมใช้งาน ส่งงานแล้ว</div>
                            <div class="info-value mb-3"><?= date('d M Y, H:i น.', strtotime($project['delivery_timestamp'])) ?></div>
                            <?php if($project['delivery_photo_path']): ?>
                                <button class="btn btn-sm btn-light border fw-bold w-100" onclick="zoomImage('<?= $project['delivery_photo_path'] ?>', 'หลักฐานส่งงาน')"><i class="bi bi-image"></i> ดูรูปถ่ายส่งงาน</button>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="info-label text-warning">รอดำเนินการ</div>
                            <div class="info-value text-secondary fs-6">ตู้ยังดำเนินการไม่เสร็จสิ้น หรือรอเอกสารยืนยัน</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4 animate-fade-up" style="animation-delay:0.2s;">
                <div class="col-lg-6">
                    <div class="card-clean p-4 h-100 d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <h6 class="fw-bold text-dark m-0"><i class="bi bi-pin-map text-danger me-2"></i> สถานที่และพิกัด</h6>
                            
                            <?php if($project['latitude'] && $project['longitude']): ?>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="text-secondary fw-bold small user-select-all px-2 border rounded bg-light" title="คลุมดำเพื่อก๊อปปี้พิกัดได้"><i class="bi bi-geo"></i> <?= $project['latitude'] ?>, <?= $project['longitude'] ?></span>
                                    <a href="https://www.google.com/maps?q=<?= $project['latitude'] ?>,<?= $project['longitude'] ?>" target="_blank" class="btn btn-sm btn-primary rounded-3 px-3 shadow-sm fw-bold" title="เปิด Google Maps">
                                        <i class="bi bi-map-fill me-1"></i> เปิดแผนที่
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if($project['address'] || $project['province']): ?>
                            <div class="mb-3 text-secondary lh-base" style="font-size: 0.95rem;">
                                <span class="fw-bold text-dark"><?= htmlspecialchars($project['address'] ?: '-') ?></span> <br>
                                ต.<?= htmlspecialchars($project['tambon'] ?: '-') ?> 
                                อ.<?= htmlspecialchars($project['amphure'] ?: '-') ?> 
                                จ.<?= htmlspecialchars($project['province'] ?: '-') ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mt-auto position-relative" style="flex-grow: 1;">
                            <div id="viewMap"></div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card-clean p-4 h-100">
                        <h6 class="fw-bold text-dark mb-4"><i class="bi bi-ui-checks text-warning me-2"></i> รายการตรวจสอบฮาร์ดแวร์</h6>
                        
                        <div class="bg-light p-3 rounded-3 border border-light mb-3">
                            <div class="info-label">ซิมการ์ด (SIM)</div>
                            <?php if($project['sim_status'] === 'installed'): ?>
                                <div class="fw-bold text-success fs-5"><?= htmlspecialchars(formatPhoneNumber($project['sim_number'])) ?></div>
                                <div class="d-flex justify-content-between align-items-center mt-1">
                                    <small class="text-secondary">S/N: <?= htmlspecialchars($project['sim_serial'] ?: '-') ?></small>
                                    
                                    <!-- 🌟 เปลี่ยนจากปุ่มกดเป็น รูปภาพ Thumbnail ย่อส่วน -->
                                    <?php if($project['sim_photo_path']): ?>
                                        <img src="<?= $project['sim_photo_path'] ?>" class="img-preview shadow-sm" style="width: 45px; height: 45px;" onclick="zoomImage('<?= $project['sim_photo_path'] ?>', 'รูปซองซิม')" title="คลิกเพื่อดูรูปซองซิมเต็มตา">
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <div class="text-secondary"><i class="bi bi-dash"></i> ไม่มีซิมการ์ด</div>
                            <?php endif; ?>
                        </div>

                        <div class="row g-3">
                            <div class="col-6"><div class="border p-3 rounded-3"><div class="info-label">กล้องวงจรปิด</div><div class="fw-bold <?= $project['camera_setup_status'] === 'completed' ? 'text-success' : 'text-warning' ?>"><?= $project['camera_setup_status'] === 'completed' ? 'ตั้งค่าแล้ว' : 'รอดำเนินการ' ?></div></div></div>
                            <div class="col-6"><div class="border p-3 rounded-3"><div class="info-label">เสาอากาศ</div><div class="fw-bold <?= $project['has_antenna'] ? 'text-dark' : 'text-secondary' ?>"><?= $project['has_antenna'] ? 'ติดตั้งแล้ว' : 'ไม่ได้ใส่' ?></div></div></div>
                            <div class="col-6"><div class="border p-3 rounded-3"><div class="info-label">SD Card</div><div class="fw-bold <?= $project['sd_status'] === 'installed' ? 'text-dark' : 'text-secondary' ?>"><?= $project['sd_status'] === 'installed' ? $project['sd_capacity_gb'].' GB' : 'ไม่ได้ใส่' ?></div></div></div>
                            <div class="col-6"><div class="bg-primary bg-opacity-10 border border-primary border-opacity-25 p-3 rounded-3"><div class="info-label text-primary m-0 mb-1">อุปกรณ์ทั้งหมด</div><div class="fw-bold text-primary fs-5 lh-1"><?= $total_equipments ?> ชิ้น</div></div></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- อุปกรณ์ -->
            <div class="row g-4 mb-4 animate-fade-up" style="animation-delay:0.3s;">
                <div class="col-12">
                    <div class="card-clean p-0">
                        <div class="p-4 border-bottom"><h6 class="fw-bold m-0"><i class="bi bi-hdd-network text-primary me-2"></i> อุปกรณ์ตู้ Control IoT</h6></div>
                        <div class="table-responsive">
                            <table class="table table-clean m-0">
                                <thead><tr><th class="ps-4">ชื่ออุปกรณ์</th><th>Serial Number</th><th>สถานะการตั้งค่า</th><th class="text-center">รูปถ่ายจริง</th></tr></thead>
                                <tbody>
                                    <?php if (count($control_items) > 0): ?>
                                        <?php foreach($control_items as $eq): 
                                            $is_err = (empty(trim($eq['serial_number'])) || trim($eq['serial_number']) === '-') || ($eq['requires_config'] == 1 && $eq['is_configured'] == 0);
                                        ?>
                                            <tr class="<?= $is_err ? 'row-err' : '' ?>">
                                                <td class="ps-4 fw-bold text-dark"><?= htmlspecialchars($eq['equipment_name']) ?> <?php if($is_err): ?><i class="bi bi-exclamation-circle text-danger ms-1" title="ข้อมูลไม่ครบ"></i><?php endif; ?></td>
                                                <td><span class="text-secondary"><i class="bi bi-upc-scan me-1"></i></span> <span class="fw-bold"><?= htmlspecialchars($eq['serial_number'] ?: 'ไม่มีข้อมูล') ?></span></td>
                                                <td>
                                                    <?php if($eq['requires_config'] == 1): ?>
                                                        <span class="badge <?= $eq['is_configured'] ? 'bg-success' : 'bg-danger' ?> rounded-pill px-2"><?= $eq['is_configured'] ? 'ตั้งค่าแล้ว' : 'รอตั้งค่า' ?></span>
                                                    <?php else: ?><span class="text-secondary small">ไม่ต้องตั้งค่า</span><?php endif; ?>
                                                </td>
                                                <td class="text-center">
                                                    <?php if ($eq['photo_path']): ?>
                                                        <img src="<?= $eq['photo_path'] ?>" class="img-preview shadow-sm" onclick="zoomImage('<?= $eq['photo_path'] ?>', '<?= htmlspecialchars($eq['equipment_name']) ?>')" title="คลิกเพื่อขยายรูป">
                                                    <?php else: ?>
                                                        <span class="text-secondary small bg-light px-3 py-1 rounded border">ไม่มีรูป</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?><tr><td colspan="4" class="text-center py-4 text-secondary">ไม่มีรายการอุปกรณ์</td></tr><?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="card-clean p-0">
                        <div class="p-4 border-bottom"><h6 class="fw-bold m-0"><i class="bi bi-lightning-charge text-info me-2"></i> อุปกรณ์ตู้ DC-IoT</h6></div>
                        <div class="table-responsive">
                            <table class="table table-clean m-0">
                                <thead><tr><th class="ps-4">ชื่ออุปกรณ์</th><th>Serial Number</th><th>สถานะการตั้งค่า</th><th class="text-center">รูปถ่ายจริง</th></tr></thead>
                                <tbody>
                                    <?php if (count($dc_items) > 0): ?>
                                        <?php foreach($dc_items as $eq): 
                                            $is_err = (empty(trim($eq['serial_number'])) || trim($eq['serial_number']) === '-') || ($eq['requires_config'] == 1 && $eq['is_configured'] == 0);
                                        ?>
                                            <tr class="<?= $is_err ? 'row-err' : '' ?>">
                                                <td class="ps-4 fw-bold text-dark"><?= htmlspecialchars($eq['equipment_name']) ?> <?php if($is_err): ?><i class="bi bi-exclamation-circle text-danger ms-1" title="ข้อมูลไม่ครบ"></i><?php endif; ?></td>
                                                <td><span class="text-secondary"><i class="bi bi-upc-scan me-1"></i></span> <span class="fw-bold"><?= htmlspecialchars($eq['serial_number'] ?: 'ไม่มีข้อมูล') ?></span></td>
                                                <td>
                                                    <?php if($eq['requires_config'] == 1): ?>
                                                        <span class="badge <?= $eq['is_configured'] ? 'bg-success' : 'bg-danger' ?> rounded-pill px-2"><?= $eq['is_configured'] ? 'ตั้งค่าแล้ว' : 'รอตั้งค่า' ?></span>
                                                    <?php else: ?><span class="text-secondary small">ไม่ต้องตั้งค่า</span><?php endif; ?>
                                                </td>
                                                <td class="text-center">
                                                    <?php if ($eq['photo_path']): ?>
                                                        <img src="<?= $eq['photo_path'] ?>" class="img-preview shadow-sm" onclick="zoomImage('<?= $eq['photo_path'] ?>', '<?= htmlspecialchars($eq['equipment_name']) ?>')" title="คลิกเพื่อขยายรูป">
                                                    <?php else: ?>
                                                        <span class="text-secondary small bg-light px-3 py-1 rounded border">ไม่มีรูป</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?><tr><td colspan="4" class="text-center py-4 text-secondary">ไม่มีรายการอุปกรณ์</td></tr><?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-clean p-4 animate-fade-up" style="animation-delay:0.4s; margin-bottom: 3rem;">
                <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3 border-light">
                    <h6 class="fw-bold text-dark m-0"><i class="bi bi-clock-history text-secondary me-2"></i> ประวัติการเปลี่ยนแปลง / โยกย้ายอุปกรณ์</h6>
                    <span class="badge bg-light text-secondary border rounded-pill px-3 py-1 fs-6"><?= count($transfer_logs) ?> รายการ</span>
                </div>
                
                <?php if (count($transfer_logs) > 0): ?>
                    <div class="timeline-container px-2">
                        <?php foreach($transfer_logs as $log): 
                            $raw_desc = htmlspecialchars($log['description']);
                            
                            $raw_desc = preg_replace('/\[(.*?)\]/', '<span class="badge bg-dark text-white fw-bold me-2"><i class="bi bi-person-circle"></i> $1</span>', $raw_desc);
                            $raw_desc = preg_replace("/'(.*?)'/", "<strong class='text-primary px-1'>$1</strong>", $raw_desc);
                            $raw_desc = str_replace("สลับอุปกรณ์คู่อัตโนมัติ:", "<span class='badge bg-warning text-dark border border-warning shadow-sm me-2 fs-6'><i class='bi bi-arrow-left-right'></i> สลับอุปกรณ์</span>", $raw_desc);
                            $raw_desc = str_replace("โยกย้าย", "<span class='badge bg-info text-white shadow-sm me-2 fs-6'><i class='bi bi-arrow-right-circle'></i> โยกย้ายอุปกรณ์</span>", $raw_desc);
                            $raw_desc = str_replace("ถอดอุปกรณ์", "<span class='badge bg-danger text-white shadow-sm me-2 fs-6'><i class='bi bi-box-arrow-up'></i> ถอดอุปกรณ์ออก</span>", $raw_desc);
                            $raw_desc = str_replace("นำอุปกรณ์", "<span class='badge bg-success text-white shadow-sm me-2 fs-6'><i class='bi bi-box-arrow-in-down'></i> นำอุปกรณ์เข้า</span>", $raw_desc);
                        ?>
                            <div class="timeline-item">
                                <div class="d-flex align-items-center mb-2">
                                    <div class="fw-bold text-secondary small bg-light px-2 py-1 rounded border"><i class="bi bi-calendar-event me-1"></i> <?= date('d M Y, H:i', strtotime($log['created_at'])) ?> น.</div>
                                </div>
                                <div class="bg-light p-3 rounded-3 border border-light" style="font-size: 0.95rem; line-height: 1.8;">
                                    <?= $raw_desc ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-secondary text-center py-4 bg-light rounded-4 border">
                        <i class="bi bi-shield-check fs-1 opacity-25 d-block mb-2"></i>
                        ยังไม่มีประวัติการโยกย้ายอุปกรณ์ในตู้นี้
                    </div>
                <?php endif; ?>
            </div>

        </main>
    </div>

    <!-- Modal รูปภาพ -->
    <div id="imageModal" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(15, 23, 42, 0.85); z-index:2000; flex-direction:column; justify-content:center; align-items:center; backdrop-filter: blur(5px);">
        <div style="position:absolute; top:20px; right:20px; display:flex; gap:10px; z-index:2010; background:rgba(255,255,255,0.95); padding:12px; border-radius:16px; box-shadow: 0 10px 30px rgba(0,0,0,0.3);">
            <button class="btn btn-light border rounded-3 fw-bold shadow-sm px-3" onclick="zoomImg(0.25)" title="ซูมเข้า"><i class="bi bi-zoom-in me-1"></i> ซูมเข้า</button>
            <button class="btn btn-light border rounded-3 fw-bold shadow-sm px-3" onclick="zoomImg(-0.25)" title="ซูมออก"><i class="bi bi-zoom-out me-1"></i> ซูมออก</button>
            <button class="btn btn-light border rounded-3 fw-bold shadow-sm px-3" onclick="resetZoom()" title="พอดีจอ"><i class="bi bi-aspect-ratio me-1"></i> ขนาดปกติ</button>
            <div class="vr mx-1"></div>
            <a id="downloadBtn" href="" download class="btn btn-success rounded-3 fw-bold shadow-sm px-3" title="ดาวน์โหลดรูปภาพ"><i class="bi bi-download me-1"></i> โหลดรูป</a>
            <button class="btn btn-danger rounded-3 fw-bold shadow-sm px-3" onclick="closeImageModal(true)" title="ปิดหน้าต่าง"><i class="bi bi-x-lg me-1"></i> ปิด</button>
        </div>
        
        <h5 id="zoomTitle" class="text-white fw-bold m-0" style="position:absolute; top:30px; left:30px; z-index:2010; text-shadow: 2px 2px 6px rgba(0,0,0,0.8);"></h5>
        
        <div id="imgContainer" style="width:100%; height:100%; overflow:hidden; display:flex; justify-content:center; align-items:center;" onclick="if(event.target.id === 'imgContainer') closeImageModal(true)">
            <img id="zoomedImg" src="" style="max-width:90%; max-height:90vh; transform-origin: center center; cursor: zoom-in; box-shadow: 0 10px 40px rgba(0,0,0,0.4);" draggable="false">
        </div>
    </div>
    
    <script>
        let currentScale = 1; let currentX = 0; let currentY = 0; let isDragging = false; let startX, startY;
        const imgEl = document.getElementById('zoomedImg'); const container = document.getElementById('imgContainer');

        function zoomImage(src, name) { 
            imgEl.src = src; document.getElementById('zoomTitle').innerText = name; 
            let dlBtn = document.getElementById('downloadBtn');
            dlBtn.href = src; dlBtn.download = 'Photo_' + name + '.jpg'; 
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

        document.addEventListener('DOMContentLoaded', function() {
            const lat = <?= $project['latitude'] ?$project['latitude'] : 'null' ?>; 
            const lng = <?= $project['longitude'] ?$project['longitude'] : 'null' ?>;
            const pName = <?= json_encode($project['project_name']) ?>;

            if (lat !== null && lng !== null) {
                const map = L.map('viewMap').setView([lat, lng], 14);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap' }).addTo(map);
                const customIcon = L.divIcon({ className: 'pulse-marker', iconSize: [20, 20], iconAnchor: [10, 10] });
                L.marker([lat, lng], {icon: customIcon}).addTo(map).bindPopup('<b>' + pName + '</b>').openPopup();
            } else { document.getElementById('viewMap').innerHTML = '<div class="d-flex h-100 align-items-center justify-content-center bg-light text-secondary rounded-3 border"><i class="bi bi-map-fill me-2 opacity-50"></i> ไม่มีข้อมูลพิกัด GPS</div>'; }
        });
    </script>
</body>
</html>