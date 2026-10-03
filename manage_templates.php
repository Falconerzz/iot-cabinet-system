<?php
require_once 'config.php'; check_login();

// ดึง Master Equipments
$master_res =$conn->query("SELECT * FROM master_equipments ORDER BY equipment_name ASC");
$masters = []; while($m =$master_res->fetch_assoc()) { $masters[] =$m['equipment_name']; }

// ดึง Templates ทั้งหมด
$templates =$conn->query("SELECT t.*, COUNT(i.id) as item_count FROM equipment_templates t LEFT JOIN template_items i ON t.id = i.template_id GROUP BY t.id ORDER BY t.id DESC");

// จัดกลุ่มอุปกรณ์ของแต่ละ Template
$tpl_items_map = [];
$items_query =$conn->query("SELECT * FROM template_items");
while ($row =$items_query->fetch_assoc()) {
    $tpl_items_map[$row['template_id']][] = [
        'name' => $row['equipment_name'],
        'req_config' => $row['requires_config']
    ];
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการแม่แบบอุปกรณ์ - Sync Vibe</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <link rel="stylesheet" href="style.css">
    <style>
        body { background-color: #f8fafc; color: #334155; }
        .card-clean { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); margin-bottom: 1.5rem; }
        .card-header-clean { background: transparent; border-bottom: 1px solid #f1f5f9; padding: 1.25rem 1.5rem; font-weight: 700; color: #0f172a; }
        
        /* 🌟 ปรับแต่ง Modal ให้สามารถ Scroll ได้ตรงกลาง และไม่ล้นจอ */
        .custom-modal-backdrop {
            position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
            background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px);
            z-index: 2000; display: none; align-items: center; justify-content: center;
            padding: 1.5rem;
        }
        .custom-modal-dialog {
            background: #ffffff; border-radius: 1.5rem; width: 100%; max-width: 800px;
            max-height: 90vh; /* บังคับความสูงไม่ให้เกิน 90% ของหน้าจอ */
            display: flex; flex-direction: column; /* เพื่อให้เนื้อหาแบ่งสัดส่วนได้ */
            box-shadow: 0 25px 50px rgba(0,0,0,0.15);
        }
        .modal-body-scroll {
            overflow-y: auto; /* เพิ่ม Scroll เฉพาะตรงกลาง */
            overflow-x: hidden;
            padding-right: 5px; /* กัน Scrollbar ทับเนื้อหา */
            flex-grow: 1; /* ขยายจนเต็มพื้นที่ที่เหลือ */
        }
        
        /* ตกแต่ง Scrollbar ให้ดูสวยงาม (เฉพาะ Webkit) */
        .modal-body-scroll::-webkit-scrollbar { width: 6px; }
        .modal-body-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        .modal-body-scroll::-webkit-scrollbar-track { background: transparent; }
    </style>
</head>
<body>
    <div class="app-container">
        <?php include 'sidebar.php'; ?>
        <main class="main-content">
            <div class="mb-5 animate-fade-up">
                <h2 class="fw-bold m-0 text-dark display-6">จัดการแม่แบบอุปกรณ์ (Templates)</h2>
                <p class="text-secondary m-0 mt-2">สร้างและแก้ไขแพตเทิร์นอุปกรณ์ เพื่อนำไปใช้รวดเร็วในการเพิ่มโครงการ</p>
            </div>

            <!-- ส่วนเพิ่ม Template ใหม่ -->
            <div class="card-clean p-4 mb-5 animate-fade-up" style="animation-delay: 0.1s;">
                <h5 class="fw-bold text-dark mb-4"><i class="bi bi-plus-square-fill text-primary me-2"></i>สร้างแม่แบบใหม่</h5>
                <form action="save_template.php" method="POST">
                    <input type="hidden" name="action" value="create">
                    <div class="row g-4 mb-4">
                        <div class="col-md-8">
                            <label class="form-label text-secondary fw-bold small">ชื่อแม่แบบ *</label>
                            <input type="text" name="template_name" class="form-control bg-light" required placeholder="เช่น ชุดมาตรฐาน Type A">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-secondary fw-bold small">สำหรับตู้ *</label>
                            <select name="cabinet_type" class="form-select bg-light" required>
                                <option value="control_iot">ตู้ Control IoT</option>
                                <option value="dc_iot">ตู้ DC-IoT</option>
                            </select>
                        </div>
                    </div>
                    <div class="p-4 bg-light rounded-4 border border-light">
                        <h6 class="fw-bold text-dark mb-3"><i class="bi bi-list-check me-2 text-primary"></i>รายชื่ออุปกรณ์ในแม่แบบ</h6>
                        <div id="template_item_list"></div>
                        <button type="button" class="btn btn-outline-primary rounded-pill mt-3 fw-bold bg-white" onclick="addTemplateItemRow('template_item_list')"><i class="bi bi-plus-lg me-1"></i> เพิ่มอุปกรณ์ลงในแม่แบบ</button>
                    </div>
                    <div class="text-end mt-4">
                        <button type="submit" class="btn btn-primary px-5 py-2 rounded-pill fw-bold shadow-sm"><i class="bi bi-save me-2"></i> บันทึกแม่แบบใหม่</button>
                    </div>
                </form>
            </div>

            <!-- รายการ Template -->
            <div class="card-clean p-4 animate-fade-up" style="animation-delay: 0.2s;">
                <h5 class="fw-bold text-dark mb-4"><i class="bi bi-layers-half text-info me-2"></i>แม่แบบทั้งหมดในระบบ</h5>
                <?php if ($templates->num_rows > 0): ?>
                    <?php while($tpl =$templates->fetch_assoc()): ?>
                        <div class="d-flex justify-content-between align-items-center p-3 mb-3 bg-white rounded-4 border border-light shadow-sm" style="transition: transform 0.2s; cursor:default;" onmouseover="this.style.transform='translateX(5px)'" onmouseout="this.style.transform='translateX(0)'">
                            <div>
                                <strong class="text-dark fs-5 me-2"><?= htmlspecialchars($tpl['template_name']) ?></strong>
                                <span class="badge <?= $tpl['cabinet_type'] === 'control_iot' ? 'bg-primary bg-opacity-10 text-primary border border-primary' : 'bg-info bg-opacity-10 text-info border border-info' ?>">
                                    <?= $tpl['cabinet_type'] === 'control_iot' ? '<i class="bi bi-hdd-network"></i> ตู้ Control' : '<i class="bi bi-lightning-charge"></i> ตู้ DC' ?>
                                </span>
                                <small class="text-secondary fw-bold ms-2 bg-light px-2 py-1 rounded border">(<?= $tpl['item_count'] ?> รายการ)</small>
                            </div>
                            
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-sm btn-outline-warning text-warning border-warning bg-warning bg-opacity-10 rounded-pill px-3 fw-bold" onclick="openEditModal(<?= $tpl['id'] ?>, '<?= addslashes(htmlspecialchars($tpl['template_name'])) ?>', '<?=$tpl['cabinet_type'] ?>')">
                                    <i class="bi bi-pencil-square me-1"></i> แก้ไข
                                </button>
                                
                                <form action="save_template.php" method="POST" onsubmit="return confirm('ยืนยันลบแม่แบบนี้ใช่หรือไม่?');" class="m-0">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="template_id" value="<?= $tpl['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger border-0 bg-danger bg-opacity-10 text-danger rounded-circle p-2" title="ลบแม่แบบ">
                                        <i class="bi bi-trash-fill"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="text-center text-secondary py-5">
                        <i class="bi bi-layers text-secondary opacity-25 display-1 d-block mb-3"></i>
                        <span class="fw-bold fs-5">ยังไม่มีแม่แบบอุปกรณ์ในระบบ</span>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- 🌟 Modal สำหรับแก้ไข Template (แบบ Scroll ได้) -->
    <div id="editModal" class="custom-modal-backdrop" onclick="if(event.target.id === 'editModal') closeEditModal();">
        <div class="custom-modal-dialog p-4">
            
            <!-- 1. ส่วนหัว (Fixed) -->
            <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom border-light flex-shrink-0">
                <h4 class="fw-bold text-dark m-0"><i class="bi bi-pencil-square text-warning me-2"></i>แก้ไขแม่แบบอุปกรณ์</h4>
                <button type="button" class="btn-close" onclick="closeEditModal()"></button>
            </div>
            
            <!-- 2. ส่วนฟอร์มตรงกลาง (Scrollable) -->
            <div class="modal-body-scroll mt-2">
                <form action="save_template.php" method="POST" id="editTemplateForm">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="template_id" id="edit_template_id" value="">
                    
                    <div class="row g-4 mb-4">
                        <div class="col-md-8">
                            <label class="form-label text-secondary fw-bold small">ชื่อแม่แบบ *</label>
                            <input type="text" name="template_name" id="edit_template_name" class="form-control bg-light" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-secondary fw-bold small">สำหรับตู้ *</label>
                            <select name="cabinet_type" id="edit_cabinet_type" class="form-select bg-light" required>
                                <option value="control_iot">ตู้ Control IoT</option>
                                <option value="dc_iot">ตู้ DC-IoT</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="p-4 bg-light rounded-4 border border-light mb-2">
                        <h6 class="fw-bold text-dark mb-3"><i class="bi bi-list-check me-2 text-primary"></i>ปรับปรุงรายชื่ออุปกรณ์</h6>
                        <div id="edit_template_item_list"></div>
                        <button type="button" class="btn btn-sm btn-outline-primary rounded-pill mt-3 bg-white px-3 shadow-sm fw-bold" onclick="addTemplateItemRow('edit_template_item_list')">
                            <i class="bi bi-plus-lg me-1"></i> เพิ่มอุปกรณ์
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- 3. ส่วนท้าย ปุ่มกด (Fixed) -->
            <div class="d-flex gap-3 justify-content-end mt-3 pt-3 border-top border-light flex-shrink-0">
                <button type="button" class="btn btn-light border px-4 py-2 rounded-pill fw-bold text-secondary" onclick="closeEditModal()">ยกเลิก</button>
                <!-- ใช้ JavaScript สั่ง Submit Form ด้านบน -->
                <button type="button" class="btn btn-warning px-5 py-2 rounded-pill fw-bold text-white shadow-sm" onclick="document.getElementById('editTemplateForm').submit();">
                    <i class="bi bi-cloud-arrow-up-fill me-2"></i> อัปเดตแม่แบบ
                </button>
            </div>
            
        </div>
    </div>

    <script>
        const masterEquipments = <?= json_encode($masters) ?>;
        const templateItemsData = <?= json_encode($tpl_items_map) ?>;

        function addTemplateItemRow(containerId, defaultName = '', defaultReqConfig = 0) {
            const container = document.getElementById(containerId); 
            const rowId = 'tpl_item_' + Date.now() + Math.floor(Math.random()*1000);
            const div = document.createElement('div'); 
            
            div.className = 'row g-3 align-items-center mb-3 bg-white p-3 rounded-4 shadow-sm border border-light'; 
            div.id = rowId;
            
            let masterOptions = '<option value="">🔍 พิมพ์ค้นหาอุปกรณ์...</option>';
            masterEquipments.forEach(m => { 
                masterOptions += `<option value="${m}" ${(m===defaultName)?'selected':''}>${m}</option>`; 
            });
            
            div.innerHTML = `
                <div class="col-md-7">
                    <label class="small fw-bold text-secondary mb-1">ชื่ออุปกรณ์</label>
                    <select name="equipment_name[]" class="form-select select2-tpl-eq" required>
                        ${defaultName ? `<option value="${defaultName}" selected>${defaultName}</option>` : ''}
                        ${masterOptions}
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="small fw-bold text-secondary mb-1">การตั้งค่าอุปกรณ์</label>
                    <select name="requires_config[]" class="form-select border-secondary border-opacity-25 bg-light">
                        <option value="0" ${defaultReqConfig == 0 ? 'selected' : ''}>ไม่ต้องตั้งค่า</option>
                        <option value="1" ${defaultReqConfig == 1 ? 'selected' : ''} class="text-warning fw-bold">จำเป็นต้องตั้งค่า</option>
                    </select>
                </div>
                <div class="col-md-1 text-end mt-4 pt-2">
                    <button type="button" class="btn btn-sm btn-outline-danger border-0 bg-danger bg-opacity-10 text-danger rounded-circle p-2" onclick="this.parentElement.parentElement.remove()" title="ลบรายการนี้">
                        <i class="bi bi-trash-fill"></i>
                    </button>
                </div>
            `;
            container.appendChild(div); 
            
            $(`#${rowId} .select2-tpl-eq`).select2({ 
                theme: 'bootstrap-5', 
                width: '100%', 
                tags: true,
                dropdownParent: $('#' + containerId).closest('.custom-modal-backdrop').length ? $('#editModal') : $(document.body)
            });
        }

        window.onload = function() { 
            addTemplateItemRow('template_item_list'); 
        };

        function openEditModal(id, name, type) {
            document.getElementById('edit_template_id').value = id;
            document.getElementById('edit_template_name').value = name;
            document.getElementById('edit_cabinet_type').value = type;
            
            document.getElementById('edit_template_item_list').innerHTML = '';
            
            if(templateItemsData[id] && templateItemsData[id].length > 0) {
                templateItemsData[id].forEach(item => {
                    addTemplateItemRow('edit_template_item_list', item.name, item.req_config);
                });
            } else {
                addTemplateItemRow('edit_template_item_list');
            }
            document.getElementById('editModal').style.display = 'flex';
        }

        function closeEditModal() { document.getElementById('editModal').style.display = 'none'; }
    </script>
</body>
</html>