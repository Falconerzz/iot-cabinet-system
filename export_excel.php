<?php
require_once 'config.php'; 
require 'vendor/autoload.php';

check_login();

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') die("Invalid Request");

$id = intval($_POST['project_id']);
$from_company = trim($_POST['from_company']);
$from_address = trim($_POST['from_address']);
$to_company   = trim($_POST['to_company']);
$to_address   = trim($_POST['to_address']);
$project_unit = trim($_POST['project_unit']);
$province     = trim($_POST['province']);

// ดึงข้อมูลโครงการ
$stmt = $conn->prepare("SELECT * FROM projects WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$project = $stmt->get_result()->fetch_assoc();
if (!$project) die("ไม่พบข้อมูลโครงการ");

// ดึงข้อมูลอุปกรณ์
$eq_stmt = $conn->prepare("SELECT * FROM equipments WHERE project_id = ? ORDER BY cabinet_type ASC, id ASC");
$eq_stmt->bind_param("i", $id);
$eq_stmt->execute();
$equipments = $eq_stmt->get_result();

$controls = []; $dcs = [];
while ($row = $equipments->fetch_assoc()) {
    if ($row['cabinet_type'] === 'control_iot') $controls[] = $row;
    else $dcs[] = $row;
}

// เริ่มสร้างไฟล์ Excel
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// ==========================================
// ตั้งค่าสำหรับการปริ้นท์ (A4)
// ==========================================
$sheet->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4);
$sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_PORTRAIT);
$sheet->getPageSetup()->setFitToWidth(1);
$sheet->getPageSetup()->setFitToHeight(0); // 0 คือไม่บังคับความสูง (ปล่อยไหลลงหน้า 2 ได้)
$sheet->getPageMargins()->setTop(0.5);
$sheet->getPageMargins()->setRight(0.2);
$sheet->getPageMargins()->setLeft(0.2);
$sheet->getPageMargins()->setBottom(0.5);

// ตั้งค่าฟอนต์มาตรฐาน
$spreadsheet->getDefaultStyle()->getFont()->setName('Tahoma')->setSize(10);

// กำหนดความกว้างคอลัมน์ให้พอดีหน้ากระดาษ A4
$sheet->getColumnDimension('A')->setWidth(8);
$sheet->getColumnDimension('B')->setWidth(15);
$sheet->getColumnDimension('C')->setWidth(25);
$sheet->getColumnDimension('D')->setWidth(15);
$sheet->getColumnDimension('E')->setWidth(20);
$sheet->getColumnDimension('F')->setWidth(12);

// ฟังก์ชันลัดสำหรับตีเส้นตาราง
function setBorders($sheet, $range) {
    $sheet->getStyle($range)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
}
// ฟังก์ชันลัดสำหรับทำสีเทาทึบ
function fillGray($sheet, $range) {
    $sheet->getStyle($range)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFD9D9D9');
}

// =======================
// วาด Header
// =======================

// 🌟 จัดชื่อให้อยู่กึ่งกลางหน้ากระดาษ (คลุม A1 ถึง E3)
$sheet->mergeCells('A1:E3');
$sheet->setCellValue('A1', 'ใบส่งสินค้า');
$sheet->getStyle('A1')->getFont()->setSize(20)->setBold(true);
$sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

// 🌟 ส่วนโลโก้ (Merge แค่คอลัมน์ F แถว 1 ถึง 3 ตามที่ต้องการ)
$sheet->mergeCells('F1:F3'); 
$logoPath = 'expan logo.png'; 
if (file_exists($logoPath)) {
    $drawing = new Drawing();
    $drawing->setName('EX-PAN Logo');
    $drawing->setDescription('EX-PAN Logo');
    $drawing->setPath($logoPath);
    $drawing->setCoordinates('F1');  // ยึดที่คอลัมน์ F แถว 1
    $drawing->setHeight(45);         
    $drawing->setOffsetX(0); 
    $drawing->setOffsetY(5);         
    $drawing->setWorksheet($sheet);
} else {
    // ถ้าไม่เจอรูป ให้โชว์ตัวหนังสือแทนและจัดชิดขวา
    $sheet->setCellValue('F1', "X\nEX-PAN");
    $sheet->getStyle('F1')->getFont()->setSize(14)->setBold(true)->getColor()->setARGB('FF0070C0');
    $sheet->getStyle('F1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
}

// วันที่ (ตรงตามแถวที่ 5 เหมือนเดิม)
$sheet->setCellValue('E5', 'วันที่');
$sheet->setCellValue('F5', date('d/m/Y'));
$sheet->getStyle('E5:F5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
setBorders($sheet, 'E5:F5');

// ข้อมูลจาก
$row = 6;
$sheet->setCellValue('A'.$row, 'จาก');
$sheet->mergeCells('A'.$row.':A'.($row+1));
$sheet->getStyle('A'.$row)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER)->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->mergeCells('B'.$row.':F'.$row);
$sheet->setCellValue('B'.$row, $from_company);
$sheet->getStyle('B'.$row)->getFont()->setBold(true);
$row++;
$sheet->mergeCells('B'.$row.':F'.$row);
$sheet->setCellValue('B'.$row, $from_address);
setBorders($sheet, 'A6:F7');

// ข้อมูลถึง
$row++; // 8
$sheet->setCellValue('A'.$row, 'ถึง');
$sheet->mergeCells('A'.$row.':A'.($row+1));
$sheet->getStyle('A'.$row)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER)->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->mergeCells('B'.$row.':F'.$row);
$sheet->setCellValue('B'.$row, $to_company);
$sheet->getStyle('B'.$row)->getFont()->setBold(true);
$row++;
$sheet->mergeCells('B'.$row.':F'.$row);
$sheet->setCellValue('B'.$row, $to_address);
setBorders($sheet, 'A8:F9');

