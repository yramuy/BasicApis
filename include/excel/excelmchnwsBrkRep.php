<?php

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

require_once '../../libs/vendor/PHPExcel.php';
require_once '../../include/DbHandler.php';



$dh = new DbHandler();  



$toiData = $dh->excelMchnwiseBrkDwnLoad(1);
// echo "<pre>";
// print_r($toiData['tktdetails']['ticketDetByTypeOfIssue']);

$list = $toiData['tktdetails']['EqpmntDetailsBasedOnEqIdAll'];
// exit();

$object = new PHPExcel();
                    $object->setActiveSheetIndex(0);
                    $heading_columns = array("Reports : Jobs By TypeofIssue");
                    $hcolumn = 3;

foreach ($heading_columns as $field) {
                        $object->getActiveSheet()->setCellValueByColumnAndRow($hcolumn, 1, $field);
                       
                    }
$table_columns = array("S.No","Job Id","Created Date","Resolved Duration","Completed Duration","Total Completed Duration");

    $column = 0;

    foreach ($table_columns as $field) {
        $object->getActiveSheet()->setCellValueByColumnAndRow($column, 4, $field);
    $column++;

}

 $excel_row = 5;
        $slNo = 1;

        foreach ($list as $acklist) {
        	// echo "<pre>";
        	// print_r($acklist);
                        $JobId = $acklist['JobId'];
                        $createdDate = $acklist['createdDate'];
                        $ResolvedTime = $acklist['ResolvedTime'];
                        $CompletedTime = $acklist['CompletedTime'];
                        $TotalCompletedTime = $acklist['TotalCompletedTime'];
                        
                                    
                    $object->getActiveSheet()->setCellValueByColumnAndRow(0,$excel_row,$slNo);
                    $object->getActiveSheet()->setCellValueByColumnAndRow(1,$excel_row,$JobId);
                    $object->getActiveSheet()->setCellValueByColumnAndRow(2,$excel_row,$createdDate);
                    $object->getActiveSheet()->setCellValueByColumnAndRow(3,$excel_row,$ResolvedTime);
                    $object->getActiveSheet()->setCellValueByColumnAndRow(4,$excel_row,$CompletedTime);
                    $object->getActiveSheet()->setCellValueByColumnAndRow(5,$excel_row,$TotalCompletedTime);

                    $excel_row++;
                   
                    $slNo++;
                   
                   } 


 $object_writer = PHPExcel_IOFactory::createWriter($object,'Excel5');
                header('Content-Type: application/vnd.ms-excel');
                header('Content-Disposition: attachment;filename="machineWiseBrkDwnRpt.xls"');
                $object_writer->save('php://output');

?>