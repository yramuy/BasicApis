<?php

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

require_once '../../libs/vendor/PHPExcel.php';
require_once '../../include/DbHandler.php';



$dh = new DbHandler();  



$toiData = $dh->excelJobsHndlByEngDwnLoad(1);
// echo "<pre>";
// print_r($toiData['tktdetails']['ticketDetByTypeOfIssue']);

$list = $toiData['tktdetails']['jobsHandledByEngineer'];
// exit();

$object = new PHPExcel();
                    $object->setActiveSheetIndex(0);
                    $heading_columns = array("Reports : Jobs Handled By Engineer");
                    $hcolumn = 3;

foreach ($heading_columns as $field) {
                        $object->getActiveSheet()->setCellValueByColumnAndRow($hcolumn, 1, $field);
                       
                    }
$table_columns = array("S.No","Job Id","Subject","Location","Plant","Department","Functional Location","Equipment");

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
                        $plantName = $acklist['plantName'];
                        $department = $acklist['department'];
                        $functionalLocation = $acklist['functionalLocation'];
                        $equipment = $acklist['equipment'];
                        
                                    
                    $object->getActiveSheet()->setCellValueByColumnAndRow(0,$excel_row,$slNo);
                    $object->getActiveSheet()->setCellValueByColumnAndRow(1,$excel_row,$JobId);
                    $object->getActiveSheet()->setCellValueByColumnAndRow(2,$excel_row,$subject);
                    $object->getActiveSheet()->setCellValueByColumnAndRow(3,$excel_row,$location);
                    $object->getActiveSheet()->setCellValueByColumnAndRow(4,$excel_row,$plantName);
                    $object->getActiveSheet()->setCellValueByColumnAndRow(5,$excel_row,$department);
                     $object->getActiveSheet()->setCellValueByColumnAndRow(6,$excel_row,$functionalLocation);
                      $object->getActiveSheet()->setCellValueByColumnAndRow(7,$excel_row,$equipment);

                    $excel_row++;
                   
                    $slNo++;
                   
                   } 


 $object_writer = PHPExcel_IOFactory::createWriter($object,'Excel5');
                header('Content-Type: application/vnd.ms-excel');
                header('Content-Disposition: attachment;filename="jobsHandledByEngineer.xls"');
                $object_writer->save('php://output');

?>