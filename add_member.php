<?php
require_once 'config.php'; check_login();

$success_msg = '';$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // รับค่าจากฟอร์ม
    $email = trim($_POST['email']);
    $username = explode('@',$email)[0]; // ดึงชื่อจากหน้า @ มาเป็น username ชั่วคราว (หรือจะให้กรอกแยกก็ได้)
    $full_name = trim($_POST['full_name']);
    $role = trim($_POST['role']);
    $phone = trim($_POST['phone']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    // จัดการอัปโหลดรูปภาพโปรไฟล์
    $profile_path = null;
    if (isset($_FILES['profile_image']) &&$_FILES['profile_image']['error'] === 0) {
        $ext = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
        $filename = "profile_" . time() . "." . $ext;
        $upload_dir = "uploads/profiles/";
        
        // สร้างโฟลเดอร์ถ้ายังไม่มี
        if (!is_dir($upload_dir)) { mkdir($upload_dir, 0777, true); }$target_file = $upload_dir .$filename;
        if (move_uploaded_file($_FILES['profile_image']['tmp_name'],$target_file)) {
            $profile_path =$target_file;
        }
    }

    // เช็คว่าอีเมลซ้ำไหม
    $check =$conn->query("SELECT id FROM users WHERE email = '$email'");
    if($check && $check->num_rows > 0) {$error_msg = "อีเมลนี้มีในระบบแล้ว กรุณาใช้อีเมลอื่น";
    } else {
        $stmt =$conn->prepare("INSERT INTO users (username, password, role, email, full_name, phone, profile_image) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssss", $username, $password,$role, $email,$full_name, $phone,$profile_path);
        
        if ($stmt->execute()) {
            $success_msg = "เพิ่มสมาชิก <b>{$full_name}</b> เข้าสู่ระบบเรียบร้อยแล้ว!";
            // บันทึก Log
            $admin = $_SESSION['username'];$conn->query("INSERT INTO activity_logs (action_type, description) VALUES ('CREATE_USER', '[$admin] เพิ่มสมาชิกใหม่: $full_name ($role)')");
        } else {
            $error_msg = "เกิดข้อผิดพลาดในการบันทึกข้อมูล: " . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เพิ่มสมาชิก - Sync Vibe</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        body { background-color: #f8fafc; color: #1e293b; }
        .card-custom { border: none; border-radius: 1.5rem; box-shadow: 0 10px 30px rgba(0,0,0,0.03); background: #ffffff; }
        .form-label-custom { font-size: 0.85rem; color: #64748b; font-weight: 700; margin-bottom: 0.5rem; display: block; }
        .form-control-custom { border-radius: 0.75rem; border: 1px solid #cbd5e1; padding: 0.75rem 1rem; font-size: 0.95rem; transition: all 0.2s; background-color: #f8fafc; }
        .form-control-custom:focus { background-color: #ffffff; border-color: #3b82f6; box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1); outline: none; }
        
        /* Profile Image Upload */
        .avatar-upload-box { position: relative; width: 120px; height: 120px; margin: 0 auto; }
        .avatar-preview { width: 120px; height: 120px; border-radius: 50%; border: 3px solid #ffffff; box-shadow: 0 5px 15px rgba(0,0,0,0.1); overflow: hidden; display: flex; justify-content: center; align-items: center; background-color: #f1f5f9; cursor: pointer; transition: 0.3s; }
        .avatar-preview img { width: 100%; height: 100%; object-fit: cover; }
        .avatar-preview:hover { transform: scale(1.05); }
        .upload-btn { position: absolute; bottom: 0; right: 0; background: #3b82f6; color: white; width: 35px; height: 35px; border-radius: 50%; display: flex; justify-content: center; align-items: center; border: 3px solid white; cursor: pointer; box-shadow: 0 2px 5px rgba(0,0,0,0.2); }
    </style>
</head>
<body>
    <div class="app-container">
        <?php include 'sidebar.php'; ?>
        <main class="main-content">
            
            <div class="mb-4 pb-3 border-bottom border-light animate-fade-up">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-primary bg-opacity-10 text-primary-custom rounded-4 d-flex justify-content-center align-items-center shadow-sm" style="width: 55px; height: 55px; font-size: 1.8rem;">
                        <i class="bi bi-person-plus-fill"></i>
                    </div>
                    <div>
                        <h2 class="fw-bold text-dark m-0">เพิ่มสมาชิกใหม่</h2>
                        <p class="text-secondary m-0 mt-1">สร้างบัญชีผู้ใช้งานใหม่สำหรับเข้าสู่ระบบ Sync Vibe</p>
                    </div>
                </div>
            </div>

            <?php if($success_msg): ?>
                <div class="alert alert-success d-flex align-items-center shadow-sm border-0 rounded-4 p-3 animate-fade-up"><i class="bi bi-check-circle-fill me-2 fs-5"></i> <div><?= $success_msg ?></div><button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button></div>
            <?php endif; ?>
            <?php if($error_msg): ?>
                <div class="alert alert-danger d-flex align-items-center shadow-sm border-0 rounded-4 p-3 animate-fade-up"><i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i> <div><?= $error_msg ?></div><button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button></div>
            <?php endif; ?>

            <div class="row justify-content-center animate-fade-up" style="animation-delay: 0.1s;">
                <div class="col-xl-8 col-lg-10">
                    <div class="card card-custom p-4 p-md-5">
                        <form action="" method="POST" enctype="multipart/form-data">
                            
                            <!-- ส่วนรูปโปรไฟล์ด้านบนสุด -->
                            <div class="text-center mb-5">
                                <div class="avatar-upload-box mb-3" onclick="document.getElementById('profile_image').click()">
                                    <div class="avatar-preview" id="avatar-preview">
                                        <i class="bi bi-person text-secondary opacity-50" style="font-size: 4rem;"></i>
                                    </div>
                                    <div class="upload-btn"><i class="bi bi-camera-fill"></i></div>
                                </div>
                                <input type="file" name="profile_image" id="profile_image" class="d-none" accept="image/*">
                                <h6 class="fw-bold text-dark mb-1">รูปโปรไฟล์ (ไม่บังคับ)</h6>
                                <p class="text-secondary small">หากไม่อัปโหลด ระบบจะสร้างอักษรย่อจากชื่อให้โดยอัตโนมัติ</p>
                            </div>

                            <div class="row g-4">
                                <div class="col-md-6">
                                    <label class="form-label-custom">อีเมล (Email) * <small class="fw-normal text-secondary">ใช้สำหรับเข้าสู่ระบบ</small></label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white border-end-0 text-secondary"><i class="bi bi-envelope"></i></span>
                                        <input type="email" name="email" class="form-control form-control-custom border-start-0 ps-0" required placeholder="example@domain.com">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label-custom">รหัสผ่าน (Password) *</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white border-end-0 text-secondary"><i class="bi bi-key"></i></span>
                                        <input type="password" name="password" class="form-control form-control-custom border-start-0 ps-0" required placeholder="ตั้งรหัสผ่าน">
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label-custom">ชื่อ - นามสกุล *</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white border-end-0 text-secondary"><i class="bi bi-person-vcard"></i></span>
                                        <input type="text" name="full_name" id="full_name" class="form-control form-control-custom border-start-0 ps-0" required placeholder="เช่น สมชาย ใจดี">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label-custom">ตำแหน่ง (Role) *</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white border-end-0 text-secondary"><i class="bi bi-briefcase"></i></span>
                                        <select name="role" class="form-select form-control-custom border-start-0 ps-0" required>
                                            <option value="admin">แอดมิน (Admin)</option>
                                            <option value="technician">ช่างเทคนิค (Technician)</option>
                                            <option value="user" selected>พนักงานทั่วไป (User)</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label-custom">เบอร์โทรศัพท์ *</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white border-end-0 text-secondary"><i class="bi bi-telephone"></i></span>
                                        <input type="text" name="phone" id="phone" class="form-control form-control-custom border-start-0 ps-0 fw-bold text-primary" required placeholder="08x-xxx-xxxx" maxlength="12">
                                    </div>
                                </div>
                            </div>

                            <div class="mt-5 border-top border-light pt-4 text-end">
                                <button type="reset" class="btn btn-light rounded-pill px-4 fw-bold me-2" onclick="resetAvatar()">ล้างข้อมูล</button>
                                <button type="submit" class="btn btn-primary rounded-pill px-5 shadow-sm fw-bold"><i class="bi bi-save me-2"></i> บันทึกสมาชิก</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

        </main>
    </div>

    <script>
        // 🌟 ฟังก์ชัน Format เบอร์โทรศัพท์ (xxx-xxx-xxxx)
        document.getElementById('phone').addEventListener('input', function (e) {
            let x = e.target.value.replace(/\D/g, '').match(/(\d{0,3})(\d{0,3})(\d{0,4})/);
            e.target.value = !x[2] ? x[1] : x[1] + '-' + x[2] + (x[3] ? '-' + x[3] : '');
        });

        // 🌟 ฟังก์ชันจัดการรูปโปรไฟล์และอักษรย่อ
        const nameInput = document.getElementById('full_name');
        const imgInput = document.getElementById('profile_image');
        const preview = document.getElementById('avatar-preview');

        // เมื่อพิมพ์ชื่อ-นามสกุล
        nameInput.addEventListener('keyup', function() {
            if (imgInput.files.length > 0) return; // ถ้าอัปโหลดรูปแล้ว ไม่ต้องสร้างอักษร
            
            let name = this.value.trim();
            if (name) {
                let parts = name.split(' ');
                // ดึงอักษรตัวแรกของชื่อ และตัวแรกของนามสกุล (ถ้ามี)
                let initials = parts[0].charAt(0);
                if (parts.length > 1 && parts[parts.length - 1].length > 0) {
                    initials += parts[parts.length - 1].charAt(0);
                }
                
                // สุ่มสีพื้นหลังให้อักษรย่อดูมีชีวิตชีวา
                const colors = ['#3b82f6', '#10b981', '#f59e0b', '#8b5cf6', '#ec4899'];
                const color = colors[name.length % colors.length];

                preview.innerHTML = `<div class="text-white d-flex justify-content-center align-items-center w-100 h-100" style="background-color: ${color}; font-size: 2.5rem; font-weight: 700; letter-spacing: 2px;">${initials.toUpperCase()}</div>`;
            } else {
                resetAvatar();
            }
        });

        // เมื่อเลือกอัปโหลดรูปภาพ
        imgInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                let reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = `<img src="${e.target.result}" alt="Profile Preview">`;
                }
                reader.readAsDataURL(this.files[0]);
            }
        });

        // รีเซ็ตตอนกดปุ่มล้างข้อมูล
        function resetAvatar() {
            preview.innerHTML = `<i class="bi bi-person text-secondary opacity-50" style="font-size: 4rem;"></i>`;
        }
    </script>
</body>
</html>