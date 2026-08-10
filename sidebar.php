<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<div class="sidebar d-flex flex-column p-3 text-white border-end border-secondary shadow">
    <a href="index.php" class="d-flex align-items-center mb-4 me-md-auto text-info text-decoration-none px-2">
        <i class="bi bi-cpu-fill fs-3 me-2"></i>
        <span class="fs-5 fw-bold">IoT Cabinet System</span>
    </a>
    <hr class="border-secondary mt-0 mb-3">
    
    <ul class="nav nav-pills flex-column mb-auto gap-1">
        <li class="nav-item">
            <a href="index.php" class="nav-link <?= $current_page == 'index.php' ? 'active bg-primary' : 'text-white' ?>">
                <i class="bi bi-grid-fill me-2"></i> โครงการทั้งหมด
            </a>
        </li>
        <li class="nav-item">
            <a href="add_project.php" class="nav-link <?= $current_page == 'add_project.php' ? 'active bg-primary' : 'text-white' ?>">
                <i class="bi bi-plus-circle-fill me-2"></i> เพิ่มโครงการใหม่
            </a>
        </li>
        <li class="nav-item">
            <a href="transfer_equipment.php" class="nav-link <?= $current_page == 'transfer_equipment.php' ? 'active bg-success' : 'text-white' ?>">
                <i class="bi bi-arrow-left-right me-2"></i> โยกย้าย/สลับอุปกรณ์
            </a>
        </li>
        <li class="nav-item">
            <a href="manage_master_equipments.php" class="nav-link <?= $current_page == 'manage_master_equipments.php' ? 'active bg-info text-dark fw-bold' : 'text-white' ?>">
                <i class="bi bi-card-checklist me-2"></i> พจนานุกรมอุปกรณ์
            </a>
        </li>
        <li class="nav-item">
            <a href="manage_templates.php" class="nav-link <?= $current_page == 'manage_templates.php' ? 'active bg-warning text-dark fw-bold' : 'text-white' ?>">
                <i class="bi bi-layers-fill me-2"></i> แม่แบบอุปกรณ์
            </a>
        </li>
        <li class="nav-item">
            <a href="logs.php" class="nav-link <?= $current_page == 'logs.php' ? 'active bg-info text-dark fw-bold' : 'text-white' ?>">
                <i class="bi bi-journal-text me-2"></i> ประวัติ Logs
            </a>
        </li>
    </ul>
    
    <hr class="border-secondary mb-3">
    <div class="px-2">
        <div class="small text-secondary mb-1">เข้าสู่ระบบในชื่อ:</div>
        <div class="fw-bold text-white mb-2"><i class="bi bi-person-circle me-1 text-info"></i> <?= htmlspecialchars($_SESSION['username'] ?? 'User') ?></div>
        <a href="logout.php" class="btn btn-outline-danger btn-sm w-100"><i class="bi bi-door-closed me-1"></i> ออกจากระบบ</a>
    </div>
</div>