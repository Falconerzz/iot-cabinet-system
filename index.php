<?php
require_once 'config.php'; check_login();

// ฟังก์ชันจัดรูปแบบเบอร์โทร xxx-xxx-xxxx
function formatPhoneNumber($phone) {
    $phone = preg_replace('/[^0-9]/', '',$phone);
    if (strlen($phone) === 10) { return preg_replace("/^(\d{3})(\d{3})(\d{4})$/", "$1-$2-$3", $phone); }
    return $phone;
}

$stat =$conn->query("
    SELECT COUNT(*) as total,
    SUM(CASE WHEN is_delivered = 1 THEN 1 ELSE 0 END) as delivered,
    SUM(CASE WHEN is_delivered = 0 THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN region = 'ภาคเหนือ' THEN 1 ELSE 0 END) as north,
    SUM(CASE WHEN region = 'ภาคอีสาน' THEN 1 ELSE 0 END) as isan,
    SUM(CASE WHEN region = 'ภาคกลาง' THEN 1 ELSE 0 END) as central,
    SUM(CASE WHEN region = 'ภาคใต้' THEN 1 ELSE 0 END) as south
    FROM projects
")->fetch_assoc();

$result =$conn->query("SELECT * FROM projects ORDER BY id DESC");
$projects_for_map = []; while($row =$result->fetch_assoc()) { $projects_for_map[] =$row; }
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>หน้าแรก & แผนที่ - Sync Vibe</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet.fullscreen@3.1.4/Control.FullScreen.css" />
    <script src="https://unpkg.com/leaflet.fullscreen@3.1.4/Control.FullScreen.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <link rel="stylesheet" href="style.css">
    <style>
        #thailandMap { width: 100%; height: 600px; border-radius: 20px; z-index: 1; border: 1px solid #e2e8f0; }
        .pulse-marker { width: 20px; height: 20px; border-radius: 50%; border: 3px solid #ffffff; box-shadow: 0 0 0 rgba(79, 70, 229, 0.5); animation: pulse 2s infinite; }
        .marker-red { background-color: #ef4444; animation: pulse-red 2s infinite; }
        .marker-green { background-color: #10b981; animation: pulse-green 2s infinite; }
        @keyframes pulse-red { 0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); } 70% { box-shadow: 0 0 0 15px rgba(239, 68, 68, 0); } 100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); } }
        @keyframes pulse-green { 0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); } 70% { box-shadow: 0 0 0 15px rgba(16, 185, 129, 0); } 100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); } }
        
        .leaflet-popup-content-wrapper { border-radius: 16px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); font-family: 'Prompt', sans-serif;}
        .project-card { border-radius: 20px; transition: all 0.3s ease; border: 1px solid #e2e8f0; background: #ffffff; }
        .project-card:hover { transform: translateY(-5px); box-shadow: 0 15px 35px rgba(99, 102, 241, 0.1); border-color: #818cf8; }
        .info-box { background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 12px; padding: 10px; display: flex; flex-direction: column; justify-content: center; }
        
        /* ปุ่มหน้า Index */
        .btn-modern { 
            background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%); 
            color: white; border: none; border-radius: 12px; padding: 0.8rem; font-weight: 600; 
            transition: all 0.2s ease; box-shadow: 0 4px 12px rgba(79, 70, 229, 0.2); 
        }
        .btn-modern:hover { 
            background: linear-gradient(135deg, #4338ca 0%, #2563eb 100%); 
            color: white; transform: translateY(-2px); box-shadow: 0 6px 15px rgba(79, 70, 229, 0.3); 
        }

        .search-container { background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 4px 15px rgba(0,0,0,0.02); }
        .search-input { background: transparent !important; border: none !important; box-shadow: none !important; font-size: 1rem; }
        .search-select { background-color: #f8fafc !important; border: none !important; border-radius: 12px !important; font-weight: 600; color: #475569;}
        mark.search-highlight { background-color: #fde047; color: #0f172a; padding: 0.1em 0.25em; border-radius: 4px; font-weight: 700; box-shadow: 0 2px 4px rgba(253, 224, 71, 0.4); }

        @media (max-width: 768px) { #thailandMap { height: 400px; } }
    </style>
</head>
<body>
    <div class="app-container">
        <?php include 'sidebar.php'; ?>
        <main class="main-content">
            <div class="d-flex justify-content-between align-items-end mb-4 animate-fade-up" style="animation-delay: 0.1s;">
                <div><h2 class="fw-bold m-0 text-dark display-6">ภาพรวมโครงการตู้ IoT</h2><p class="text-secondary m-0 mt-2 fs-6">สถิติและแผนที่จุดติดตั้งทั่วประเทศไทย</p></div>
                <a href="add_project.php" class="btn btn-modern rounded-pill px-4"><i class="bi bi-plus-lg me-2"></i>สร้างโครงการใหม่</a>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-4 col-sm-6 animate-fade-up" style="animation-delay: 0.2s;"><div class="stat-card"><div class="stat-icon bg-primary bg-opacity-10 text-primary-custom"><i class="bi bi-hdd-network"></i></div><div><p class="text-secondary fw-bold mb-1 small">จำนวนตู้ทั้งหมด</p><h3 class="fw-bold m-0 text-dark"><?= intval($stat['total']) ?> <span class="fs-6 text-secondary fw-normal">ตู้</span></h3></div></div></div>
                <div class="col-md-4 col-sm-6 animate-fade-up" style="animation-delay: 0.3s;"><div class="stat-card"><div class="stat-icon bg-success bg-opacity-10 text-success"><i class="bi bi-check-circle-fill"></i></div><div><p class="text-secondary fw-bold mb-1 small">ส่งงานเรียบร้อยแล้ว</p><h3 class="fw-bold m-0 text-dark"><?= intval($stat['delivered']) ?> <span class="fs-6 text-secondary fw-normal">ตู้</span></h3></div></div></div>
                <div class="col-md-4 col-sm-6 animate-fade-up" style="animation-delay: 0.4s;"><div class="stat-card"><div class="stat-icon bg-warning bg-opacity-10 text-warning"><i class="bi bi-hourglass-split"></i></div><div><p class="text-secondary fw-bold mb-1 small">อยู่ระหว่างดำเนินการ / รอส่งงาน</p><h3 class="fw-bold m-0 text-dark"><?= intval($stat['pending']) ?> <span class="fs-6 text-secondary fw-normal">ตู้</span></h3></div></div></div>
            </div>

            <div class="row g-4 mb-5 animate-fade-up" style="animation-delay: 0.5s;">
                <div class="col-6 col-md-3"><div class="stat-card p-3"><div class="stat-icon bg-info bg-opacity-10 text-info" style="width:45px;height:45px;font-size:1.2rem;"><i class="bi bi-compass"></i></div><div><p class="text-secondary fw-bold mb-0" style="font-size:0.75rem;">ภาคเหนือ</p><h5 class="fw-bold m-0 text-dark"><?= intval($stat['north']) ?></h5></div></div></div>
                <div class="col-6 col-md-3"><div class="stat-card p-3"><div class="stat-icon bg-info bg-opacity-10 text-info" style="width:45px;height:45px;font-size:1.2rem;"><i class="bi bi-compass"></i></div><div><p class="text-secondary fw-bold mb-0" style="font-size:0.75rem;">ภาคอีสาน</p><h5 class="fw-bold m-0 text-dark"><?= intval($stat['isan']) ?></h5></div></div></div>
                <div class="col-6 col-md-3"><div class="stat-card p-3"><div class="stat-icon bg-info bg-opacity-10 text-info" style="width:45px;height:45px;font-size:1.2rem;"><i class="bi bi-compass"></i></div><div><p class="text-secondary fw-bold mb-0" style="font-size:0.75rem;">ภาคกลาง</p><h5 class="fw-bold m-0 text-dark"><?= intval($stat['central']) ?></h5></div></div></div>
                <div class="col-6 col-md-3"><div class="stat-card p-3"><div class="stat-icon bg-info bg-opacity-10 text-info" style="width:45px;height:45px;font-size:1.2rem;"><i class="bi bi-compass"></i></div><div><p class="text-secondary fw-bold mb-0" style="font-size:0.75rem;">ภาคใต้</p><h5 class="fw-bold m-0 text-dark"><?= intval($stat['south']) ?></h5></div></div></div>
            </div>

            <div class="card border-0 p-3 mb-5 shadow-sm animate-fade-up rounded-4" style="animation-delay: 0.6s;">
                <div id="thailandMap"></div>
            </div>

            <h4 class="fw-bold text-dark mb-3 animate-fade-up" style="animation-delay: 0.7s;"><i class="bi bi-card-list text-primary me-2"></i>รายการตู้และโครงการ</h4>
            <div class="search-container p-2 mb-4 animate-fade-up" style="animation-delay: 0.75s;">
                <div class="row g-2 align-items-center">
                    <div class="col-md-3">
                        <select id="searchField" class="form-select search-select">
                            <option value="all">🔍 ค้นหาจากทุกหัวข้อ</option>
                            <option value="name">📌 ค้นหาชื่อโครงการ</option>
                            <option value="gateway">🌐 ค้นหา Gateway Code</option>
                            <option value="phone">📞 ค้นหาเบอร์โทร / S/N</option>
                        </select>
                    </div>
                    <div class="col-md-6 border-start border-end border-light px-3">
                        <div class="d-flex align-items-center"><i class="bi bi-search text-secondary mx-2"></i><input type="text" id="searchInput" class="form-control search-input" placeholder="พิมพ์คำค้นหาที่คุณต้องการ..."></div>
                    </div>
                    <div class="col-md-3">
                        <select id="filterRegion" class="form-select search-select">
                            <option value="all">📍 ทุกภูมิภาค (หมวดหมู่)</option>
                            <option value="ภาคเหนือ">ภาคเหนือ</option>
                            <option value="ภาคอีสาน">ภาคตะวันออกเฉียงเหนือ (อีสาน)</option>
                            <option value="ภาคกลาง">ภาคกลางและตะวันออก</option>
                            <option value="ภาคใต้">ภาคใต้</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="row g-4 animate-fade-up" id="projectListContainer" style="animation-delay: 0.8s;">
                <?php foreach($projects_for_map as$row): 
                    $fmt_phone = formatPhoneNumber($row['sim_number'] ?? '');
                ?>
                    <div class="col-xl-4 col-lg-6 col-md-6 project-card-item" 
                         data-name="<?= strtolower(htmlspecialchars($row['project_name'])) ?>" 
                         data-cabinet="<?= strtolower(htmlspecialchars($row['cabinet_id'])) ?>" 
                         data-gateway="<?= strtolower(htmlspecialchars($row['gateway_code'] ?? '')) ?>" 
                         data-phone="<?= strtolower(htmlspecialchars($row['sim_number'] ?? '')) ?>"
                         data-phonefmt="<?= strtolower(htmlspecialchars($fmt_phone)) ?>"
                         data-serial="<?= strtolower(htmlspecialchars($row['sim_serial'] ?? '')) ?>"
                         data-region="<?= htmlspecialchars($row['region'] ?? '') ?>">
                        
                        <div class="project-card shadow-sm h-100 d-flex flex-column overflow-hidden">
                            <div class="p-4 pb-0">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <span class="badge <?= $row['is_delivered'] ? 'bg-success bg-opacity-10 text-success border border-success' : 'bg-warning bg-opacity-10 text-dark border border-warning' ?> px-2 py-1"><?= $row['is_delivered'] ? '<i class="bi bi-check-circle-fill me-1"></i> ส่งงานแล้ว' : '<i class="bi bi-hourglass-split me-1"></i> รอส่งงาน' ?></span>
                                    <span class="badge bg-light text-secondary border px-2 py-1"><i class="bi bi-geo-alt-fill me-1 text-danger"></i> <?= $row['region'] ?: 'ไม่ระบุ' ?></span>
                                </div>
                                <h5 class="fw-bold text-dark mt-3 mb-1 text-truncate highlight-target-name" data-orig="<?= htmlspecialchars($row['project_name']) ?>" title="<?= htmlspecialchars($row['project_name']) ?>"><?= htmlspecialchars($row['project_name']) ?></h5>
                                
                                <!-- 🌟 แก้ไขจุดที่พิมพ์ผิด: เปลี่ยนจาก $project เป็น $row -->
                                <p class="text-secondary small mb-3">Gateway Code: <strong class="text-primary fs-6 highlight-target-gw" data-orig="<?= htmlspecialchars($row['gateway_code'] ?: 'ยังไม่ระบุ') ?>"><?= htmlspecialchars($row['gateway_code'] ?: 'ยังไม่ระบุ') ?></strong></p>
                                
                            </div>
                            
                            <div class="p-4 pt-0 flex-grow-1">
                                <div class="row g-2 mb-3">
                                    <div class="col-6"><div class="info-box h-100"><span class="d-block text-secondary mb-1" style="font-size: 0.75rem;"><i class="bi bi-sim-fill me-1"></i>เบอร์ซิมการ์ด</span><span class="fw-bold text-dark small text-truncate d-block highlight-target-phone" data-orig="<?= htmlspecialchars($fmt_phone ?: 'ไม่มีซิม') ?>"><?= htmlspecialchars($fmt_phone ?: 'ไม่มีซิม') ?></span></div></div>
                                    <div class="col-6"><div class="info-box h-100"><span class="d-block text-secondary mb-1" style="font-size: 0.75rem;"><i class="bi bi-terminal me-1"></i>ลงโปรแกรม</span><span class="fw-bold <?= $row['is_programmed'] ? 'text-success' : 'text-danger' ?> small"><i class="bi <?= $row['is_programmed'] ? 'bi-check-circle-fill' : 'bi-x-circle-fill' ?>"></i> <?= $row['is_programmed'] ? 'เรียบร้อย' : 'ยังไม่ลง' ?></span></div></div>
                                </div>
                                <?php if($row['project_tags']): ?>
                                    <div class="d-flex flex-wrap gap-1">
                                        <?php foreach(explode(',', $row['project_tags']) as$tag): ?><span class="badge bg-light text-secondary border px-2 py-1" style="font-size: 0.7rem;">#<?= htmlspecialchars(trim($tag)) ?></span><?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="p-3 border-top border-light mt-auto text-center">
                                <a href="view_project.php?id=<?= $row['id'] ?>" class="btn btn-modern w-100 d-flex justify-content-center align-items-center"><i class="bi bi-search me-2"></i> ดูรายละเอียดข้อมูล</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                <div id="noResultMsg" class="col-12 text-center py-5" style="display: none;"><div class="bg-secondary bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;"><i class="bi bi-search text-secondary fs-1"></i></div><h5 class="fw-bold text-dark">ไม่พบข้อมูลที่ค้นหา</h5><p class="text-secondary">ลองเปลี่ยนคำค้นหา หรือเลือกตัวกรองภูมิภาคใหม่ดูนะครับ</p></div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/bootstrap.bundle.min.js"></script>
    <script>
        const projectsData = <?= json_encode($projects_for_map) ?>;
        
        const map = L.map('thailandMap', { 
            fullscreenControl: true, 
            fullscreenControlOptions: { position: 'topleft' },
            scrollWheelZoom: false
        }).setView([13.5000, 100.9925], 6);
        map.once('focus', function() { map.scrollWheelZoom.enable(); });
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap' }).addTo(map);

        projectsData.forEach(proj => {
            if (proj.latitude && proj.longitude) {
                const colorClass = proj.is_delivered == 1 ? 'marker-green' : 'marker-red';
                const customIcon = L.divIcon({ className: `pulse-marker ${colorClass}`, iconSize: [20, 20], iconAnchor: [10, 10] });
                const marker = L.marker([proj.latitude, proj.longitude], {icon: customIcon}).addTo(map);
                
                const statusBadge = proj.is_delivered == 1 ? '<span class="badge bg-success">ส่งงานแล้ว</span>' : '<span class="badge bg-warning text-dark">ยังไม่ส่งงาน</span>';
                const gwCode = proj.gateway_code ? proj.gateway_code : 'ยังไม่ระบุ';
                const popupContent = `
                    <div class="text-center p-1">
                        <h6 class="fw-bold text-primary mb-1">${proj.project_name}</h6>
                        <div class="mb-2">${statusBadge}</div>
                        <p class="small text-secondary mb-3">Gateway Code: <strong class="text-dark">${gwCode}</strong></p>
                        <a href="view_project.php?id=${proj.id}" class="btn btn-sm btn-primary rounded-pill w-100 fw-bold" style="background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%); border:none;">ดูรายละเอียด</a>
                    </div>
                `;
                marker.bindPopup(popupContent);
            }
        });

        function escapeRegExp(string) { return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'); }
        function applyHighlight(element, searchText) {
            let origText = element.attr('data-orig');
            if (!origText) return;
            if (!searchText) { element.html(origText); return; }
            let regex = new RegExp("(" + escapeRegExp(searchText) + ")", "gi");
            let newHtml = origText.replace(regex, "<mark class='search-highlight'>$1</mark>");
            element.html(newHtml);
        }

        $(document).ready(function() {
            function filterProjects() {
                let searchField = $('#searchField').val();
                let searchText = $('#searchInput').val().toLowerCase().trim();
                let filterRegion = $('#filterRegion').val();
                let visibleCount = 0;

                $('.project-card-item').each(function() {
                    let card = $(this);
                    let name = card.data('name'); 
                    let gateway = card.data('gateway'); 
                    let phone = card.data('phone'); 
                    let phoneFmt = card.data('phonefmt');
                    let serial = card.data('serial');
                    let region = card.data('region');
                    
                    let textMatch = false;
                    
                    if (searchField === 'all') { textMatch = name.includes(searchText) || gateway.includes(searchText) || phone.includes(searchText) || phoneFmt.includes(searchText) || serial.includes(searchText); } 
                    else if (searchField === 'name') { textMatch = name.includes(searchText); } 
                    else if (searchField === 'gateway') { textMatch = gateway.includes(searchText); } 
                    else if (searchField === 'phone') { textMatch = phone.includes(searchText) || phoneFmt.includes(searchText) || serial.includes(searchText); }

                    let regionMatch = (filterRegion === 'all' || region === filterRegion);

                    if (textMatch && regionMatch) { 
                        card.show(); visibleCount++; 
                        let nameEl = card.find('.highlight-target-name');
                        let gwEl = card.find('.highlight-target-gw');
                        let phoneEl = card.find('.highlight-target-phone');

                        applyHighlight(nameEl, ''); applyHighlight(gwEl, ''); applyHighlight(phoneEl, '');
                        if (searchText !== '') {
                            if (searchField === 'all' || searchField === 'name') applyHighlight(nameEl, searchText);
                            if (searchField === 'all' || searchField === 'gateway') applyHighlight(gwEl, searchText);
                            if (searchField === 'all' || searchField === 'phone') applyHighlight(phoneEl, searchText);
                        }
                    } else { card.hide(); }
                });
                if(visibleCount === 0) { $('#noResultMsg').show(); } else { $('#noResultMsg').hide(); }
            }
            $('#searchInput').on('keyup', filterProjects); $('#searchField, #filterRegion').on('change', filterProjects);
        });
    </script>
</body>
</html>