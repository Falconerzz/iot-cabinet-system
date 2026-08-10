<?php
require_once 'config.php';
check_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $project_name = clean_equipment_name($_POST['project_name']);
    $control_iot_id = trim($_POST['control_iot_id']);
    $dc_iot_id = trim($_POST['dc_iot_id']);

    $reverse_ssh_name = trim($_POST['reverse_ssh_name'] ?? '');
    $gateway_name = trim($_POST['gateway_name'] ?? '');
    $gateway_code = trim($_POST['gateway_code'] ?? '');
    $installation_date = !empty($_POST['installation_date']) ? $_POST['installation_date'] : NULL;

    $has_antenna = intval($_POST['has_antenna'] ?? 0);
    $sim_status = $_POST['sim_status'] ?? 'not_installed';
    $sim_number = ($sim_status === 'installed') ? trim($_POST['sim_number'] ?? '') : NULL;

    $sd_status = $_POST['sd_status'] ?? 'not_installed';
    $sd_capacity_gb = ($sd_status === 'installed') ? trim($_POST['sd_capacity_gb'] ?? '') : NULL;

    $camera_setup_status = $_POST['camera_setup_status'] ?? 'pending';

    $stmt = $conn->prepare("INSERT INTO projects (project_name, control_iot_id, dc_iot_id, reverse_ssh_name, gateway_name, gateway_code, installation_date, has_antenna, sim_status, sim_number, sd_status, sd_capacity_gb, camera_setup_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssssisssss", $project_name, $control_iot_id, $dc_iot_id, $reverse_ssh_name, $gateway_name, $gateway_code, $installation_date, $has_antenna, $sim_status, $sim_number, $sd_status, $sd_capacity_gb, $camera_setup_status);
    $stmt->execute();
    $project_id = $stmt->insert_id;
    $stmt->close();

    function processEquipments($conn, $project_id, $cabinet_type, $names, $serials, $files) {
        if (empty($names)) return;

        for ($i = 0; $i < count($names); $i++) {
            $eq_name = clean_equipment_name($names[$i]);
            $sn = trim($serials[$i]);
            $photo_path = NULL;

            if (empty($eq_name) || empty($sn)) continue;

            $m_stmt = $conn->prepare("INSERT IGNORE INTO master_equipments (equipment_name) VALUES (?)");
            $m_stmt->bind_param("s", $eq_name);
            $m_stmt->execute();
            $m_stmt->close();

            if (isset($files['name'][$i]) && $files['error'][$i] === UPLOAD_ERR_OK) {
                $ext = pathinfo($files['name'][$i], PATHINFO_EXTENSION);
                $newName = uniqid($cabinet_type . '_', true) . '.' . $ext;
                $targetDir = 'uploads/';
                
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0777, true);
                }

                $targetFilePath = $targetDir . $newName;
                if (move_uploaded_file($files['tmp_name'][$i], $targetFilePath)) {
                    $photo_path = $targetFilePath;
                }
            }

            $stmt = $conn->prepare("INSERT INTO equipments (project_id, cabinet_type, equipment_name, serial_number, photo_path) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("issss", $project_id, $cabinet_type, $eq_name, $sn, $photo_path);
            $stmt->execute();
            $stmt->close();
        }
    }

    if (isset($_POST['control_name'])) {
        processEquipments($conn, $project_id, 'control_iot', $_POST['control_name'], $_POST['control_serial'], $_FILES['control_photo']);
    }

    if (isset($_POST['dc_name'])) {
        processEquipments($conn, $project_id, 'dc_iot', $_POST['dc_name'], $_POST['dc_serial'], $_FILES['dc_photo']);
    }

    log_activity($conn, $project_id, 'CREATE_PROJECT', "สร้างโครงการใหม่ '{$project_name}' (Control: {$control_iot_id}, DC: {$dc_iot_id})");

    header("Location: index.php");
    exit();
}
?>