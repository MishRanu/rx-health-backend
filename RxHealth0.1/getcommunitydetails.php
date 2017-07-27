<?php
header("Access-Control-Allow-Origin: *");
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Origin, Content-Type, Accept, Authorization, X-Request-With');
header('Access-Control-Allow-Credentials: true');
//$json=$_GET ['json'];
require('db_config.php');
//require('helperfunctions.php');

$json = file_get_contents('php://input');
$obj = json_decode($json, true);

try{

  $result1 = $db->prepare("SELECT d.UserID from Dconnection d WHERE d.CommuID = :CommuID AND d.UserType = 3"); 
  $result1->bindParam(":CommuID", $obj['CommuID'], PDO::PARAM_INT); 
  $result1->execute(); 
  $row1 = $result1->fetch(); 

  $result2 = $db->prepare("SELECT u.Pic from user u WHERE u.UserID = :UserID"); 
  $result2->bindParam(":UserID", $row1['UserID'], PDO::PARAM_INT); 
  $result2->execute(); 
  $row2 = $result2->fetch(); 

  $result = $db->prepare("SELECT c.CommuID, c.Name, c.Status FROM ComDetails c WHERE c.CommuID = :CommuID");
  $result->bindParam(":CommuID", $obj['CommuID'], PDO::PARAM_INT);
  $result->execute();
  $row = $result->fetch(); 
  $response['DID'] = $row1['UserID'];
  $response['CommuID'] = $row['CommuID'];
  $response['Name'] = $row['Name'];
  $response['Pic'] = $row2['Pic']; 
  $response['Status'] = $row['Status']; 
  $response['ResponseMessage'] = "Community Data";
  $response['ResponseCode'] = "200";
  $status['Status'] = $response;
  header('Content-type: application/json');
  echo json_encode($status);
}catch(PDOException $ex){
  $response['ResponseCode'] = "500";
    $response['ResponseMessage'] = "An Error occured!" . $ex; //user friendly message
    $status['Status'] = $response;
    header('Content-type: application/json');
  echo json_encode($status);
}
  