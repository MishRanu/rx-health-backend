<?php
header("Access-Control-Allow-Origin: *");
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Origin, Content-Type, Accept, Authorization, X-Request-With');
header('Access-Control-Allow-Credentials: true');
//$json=$_GET ['json'];
require('db_config.php');

$json = file_get_contents('php://input');
$obj = json_decode($json, true);

try{
  $result = $db->prepare("SELECT * FROM user WHERE IsDoctor = 1 AND UserID != 1 AND UserID != 11");
  $result->execute();
  while($row = $result->fetch()){
    $name = 'Dr. '.(string)$row['FName'].' '.(string)$row['LName'].' community';
    $result2 = $db->prepare("INSERT INTO ComDetails (Name,ComType) VALUES (:Name,0)");
    $result2->bindParam(":Name", $name, PDO::PARAM_STR);
    $result2->execute();
    $commuid = $db->lastInsertId();
    $result3 = $db->prepare("INSERT INTO Dconnection (UserID,CommuID,UserType) VALUES (:UserID,:CommuID,3)");
    $result3->bindParam(":UserID", $row['UserID'], PDO::PARAM_INT);
    $result3->bindParam(":CommuID", $commuid, PDO::PARAM_INT);
    $result3->execute();
  }
  $response['ResponseCode'] = "200";
  $response['ResponseMessage'] = "Community Request Sent";
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
