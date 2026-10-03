<?php
require_once 'config.php'; check_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $source_eq_id = intval($_POST['source_eq_id']);
    $target_project_id = intval($_POST['target_project_id']);
    $target_cabinet_type = $_POST['target_cabinet_type'];
    $target_eq_id = $_POST['target_eq_id'] ?? 'NONE'; // อาจจะเป็น 'NONE' หรือ ID ชิ้นที่ต้องสลับ

    // 1. ดึงข้อมูลอุปกรณ์ต้นทาง
    $stmt = $conn->prepare("SELECT e.*, p.project_name as old_project_name FROM equipments e JOIN projects p ON e.project_id = p.id WHERE e.id = ?");
    $stmt->bind_param("i", $source_eq_id); $stmt->execute(); 
    $src_eq = $stmt->get_result()->fetch_assoc(); $stmt->close();

    // 2. ดึงชื่อโครงการปลายทาง
    $p_stmt = $conn->prepare("SELECT project_name FROM projects WHERE id = ?");
    $p_stmt->bind_param("i", $target_project_id); $p_stmt->execute(); 
    $target_project = $p_stmt->get_result()->fetch_assoc(); $p_stmt->close();

    if ($src_eq && $target_project) {
        $old_proj_id = $src_eq['project_id']; 
        $old_proj_name = $src_eq['old_project_name']; 
        $old_cab_type = $src_eq['cabinet_type'];
        
        $cab_label = ($old_cab_type === 'control_iot') ? 'ตู้ Control IoT' : 'ตู้ DC-IoT';

        if ($target_eq_id !== 'NONE') {
            // == โหมดสลับ (SWAP) ==
            $target_eq_id_int = intval($target_eq_id);
            $t_eq_stmt = $conn->prepare("SELECT * FROM equipments WHERE id = ?"); 
            $t_eq_stmt->bind_param("i", $target_eq_id_int); $t_eq_stmt->execute(); 
            $tgt_eq = $t_eq_stmt->get_result()->fetch_assoc(); $t_eq_stmt->close();

            if ($tgt_eq) {
                // สลับชิ้น 1 (ย้ายเข้าใหม่)
                $u1 = $conn->prepare("UPDATE equipments SET project_id = ?, cabinet_type = ? WHERE id = ?"); 
                $u1->bind_param("isi", $target_project_id, $target_cabinet_type, $source_eq_id); $u1->execute(); $u1->close();
                // สลับชิ้น 2 (ดึงกลับ)
                $u2 = $conn->prepare("UPDATE equipments SET project_id = ?, cabinet_type = ? WHERE id = ?"); 
                $u2->bind_param("isi", $old_proj_id, $old_cab_type, $target_eq_id_int); $u2->execute(); $u2->close();
                
                $log_msg = "🔄 สลับอุปกรณ์คู่อัตโนมัติ: นำ '{$src_eq['equipment_name']}' ไปใส่ '{$target_project['project_name']}' และนำ '{$tgt_eq['equipment_name']}' กลับมาใส่ '{$old_proj_name}'";
                log_activity($conn, $target_project_id, 'TRANSFER_EQUIPMENT', $log_msg); 
                log_activity($conn, $old_proj_id, 'TRANSFER_EQUIPMENT', $log_msg);
            }
        } else {
            // == โหมดย้ายปกติ (TRANSFER) ==
            $update_stmt = $conn->prepare("UPDATE equipments SET project_id = ?, cabinet_type = ? WHERE id = ?"); 
            $update_stmt->bind_param("isi", $target_project_id, $target_cabinet_type, $source_eq_id); $update_stmt->execute(); $update_stmt->close();
            
            $log_msg = "➡️ โยกย้าย '{$src_eq['equipment_name']}' จากโครงการ '{$old_proj_name}' ไปยัง '{$target_project['project_name']}'";
            log_activity($conn, $target_project_id, 'TRANSFER_EQUIPMENT', $log_msg); 
            log_activity($conn, $old_proj_id, 'TRANSFER_EQUIPMENT', $log_msg);
        }
    }
    header("Location: view_project.php?id=" . $target_project_id); exit();
}
?>