<?php
require_once 'config.php'; check_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $project_id = intval($_POST['project_id']);
        $stmt = $conn->prepare("SELECT project_name FROM projects WHERE id = ?"); $stmt->bind_param("i", $project_id); $stmt->execute(); $p = $stmt->get_result()->fetch_assoc(); $p_name = $p ? $p['project_name'] : 'Unknown'; $stmt->close();
        $del_stmt = $conn->prepare("DELETE FROM projects WHERE id = ?"); $del_stmt->bind_param("i", $project_id); $del_stmt->execute(); $del_stmt->close();
        log_activity($conn, NULL, 'DELETE_PROJECT', "ลบโครงการ '{$p_name}' ออกจากระบบ");
        header("Location: index.php"); exit();
    }

    $project_id = intval($_POST['project_id']);
    $project_name = clean_equipment_name($_POST['project_name']);
    $cabinet_id = trim($_POST['cabinet_id']);
    $project_tags = isset($_POST['project_tags']) ? implode(',', $_POST['project_tags']) : NULL;
    $region = $_POST['region'] ?? NULL;
    
    $address = trim($_POST['address'] ?? '');
    $province = trim($_POST['province'] ?? '');
    $amphure = trim($_POST['amphure'] ?? '');
    $tambon = trim($_POST['tambon'] ?? '');
    $project_notes = trim($_POST['project_notes'] ?? '');
    
    $is_programmed = intval($_POST['is_programmed'] ?? 0);
    $programmer_name = ($is_programmed) ? trim($_POST['programmer_name'] ?? '') : NULL;
    // 🌟 เอา reverse ssh ออก เหลือแค่ชื่อและโค้ดของ Gateway
    $gateway_name = ($is_programmed) ? trim($_POST['gateway_name'] ?? '') : NULL;
    $gateway_code = ($is_programmed) ? trim($_POST['gateway_code'] ?? '') : NULL;
    
    $installation_date = !empty($_POST['installation_date']) ? $_POST['installation_date'] : NULL;
    $latitude = !empty($_POST['latitude']) ? floatval($_POST['latitude']) : NULL;
    $longitude = !empty($_POST['longitude']) ? floatval($_POST['longitude']) : NULL;

    $has_antenna = intval($_POST['has_antenna'] ?? 0);
    
    $sim_status = $_POST['sim_status'] ?? 'not_installed';
    $sim_number = ($sim_status === 'installed') ? trim($_POST['sim_number'] ?? '') : NULL;
    $sim_serial = ($sim_status === 'installed') ? trim($_POST['sim_serial'] ?? '') : NULL;
    
    $sim_photo_path = NULL;
    $orig_sim_stmt = $conn->prepare("SELECT sim_photo_path FROM projects WHERE id = ?");
    $orig_sim_stmt->bind_param("i", $project_id); $orig_sim_stmt->execute(); 
    $orig_sim = $orig_sim_stmt->get_result()->fetch_assoc(); $orig_sim_stmt->close();
    
    if ($sim_status === 'installed') {
        if (isset($_FILES['sim_photo']) && $_FILES['sim_photo']['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['sim_photo']['name'], PATHINFO_EXTENSION);
            $targetDir = 'uploads/'; if (!is_dir($targetDir)) { mkdir($targetDir, 0777, true); }
            $sim_photo_path = $targetDir . uniqid('sim_', true) . '.' . $ext;
            move_uploaded_file($_FILES['sim_photo']['tmp_name'], $sim_photo_path);
        } else {
            $sim_photo_path = $orig_sim['sim_photo_path']; 
        }
    }

    $sd_status = $_POST['sd_status'] ?? 'not_installed';
    $sd_capacity_gb = ($sd_status === 'installed') ? trim($_POST['sd_capacity_gb'] ?? '') : NULL;
    $camera_setup_status = $_POST['camera_setup_status'] ?? 'pending';

    $is_delivered = intval($_POST['is_delivered'] ?? 0);
    $delivery_photo_path = NULL; $delivery_timestamp = NULL;

    $orig_stmt = $conn->prepare("SELECT delivery_photo_path, delivery_timestamp FROM projects WHERE id = ?");
    $orig_stmt->bind_param("i", $project_id); $orig_stmt->execute(); $orig_project = $orig_stmt->get_result()->fetch_assoc(); $orig_stmt->close();
    
    if ($is_delivered) {
        if (isset($_FILES['delivery_photo']) && $_FILES['delivery_photo']['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['delivery_photo']['name'], PATHINFO_EXTENSION);
            $targetDir = 'uploads/'; if (!is_dir($targetDir)) { mkdir($targetDir, 0777, true); }
            $delivery_photo_path = $targetDir . uniqid('delivery_', true) . '.' . $ext;
            if (move_uploaded_file($_FILES['delivery_photo']['tmp_name'], $delivery_photo_path)) { $delivery_timestamp = date('Y-m-d H:i:s'); }
        } else {
            $delivery_photo_path = $orig_project['delivery_photo_path']; 
            $delivery_timestamp = $orig_project['delivery_timestamp'] ?: date('Y-m-d H:i:s'); 
        }
    }

    // 🌟 28 Parameter
    $stmt = $conn->prepare("UPDATE projects SET project_name=?, project_tags=?, cabinet_id=?, region=?, address=?, province=?, amphure=?, tambon=?, project_notes=?, is_programmed=?, programmer_name=?, gateway_name=?, gateway_code=?, installation_date=?, latitude=?, longitude=?, has_antenna=?, sim_status=?, sim_number=?, sim_serial=?, sim_photo_path=?, sd_status=?, sd_capacity_gb=?, camera_setup_status=?, is_delivered=?, delivery_photo_path=?, delivery_timestamp=? WHERE id=?");
    $stmt->bind_param("sssssssssissssddisssssssissi", $project_name, $project_tags, $cabinet_id, $region, $address, $province, $amphure, $tambon, $project_notes, $is_programmed, $programmer_name, $gateway_name, $gateway_code, $installation_date, $latitude, $longitude, $has_antenna, $sim_status, $sim_number, $sim_serial, $sim_photo_path, $sd_status, $sd_capacity_gb, $camera_setup_status, $is_delivered, $delivery_photo_path, $delivery_timestamp, $project_id);
    $stmt->execute(); $stmt->close();

    $old_photos = []; $p_res = $conn->query("SELECT id, photo_path FROM equipments WHERE project_id = $project_id"); while($r = $p_res->fetch_assoc()) { $old_photos[$r['id']] = $r['photo_path']; }
    $del_stmt = $conn->prepare("DELETE FROM equipments WHERE project_id = ?"); $del_stmt->bind_param("i", $project_id); $del_stmt->execute(); $del_stmt->close();

    function processEquipmentsUpdate($conn, $project_id, $cabinet_type, $names, $serials, $ids, $files, $old_photos, $configured_array, $req_config_array) {
        if (empty($names)) return;
        for ($i = 0; $i < count($names); $i++) {
            $eq_name = clean_equipment_name($names[$i]); $sn = trim($serials[$i]); $old_id = isset($ids[$i]) ? $ids[$i] : 'new'; $photo_path = NULL;
            if (empty($eq_name)) continue; 
            
            $is_configured = isset($configured_array[$i]) ? intval($configured_array[$i]) : 0;
            $requires_config = isset($req_config_array[$i]) ? intval($req_config_array[$i]) : 0;
            
            $m_stmt = $conn->prepare("INSERT IGNORE INTO master_equipments (equipment_name) VALUES (?)"); $m_stmt->bind_param("s", $eq_name); $m_stmt->execute(); $m_stmt->close();

            if (isset($files['name'][$i]) && $files['error'][$i] === UPLOAD_ERR_OK) {
                $ext = pathinfo($files['name'][$i], PATHINFO_EXTENSION); $newName = uniqid($cabinet_type . '_', true) . '.' . $ext; $targetDir = 'uploads/';
                if (!is_dir($targetDir)) { mkdir($targetDir, 0777, true); }
                $targetFilePath = $targetDir . $newName; if (move_uploaded_file($files['tmp_name'][$i], $targetFilePath)) { $photo_path = $targetFilePath; }
            } else if ($old_id !== 'new' && isset($old_photos[$old_id])) { $photo_path = $old_photos[$old_id]; }
            $stmt = $conn->prepare("INSERT INTO equipments (project_id, cabinet_type, equipment_name, serial_number, photo_path, is_configured, requires_config) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issssii", $project_id, $cabinet_type, $eq_name, $sn, $photo_path, $is_configured, $requires_config); $stmt->execute(); $stmt->close();
        }
    }

    if (isset($_POST['control_name'])) { processEquipmentsUpdate($conn, $project_id, 'control_iot', $_POST['control_name'], $_POST['control_serial'], $_POST['control_id'] ?? [], $_FILES['control_photo'], $old_photos, $_POST['control_configured'] ?? [], $_POST['control_req_config'] ?? []); }
    if (isset($_POST['dc_name'])) { processEquipmentsUpdate($conn, $project_id, 'dc_iot', $_POST['dc_name'], $_POST['dc_serial'], $_POST['dc_id'] ?? [], $_FILES['dc_photo'], $old_photos, $_POST['dc_configured'] ?? [], $_POST['dc_req_config'] ?? []); }
    
    log_activity($conn, $project_id, 'UPDATE_PROJECT', "อัปเดตข้อมูลตู้โครงการ '{$project_name}'");
    header("Location: view_project.php?id=" . $project_id); exit();
}
?>