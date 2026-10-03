<?php
require_once 'config.php'; check_login();

$user_action = isset($_SESSION['username']) ? $_SESSION['username'] : 'ระบบ';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
    if ($sim_status === 'installed' && isset($_FILES['sim_photo']) && $_FILES['sim_photo']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['sim_photo']['name'], PATHINFO_EXTENSION);
        $targetDir = 'uploads/'; if (!is_dir($targetDir)) { mkdir($targetDir, 0777, true); }
        $sim_photo_path = $targetDir . uniqid('sim_', true) . '.' . $ext;
        move_uploaded_file($_FILES['sim_photo']['tmp_name'], $sim_photo_path);
    }

    $sd_status = $_POST['sd_status'] ?? 'not_installed';
    $sd_capacity_gb = ($sd_status === 'installed') ? trim($_POST['sd_capacity_gb'] ?? '') : NULL;
    $camera_setup_status = $_POST['camera_setup_status'] ?? 'pending';
    
    $is_delivered = intval($_POST['is_delivered'] ?? 0);
    $delivery_photo_path = NULL; $delivery_timestamp = NULL;
    if ($is_delivered && isset($_FILES['delivery_photo']) && $_FILES['delivery_photo']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['delivery_photo']['name'], PATHINFO_EXTENSION);
        $targetDir = 'uploads/'; if (!is_dir($targetDir)) { mkdir($targetDir, 0777, true); }
        $delivery_photo_path = $targetDir . uniqid('delivery_', true) . '.' . $ext;
        if (move_uploaded_file($_FILES['delivery_photo']['tmp_name'], $delivery_photo_path)) { $delivery_timestamp = date('Y-m-d H:i:s'); }
    }

    // 🌟 28 Parameter (ลบ reverse_ssh_name ออก)
    $stmt = $conn->prepare("INSERT INTO projects (project_name, project_tags, cabinet_id, region, address, province, amphure, tambon, project_notes, is_programmed, programmer_name, gateway_name, gateway_code, installation_date, latitude, longitude, has_antenna, sim_status, sim_number, sim_serial, sim_photo_path, sd_status, sd_capacity_gb, camera_setup_status, is_delivered, delivery_photo_path, delivery_timestamp, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssssssissssddisssssssisss", $project_name, $project_tags, $cabinet_id, $region, $address, $province, $amphure, $tambon, $project_notes, $is_programmed, $programmer_name, $gateway_name, $gateway_code, $installation_date, $latitude, $longitude, $has_antenna, $sim_status, $sim_number, $sim_serial, $sim_photo_path, $sd_status, $sd_capacity_gb, $camera_setup_status, $is_delivered, $delivery_photo_path, $delivery_timestamp, $user_action);
    $stmt->execute(); 
    $project_id = $stmt->insert_id; 
    $stmt->close();

    function processEquipments($conn, $project_id, $cabinet_type, $names, $serials, $files, $configured_array, $req_config_array) {
        if (empty($names)) return;
        for ($i = 0; $i < count($names); $i++) {
            $eq_name = clean_equipment_name($names[$i]); 
            $sn = trim($serials[$i] ?? ''); 
            $photo_path = NULL;
            if (empty($eq_name)) continue; 
            
            $is_configured = isset($configured_array[$i]) ? intval($configured_array[$i]) : 0;
            $requires_config = isset($req_config_array[$i]) ? intval($req_config_array[$i]) : 0;
            
            if (isset($files['name'][$i]) && $files['error'][$i] === UPLOAD_ERR_OK) {
                $ext = pathinfo($files['name'][$i], PATHINFO_EXTENSION);
                $targetDir = 'uploads/'; if (!is_dir($targetDir)) { mkdir($targetDir, 0777, true); }
                $targetFilePath = $targetDir . uniqid($cabinet_type . '_', true) . '.' . $ext;
                if (move_uploaded_file($files['tmp_name'][$i], $targetFilePath)) { $photo_path = $targetFilePath; }
            }
            $stmt = $conn->prepare("INSERT INTO equipments (project_id, cabinet_type, equipment_name, serial_number, photo_path, is_configured, requires_config) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issssii", $project_id, $cabinet_type, $eq_name, $sn, $photo_path, $is_configured, $requires_config);
            $stmt->execute(); $stmt->close();
        }
    }
    
    if (isset($_POST['control_name'])) { processEquipments($conn, $project_id, 'control_iot', $_POST['control_name'], $_POST['control_serial'], $_FILES['control_photo'], $_POST['control_configured'] ?? [], $_POST['control_req_config'] ?? []); }
    if (isset($_POST['dc_name'])) { processEquipments($conn, $project_id, 'dc_iot', $_POST['dc_name'], $_POST['dc_serial'], $_FILES['dc_photo'], $_POST['dc_configured'] ?? [], $_POST['dc_req_config'] ?? []); }

    log_activity($conn, $project_id, 'CREATE_PROJECT', "[$user_action] สร้างตู้ใหม่ '$project_name' รหัส $cabinet_id อิงจากแม่แบบ");
    header("Location: index.php"); exit();
}
?>