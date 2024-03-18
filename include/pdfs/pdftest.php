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

$data = $dh->pdfDwnLoad($userId);

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
}
th {
    color:white;
    background-color: #154118;
    height: 50px;
}



</style>
</head>
<h3 style='text-align: center'>Reports : Jobs By Type Of Issue<h3>
<table class='row' width='100%' cellspacing='0' cellpadding='2' id='pdftable'>                    
<tr>
    <th style='text-align: center'  width='5%'><h4>S.No</h4></th>
    <th style='text-align: center' width='10%'>Job Id</th>
    <th style='text-align: center' width='20%'>Subject</th>
    <th style='text-align: center' width='10%'>Location</th>
    <th style='text-align: center' width='10%'>Plant</th>
    <th style='text-align: center' width='15%'>Department</th>
    <th style='text-align: center' width='20%'>Functional Location</th>
    <th style='text-align: center' width='20%'>Equipment</th>
    <th style='text-align: center' width='10%'>Type Of Issue</th>
</tr>
</table>

";

// echo sizeof($data['tktdetails']['ticketDetByTypeOfIssue']);
for ($i=0; $i < sizeof($data['tktdetails']['ticketDetByTypeOfIssue']) ; $i++) { 

            $sno   = $i+1;
            $jobId  = $data['tktdetails']['ticketDetByTypeOfIssue'][$i]['job_id'];
             $subject  = $data['tktdetails']['ticketDetByTypeOfIssue'][$i]['subject'];
              $location  = $data['tktdetails']['ticketDetByTypeOfIssue'][$i]['location'];
                $plant  = $data['tktdetails']['ticketDetByTypeOfIssue'][$i]['plant'];
                  $department  = $data['tktdetails']['ticketDetByTypeOfIssue'][$i]['department'];
                    $funclocation  = $data['tktdetails']['ticketDetByTypeOfIssue'][$i]['functionallocation'];
                    $equipment  = $data['tktdetails']['ticketDetByTypeOfIssue'][$i]['equipment'];
                     $issue  = $data['tktdetails']['ticketDetByTypeOfIssue'][$i]['issue'];

                                       $html .= "
<table class='row' width='100%' cellspacing='0' cellpadding='2' id='pdftable'>                    
<tr>
<td style='text-align: center' width='5%'>$sno</td>
    <td style='text-align: center' width='10%'>$jobId</td>
    <td style='text-align: center' width='20%'>$subject</td>
    <td style='text-align: center' width='10%'>$location</td>

     <td style='text-align: center' width='10%'>$plant</td>
    <td style='text-align: center' width='15%'>$department</td>
    <td style='text-align: center' width='20%'>$funclocation</td>
    <td style='text-align: center' width='20%'>$equipment</td>
    <td style='text-align: center' width='10%'>$issue</td>
</tr>
</table>

";
                                    }


// exit();
// foreach ($data as $key => $value) {
//     echo $value['job_id'];
// }
// echo "<pre>";
// print_r($value);
// exit();


// $html .= "
// <table class='row' width='100%' cellspacing='0' cellpadding='2'>                    
// <tr>
//     <th style='text-align: left' width=3><h4>S.No</h4></th>
//     <th style='text-align: left' width=5>Job Id</th>
//     <th style='text-align: left' width=10>Subject</th>
//     <th style='text-align: left' width=10>Location</th>
//     <th style='text-align: left' width=10>Plant</th>
//     <th style='text-align: left' width=15>Department</th>
//     <th style='text-align: left' width=20>Functional Location</th>
//     <th style='text-align: left' width=20>Sub Functional Location</th>
//     <th style='text-align: left' width=20>Equipment</th>
//     <th style='text-align: left' width=10>Type Of Issue</th>
// </tr>
// <tr>
//     <td>$value[1]</td>
// </tr>
// </table>

// ";
$mpdf=new mPDF('c', 'A4-L'); 
$mpdf->SetTitle($title);
$mpdf->WriteHTML($html);
$mpdf->SetDisplayMode('fullpage');
$mpdf->Output($fileName,'I');
exit;
?>