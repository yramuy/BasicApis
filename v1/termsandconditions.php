<?php
error_reporting(0);
require_once '../include/DbHandler.php';
$db = new DbHandler();
$cid=trim($_GET['cid']);
$result=$db->getTermsAndConditons($cid);
//print_r($result);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <title>Terms And Conditions</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="icon" href="" type="images/png" sizes="16x16">
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
  <script type="text/javascript" src="jquery_003.js"></script>
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
</head>
<style>
.head-text{
		font-size:20px;
		padding-left:20px;
	}
.p1{
	color: #000;
	margin:0px;
}
.p2{
	color: #9d9d9d;
	margin:0px;
}	
</style>
<body>
	<nav class="navbar navbar-default">
	  <div class="container-fluid">
		<!--<div class="col-md-12 " style="padding:10px;" align="center"><img src="#" width="100px" height="auto"></div>-->
	  </div>
 </nav>
	<div class="container">
		
		<div class="">
<!--
			<h2 style="padding-bottom:20px;font-size:20px;"><b>Terms And Conditions</b></h2>
-->
			
			<div class="col-md-12">
				<div style="clear:both;"></div>
				
			<?php 
			echo html_entity_decode($result['description']);
			//echo  htmlspecialchars_decode($result['aboutMessage'],ENT_COMPAT);?>
			</div>
		</div>
	</div>
</body>
</html>
