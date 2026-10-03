<?php
require_once 'config.php'; check_login();

// ดึงข้อมูลโครงการ
$result = $conn->query("SELECT id, project_name, cabinet_id, region, province, is_delivered FROM projects ORDER BY id DESC");
$projects = [];
while($row = $result->fetch_assoc()) {
    $projects[] = $row;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ดาวน์โหลดใบส่งสินค้า - Sync Vibe</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <link rel="stylesheet" href="style.css">
    <style>
        body { background-color: #f8fafc; }
        .card-custom { border: none; border-radius: 1.25rem; box-shadow: 0 4px 20px rgba(0,0,0,0.03); transition: transform 0.2s ease; background: #ffffff; }
        
        /* Search Box Modern */
        .search-container { background: #ffffff; border-radius: 1rem; border: 1px solid #e2e8f0; box-shadow: 0 4px 15px rgba(0,0,0,0.02); transition: all 0.3s ease; }
        .search-container:focus-within { box-shadow: 0 4px 20px rgba(0,0,0,0.08); border-color: #cbd5e1; }
        .search-input { background: transparent !important; border: none !important; box-shadow: none !important; font-size: 1.05rem; color: #1e293b; font-weight: 500; }
        .search-input::placeholder { color: #94a3b8; font-weight: 400; }
        
        /* สไตล์สำหรับการไฮไลท์ข้อความค้นหา */
        .highlight { background-color: #fef08a; padding: 2px 4px; border-radius: 4px; color: #854d0e; font-weight: bold; transition: all 0.2s; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        
        /* Table Styles */
        .table-custom { margin: 0; }
        .table-custom thead th { background: #f8fafc; color: #64748b; font-size: 0.85rem; font-weight: 700; padding: 1rem; border-bottom: 2px solid #e2e8f0; border-top: none; }
        .table-custom tbody tr { transition: all 0.2s ease; border-bottom: 1px solid #f1f5f9; }
        .table-custom tbody tr:hover { background-color: #f8fafc; transform: translateY(-1px); box-shadow: 0 2px 10px rgba(0,0,0,0.02); }
        .table-custom td { padding: 1.25rem 1rem; vertical-align: middle; color: #334155; }
        
        /* Badges */
        .badge-province { background-color: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; font-weight: 600; padding: 0.5em 0.8em; }
        .badge-status { font-weight: 600; padding: 0.5em 0.8em; }
        
        /* Modal Styles */
        .custom-modal-dialog { background: #ffffff; border-radius: 1.5rem; border: none; box-shadow: 0 20px 50px rgba(0,0,0,0.1); overflow: hidden; }
        .modal-header-custom { background: #f8fafc; padding: 1.5rem; border-bottom: 1px solid #e2e8f0; }
        .form-label-custom { font-size: 0.85rem; color: #64748b; font-weight: 700; margin-bottom: 0.5rem; display: block; }
        .form-control-custom { border-radius: 0.75rem; border: 1px solid #cbd5e1; padding: 0.75rem 1rem; font-size: 0.95rem; transition: all 0.2s; background-color: #f8fafc; }
        .form-control-custom:focus { background-color: #ffffff; border-color: #10b981; box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.1); }
        .section-title { font-size: 1rem; font-weight: 700; color: #1e293b; margin-bottom: 1rem; display: flex; align-items: center; }
        .section-title i { font-size: 1.25rem; margin-right: 0.5rem; }
    </style>
</head>
<body>
    <div class="app-container">
        <?php include 'sidebar.php'; ?>
        <main class="main-content">
            <div class="mb-4 animate-fade-up">
                <div class="d-flex align-items-center gap-3 mb-2">
                    <div class="bg-success bg-opacity-10 text-success rounded-3 d-flex justify-content-center align-items-center" style="width: 48px; height: 48px; font-size: 1.5rem;">
                        <i class="bi bi-file-earmark-excel-fill"></i>
                    </div>
                    <div>
                        <h2 class="fw-bold text-dark m-0">ออกใบส่งสินค้า (Export)</h2>
                        <p class="text-secondary m-0">สร้างไฟล์ Excel (.xlsx) สำหรับโครงการต่างๆ</p>
                    </div>
                </div>
            </div>

            <div class="search-container p-2 mb-4 animate-fade-up" style="animation-delay: 0.1s;">
                <div class="d-flex align-items-center px-3 py-2">
                    <i class="bi bi-search text-secondary opacity-75 me-3 fs-5"></i>
                    <input type="text" id="searchInput" class="form-control search-input py-2" placeholder="ค้นหาชื่อโครงการ หรือ ID ตู้...">
                </div>
            </div>

            <div class="card card-custom p-0 overflow-hidden animate-fade-up" style="animation-delay: 0.2s;">
                <div class="table-responsive">
                    <table class="table table-custom align-middle">
                        <thead>
                            <tr>
                                <th width="15%" class="ps-4">รหัสตู้ (ID)</th>
                                <th width="35%">ชื่อโครงการ</th>
                                <th width="15%">จังหวัด</th>
                                <th width="15%">สถานะส่งงาน</th>
                                <th width="20%" class="text-center pe-4">ดาวน์โหลด</th>
                            </tr>
                        </thead>
                        <tbody id="projectTableBody">
                            <?php if (count($projects) > 0): ?>
                                <?php foreach($projects as $row): ?>
                                    <tr class="project-row">
                                        <td class="ps-4">
                                            <span class="badge bg-primary bg-opacity-10 text-primary-custom border border-primary border-opacity-25 px-2 py-1 rounded cabinet-col" data-original="<?= htmlspecialchars($row['cabinet_id']) ?>" style="font-size:0.9rem;">
                                                <?= htmlspecialchars($row['cabinet_id']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="fw-bold text-dark fs-6 name-col" data-original="<?= htmlspecialchars($row['project_name']) ?>"><?= htmlspecialchars($row['project_name']) ?></div>
                                        </td>
                                        <td>
                                            <span class="badge badge-province rounded-pill">
                                                <i class="bi bi-geo-alt-fill text-danger me-1"></i> <?= htmlspecialchars($row['province'] ?: 'ไม่ระบุ') ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if($row['is_delivered']): ?>
                                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill badge-status"><i class="bi bi-check-circle-fill me-1"></i> ส่งงานแล้ว</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 rounded-pill badge-status"><i class="bi bi-hourglass-split me-1"></i> รอส่งงาน</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center pe-4">
                                            <button type="button" class="btn btn-success rounded-pill px-4 py-2 shadow-sm fw-bold w-100 d-flex align-items-center justify-content-center gap-2" 
                                                    onclick="openExportModal(<?= $row['id'] ?>, '<?= htmlspecialchars($row['project_name']) ?>', '<?= htmlspecialchars($row['province']) ?>')"
                                                    style="transition: all 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                                                <i class="bi bi-file-earmark-excel fs-5"></i> <span>โหลดใบส่งของ</span>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-5">
                                        <div class="text-secondary opacity-50 mb-3"><i class="bi bi-folder-x" style="font-size: 3rem;"></i></div>
                                        <h6 class="fw-bold text-secondary">ไม่พบข้อมูลโครงการ</h6>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal กรอกข้อมูลก่อน Export -->
    <div id="exportModal" class="custom-modal-backdrop" style="display: none; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px);">
        <div class="custom-modal-dialog animate-fade-up" style="max-width: 850px; width: 95%;">
            
            <div class="modal-header-custom d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-success text-white rounded-3 d-flex justify-content-center align-items-center shadow-sm" style="width: 40px; height: 40px; font-size: 1.25rem;">
                        <i class="bi bi-file-earmark-spreadsheet"></i>
                    </div>
                    <h4 class="fw-bold text-dark m-0">ตั้งค่าข้อมูลใบส่งสินค้า</h4>
                </div>
                <button type="button" class="btn-close bg-light rounded-circle p-2 shadow-sm" onclick="closeExportModal()"></button>
            </div>
            
            <div class="p-4 bg-white">
                <form action="export_excel.php" method="POST" target="_blank" onsubmit="setTimeout(closeExportModal, 500);">
                    <input type="hidden" name="project_id" id="export_project_id">
                    
                    <div class="row g-4">
                        <!-- ฝั่งซ้าย: ข้อมูลผู้ส่ง-ผู้รับ -->
                        <div class="col-md-7 border-end border-light pe-md-4">
                            <div class="section-title text-primary-custom"><i class="bi bi-building-up text-primary-custom opacity-75"></i> ข้อมูลผู้ส่ง (จาก)</div>
                            <div class="row g-3 mb-4">
                                <div class="col-12">
                                    <label class="form-label-custom">ชื่อบริษัท *</label>
                                    <input type="text" name="from_company" class="form-control-custom w-100" value="บริษัท เอ็กซ์-แปน จำกัด" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label-custom">ที่อยู่ *</label>
                                    <input type="text" name="from_address" class="form-control-custom w-100" value="18 ซอยบางแวก 91 แขวงบางไผ่ เขตบางแค จังหวัดกรุงเทพมหานคร 10160" required>
                                </div>
                            </div>

                            <hr class="border-light opacity-50 mb-4">

                            <div class="section-title text-info"><i class="bi bi-building-down text-info opacity-75"></i> ข้อมูลผู้รับ (ถึง)</div>
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label-custom">ชื่อบริษัท *</label>
                                    <input type="text" name="to_company" class="form-control-custom w-100" value="บริษัท ไลท์ดอร์ส จำกัด" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label-custom">ที่อยู่ *</label>
                                    <input type="text" name="to_address" class="form-control-custom w-100" value="28/5 ซอยบรมราชชนนี 115/2 แขวงศาลาธรรมสพน์ ทวีวัฒนา กรุงเทพฯ 10170" required>
                                </div>
                            </div>
                        </div>

                        <!-- ฝั่งขวา: ข้อมูลโครงการ -->
                        <div class="col-md-5 ps-md-4">
                            <div class="bg-light p-4 rounded-4 h-100 border border-light">
                                <div class="section-title text-warning"><i class="bi bi-folder-fill text-warning opacity-75"></i> ข้อมูลโครงการ</div>
                                
                                <div class="mb-4 bg-white p-3 rounded-3 shadow-sm border border-light">
                                    <div class="text-secondary small fw-bold mb-1">โครงการที่จะออกเอกสาร</div>
                                    <div id="disp_project_name" class="fw-bold text-dark fs-6 lh-base"></div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label-custom">หมวด / สังกัดโครงการ</label>
                                    <input type="text" name="project_unit" class="form-control-custom w-100 bg-white" value="สทน.4" required placeholder="เช่น สทน.4">
                                </div>

                                <!-- 🌟 ส่วนจังหวัดที่แก้ไขใหม่ -->
                                <div class="mb-3">
                                    <label class="form-label-custom"><i class="bi bi-geo-alt-fill text-danger me-1"></i> จังหวัด</label>
                                    <input type="text" name="province" id="export_province" class="form-control-custom w-100 text-danger fw-bold bg-white" readonly tabindex="-1">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-3 mt-4 pt-4 border-top border-light">
                        <button type="button" class="btn btn-light rounded-pill px-4 fw-bold text-secondary" onclick="closeExportModal()">ยกเลิก</button>
                        <button type="submit" class="btn btn-success rounded-pill px-5 shadow-sm fw-bold d-flex align-items-center gap-2">
                            <i class="bi bi-download"></i> โหลดไฟล์ Excel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openExportModal(id, name, prov) {
            document.getElementById('export_project_id').value = id;
            document.getElementById('disp_project_name').innerText = name;
            document.getElementById('export_province').value = prov || 'ไม่ระบุ';
            $('#exportModal').fadeIn(200).css('display', 'flex');
        }
        function closeExportModal() {
            $('#exportModal').fadeOut(200);
        }

        $(document).ready(function() {
            // ฟังก์ชัน Highlight ข้อความ
            function highlightText(element, keyword) {
                let text = $(element).attr('data-original');
                if (!keyword) { $(element).html(text); return; }
                let regex = new RegExp("(" + keyword.replace(/[\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g, "\\$&") + ")", "gi");
                let highlighted = text.replace(regex, "<span class='highlight'>$1</span>");
                $(element).html(highlighted);
            }

            $('#searchInput').on('keyup', function() {
                let value = $(this).val().toLowerCase().trim();
                let visibleCount = 0;
                
                $('.project-row').each(function() {
                    let nameElem = $(this).find('.name-col');
                    let cabinetElem = $(this).find('.cabinet-col');
                    
                    let name = nameElem.attr('data-original').toLowerCase();
                    let cabinet = cabinetElem.attr('data-original').toLowerCase();
                    
                    if(name.indexOf(value) > -1 || cabinet.indexOf(value) > -1) { 
                        $(this).show();
                        highlightText(nameElem, value);
                        highlightText(cabinetElem, value);
                        visibleCount++;
                    } else { 
                        $(this).hide(); 
                    }
                });
                
                if (visibleCount === 0 && $('.project-row').length > 0) {
                    if ($('#noSearchMatch').length === 0) {
                        $('#projectTableBody').append(
                            '<tr id="noSearchMatch"><td colspan="5" class="text-center py-5">' +
                            '<div class="text-secondary opacity-50 mb-3"><i class="bi bi-search" style="font-size: 2.5rem;"></i></div>' +
                            '<h6 class="fw-bold text-secondary">ไม่พบข้อมูลที่ตรงกับ "'+ value +'"</h6>' +
                            '</td></tr>'
                        );
                    } else {
                        $('#noSearchMatch').show().find('h6').text('ไม่พบข้อมูลที่ตรงกับ "' + value + '"');
                    }
                } else {
                    $('#noSearchMatch').hide();
                }
            });
            
            $('#exportModal').on('click', function(e) {
                if (e.target === this) closeExportModal();
            });
        });
    </script>
</body>
</html>