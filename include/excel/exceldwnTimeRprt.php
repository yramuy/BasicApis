<?php

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

require_once '../../libs/vendor/PHPExcel.php';
require_once '../../include/DbHandler.php';



$dh = new DbHandler();  



$toiData = $dh->excelDowntimeReportDwnLoad(1);
// echo "<pre>";
// print_r($toiData['tktdetails']['ticketDetByTypeOfIssue']);

$list = $toiData['tktdetails']['ticketsDownTimeReport'];
// exit();

$object = new PHPExcel();
                    $object->setActiveSheetIndex(0);
                    $heading_columns = array("Reports : Down Time Report");
                    $hcolumn = 3;

foreach ($heading_columns as $field) {
                        $object->getActiveSheet()->setCellValueByColumnAndRow($hcolumn, 1, $field);
                       
                    }
$table_columns = array("S.No","Job Id","Subject","Location","Plant","Department","Equipment","Created Time");

    $column = 0;

    foreach ($table_columns as $field) {
        $object->getActiveSheet()->setCellValueByColumnAndRow($column, 4, $field);
    $column++;

}

 $excel_row = 7;
        $slNo = 1;

        foreach ($list as $acklist) {
        	// echo "<pre>";
        	// print_r($acklist);
                        $JobId = $acklist['jobId'];
                        $subject = $acklist['subject'];
                        $location = $acklist['location'];
                        $plant = $acklist['plant'];
                        $department = $acklist['department'];
                        $functionalLocation = $acklist['functionalLocation'];
                        $equipment = $acklist['equipmment'];
                        $createdDate = $acklist['createdDate'];
                        
                                    
                    $object->getActiveSheet()->setCellValueByColumnAndRow(0,$excel_row,$slNo);
                    $object->getActiveSheet()->setCellValueByColumnAndRow(1,$excel_row,$JobId);
                    $object->getActiveSheet()->setCellValueByColumnAndRow(2,$excel_row,$subject);
                    $object->getActiveSheet()->setCellValueByColumnAndRow(3,$excel_row,$location);
                    $object->getActiveSheet()->setCellValueByColumnAndRow(4,$excel_row,$plant);
                    $object->getActiveSheet()->setCellValueByColumnAndRow(5,$excel_row,$department);
                     $object->getActiveSheet()->setCellValueByColumnAndRow(6,$excel_row,$functionalLocation);
                      $object->getActiveSheet()->setCellValueByColumnAndRow(7,$excel_row,$equipment);
                      $object->getActiveSheet()->setCellValueByColumnAndRow(7,$excel_row,$createdDate);

                    $excel_row++;
                   
                    $slNo++;
                   
                   } 


 $object_writer = PHPExcel_IOFactory::createWriter($object,'Excel5');
                header('Content-Type: application/vnd.ms-excel');
                header('Content-Disposition: attachment;filename="downtimeReport.xls"');
                $object_writer->save('php://output');

?>