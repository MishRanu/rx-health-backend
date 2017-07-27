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
  if($obj['reply']){
    $result2 = $db->prepare("INSERT INTO Reply (ComID,UserID,Reply,IsAnony) VALUES (:ComID,:UserID,:Reply,:Anon)");
    $result2->bindParam(":ComID", $obj['ComID'],PDO::PARAM_INT);
    $result2->bindParam(":UserID", $obj['UserID'],PDO::PARAM_INT);
    $result2->bindParam(":Reply", $obj['Comment'],PDO::PARAM_STR);
    $result2->bindParam(":Anon", $obj['Anon'],PDO::PARAM_INT);
    $result2->execute();
    $response['RepID'] = $db->lastInsertId();
    $response['ResponseMessage'] = "Replied Successfully";
    $result3 = $db->prepare("SELECT UserID FROM Comments WHERE ComID = :ComID");
    $result3->bindParam(":ComID", $obj['ComID'], PDO::PARAM_INT);
    $result3->execute();
    $row3 = $result3->fetch();
    if($row3['UserID'] != $obj['UserID']){
      $result4 = $db->prepare("INSERT INTO Notifications (Type,ID,UserID) VALUES (17,:UserID,:ID)");
      $result4->bindParam(":UserID", $response['RepID'],PDO::PARAM_INT);
      $result4->bindParam(":ID", $row3['UserID'], PDO::PARAM_INT);
      $result4->execute();
      $nid = $db->lastInsertId();
      $result = $db->prepare("SELECT * FROM Notifications WHERE NID = :NID"); //LIMIT ".$offset.",10");
      $result->bindParam(":NID", $nid, PDO::PARAM_INT);
      $result->execute();
      $row = $result->fetch();
      $data = getnotifications($row, $db);
      $response['CurlResponse'] = json_decode(pushnotification($row3['UserID'], "Comment Notification", "User has commented on your Article", "Comment", $data, null, $db), true);
    }
  }else{
    $result2 = $db->prepare("INSERT INTO Comments (ShrID,UserID,Comment,IsAnony) VALUES (:ShrID,:UserID,:Comment,:Anon)");
    $result2->bindParam(":ShrID", $obj['ShrID'],PDO::PARAM_INT);
    $result2->bindParam(":UserID", $obj['UserID'],PDO::PARAM_INT);
    $result2->bindParam(":Comment", $obj['Comment'],PDO::PARAM_STR);
    $result2->bindParam(":Anon", $obj['Anon'],PDO::PARAM_INT);
    $result2->execute();
    $response['ComID'] = $db->lastInsertId();
    $result3 = $db->prepare("SELECT UserID FROM ShareArticle WHERE ShrID = :ShrID");
    $result3->bindParam(":ShrID", $obj['ShrID'], PDO::PARAM_INT);
    $result3->execute();
    $row3 = $result3->fetch();
    if($row3['UserID'] != $obj['UserID']){
      $result4 = $db->prepare("INSERT INTO Notifications (Type,ID,UserID) VALUES (15,:UserID,:ID)");
      $result4->bindParam(":UserID", $response['ComID'],PDO::PARAM_INT);
      $result4->bindParam(":ID", $row3['UserID'], PDO::PARAM_INT);
      $result4->execute();
      $nid = $db->lastInsertId();
      $result = $db->prepare("SELECT * FROM Notifications WHERE NID = :NID"); //LIMIT ".$offset.",10");
      $result->bindParam(":NID", $nid, PDO::PARAM_INT);
      $result->execute();
      $row = $result->fetch();
      $data = getnotifications($row, $db);
      $response['CurlResponse'] = json_decode(pushnotification($row3['UserID'], "Reply Notification", "User has replied on your Article", "Reply", $data, null, $db), true);
    }
    $result1 = $db->prepare("UPDATE ShareArticle SET CommentCount = CommentCount+1 WHERE ShrID = :ShrID");
    $result1->bindParam(":ShrID", $obj['ShrID'],PDO::PARAM_INT);
    $result1->execute();
    $response['ResponseMessage'] = "Commented Successfully";
  }
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
