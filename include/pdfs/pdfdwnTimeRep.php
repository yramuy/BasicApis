<?php
require_once '../../libs/vendor/mpdf/mpdf.php';
require_once '../../include/DbHandler.php';
$mpdf = new mPDF();
$mpdf->setFooter('{PAGENO}'.'/{nb}');
$mpdf->mirrorMargins = 1;


$dh = new DbHandler();  

$data = $dh->pdfDwnTimeRptDwnLoad($userId);

//echo "test";

/*echo "<pre>";
print_r($data['tktdetails']['ticketsDownTimeReport'][0]['equipment']);
exit();*/


//$jobId = $data['job_id'];

$html .= "
<table class='row' width='100%' cellspacing='0' cellpadding='5'>                    
<tr>
    <th style='text-align: left' width='3%'><h4>S.No</h4></th>
                        <th style='text-align: left' width='9%'><h4>Job Id</h4></th>
                        <th style='text-align: center' width='18%'>Subject</th>
                        <th style='text-align: center' width='8%'>Location</th>
                        <th style='text-align: center' width='10%'>Plant</th>
                        <th style='text-align: center' width='10%'>Department</th>
                        <th style='text-align: center' width='10%'>Functional  Location</th>
                        <th style='text-align: center' width='10%'>Equipment</th>
                        <th style='text-align: center' width='10%'>Created Date</th>
                        <th style='text-align: center' width='10%'>Closed Date</th>
                        <th style='text-align: center' width='10%'>Down Time</th>
</tr>
</table>

";

// echo sizeof($data['tktdetails']['machineWiseBreakdown']);
for ($i=0; $i < sizeof($data['tktdetails']['ticketsDownTimeReport']) ; $i++) { 

  //echo "for";

            $sno   = $i+1;
            //$sNo  = $data['tktdetails']['machineWiseBreakdown'][$i]['$sno'];
             $JobId  = $data['tktdetails']['ticketsDownTimeReport'][$i]['jobId'];
              $subject  = $data['tktdetails']['ticketsDownTimeReport'][$i]['subject'];
              $location  = $data['tktdetails']['ticketsDownTimeReport'][$i]['location'];
              $plantName  = $data['tktdetails']['ticketsDownTimeReport'][$i]['plant'];
              $department  = $data['tktdetails']['ticketsDownTimeReport'][$i]['department'];
               $functionalLocation  = $data['tktdetails']['ticketsDownTimeReport'][$i]['functionalLocation'];
                $equipment  = $data['tktdetails']['ticketsDownTimeReport'][$i]['equipmment'];
                 $createdDate  = $data['tktdetails']['ticketsDownTimeReport'][$i]['createdDate'];
                  $closedDate  = $data['tktdetails']['ticketsDownTimeReport'][$i]['ClosedDate'];
                   $downTime  = $data['tktdetails']['ticketsDownTimeReport'][$i]['DownTime'];
                

                                       $html .= "
<table class='row' width='100%' cellspacing='0' cellpadding='5'>                    
<tr>
<td style='text-align: center' width='3%'>$sno</td>
    <td style='text-align: center' width='9%'>$JobId</td>
    <td style='text-align: center' width='18%'>$subject</td>
    <td style='text-align: center' width='8%'>$location</td>
    <td style='text-align: center' width='10%'>$plantName</td>
    <td style='text-align: center' width='10%'>$department</td>
    <td style='text-align: center' width='10%'>$functionalLocation</td>
    <td style='text-align: center' width='10%'>$equipment</td>
    <td style='text-align: center' width='10%'>$createdDate</td>
    <td style='text-align: center' width='10%'>$closedDate</td>
    <td style='text-align: center' width='10%'>$downTime</td>
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