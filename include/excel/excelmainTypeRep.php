<?php

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

require_once '../../libs/vendor/PHPExcel.php';
require_once '../../include/DbHandler.php';



$dh = new DbHandler();  



$toiData = $dh->excelMainTypRepDwnLoad(1);
/*echo "<pre>";
print_r($toiData['tktdetails']['ticketDetByTypeOfIssue']);
exit();*/

$list = $toiData['tktdetails']['MaintenanceTypeReport'];
// exit();

$object = new PHPExcel();
                    $object->setActiveSheetIndex(0);
                    $heading_columns = array("Reports : Maintenance Type Report");
                    $hcolumn = 3;

foreach ($heading_columns as $field) {
                        $object->getActiveSheet()->setCellValueByColumnAndRow($hcolumn, 1, $field);
                       
                    }
$table_columns = array("S.No","Job Id","Maintenance Name","Equipment","Create Date","Resolved Time","Completed Time","Total Completed Time");

    $column = 0;

    foreach ($table_columns as $field) {
        $object->getActiveSheet()->setCellValueByColumnAndRow($column, 4, $field);
    $column++;

}

 $excel_row = 7;
        $slNo = 1;

        foreach ($list as $acklist) {
        	//echo "<pre>";
        	//print_r($acklist);
            //exit();
                        $sno = $acklist['sno'];
                        $id = $acklist['jobId'];
                        $maintenanceName = $acklist['maintenanceName'];
                        $equipment = $acklist['equipment'];
                        $createDate = $acklist['createDate'];
                        $ResolvedTime = $acklist['ResolvedTime'];
                        $CompletedTime = $acklist['CompletedTime'];
                        $TotalCompletedTime = $acklist['TotalCompletedTime'];
                        
                                    
                    $object->getActiveSheet()->setCellValueByColumnAndRow(0,$excel_row,$sno);
                    $object->getActiveSheet()->setCellValueByColumnAndRow(1,$excel_row,$id);
                    $object->getActiveSheet()->setCellValueByColumnAndRow(2,$excel_row,$maintenanceName);
                    $object->getActiveSheet()->setCellValueByColumnAndRow(3,$excel_row,$equipment);
                    $object->getActiveSheet()->setCellValueByColumnAndRow(4,$excel_row,$createDate);
                    $object->getActiveSheet()->setCellValueByColumnAndRow(5,$excel_row,$ResolvedTime);


                   
                        $object->getActiveSheet()->setCellValueByColumnAndRow(6,$excel_row,$CompletedTime);
                  
                    $object->getActiveSheet()->setCellValueByColumnAndRow(7,$excel_row,$TotalCompletedTime);
                    //$object->getActiveSheet()->setCellValueByColumnAndRow(9,$excel_row,$typeOfIssue);
                    $excel_row++;
                   
                    $slNo++;
                   
                   } 


 $object_writer = PHPExcel_IOFactory::createWriter($object,'Excel5');
                header('Content-Type: application/vnd.ms-excel');
                header('Content-Disposition: attachment;filename="MaintenanceTypeReport.xls"');
                $object_writer->save('php://output');

?>