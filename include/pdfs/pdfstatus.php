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

$data = $dh->pdfStatusDwnLoad($userId);
// echo "<pre>";
// print_r($data);
// exit();
//$jobId = $data['job_id'];

$html .= "
<head>
<style>

#pdftable {
  border-collapse: collapse;
  width: 100%;
}



#pdftable tr:nth-child(even){background-color: #f2f2f2;}

#pdftable tr:hover {background-color: #ddd;}

#pdftable th {
   color:white;
    background-color: #154118;
    height: 50px;
    padding-top: 12px;
  padding-bottom: 12px;
}




</style>
</head>
<h3 style='text-align: center'>Reports : Jobs By Status<h3>
<table class='row' width='100%' cellspacing='0' cellpadding='2' id='pdftable' >                    
<tr>
    <th style='text-align: center' width='5%'><h4>S.No</h4></th>
    <th style='text-align: center' width='10%'>Job Id</th>
    <th style='text-align: center' width='20%'>Subject</th>
    <th style='text-align: center' width='15%'>Plant</th>
    <th style='text-align: center' width='15%'>Department</th>
    <th style='text-align: center' width='15%'>Equipment</th>
    <th style='text-align: center' width='12%'>Status</th>
</tr>
</table>

";

// echo sizeof($data['tktdetails']['ticketDetByTypeOfIssue']);
for ($i=0; $i < sizeof($data['tktdetails']['jobsByTicketStatus']) ; $i++) { 

            $sno   = $i+1;
            $jobId  = $data['tktdetails']['jobsByTicketStatus'][$i]['jobId'];
             $subject  = $data['tktdetails']['jobsByTicketStatus'][$i]['subject'];
              // $location  = $data['tktdetails']['jobsByTicketStatus'][$i]['location'];
                $plant  = $data['tktdetails']['jobsByTicketStatus'][$i]['plant'];
                  $department  = $data['tktdetails']['jobsByTicketStatus'][$i]['department'];
                    // $funclocation  = $data['tktdetails']['jobsByTicketStatus'][$i]['functionallocation'];
                    $equipment  = $data['tktdetails']['jobsByTicketStatus'][$i]['equipment'];
                     $status  = $data['tktdetails']['jobsByTicketStatus'][$i]['status'];

                                       $html .= "
<table class='row' width='100%' cellspacing='0' cellpadding='2' id='pdftable' >                    
<tr>
<td style='text-align: center' width='5%'>$sno</td>
    <td style='text-align: center' width='10%'>$jobId</td>
    <td style='text-align: center' width='20%'>$subject</td>
     <td style='text-align: center' width='15%'>$plant</td>
    <td style='text-align: center' width='15%'>$department</td>
    <td style='text-align: center' width='15%'>$equipment</td>
    <td style='text-align: center' width='12%'>$status</td>
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