<?php
require_once 'config.php'; check_login();

$msg = ''; $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $eq_name = clean_equipment_name($_POST['equipment_name'] ?? '');
        if (!empty($eq_name)) {
            $stmt = $conn->prepare("INSERT INTO master_equipments (equipment_name) VALUES (?)"); 
            $stmt->bind_param("s", $eq_name);
            if ($stmt->execute()) { 
                $msg = "เพิ่ม '{$eq_name}' เข้าสู่พจนานุกรมสำเร็จ"; 
            } else { 
                $error = "มีชื่อนี้ในระบบแล้ว"; 
            } 
            $stmt->close();
        }
    } elseif ($action === 'edit') {
        $id = intval($_POST['id']);
        $new_name = clean_equipment_name($_POST['new_equipment_name'] ?? '');
        if (!empty($new_name)) {
            $stmt = $conn->prepare("UPDATE master_equipments SET equipment_name = ? WHERE id = ?"); 
            $stmt->bind_param("si", $new_name, $id);
            if ($stmt->execute()) { 
                $msg = "แก้ไขชื่ออุปกรณ์สำเร็จ"; 
            } else { 
                $error = "ไม่สามารถแก้ไขได้ (อาจมีชื่อนี้ซ้ำในระบบ)"; 
            }
            $stmt->close();
        }
    } elseif ($action === 'delete') {
        $id = intval($_POST['id']); 
        $stmt = $conn->prepare("DELETE FROM master_equipments WHERE id = ?"); 
        $stmt->bind_param("i", $id); 
        $stmt->execute(); 
        $stmt->close(); 
        $msg = "ลบข้อมูลสำเร็จ";
    }
}

$masters = $conn->query("SELECT * FROM master_equipments ORDER BY equipment_name ASC");
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>พจนานุกรมชื่ออุปกรณ์ - Sync Vibe</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <link rel="stylesheet" href="style.css">
    <style>
        .edit-input-group { display: none; }
        .edit-mode .edit-input-group { display: flex; }
        .edit-mode .display-group { display: none; }
    </style>
</head>
<body>
    <div class="app-container">
        <?php include 'sidebar.php'; ?>
        <main class="main-content">
            <div class="mb-5 animate-fade-up">
                <h2 class="fw-bold m-0 text-gradient display-6">พจนานุกรมชื่ออุปกรณ์มาตรฐาน</h2>
                <p class="text-secondary m-0 mt-2">จัดการคำที่ใช้ในตัวเลือก Dropdown ของระบบทั้งหมด</p>
            </div>
            
            <?php if ($msg): ?>
                <div class="alert alert-success shadow-sm rounded-4 border border-success border-opacity-25 bg-success bg-opacity-10 text-success fw-bold d-flex align-items-center mb-4 animate-fade-up">
                    <i class="bi bi-check-circle-fill fs-4 me-3"></i> <?= htmlspecialchars($msg) ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger shadow-sm rounded-4 border border-danger border-opacity-25 bg-danger bg-opacity-10 text-danger fw-bold d-flex align-items-center mb-4 animate-fade-up">
                    <i class="bi bi-exclamation-triangle-fill fs-4 me-3"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <div class="card card-custom p-4 mb-4 animate-fade-up" style="animation-delay: 0.1s;">
                <h5 class="fw-bold text-dark mb-4"><i class="bi bi-plus-circle-fill text-primary-custom me-2"></i>เพิ่มชื่ออุปกรณ์ใหม่</h5>
                <form action="manage_master_equipments.php" method="POST" class="row g-3 align-items-center">
                    <input type="hidden" name="action" value="add">
                    <div class="col-md-9">
                        <input type="text" name="equipment_name" class="form-control" placeholder="พิมพ์ชื่ออุปกรณ์ที่ต้องการเพิ่ม เช่น IRT_Model_2" required>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary w-100 rounded-pill py-2 shadow-sm"><i class="bi bi-save me-1"></i> บันทึกข้อมูล</button>
                    </div>
                </form>
            </div>

            <div class="card card-custom p-4 animate-fade-up" style="animation-delay: 0.2s;">
                <h5 class="fw-bold text-dark mb-4"><i class="bi bi-card-list text-info me-2"></i>รายการทั้งหมดในพจนานุกรม</h5>
                <div class="table-responsive">
                    <table class="table table-custom table-hover align-middle">
                        <thead>
                            <tr>
                                <th width="10%">#</th>
                                <th width="60%">ชื่ออุปกรณ์มาตรฐาน (ตรงตัว)</th>
                                <th class="text-end" width="30%">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($masters->num_rows > 0): ?>
                                <?php $i=1; while($row = $masters->fetch_assoc()): ?>
                                    <tr id="row_<?= $row['id'] ?>">
                                        <td><?= $i++ ?></td>
                                        
                                        <!-- กลุ่มแสดงผลปกติ -->
                                        <td class="display-group">
                                            <span class="fw-bold text-primary-custom fs-6"><?= htmlspecialchars($row['equipment_name']) ?></span>
                                        </td>
                                        <td class="text-end display-group">
                                            <button type="button" class="btn btn-sm btn-outline-warning text-warning border-warning bg-warning bg-opacity-10 rounded-pill px-3 me-2" onclick="toggleEdit(<?= $row['id'] ?>)">
                                                <i class="bi bi-pencil-square"></i> แก้ไข
                                            </button>
                                            <form action="manage_master_equipments.php" method="POST" onsubmit="return confirm('ยืนยันลบชื่ออุปกรณ์นี้ใช่หรือไม่?');" style="display:inline;">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger border-0 bg-danger bg-opacity-10 text-danger rounded-pill px-3">
                                                    <i class="bi bi-trash"></i> ลบ
                                                </button>
                                            </form>
                                        </td>

                                        <!-- กลุ่มกล่องแก้ไข (ซ่อนอยู่) -->
                                        <td colspan="2" class="edit-input-group">
                                            <form action="manage_master_equipments.php" method="POST" class="d-flex w-100 gap-2 align-items-center">
                                                <input type="hidden" name="action" value="edit">
                                                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                                <input type="text" name="new_equipment_name" class="form-control form-control-sm" value="<?= htmlspecialchars($row['equipment_name']) ?>" required>
                                                <button type="submit" class="btn btn-sm btn-success rounded-pill px-3 shadow-sm text-white"><i class="bi bi-check-lg"></i> บันทึก</button>
                                                <button type="button" class="btn btn-sm btn-light border rounded-pill px-3 text-secondary" onclick="toggleEdit(<?= $row['id'] ?>)"><i class="bi bi-x-lg"></i> ยกเลิก</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="3" class="text-center text-secondary py-4">ยังไม่มีข้อมูลในพจนานุกรม</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/bootstrap.bundle.min.js"></script>
    <script>
        // ฟังก์ชันสลับโหมด แก้ไข/แสดงผล
        function toggleEdit(id) {
            const row = document.getElementById('row_' + id);
            row.classList.toggle('edit-mode');
        }
    </script>
</body>
</html>