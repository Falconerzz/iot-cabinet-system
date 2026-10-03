<div class="sidebar">
    <div class="logo">
        <div class="bg-primary rounded px-2 py-1 me-2 d-flex align-items-center justify-content-center"><i class="bi bi-broadcast text-white"></i></div>
        <span class="text-white">Sync Vibe</span>
    </div>
    
    <nav class="nav flex-column mb-auto">
        <div class="nav-category">Main Menu</div>
        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>" href="index.php">
            <i class="bi bi-grid-1x2-fill"></i> ภาพรวมโครงการ
        </a>
        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'add_project.php' ? 'active' : '' ?>" href="add_project.php">
            <i class="bi bi-plus-circle-fill"></i> สร้างโครงการใหม่
        </a>
        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'transfer_equipment.php' ? 'active' : '' ?>" href="transfer_equipment.php">
            <i class="bi bi-arrow-left-right"></i> โยกย้ายอุปกรณ์
        </a>
        
        <div class="nav-category mt-3">Settings & Data</div>
        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'manage_master_equipments.php' ? 'active' : '' ?>" href="manage_master_equipments.php">
            <i class="bi bi-journal-bookmark-fill"></i> พจนานุกรมชื่อ
        </a>
        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'manage_templates.php' ? 'active' : '' ?>" href="manage_templates.php">
            <i class="bi bi-layers-fill"></i> จัดการแม่แบบอุปกรณ์
        </a>

        <div class="nav-category mt-3">Reports & System</div>
        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'export_list.php' ? 'active' : '' ?>" href="export_list.php">
            <i class="bi bi-file-earmark-excel-fill"></i> ออกใบส่งสินค้า
        </a>
        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'logs.php' ? 'active' : '' ?>" href="logs.php">
            <i class="bi bi-clock-history"></i> ประวัติกิจกรรม (Logs)
        </a>
        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'add_member.php' ? 'active' : '' ?>" href="add_member.php">
            <i class="bi bi-person-plus-fill"></i> จัดการสมาชิก
        </a>
    </nav>
    
    <div class="user-profile mt-auto">
        <div class="d-flex align-items-center mb-3">
            <div class="user-avatar bg-white text-primary d-flex align-items-center justify-content-center me-3 shadow-sm fw-bold">
                <?= isset($_SESSION['username']) ? strtoupper(substr($_SESSION['username'], 0, 1)) : 'A' ?>
            </div>
            <div class="text-truncate">
                <small class="text-secondary fw-bold d-block" style="font-size: 0.7rem; text-transform: uppercase;">สถานะ: ผู้ดูแลระบบ</small>
                <span class="fw-bold text-white text-truncate d-block fs-6"><?= isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Admin' ?></span>
            </div>
        </div>
        <a href="logout.php" class="btn btn-danger w-100 rounded-pill fw-bold btn-sm d-flex align-items-center justify-content-center gap-2">
            <i class="bi bi-box-arrow-right"></i> ออกจากระบบ
        </a>
    </div>
</div>

<style>
/* 🌟 Sidebar Dark Theme */
.sidebar {
    width: 280px;
    height: 100vh;
    background: #0f172a; /* Slate 900 */
    border-right: 1px solid #1e293b;
    position: fixed;
    top: 0;
    left: 0;
    display: flex;
    flex-direction: column;
    z-index: 1000;
    box-shadow: 4px 0 25px rgba(0,0,0,0.1);
    overflow-y: auto; 
    overflow-x: hidden;
}

.sidebar::-webkit-scrollbar { width: 4px; }
.sidebar::-webkit-scrollbar-thumb { background: #334155; border-radius: 10px; }

.sidebar .logo {
    padding: 1.5rem;
    font-size: 1.3rem;
    font-weight: 800;
    color: #ffffff;
    display: flex;
    align-items: center;
    border-bottom: 1px solid #1e293b;
    letter-spacing: 0.5px;
    position: sticky;
    top: 0;
    background: #0f172a;
    z-index: 10;
}

.sidebar .nav {
    padding: 1.25rem 1rem;
    gap: 0.2rem;
}

.nav-category {
    font-size: 0.7rem;
    font-weight: 800;
    text-transform: uppercase;
    color: #475569;
    letter-spacing: 1px;
    margin-bottom: 0.5rem;
    padding-left: 0.5rem;
}

.sidebar .nav-link {
    color: #94a3b8;
    padding: 0.75rem 1rem;
    border-radius: 12px;
    font-weight: 600;
    font-size: 0.9rem;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
}

.sidebar .nav-link i {
    font-size: 1.15rem;
    margin-right: 14px;
    width: 20px;
    text-align: center;
    transition: all 0.2s;
    color: #64748b;
}

.sidebar .nav-link:hover {
    background-color: #1e293b;
    color: #f8fafc;
    transform: translateX(4px);
}

.sidebar .nav-link:hover i {
    color: #94a3b8;
}

.sidebar .nav-link.active {
    background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%);
    color: #ffffff;
    box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
}

.sidebar .nav-link.active i {
    color: #ffffff;
}

.user-profile {
    padding: 1.25rem;
    border-top: 1px solid #1e293b;
    background: #0f172a;
    margin-top: auto; 
    position: sticky;
    bottom: 0;
}

.user-avatar {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    font-size: 1.2rem;
    flex-shrink: 0; 
}

.main-content {
    margin-left: 280px;
    padding: 2rem;
    min-height: 100vh;
}

@media (max-width: 991.98px) {
    .sidebar { transform: translateX(-100%); transition: transform 0.3s; }
    .sidebar.show { transform: translateX(0); }
    .main-content { margin-left: 0; }
}
</style>