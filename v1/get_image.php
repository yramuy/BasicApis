<?php 
require_once '../include/DbConnect.php';
// Check connection
$db = new DbConnect();
$conn = $db->connect();

$id = $_GET['id']; 
$query = "SELECT epic_picture,epic_type FROM hs_hr_emp_picture WHERE emp_number = $id"; 
$result = mysqli_query($conn,$query); 
$photo = mysqli_fetch_array($result); 
header('Content-Type:'.$photo['epic_type']); 
echo $photo['epic_picture']; 
// if(empty($photo['epic_picture'])){
// echo 'http://192.168.235.39/entreplan3.1/symfony/web/webres_598bd8c4489f52.47381308/themes/default/images/noimage.png'; 
// }else{
// echo $photo['epic_picture']; 
// }
?>