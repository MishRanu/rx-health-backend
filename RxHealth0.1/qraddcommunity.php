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


    $result = $db->prepare("INSERT INTO Dconnection (CommuID,UserID,UserType) VALUES (:CommuID,:UserID,1)");
    $result->bindParam(":CommuID", $obj['CommuID'],PDO::PARAM_INT);
    $result->bindParam(":UserID", $obj['UserID'],PDO::PARAM_INT);
    $result->execute();
    $response['ConnectionID'] = $db->lastInsertId();

  $response['ResponseCode'] = "200";
  $response['ResponseMessage'] = "Added to community using qr";
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