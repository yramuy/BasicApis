<?php
require_once '../../libs/vendor/mpdf/mpdf.php';
require_once '../../include/DbHandler.php';
$mpdf = new mPDF();
$mpdf->setFooter('{PAGENO}'.'/{nb}');
$mpdf->mirrorMargins = 1;


$dh = new DbHandler();  

$data = $dh->pdfMchnwiseBrkDwnLoad($userId);

//echo "<pre>";
//print_r($data['tktdetails']['EqpmntDetailsBasedOnEqIdAll'][0]['JobId']);
//exit();

//$jobId = $data['job_id'];

$html .= "
<head>
<style>


#pdftable th {
  padding-top: 12px;
  padding-bottom: 12px;
  text-align: left;
  background-color: #154118;
  color: white;
   height: 50px;
}



</style>
</head>
<h3 style='text-align: center'>Reports : Jobs By Machine Wise BreakDown<h3>
<table class='row' width='100%' cellspacing='0' cellpadding='5' id='pdftable'>                    
<tr>
    <th style='text-align: center' width='5%'><h4>S.No</h4></th>
    <th style='text-align: center' width='20%'>JobId</th>
    <th style='text-align: center' width='25%'>Created Date</th>
     <th style='text-align: center'>Resolved Duration</th>
      <th style='text-align: center'>Completed Duration</th>
       <th style='text-align: center'>Total Completed Duration</th>
</tr>
</table>

";

// echo sizeof($data['tktdetails']['machineWiseBreakdown']);
for ($i=0; $i < sizeof($data['tktdetails']['EqpmntDetailsBasedOnEqIdAll']) ; $i++) { 

  //echo "for";

            $sno   = $i+1;
            //$sNo  = $data['tktdetails']['machineWiseBreakdown'][$i]['$sno'];
             $JobId  = $data['tktdetails']['EqpmntDetailsBasedOnEqIdAll'][$i]['JobId'];

             //echo "$JobId";
             //exit();
              $createdDate  = $data['tktdetails']['EqpmntDetailsBasedOnEqIdAll'][$i]['createdDate'];
              $ResolvedTime  = $data['tktdetails']['EqpmntDetailsBasedOnEqIdAll'][$i]['ResolvedTime'];
              $CompletedTime  = $data['tktdetails']['EqpmntDetailsBasedOnEqIdAll'][$i]['CompletedTime'];
              $TotalCompletedTime  = $data['tktdetails']['EqpmntDetailsBasedOnEqIdAll'][$i]['TotalCompletedTime'];
                

                                       $html .= "
<table class='row' width='100%' cellspacing='0' cellpadding='5' id='pdftable'>                    
<tr>
<td style='text-align: center' width='5%'>$sno</td>
    <td style='text-align: center' width='20%'>$JobId</td>
    <td style='text-align: center' width='25%'>$createdDate</td>
    <td style='text-align: center'>$ResolvedTime</td>
    <td style='text-align: center'>$CompletedTime</td>
    <td style='text-align: center'>$TotalCompletedTime</td>
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