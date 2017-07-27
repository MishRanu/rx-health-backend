<?php
/*
Table Community Requests
Status :
0 : Pending
1: Accepted
2: Rejected
*/
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
  $query = $db->prepare("SELECT * FROM Notifications WHERE NID = :NID");
  $query->bindParam(":NID", $obj['NID'],PDO::PARAM_INT);
  $query->execute();
  $row = $query->fetch();
  $query2 = $db->prepare("SELECT * FROM CommunityRequests WHERE ReqID = :ReqID");
  $query2->bindParam(":ReqID", $row['ID'],PDO::PARAM_INT);
  $query2->execute();
  $row2 = $query2->fetch();
  $query3 = $db->prepare("SELECT CID FROM Dconnection WHERE CommuID = :CommuID AND UserID = :UserID");
  $query3->bindParam(":CommuID", $row2['CommuID'],PDO::PARAM_INT);
  $query3->bindParam(":UserID", $row2['UserID'],PDO::PARAM_INT);
  $query3->execute();
  $row3 = $query3->fetch();
  if($obj['Accept']){
    if($row3){
      $query4 = $db->prepare("UPDATE Dconnection SET UserType = UserType + 1 WHERE CommuID = :CommuID AND UserID = :UserID");
      $query4->bindParam(":CommuID", $row2['CommuID'],PDO::PARAM_INT);
      $query4->bindParam(":UserID", $row2['UserID'],PDO::PARAM_INT);
      $query4->execute();
    }else{
      $result = $db->prepare("INSERT INTO Dconnection (CommuID,UserID,UserType) VALUES (:CommuID,:UserID,1)");
      $result->bindParam(":CommuID", $row2['CommuID'],PDO::PARAM_INT);
      $result->bindParam(":UserID", $row2['UserID'],PDO::PARAM_INT);
      $result->execute();
    }
    $query = $db->prepare("SELECT ComType,Name FROM ComDetails WHERE CommuID = :CommuID");
    $query->bindParam(":CommuID", $row2['CommuID'], PDO::PARAM_INT);
    $query->execute();
    $que = $query->fetch();
    list($response['CID'], $response['CommuID'], $response['Type'], $response['Name']) = [$db->lastInsertId(), $row2['CommuID'], $que['ComType'], $que['Name']];
    $response['ResponseMessage'] = "Community Request Accepted";
    $status = 1;
  }else{
    $response['ResponseMessage'] = "Community Request Rejected";
    $status = 2;
  }
  $result2 = $db->prepare("INSERT INTO Notifications (Type,ID,UserID) VALUES (12,:ID,:UserID)");
  $result2->bindParam(":UserID",$row2['DID'],PDO::PARAM_INT);
  $result2->bindParam(":ID",$row['ID'],PDO::PARAM_INT);
  $result2->execute();
  $nid = $db->lastInsertId();
  $result = $db->prepare("SELECT * FROM Notifications WHERE NID = :NID"); //LIMIT ".$offset.",10");
  $result->bindParam(":NID", $nid, PDO::PARAM_INT);
  $result->execute();
  $row = $result->fetch();
  $data = getnotifications($row, $db);
  $response['CurlResponse'] = json_decode(pushnotification($row2['DID'], 'Community Request Accepted', "User has accepted your Community Request", "ComReqAccept", $data, null, $db), true);
  $result3 = $db->prepare("UPDATE CommunityRequests SET Status=:Status WHERE ReqID = :ReqID");
  $result3->bindParam(":ReqID", $row['ID'],PDO::PARAM_INT);
  $result3->bindParam(":Status", $status,PDO::PARAM_INT);
  $result3->execute();
  $response['ResponseCode'] = "200";
  $stat['Status'] = $response;
  header('Content-type: application/json');
  echo json_encode($stat);
}catch(PDOException $ex){
  $response['ResponseCode'] = "500";
    $response['ResponseMessage'] = "An Error occured!" . $ex; //user friendly message
    $status['Status'] = $response;
    header('Content-type: application/json');
  echo json_encode($status);
}
