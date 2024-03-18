<?php
require_once '../../libs/vendor/mpdf/mpdf.php';
require_once '../../include/DbHandler.php';
$mpdf = new mPDF();
$mpdf->setFooter('{PAGENO}'.'/{nb}');
$mpdf->mirrorMargins = 1;
// $uri = $_SERVER["REQUEST_URI"];
// $uriArray = explode('/', $uri);
// $id = $uriArray[4];

$dh = new DbHandler();  

$data = $dh->pdfMainTypRepDwnLoad($userId);

/*print_r($data['tktdetails']['MaintenanceTypeReport']);
exit();*/
//$jobId = $data['job_id'];

$html .= "
<head>
<style>



#pdftable th {
  padding-top: 12px;
  padding-bottom: 12px;
  text-align: left;
  background-color: #4CAF50;
  color: white;
  height: 50px;
}




</style>
</head>
<h3 style='text-align: center'>Reports : Maintenance Type Report<h3>
<table class='row' width='100%' cellspacing='0' cellpadding='5' id='pdftable'>                    
<tr>
    <th style='text-align: center' width='5%'><h4>S.No</h4></th>
    <th style='text-align: center' width='10%'>Job Id</th>
    <th style='text-align: center' width='20%'>Maintenance Type</th>
    <th style='text-align: center' width='10%'>Equipment</th>
    <th style='text-align: center' width='10%'>Created Date</th>
    <th style='text-align: center' width='15%'>Resolved Time</th>
    <th style='text-align: center' width='20%'>Completed Time/th>
    <th style='text-align: center' width='20%'>Total Completed Time</th>
    
</tr>
</table>

";

// echo sizeof($data['tktdetails']['MaintenanceTypeReport']);
for ($i=0; $i < sizeof($data['tktdetails']['MaintenanceTypeReport']) ; $i++) { 

            $sno   = $i+1;
            $jobId  = $data['tktdetails']['MaintenanceTypeReport'][$i]['jobId'];
             $maintenanceName  = $data['tktdetails']['MaintenanceTypeReport'][$i]['maintenanceName'];
              $equipment  = $data['tktdetails']['MaintenanceTypeReport'][$i]['equipment'];
                $createDate  = $data['tktdetails']['MaintenanceTypeReport'][$i]['createDate'];
                  $ResolvedTime  = $data['tktdetails']['MaintenanceTypeReport'][$i]['ResolvedTime'];
                    $CompletedTime  = $data['tktdetails']['MaintenanceTypeReport'][$i]['CompletedTime'];
                    $TotalCompletedTime  = $data['tktdetails']['MaintenanceTypeReport'][$i]['TotalCompletedTime'];
                 

                                       $html .= "
<table class='row' width='100%' cellspacing='0' cellpadding='5' id='pdftable'>                    
<tr>
<td style='text-align: center' width='5%'>$sno</td>
    <td style='text-align: center' width='10%'>$jobId</td>
    <td style='text-align: center' width='20%'>$maintenanceName</td>
    <td style='text-align: center' width='10%'>$equipment</td>

     <td style='text-align: center' width='10%'>$createDate</td>
    <td style='text-align: center' width='15%'>$ResolvedTime</td>
    <td style='text-align: center' width='20%'>$CompletedTime</td>
    <td style='text-align: center' width='20%'>$TotalCompletedTime</td>
    
</tr>
</table>

";
                                    }


$mpdf=new mPDF('c', 'A4-L'); 
$mpdf->SetTitle($title);
$mpdf->WriteHTML($html);
$mpdf->SetDisplayMode('fullpage');
$mpdf->Output($fileName,'I');
exit;
?>