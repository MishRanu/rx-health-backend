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
  $result = $db->prepare("UPDATE ComDetails SET Name= :Name, Status = :Status WHERE CommuID = :CommuID");
  $result->bindParam(":CommuID", $obj['CommuID'], PDO::PARAM_INT);
  $result->bindParam(":Name", $obj['Name'], PDO::PARAM_STR);
  $result->bindParam(":Status", $obj['Status'], PDO::PARAM_STR);
  $result->execute();
  $response['ResponseMessage'] = "Community Details";
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
