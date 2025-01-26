<?php
require_once "auth.php";
$sid = intval($_GET['sid']);
if ($sid && !in_array($sid, $_CURRENT_USER->sites()))
    $sid = 0;


$timeFrom = typemap(implode('-',array_reverse(explode('/',trim($_GET['from'] ?? date('01/m/Y'))))),"date");
$timeTill = typemap(implode('-',array_reverse(explode('/',trim($_GET['to'] ?? date('t/m/Y'))))),"date");

$where = ["h.siteID IN (" . ($sid ? $sid : $_CURRENT_USER->sites(true)) . ")", "h.time_create BETWEEN '" . $timeFrom . " 00:00:00' AND '" . $timeTill . " 23:59:59'"];

if ($freeText = udb::escape_string(typemap($_GET['free'] ?? '', 'string'))){
    $list = ['clientName', 'clientEmail', 'clientPhone', 'clientPhone2', 'clientPassport'];
    $where[] = "(`" . implode("` LIKE '%" . $freeText . "%' OR `", $list) . "` LIKE '%" . $freeText . "%')";
}

$pager = new UserPager();
$pager->setPage(50);

if(!$_CURRENT_USER->access(TfusaUser::ACCESS_BIT_ADMIN)){
    $paysFROM = " INNER JOIN `orders` ON (orders.orderID = h.orderID AND orders.therapistID = " . $_CURRENT_USER->id() . ")";
}
$que = "SELECT SQL_CALC_FOUND_ROWS h.* FROM `health_declare` AS `h` " . $paysFROM . " WHERE " . implode(' AND ', $where) . " ORDER BY h.time_create DESC ";
$pays = udb::key_row($que, 'declareID');

require '../PHPExcel/IOFactory.php';





function cellColor($cells,$color){
    global $objPHPExcel;

    $objPHPExcel->getActiveSheet()->getStyle($cells)->getFill()->applyFromArray(array(
        'type' => PHPExcel_Style_Fill::FILL_SOLID,
        'startcolor' => array(
            'rgb' => $color
        ),
        'alignment' => array(
            'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
        )
    ));
}


//set the desired name of the excel file
$fileName = 'health_declare_list_'.time();


if (count($pays)){
    foreach($pays as $pay){


    
        

        $excelData[] = array(
        $pay['declareID'],
        $pay['clientName'],
        $pay['clientPhone'],
        $pay['clientEmail'],
        $pay['clientBirthday'],
        $pay['clientAddress'],
        $pay['personaccept']?"כן":"לא"
        );

}
}

//print_r($excelData);


// Create new PHPExcel object
$objPHPExcel = new PHPExcel();

// Set document properties
$objPHPExcel->getProperties()->setCreator("Me")->setLastModifiedBy("Me")->setTitle("My Excel Sheet")->setSubject("My Excel Sheet")->setDescription("Excel Sheet")->setKeywords("Excel Sheet")->setCategory("Me");

// Set active sheet index to the first sheet, so Excel opens this as the first sheet
$objPHPExcel->setActiveSheetIndex(0);

$style = array(
'alignment' => array(
    'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
)
);

$objPHPExcel->getDefaultStyle()->applyFromArray($style);

$objPHPExcel->getActiveSheet()->getStyle('A1:A1')->applyFromArray(array('font' => array('size' => 12,'bold' => true)));
$objPHPExcel->getActiveSheet()->getStyle('B1:B1')->applyFromArray(array('font' => array('size' => 12,'bold' => true)));
$objPHPExcel->getActiveSheet()->getStyle('C1:C1')->applyFromArray(array('font' => array('size' => 12,'bold' => true)));
$objPHPExcel->getActiveSheet()->getStyle('D1:D1')->applyFromArray(array('font' => array('size' => 12,'bold' => true)));
$objPHPExcel->getActiveSheet()->getStyle('E1:E1')->applyFromArray(array('font' => array('size' => 12,'bold' => true)));
$objPHPExcel->getActiveSheet()->getStyle('F1:F1')->applyFromArray(array('font' => array('size' => 12,'bold' => true)));
$objPHPExcel->getActiveSheet()->getStyle('G1:G1')->applyFromArray(array('font' => array('size' => 12,'bold' => true)));



// Add column headers
$objPHPExcel->getActiveSheet()
->setCellValue('A1', '#')
->setCellValue('B1', 'שם')
->setCellValue('C1', 'טלפון')
->setCellValue('D1', 'אימייל')
->setCellValue('E1', 'תאריך לידה')
->setCellValue('F1', 'כתובת')
->setCellValue('G1', 'מאשר דיוור')

;

cellColor('A1', 'E7E6E6');
cellColor('B1', 'E7E6E6');
cellColor('C1', 'E7E6E6');
cellColor('D1', 'E7E6E6');
cellColor('E1', 'E7E6E6');
cellColor('F1', 'E7E6E6');
cellColor('G1', 'E7E6E6');

$objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(8);
$objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(18);
$objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(18);
$objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(18);
$objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(18);
$objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(18);
$objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(18);



//Put each record in a new cell
for($i=0; $i<count($excelData); $i++){
$ii = $i+2;
$objPHPExcel->getActiveSheet()->setCellValue('A'.$ii, $excelData[$i][0]);
$objPHPExcel->getActiveSheet()->setCellValue('B'.$ii, $excelData[$i][1]);
$objPHPExcel->getActiveSheet()->setCellValue('C'.$ii, $excelData[$i][2]);
$objPHPExcel->getActiveSheet()->setCellValue('D'.$ii, $excelData[$i][3]);
$objPHPExcel->getActiveSheet()->setCellValue('E'.$ii, $excelData[$i][4]);
$objPHPExcel->getActiveSheet()->setCellValue('F'.$ii, $excelData[$i][5]);
$objPHPExcel->getActiveSheet()->setCellValue('G'.$ii, $excelData[$i][6]);

}

// Set worksheet title
$objPHPExcel->getActiveSheet()->setRightToLeft(true)->setTitle($fileName);

// Redirect output to a client’s web browser (Excel2007)
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $fileName . '.xlsx"');
header('Cache-Control: max-age=0');

$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
$objWriter->save('php://output');

exit;