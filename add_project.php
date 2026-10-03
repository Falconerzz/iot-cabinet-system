<?php 
require_once 'config.php'; check_login(); 

$templates_query = $conn->query("SELECT * FROM equipment_templates ORDER BY cabinet_type ASC, id DESC");
$templates_arr = [];
while($t = $templates_query->fetch_assoc()) {
    $tid = $t['id'];
    $t['items'] = $conn->query("SELECT * FROM template_items WHERE template_id = $tid")->fetch_all(MYSQLI_ASSOC);
    $templates_arr[] = $t;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เพิ่มโครงการใหม่ - Sync Vibe</title>
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
        .card-header-clean { background: transparent; border-bottom: 1px solid #f1f5f9; padding: 1.25rem 1.5rem; font-weight: 700; color: #0f172a; }
        .form-label { font-size: 0.85rem; font-weight: 600; color: #64748b; margin-bottom: 0.4rem; }
        .form-control, .form-select { border-radius: 8px; border: 1px solid #cbd5e1; padding: 0.6rem 1rem; font-size: 0.95rem; transition: all 0.3s; }
        .form-control:focus, .form-select:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
        .pulse-marker { width: 20px; height: 20px; border-radius: 50%; background-color: #ef4444; border: 3px solid #fff; box-shadow: 0 0 0 rgba(239, 68, 68, 0.4); animation: pulse-red 2s infinite; }
        @keyframes pulse-red { 0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); } 70% { box-shadow: 0 0 0 10px rgba(239, 68, 68, 0); } 100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); } }
        .table-clean th { background: #f8fafc; color: #64748b; font-size: 0.85rem; font-weight: 600; border-bottom: 1px solid #e2e8f0; }
        .table-clean td { vertical-align: middle; border-bottom: 1px solid #f1f5f9; }
    </style>
</head>
<body>
    <div class="app-container">
        <?php include 'sidebar.php'; ?>
        <main class="main-content">
            <div class="mb-4">
                <h3 class="fw-bold text-dark m-0">สร้างโครงการใหม่</h3>
                <p class="text-secondary small mt-1">กรอกข้อมูลพื้นฐานและเลือกแม่แบบอุปกรณ์เพื่อสร้างตู้ IoT</p>
            </div>

            <form action="save_project.php" method="POST" enctype="multipart/form-data">
                
                <div class="card-clean">
                    <div class="card-header-clean"><i class="bi bi-info-circle me-2 text-primary"></i> ข้อมูลโครงการ</div>
                    <div class="p-4">
                        <div class="row g-4">
                            <div class="col-md-6"><label class="form-label">ชื่อโครงการ *</label><input type="text" name="project_name" class="form-control" required placeholder="เช่น โครงการ Smart City"></div>
                            <div class="col-md-6"><label class="form-label">รหัสตู้ (Cabinet ID) *</label><input type="text" name="cabinet_id" class="form-control" required placeholder="เช่น CAB-001"></div>
                            <div class="col-md-6"><label class="form-label">แท็ก (Tags)</label><select name="project_tags[]" class="form-select select2-tags" multiple="multiple" data-placeholder="พิมพ์แท็กและกด Enter"></select></div>
                            <div class="col-md-6"><label class="form-label">ภูมิภาค</label><select name="region" class="form-select"><option value="ภาคเหนือ">ภาคเหนือ</option><option value="ภาคอีสาน">ภาคตะวันออกเฉียงเหนือ</option><option value="ภาคกลาง">ภาคกลาง</option><option value="ภาคใต้">ภาคใต้</option></select></div>
                            <div class="col-12"><label class="form-label">หมายเหตุ / โน๊ตเพิ่มเติม (ไม่บังคับ)</label><textarea name="project_notes" class="form-control" rows="2" placeholder="ระบุข้อมูลที่ต้องการบันทึกเพิ่มเติม..."></textarea></div>
                        </div>
                    </div>
                </div>

                <div class="card-clean">
                    <div class="card-header-clean"><i class="bi bi-geo-alt me-2 text-danger"></i> สถานที่ตั้งและพิกัด GPS</div>
                    <div class="p-4">
                        <div class="row g-4">
                            <div class="col-lg-7">
                                <label class="form-label">ปักหมุดบนแผนที่</label>
                                <div id="pickerMap" style="height: 380px; border-radius: 8px; border: 1px solid #cbd5e1; z-index: 1;"></div>
                                <small class="text-secondary mt-2 d-block"><i class="bi bi-info-circle"></i> เลื่อนหมุดบนแผนที่เพื่ออัปเดตพิกัดอัตโนมัติ</small>
                            </div>
                            
                            <div class="col-lg-5">
                                <div class="row g-3">
                                    <div class="col-12"><label class="form-label">Latitude (ละติจูด)</label><input type="text" id="latInput" name="latitude" class="form-control fw-bold text-primary"></div>
                                    <div class="col-12"><label class="form-label">Longitude (ลองจิจูด)</label><input type="text" id="lngInput" name="longitude" class="form-control fw-bold text-primary"></div>
                                    <div class="col-12"><hr class="my-2 border-light"></div>
                                    <div class="col-12"><label class="form-label">ที่อยู่ติดตั้งโดยละเอียด *</label><input type="text" name="address" class="form-control" required placeholder="บ้านเลขที่ หมู่ ซอย ถนน"></div>
                                    <div class="col-12"><label class="form-label">จังหวัด *</label><select name="province" id="province" class="form-select select2-searchable" required><option value="">กำลังโหลด...</option></select></div>
                                    <div class="col-md-6"><label class="form-label">อำเภอ *</label><select name="amphure" id="amphoe" class="form-select select2-searchable" required disabled><option value="">เลือกอำเภอ</option></select></div>
                                    <div class="col-md-6"><label class="form-label">ตำบล *</label><select name="tambon" id="district" class="form-select select2-searchable" required disabled><option value="">เลือกตำบล</option></select></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-4 mb-4">
                    <div class="col-lg-6">
                        <div class="card-clean h-100 mb-0">
                            <div class="card-header-clean"><i class="bi bi-gear me-2 text-secondary"></i> การตั้งค่าและลงโปรแกรม</div>
                            <div class="p-4">
                                <div class="form-check form-switch mb-4">
                                    <input class="form-check-input" type="checkbox" id="isProgrammedToggle" name="is_programmed" value="1">
                                    <label class="form-check-label fw-bold text-dark ms-2" for="isProgrammedToggle">ลงโปรแกรมระบบเรียบร้อยแล้ว</label>
                                </div>
                                <div id="programDetailsBox" style="display:none;">
                                    <div class="row g-3">
                                        <div class="col-md-4"><label class="form-label">ผู้ลงโปรแกรม</label><input type="text" name="programmer_name" class="form-control"></div>
                                        <div class="col-md-4"><label class="form-label">Gateway Name</label><input type="text" name="gateway_name" class="form-control"></div>
                                        <div class="col-md-4"><label class="form-label">Gateway Code</label><input type="text" name="gateway_code" class="form-control"></div>
                                    </div>
                                </div>
                                <hr class="my-4 border-light">
                                <div class="row g-3">
                                    <div class="col-sm-6"><label class="form-label">วันที่ติดตั้ง</label><input type="date" name="installation_date" class="form-control"></div>
                                    <div class="col-sm-6"><label class="form-label">สถานะกล้องวงจรปิด</label><select name="camera_setup_status" class="form-select"><option value="pending">ยังไม่ตั้งค่า</option><option value="completed">ตั้งค่าเรียบร้อย</option></select></div>
                                    <div class="col-12">
                                        <div class="form-check form-switch mt-2">
                                            <input class="form-check-input" type="checkbox" id="isDeliveredToggle" name="is_delivered" value="1">
                                            <label class="form-check-label fw-bold text-success ms-2" for="isDeliveredToggle">ตู้พร้อมใช้งาน ส่งงานแล้ว</label>
                                        </div>
                                        <div id="deliveryDetailsBox" class="mt-3" style="display:none;">
                                            <label class="form-label">อัปโหลดรูปถ่ายหน้างานเพื่อยืนยัน</label>
                                            <input type="file" name="delivery_photo" class="form-control" accept="image/*">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="card-clean h-100 mb-0">
                            <div class="card-header-clean"><i class="bi bi-hdd me-2 text-secondary"></i> อุปกรณ์เสริม (SIM / SD Card / เสาอากาศ)</div>
                            <div class="p-4">
                                <div class="mb-3"><label class="form-label">เสาอากาศ</label><select name="has_antenna" class="form-select"><option value="0">ยังไม่ได้ติดตั้ง</option><option value="1">ติดตั้งเรียบร้อยแล้ว</option></select></div>
                                
                                <div class="mb-3"><label class="form-label">สถานะซิมการ์ด</label><select name="sim_status" class="form-select" onchange="document.getElementById('simBox').style.display=(this.value==='installed')?'block':'none'"><option value="not_installed">ยังไม่มีซิม</option><option value="installed">ใส่ซิมเรียบร้อยแล้ว</option></select></div>
                                <div id="simBox" class="bg-light p-3 rounded-3 mb-3 border border-light" style="display:none;">
                                    <div class="row g-2">
                                        <div class="col-sm-6"><label class="form-label">เบอร์โทรศัพท์</label><input type="text" name="sim_number" class="form-control" placeholder="08xxxxxxxx"></div>
                                        <div class="col-sm-6"><label class="form-label">Serial Number</label><input type="text" name="sim_serial" class="form-control" placeholder="เลขบนซิม"></div>
                                        <div class="col-12 mt-2"><label class="form-label">รูปซองซิม</label><input type="file" name="sim_photo" class="form-control" accept="image/*"></div>
                                    </div>
                                </div>

                                <div class="mb-2"><label class="form-label">สถานะ SD Card</label><select name="sd_status" class="form-select" onchange="document.getElementById('sdBox').style.display=(this.value==='installed')?'block':'none'"><option value="not_installed">ยังไม่มี SD Card</option><option value="installed">ใส่เรียบร้อยแล้ว</option></select></div>
                                <div id="sdBox" style="display:none;"><input type="text" name="sd_capacity_gb" class="form-control mt-2" placeholder="ระบุความจุ (เช่น 64GB)"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-clean border-primary">
                    <div class="card-header-clean bg-primary bg-opacity-10 text-primary border-bottom-0 rounded-top-3"><i class="bi bi-layers me-2"></i> แม่แบบอุปกรณ์ (Template)</div>
                    <div class="p-4 pt-2">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <label class="form-label">เลือกแม่แบบตู้ Control IoT *</label>
                                <select name="template_control_id" id="tpl_control_select" class="form-select" required>
                                    <option value="" selected disabled>-- เลือกแม่แบบ --</option>
                                    <?php foreach($templates_arr as $t): if($t['cabinet_type'] === 'control_iot'): ?>
                                        <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['template_name']) ?> (<?= count($t['items']) ?> ชิ้น)</option>
                                    <?php endif; endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">เลือกแม่แบบตู้ DC-IoT *</label>
                                <select name="template_dc_id" id="tpl_dc_select" class="form-select" required>
                                    <option value="" selected disabled>-- เลือกแม่แบบ --</option>
                                    <?php foreach($templates_arr as $t): if($t['cabinet_type'] === 'dc_iot'): ?>
                                        <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['template_name']) ?> (<?= count($t['items']) ?> ชิ้น)</option>
                                    <?php endif; endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-clean">
                    <div class="card-header-clean"><i class="bi bi-list-ul me-2"></i> รายการอุปกรณ์ (โหลดอัตโนมัติ)</div>
                    <div class="p-4">
                        <h6 class="fw-bold text-dark mb-3">ตู้ Control IoT</h6>
                        <div class="table-responsive border rounded-3 mb-4"><table class="table table-clean m-0"><thead><tr><th width="35%">ชื่ออุปกรณ์</th><th width="30%">Serial Number</th><th width="20%">ตั้งค่า</th><th width="15%">รูปภาพ</th></tr></thead><tbody id="control_iot_tbody"><tr><td colspan="4" class="text-center py-4 text-secondary">กรุณาเลือกแม่แบบตู้ Control</td></tr></tbody></table></div>
                        
                        <h6 class="fw-bold text-dark mb-3">ตู้ DC-IoT</h6>
                        <div class="table-responsive border rounded-3"><table class="table table-clean m-0"><thead><tr><th width="35%">ชื่ออุปกรณ์</th><th width="30%">Serial Number</th><th width="20%">ตั้งค่า</th><th width="15%">รูปภาพ</th></tr></thead><tbody id="dc_iot_tbody"><tr><td colspan="4" class="text-center py-4 text-secondary">กรุณาเลือกแม่แบบตู้ DC</td></tr></tbody></table></div>
                    </div>
                </div>

                <div class="text-end mb-5">
                    <button type="submit" class="btn btn-primary px-5 py-2 fw-bold"><i class="bi bi-check2-circle me-2"></i> บันทึกข้อมูลโครงการ</button>
                </div>
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

    <script>
        const templates = <?= json_encode($templates_arr) ?>;
        let html5QrCode = null; let currentInput = null;
        
        function renderTemplateItems(tid, tbodyId, prefix) {
            let tb = $('#' + tbodyId); tb.empty();
            if(!tid) return;
            let t = templates.find(x => x.id == tid);
            if(!t || t.items.length === 0) { tb.append('<tr><td colspan="4" class="text-center py-3">ไม่มีอุปกรณ์</td></tr>'); return; }
            
            t.items.forEach((item, idx) => {
                let conf = item.requires_config == 1 
                    ? `<select class="form-select form-select-sm border-warning fw-bold text-dark" name="${prefix}_configured[${idx}]"><option value="0" class="text-danger">รอตั้งค่า</option><option value="1" class="text-success">ตั้งค่าแล้ว</option></select>` 
                    : `<span class="text-secondary small"><i class="bi bi-dash"></i> ไม่ต้องตั้งค่า</span><input type="hidden" name="${prefix}_configured[${idx}]" value="0">`;
                    
                tb.append(`<tr>
                    <td><input type="text" class="form-control-plaintext fw-bold text-dark" name="${prefix}_name[]" value="${item.equipment_name}" readonly><input type="hidden" name="${prefix}_req_config[]" value="${item.requires_config}"></td>
                    <td><div class="input-group"><input type="text" id="sn_${prefix}_${idx}" class="form-control form-control-sm" name="${prefix}_serial[]" placeholder="สแกน หรือ พิมพ์ S/N"><button type="button" class="btn btn-primary btn-sm rounded-end" onclick="openCamera('sn_${prefix}_${idx}')"><i class="bi bi-qr-code-scan"></i></button></div></td>
                    <td>${conf}</td>
                    <td><input type="file" class="form-control form-control-sm" name="${prefix}_photo[]" accept="image/*" onchange="scanImageFile(this, 'sn_${prefix}_${idx}')"></td>
                </tr>`);
            });
        }

        $('#tpl_control_select').change(function() { renderTemplateItems($(this).val(), 'control_iot_tbody', 'control'); });
        $('#tpl_dc_select').change(function() { renderTemplateItems($(this).val(), 'dc_iot_tbody', 'dc'); });

        $(document).ready(function() {
            $('.select2-searchable').select2({ theme: 'bootstrap-5', width: '100%' });
            $('.select2-tags').select2({ theme: 'bootstrap-5', tags: true, tokenSeparators: [',', ' '] });

            $.when($.getJSON('https://cdn.jsdelivr.net/gh/thailand-geography-data/thailand-geography-json@main/src/provinces.json'), $.getJSON('https://cdn.jsdelivr.net/gh/thailand-geography-data/thailand-geography-json@main/src/districts.json'), $.getJSON('https://cdn.jsdelivr.net/gh/thailand-geography-data/thailand-geography-json@main/src/subdistricts.json')).done(function(p, a, t) {
                window.provinces = p[0].sort((a,b)=>a.provinceNameTh.localeCompare(b.provinceNameTh)); window.amphures = a[0]; window.tambons = t[0];
                let opts = '<option value="">เลือกจังหวัด</option>'; window.provinces.forEach(x => opts+=`<option value="${x.provinceNameTh}" data-id="${x.provinceCode}">${x.provinceNameTh}</option>`);
                $('#province').html(opts);
            });
            $('#province').change(function() { let id=$(this).find(':selected').data('id'); $('#amphoe').empty().append('<option value="">เลือกอำเภอ</option>').prop('disabled',true); $('#district').empty().append('<option value="">เลือกตำบล</option>').prop('disabled',true); if(id){ let opts='<option value="">เลือกอำเภอ</option>'; window.amphures.filter(x=>x.provinceCode===id).forEach(x=>opts+=`<option value="${x.districtNameTh}" data-id="${x.districtCode}">${x.districtNameTh}</option>`); $('#amphoe').html(opts).prop('disabled',false); } });
            $('#amphoe').change(function() { let id=$(this).find(':selected').data('id'); $('#district').empty().append('<option value="">เลือกตำบล</option>').prop('disabled',true); if(id){ let opts='<option value="">เลือกตำบล</option>'; window.tambons.filter(x=>x.districtCode===id).forEach(x=>opts+=`<option value="${x.subdistrictNameTh}" data-id="${x.subdistrictCode}">${x.subdistrictNameTh}</option>`); $('#district').html(opts).prop('disabled',false); } });
        });

        const map = L.map('pickerMap').setView([13.5000, 100.9925], 6); L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
        const icon = L.divIcon({ className: 'pulse-marker', iconSize: [20, 20], iconAnchor: [10, 10] });
        let marker = L.marker([13.5000, 100.9925], { icon: icon, draggable: true }).addTo(map);
        marker.on('dragend', function(e) { let p = marker.getLatLng(); $('#latInput').val(p.lat.toFixed(8)); $('#lngInput').val(p.lng.toFixed(8)); map.panTo(p); });
        $('#latInput, #lngInput').on('input', function() { let lat=parseFloat($('#latInput').val()), lng=parseFloat($('#lngInput').val()); if(!isNaN(lat)&&!isNaN(lng)){ let p=new L.LatLng(lat,lng); marker.setLatLng(p); map.setView(p,12); } });

        $('#isProgrammedToggle').change(function() { $('#programDetailsBox').slideToggle(this.checked); });
        $('#isDeliveredToggle').change(function() { $('#deliveryDetailsBox').slideToggle(this.checked); });
        
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

        // 🌟 ปรับปรุงใหม่: รองรับ S/N ที่มีทั้งตัวอักษรและตัวเลขผสมกัน (เช่น YBE12546)
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
                    
                    // ป้องกันเฉพาะกรณีที่เป็นลิงก์คู่มือเว็บยาวๆ
                    if(text.startsWith('http://') || text.startsWith('https://')) {
                        targetEl.value = ""; 
                        targetEl.placeholder = "สแกนติดเว็บ โปรดพิมพ์เอง";
                        targetEl.style.backgroundColor = "#fee2e2"; 
                        setTimeout(() => { 
                            targetEl.style.backgroundColor = originalBg; 
                            targetEl.placeholder = "สแกน หรือ พิมพ์ S/N";
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
                        targetEl.placeholder = "สแกน หรือ พิมพ์ S/N";
                    }, 3000);
                }); 
        }
    </script>
</body>
</html>