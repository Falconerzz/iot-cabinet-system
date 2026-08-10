<?php
require_once 'config.php';
check_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $project_id = intval($_POST['project_id']);

        $stmt = $conn->prepare("SELECT project_name FROM projects WHERE id = ?");
        $stmt->bind_param("i", $project_id);
        $stmt->execute();
        $p = $stmt->get_result()->fetch_assoc();
        $p_name = $p ? $p['project_name'] : 'Unknown';
        $stmt->close();

        $del_stmt = $conn->prepare("DELETE FROM projects WHERE id = ?");
        $del_stmt->bind_param("i", $project_id);
        $del_stmt->execute();
        $del_stmt->close();

        log_activity($conn, NULL, 'DELETE_PROJECT', "ลบโครงการ '{$p_name}' (ID: {$project_id}) ออกจากระบบ");

        header("Location: index.php");
        exit();
    }

    $project_id = intval($_POST['project_id']);
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

    $stmt = $conn->prepare("UPDATE projects SET project_name=?, control_iot_id=?, dc_iot_id=?, reverse_ssh_name=?, gateway_name=?, gateway_code=?, installation_date=?, has_antenna=?, sim_status=?, sim_number=?, sd_status=?, sd_capacity_gb=?, camera_setup_status=? WHERE id=?");
    $stmt->bind_param("sssssssisssssi", $project_name, $control_iot_id, $dc_iot_id, $reverse_ssh_name, $gateway_name, $gateway_code, $installation_date, $has_antenna, $sim_status, $sim_number, $sd_status, $sd_capacity_gb, $camera_setup_status, $project_id);
    $stmt->execute();
    $stmt->close();

    $old_photos = [];
    $p_res = $conn->query("SELECT id, photo_path FROM equipments WHERE project_id = $project_id");
    while($r = $p_res->fetch_assoc()) {
        $old_photos[$r['id']] = $r['photo_path'];
    }

    $del_stmt = $conn->prepare("DELETE FROM equipments WHERE project_id = ?");
    $del_stmt->bind_param("i", $project_id);
    $del_stmt->execute();
    $del_stmt->close();

    function processEquipmentsUpdate($conn, $project_id, $cabinet_type, $names, $serials, $ids, $files, $old_photos) {
        if (empty($names)) return;

        for ($i = 0; $i < count($names); $i++) {
            $eq_name = clean_equipment_name($names[$i]);
            $sn = trim($serials[$i]);
            $old_id = isset($ids[$i]) ? $ids[$i] : 'new';
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
            } else if ($old_id !== 'new' && isset($old_photos[$old_id])) {
                $photo_path = $old_photos[$old_id];
            }

            $stmt = $conn->prepare("INSERT INTO equipments (project_id, cabinet_type, equipment_name, serial_number, photo_path) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("issss", $project_id, $cabinet_type, $eq_name, $sn, $photo_path);
            $stmt->execute();
            $stmt->close();
        }
    }

    if (isset($_POST['control_name'])) {
        processEquipmentsUpdate($conn, $project_id, 'control_iot', $_POST['control_name'], $_POST['control_serial'], $_POST['control_id'] ?? [], $_FILES['control_photo'], $old_photos);
    }

    if (isset($_POST['dc_name'])) {
        processEquipmentsUpdate($conn, $project_id, 'dc_iot', $_POST['dc_name'], $_POST['dc_serial'], $_POST['dc_id'] ?? [], $_FILES['dc_photo'], $old_photos);
    }

    log_activity($conn, $project_id, 'UPDATE_PROJECT', "อัปเดตแก้ไขข้อมูลโครงการ '{$project_name}' และรายการอุปกรณ์เรียบร้อย");

    header("Location: view_project.php?id=" . $project_id);
    exit();
}
?>