// โครงการ
$row++; // 10
$sheet->setCellValue('A'.$row, 'โครงการ');
$sheet->getStyle('A'.$row)->getFont()->setBold(true);
$sheet->mergeCells('B'.$row.':D'.$row);
$sheet->setCellValue('B'.$row, $project['project_name']);
$sheet->setCellValue('E'.$row, $project_unit);
$sheet->setCellValue('F'.$row, $province);
$sheet->getStyle('B'.$row.':F'.$row)->getFont()->setBold(true)->getColor()->setARGB('FFFF0000'); // ตัวอักษรสีแดง
$sheet->getStyle('E'.$row.':F'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
setBorders($sheet, 'A10:F10');

// หัวตารางรายการ (สีเขียวอ่อน)
$row++; // 11
$sheet->setCellValue('A'.$row, 'ITEM');
$sheet->mergeCells('B'.$row.':D'.$row);
$sheet->setCellValue('B'.$row, 'DESCRIPTION');
$sheet->setCellValue('E'.$row, 'SERIAL NO.');
$sheet->setCellValue('F'.$row, 'COMMENT');
$sheet->getStyle('A'.$row.':F'.$row)->getFont()->setBold(true);
$sheet->getStyle('A'.$row.':F'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('A'.$row.':F'.$row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE2EFDA'); // เขียวอ่อน
setBorders($sheet, 'A11:F11');

// =======================
// ลูปรายการอุปกรณ์
// =======================
$row++; // 12
$main_item = 1;

function printCategoryHeader($sheet, &$row, $main_item, $title) {
    $sheet->setCellValue('A'.$row, $main_item);
    $sheet->mergeCells('B'.$row.':D'.$row);
    $sheet->setCellValue('B'.$row, $title);
    $sheet->getStyle('A'.$row.':D'.$row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE2EFDA'); // เขียวอ่อน
    fillGray($sheet, 'E'.$row); // ทึบ Serial No
    fillGray($sheet, 'F'.$row); // ทึบ Comment
    $sheet->getStyle('A'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    setBorders($sheet, 'A'.$row.':F'.$row);
    $row++;
}

// หมวด 1: Control IoT
if (count($controls) > 0) {
    printCategoryHeader($sheet, $row, $main_item, 'อุปกรณ์ระบบตู้ควบคุมหลัก (Control IoT)');
    $sub_item = 1;
    foreach($controls as $eq) {
        $sheet->setCellValue('A'.$row, $main_item.'.'.$sub_item);
        $sheet->mergeCells('B'.$row.':D'.$row);
        $sheet->setCellValue('B'.$row, $eq['equipment_name']);
        
        if (empty($eq['serial_number']) || $eq['serial_number'] == '-') {
            fillGray($sheet, 'E'.$row); // ถ้าไม่มี Serial ให้ทำพื้นหลังสีเทาทึบ
        } else {
            $sheet->setCellValue('E'.$row, $eq['serial_number']);
        }
        
        $sheet->setCellValue('F'.$row, '☐');
        $sheet->getStyle('A'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('E'.$row.':F'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        setBorders($sheet, 'A'.$row.':F'.$row);
        $row++;
        $sub_item++;
    }
    $main_item++;
}

// หมวด 2: DC IoT
if (count($dcs) > 0) {
    printCategoryHeader($sheet, $row, $main_item, 'อุปกรณ์ระบบตู้ควบคุมเสริม (DC IoT)');
    $sub_item = 1;
    foreach($dcs as $eq) {
        $sheet->setCellValue('A'.$row, $main_item.'.'.$sub_item);
        $sheet->mergeCells('B'.$row.':D'.$row);
        $sheet->setCellValue('B'.$row, $eq['equipment_name']);
        
        if (empty($eq['serial_number']) || $eq['serial_number'] == '-') {
            fillGray($sheet, 'E'.$row);
        } else {
            $sheet->setCellValue('E'.$row, $eq['serial_number']);
        }

        $sheet->setCellValue('F'.$row, '☐');
        $sheet->getStyle('A'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('E'.$row.':F'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        setBorders($sheet, 'A'.$row.':F'.$row);
        $row++;
        $sub_item++;
    }
    $main_item++;
}

// หมวด 3: อุปกรณ์เสริม (SIM / SD Card)
if ($project['sim_status'] === 'installed' || $project['sd_status'] === 'installed') {
    printCategoryHeader($sheet, $row, $main_item, 'อุปกรณ์เสริม และระบบสื่อสาร');
    $sub_item = 1;
    
    if ($project['sim_status'] === 'installed') {
        $sheet->setCellValue('A'.$row, $main_item.'.'.$sub_item);
        $sheet->mergeCells('B'.$row.':D'.$row);
        $sheet->setCellValue('B'.$row, "IoT SIM Card");
        
        // 🌟 เอาเบอร์โทรศัพท์ (ฟอร์แมตแล้ว) ไปใส่ในช่อง Serial No. ตามที่ผู้ใช้ต้องการ
        $phone = $project['sim_number'] ? preg_replace("/^(\d{3})(\d{3})(\d{4})$/", "$1-$2-$3", preg_replace('/[^0-9]/', '', $project['sim_number'])) : '';
        $sim_sn = $project['sim_serial'] ?: '';
        
        // ถัามีทั้งเบอร์และ SN ให้ขึ้นบรรทัดใหม่วงเล็บ S/N ไว้, ถ้ามีอย่างใดอย่างนึงก็ใส่อันนั้น
        $display_serial = $phone;
        if($sim_sn && $phone) {
             $display_serial = $phone . "\n(" . $sim_sn . ")";
        } else if($sim_sn) {
             $display_serial = $sim_sn;
        }
        
        if (empty($display_serial)) {
            fillGray($sheet, 'E'.$row);
        } else {
            $sheet->setCellValue('E'.$row, $display_serial);
            $sheet->getStyle('E'.$row)->getAlignment()->setWrapText(true); // อนุญาตให้ปัดบรรทัดถ้ายาว
        }
        
        $sheet->setCellValue('F'.$row, '☐');
        $sheet->getStyle('A'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('E'.$row.':F'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        setBorders($sheet, 'A'.$row.':F'.$row);
        $row++;
        $sub_item++;
    }
    
    if ($project['sd_status'] === 'installed') {
        $sheet->setCellValue('A'.$row, $main_item.'.'.$sub_item);
        $sheet->mergeCells('B'.$row.':D'.$row);
        $sheet->setCellValue('B'.$row, "SD Card " . $project['sd_capacity_gb'] . " GB");
        fillGray($sheet, 'E'.$row); // ทึบ Serial No ของเมม
        $sheet->setCellValue('F'.$row, '☐');
        $sheet->getStyle('A'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('F'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        setBorders($sheet, 'A'.$row.':F'.$row);
        $row++;
    }
}

// 🌟 ช่องว่างเผื่อเติมในกระดาษ (ทำสีเทาทึบช่อง Serial และ Comment)
for($i=0; $i<3; $i++) {
    $sheet->mergeCells('B'.$row.':D'.$row);
    fillGray($sheet, 'E'.$row);
    fillGray($sheet, 'F'.$row);
    setBorders($sheet, 'A'.$row.':F'.$row);
    $row++;
}

// =======================
// 🌟 ลายเซ็นต์ด้านล่าง (แบ่งครึ่งซ้าย-ขวาให้เท่ากันเป๊ะ และจัดกึ่งกลาง)
// =======================
$row++;

// ฝั่งซ้าย (ผู้ส่งสินค้า) คลุม A ถึง C
$sheet->mergeCells('A'.$row.':C'.$row);
$sheet->setCellValue('A'.$row, "\n\n........................................................\nผู้ส่งสินค้า\nวันที่ ......./......./.......");
$sheet->getStyle('A'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_BOTTOM)->setWrapText(true);

// ฝั่งขวา (ผู้รับสินค้า) คลุม D ถึง F
$sheet->mergeCells('D'.$row.':F'.$row);
$sheet->setCellValue('D'.$row, "\n\n........................................................\nผู้รับสินค้า\nวันที่ ......./......./.......");
$sheet->getStyle('D'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_BOTTOM)->setWrapText(true);

// ตั้งค่าความสูงแถวลายเซ็น
$sheet->getRowDimension($row)->setRowHeight(90);

// สั่งเซฟและดาวน์โหลดไฟล์
$writer = new Xlsx($spreadsheet);
$filename = "ใบส่งสินค้า_" . preg_replace('/[^A-Za-z0-9ก-๙\-]/u', '_', $project['project_name']) . "_" . $project['cabinet_id'] . ".xlsx";

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="'.$filename.'"');
header('Cache-Control: max-age=0');
$writer->save('php://output');
exit();
?>