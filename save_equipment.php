// ตัวอย่างการแทรก Log ในไฟล์เซฟข้อมูล
$user_action = $_SESSION['username']; // ดึงชื่อคนใช้งานปัจจุบัน

// ... (โค้ดอัปเดตข้อมูลตู้ของคุณ) ...

if ($stmt->execute()) {
    // 🌟 บันทึก Log ว่าใครแก้ไขตู้
    $log_desc = "ผู้ใช้ [{$user_action}] ได้อัปเดตข้อมูลหลักของตู้รหัส {$cabinet_id}";
    $conn->query("INSERT INTO activity_logs (project_id, action_type, description) VALUES ($project_id, 'UPDATE_PROJECT', '$log_desc')");
}

// ... (โค้ดเกี่ยวกับการโยกย้ายอุปกรณ์) ...

// สมมติว่ามีการถอดอุปกรณ์ id 10 ไปใส่โปรเจกต์ 5
$old_cabinet = "CAB-S01";
$new_cabinet = "CAB-N01";
$eq_name = "4G Router";
$eq_sn = "RT-N01-4455";

// 🌟 บันทึก Log การโยกย้ายแบบละเอียดระบุตัวคนทำ
$log_transfer = "ผู้ใช้ [{$user_action}] ได้โยกย้ายอุปกรณ์ {$eq_name} (S/N: {$eq_sn}) ออกจากตู้ {$old_cabinet} ไปยังตู้ {$new_cabinet}";
$conn->query("INSERT INTO activity_logs (project_id, action_type, description) VALUES ($project_id, 'TRANSFER_EQUIPMENT', '$log_transfer')");