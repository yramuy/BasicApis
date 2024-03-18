<?php 
require_once '../include/DbConnect.php';
// Check connection
$db = new DbConnect();
$conn = $db->connect();

$id = $_GET['id']; 
$query = "SELECT file_content,file_type FROM ohrm_ticket_attachment WHERE id = $id"; 
$result = mysqli_query($conn,$query); 
$photo = mysqli_fetch_array($result); 
header('Content-Type:'.$photo['file_type']); 
echo $photo['file_content']; 
?>