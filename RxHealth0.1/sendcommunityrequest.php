<?php
header("Access-Control-Allow-Origin: *");
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Origin, Content-Type, Accept, Authorization, X-Request-With');
header('Access-Control-Allow-Credentials: true');
//$json=$_GET ['json'];
require('db_config.php');
require('helperfunctions1.php');

$json = file_get_contents('php://input');
$obj = json_decode($json, true);

try{
  $list = $obj['To'];
  for($i=0;$i < count($list);$i++){
    $result1 = $db->prepare("SELECT ReqID FROM CommunityRequests WHERE DID = :DID AND UserID = :UserID AND CommuID = :CommuID AND Status = 0");
    $result1->bindParam(":UserID",$list[$i],PDO::PARAM_INT);
    $result1->bindParam(":DID",$obj['UserID'],PDO::PARAM_INT);
    $result1->bindParam(":CommuID",$obj['CommuID'],PDO::PARAM_INT);
    $result1->execute();
    $row = $result1->fetch();
    if($row){
      $response['Alert'] = "You have already sent connection request";
      continue;
    }
    $query = $db->prepare("INSERT INTO CommunityRequests (DID,UserID,CommuID) VALUES (:DID,:UserID,:CommuID)");
    $query->bindParam(":UserID",$list[$i],PDO::PARAM_INT);
    $query->bindParam(":DID",$obj['UserID'],PDO::PARAM_INT);
    $query->bindParam(":CommuID",$obj['CommuID'],PDO::PARAM_INT);
    $query->execute();
    $reqid = $db->lastInsertId();
    $result = $db->prepare("INSERT INTO Notifications (Type,ID,UserID) VALUES (11,:ID,:UserID)");
    $result->bindParam(":UserID",$list[$i],PDO::PARAM_INT);
    $result->bindParam(":ID",$reqid,PDO::PARAM_INT);
    $result->execute();
    $nid = $db->lastInsertId();
    $result = $db->prepare("SELECT * FROM Notifications WHERE NID = :NID"); //LIMIT ".$offset.",10");
    $result->bindParam(":NID", $nid, PDO::PARAM_INT);
    $result->execute();
    $row = $result->fetch();
    $data = getnotifications($row, $db);
    $response['CurlResponse'] = json_decode(pushnotification($list[$i], 'Doctor Community Request', "Doctor has requested to join Community", "Doctor Request", $data, null, $db), true);
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
