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
  if($obj['UserID'] == $obj['ID']){
    $result = $db->prepare("DELETE FROM Dconnection WHERE UserID = :UserID AND UserType < 3 AND CommuID = :CommuID");
    $result->bindParam(":UserID", $obj['ID'],PDO::PARAM_INT);
    $result->execute();
    $response['ResponseMessage'] = "User removed successfully";
    $response['ResponseCode'] = "200";
  }else{
    $result = $db->prepare("SELECT UserType FROM Dconnection WHERE UserID = :UserID AND UserType IN (2,3) AND CommuID = :CommuID");
    $result->bindParam(":UserID", $obj['UserID'],PDO::PARAM_INT);
    $result->bindParam(":CommuID", $obj['CommuID'],PDO::PARAM_INT);
    $result->execute();
    $row = $result->fetch();
    if($row){
      $result = $db->prepare("DELETE FROM Dconnection WHERE UserID = :UserID AND UserType < :UserType AND CommuID = :CommuID");
      $result->bindParam(":UserID", $obj['ID'],PDO::PARAM_INT);
      $result->bindParam(":CommuID", $obj['CommuID'],PDO::PARAM_INT);
      $result->bindParam(":UserType", $row['UserType'],PDO::PARAM_INT);
      $result->execute();
      $response['ResponseMessage'] = "User removed successfully";
      $response['ResponseCode'] = "200";
    }else{
      $response['ResponseMessage'] = "User doesn't have rights to remove other users";
      $response['ResponseCode'] = "500";
    }
  }
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
