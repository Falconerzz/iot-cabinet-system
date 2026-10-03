<?php
require_once 'config.php'; check_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create' || $action === 'update') {
        $template_name = clean_equipment_name($_POST['template_name']); 
        $cabinet_type = $_POST['cabinet_type']; 
        $equipment_names = $_POST['equipment_name'] ?? [];
        $requires_configs = $_POST['requires_config'] ?? [];
        
        if (!empty($template_name) && !empty($equipment_names)) {
            if ($action === 'create') {
                $stmt = $conn->prepare("INSERT INTO equipment_templates (template_name, cabinet_type) VALUES (?, ?)"); 
                $stmt->bind_param("ss", $template_name, $cabinet_type); $stmt->execute(); 
                $template_id = $stmt->insert_id; $stmt->close();
            } else {
                $template_id = intval($_POST['template_id']);
                $stmt = $conn->prepare("UPDATE equipment_templates SET template_name = ?, cabinet_type = ? WHERE id = ?");
                $stmt->bind_param("ssi", $template_name, $cabinet_type, $template_id); $stmt->execute(); $stmt->close();
                // ลบของเก่าทิ้งเพื่อ insert ใหม่
                $conn->query("DELETE FROM template_items WHERE template_id = $template_id");
            }

            $item_stmt = $conn->prepare("INSERT INTO template_items (template_id, equipment_name, requires_config) VALUES (?, ?, ?)");
            foreach ($equipment_names as $index => $eq_name) {
                $eq_name = clean_equipment_name($eq_name);
                $req_conf = isset($requires_configs[$index]) ? intval($requires_configs[$index]) : 0;
                if (!empty($eq_name)) {
                    $m_stmt = $conn->prepare("INSERT IGNORE INTO master_equipments (equipment_name) VALUES (?)"); 
                    $m_stmt->bind_param("s", $eq_name); $m_stmt->execute(); $m_stmt->close();
                    
                    $item_stmt->bind_param("isi", $template_id, $eq_name, $req_conf); $item_stmt->execute();
                }
            }
            $item_stmt->close();
        }
    } elseif ($action === 'delete') {
        $template_id = intval($_POST['template_id']); 
        $stmt = $conn->prepare("DELETE FROM equipment_templates WHERE id = ?"); 
        $stmt->bind_param("i", $template_id); $stmt->execute(); $stmt->close();
    }
    header("Location: manage_templates.php"); exit();
}
?>