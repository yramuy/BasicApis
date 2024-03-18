<?php
require_once '../../libs/vendor/mpdf/mpdf.php';
require_once '../../include/DbHandler.php';
$mpdf = new mPDF();
$mpdf->setFooter('{PAGENO}'.'/{nb}');
$mpdf->mirrorMargins = 1;


$dh = new DbHandler();  

$data = $dh->pdfJobsHndlByEngDwnLoad($userId);

//echo "<pre>";
//print_r($data['tktdetails']['jobsHandledByEngineer'][0]['JobId']);
//exit();

//$jobId = $data['job_id'];

$html .= "
<head>
<style>

#pdftable td, #pdftable th {
  border: 1px solid #ddd;
  padding: 8px;
}

#pdftable tr:nth-child(even){background-color: #f2f2f2;}

#pdftable tr:hover {background-color: #ddd;}

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
<h3 style='text-align: center'>Reports : Jobs Handled By Engineer<h3>
<table class='row' width='100%' cellspacing='0' cellpadding='4' id='pdftable'>                    
<tr>
    <th style='text-align: left' width='5%'><h4>S.No</h4></th>
    <th style='text-align: left' width='10%'>Job Id</th>
    <th style='text-align: left' width='20%'>Subject</th>
     <th style='text-align: left' width='10%'>Location</th>
      <th style='text-align: left' width='15%'>Plant</th>
       <th style='text-align: left' width='15%'>Department</th>
        <th style='text-align: left' width='20%'>Functional Location</th>
         <th style='text-align: left' width='20%'>Equipment</th>
</tr>
</table>

";

// echo sizeof($data['tktdetails']['machineWiseBreakdown']);
for ($i=0; $i < sizeof($data['tktdetails']['jobsHandledByEngineer']) ; $i++) { 

  //echo "for";

            $sno   = $i+1;
            //$sNo  = $data['tktdetails']['machineWiseBreakdown'][$i]['$sno'];
             $JobId  = $data['tktdetails']['jobsHandledByEngineer'][$i]['jobId'];
              $subject  = $data['tktdetails']['jobsHandledByEngineer'][$i]['subject'];
              $location  = $data['tktdetails']['jobsHandledByEngineer'][$i]['location'];
              $plantName  = $data['tktdetails']['jobsHandledByEngineer'][$i]['plantName'];
              $department  = $data['tktdetails']['jobsHandledByEngineer'][$i]['department'];
               $functionalLocation  = $data['tktdetails']['jobsHandledByEngineer'][$i]['functionalLocation'];
                $equipment  = $data['tktdetails']['jobsHandledByEngineer'][$i]['equipment'];
                

                                       $html .= "
<table class='row' width='100%' cellspacing='0' cellpadding='4' id='pdftable'>                    
<tr>
<td style='text-align: left' width='5%'>$sno</td>
    <td style='text-align: left' width='10%'>$JobId</td>
    <td style='text-align: left' width='20%'>$subject</td>
    <td style='text-align: left' width='10%'>$location</td>
    <td style='text-align: left' width='15%'>$plantName</td>
    <td style='text-align: left' width='15%'>$department</td>
    <td style='text-align: left' width='20%'>$functionalLocation</td>
    <td style='text-align: left' width='20%'>$equipment</td>
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