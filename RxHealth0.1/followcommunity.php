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
  $query = $db->prepare("SELECT CID FROM Dconnection WHERE  CommuID = :CommuID AND UserID = :UserID");
  $query->bindParam(":CommuID", $obj['CommuID'], PDO::PARAM_INT);
  $query->bindParam(":UserID", $obj['UserID'], PDO::PARAM_INT);
  $query->execute();
  $que = $query->fetch();
  if(!$que){
    $result = $db->prepare("INSERT INTO Dconnection (CommuID,UserID,UserType) VALUES (:CommuID,:UserID,0)");
    $result->bindParam(":CommuID", $obj['CommuID'],PDO::PARAM_INT);
    $result->bindParam(":UserID", $obj['UserID'],PDO::PARAM_INT);
    $result->execute();
    $response['CID'] = $db->lastInsertId();
    $result2 = $db->prepare("SELECT Name,ComType FROM ComDetails WHERE CommuID = :CommuID");
    $result2->bindParam(":CommuID", $obj['CommuID'],PDO::PARAM_INT);
    $result2->execute();
    $row2 = $result2->fetch();
    $response['Name'] = $row2['Name'];
    $response['Type'] = $row2['ComType'];
    $response['ResponseMessage'] = "Followed Community Successfully";
    $query = $db->prepare("SELECT UserID FROM Dconnection WHERE UserType IN (2,3) AND CommuID = :CommuID");
    $query->bindParam(":CommuID", $obj['CommuID'],PDO::PARAM_INT);
    $query->execute();
    while($row = $query->fetch()){
      $result2 = $db->prepare("INSERT INTO Notifications (Type,ID,UserID) VALUES (13,:ID,:UserID)");
      $result2->bindParam(":UserID",$row['UserID'],PDO::PARAM_INT);
      $result2->bindParam(":ID",$response['CID'],PDO::PARAM_INT);
      $result2->execute();
      $nid = $db->lastInsertId();
      $result = $db->prepare("SELECT * FROM Notifications WHERE NID = :NID"); //LIMIT ".$offset.",10");
      $result->bindParam(":NID", $nid, PDO::PARAM_INT);
      $result->execute();
      $row = $result->fetch();
      $data = getnotifications($row, $db);
      $response['CurlResponse'] = json_decode(pushnotification($row['UserID'], 'Follow Notification', "User has followed your Community", "Following", $data, null, $db), true);
    }
    $response['CurlResponses'] = $curlresponse;
    $response['ResponseMessage'] = "User followed Community";
    $response['ResponseCode'] = "200";
  }else{
    $response['ResponseMessage'] = "User already present in this community";
    $response['ResponseCode'] = "500";
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
