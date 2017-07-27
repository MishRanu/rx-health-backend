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
  if($obj['Like']){
    $str = '+';
    $result = $db->prepare("INSERT INTO Likes (AppUserID,ShrID) VALUES (:UserID,:ShrID)");
    $result->bindParam(":UserID", $obj['UserID'],PDO::PARAM_INT);
    $result->bindParam(":ShrID", $obj['ShrID'], PDO::PARAM_INT);
    $result->execute();
    $response['ResponseMessage'] = "Article Liked";
    $likeid = $db->lastInsertId();
    $response['LikeId'] = $likeid;
    $result3 = $db->prepare("SELECT UserID FROM ShareArticle WHERE ShrID = :ShrID");
    $result3->bindParam(":ShrID", $obj['ShrID'], PDO::PARAM_INT);
    $result3->execute();
    $row3 = $result3->fetch();
    if($row3['UserID'] != $obj['UserID']){
      $result2 = $db->prepare("INSERT INTO Notifications (Type,ID,UserID) VALUES (14,:ID,:UserID)");
      $result2->bindParam(":ID", $likeid,PDO::PARAM_INT);
      $result2->bindParam(":UserID", $row3['UserID'], PDO::PARAM_INT);
      $result2->execute();
      $nid = $db->lastInsertId();
      $result = $db->prepare("SELECT * FROM Notifications WHERE NID = :NID"); //LIMIT ".$offset.",10");
      $result->bindParam(":NID", $nid, PDO::PARAM_INT);
      $result->execute();
      $row = $result->fetch();
      $data = getnotifications($row, $db);
      $response['Curlresponse'] = json_decode(pushnotification($row3['UserID'], 'Like Notification', "User has liked your Article", "Like", $data, null, $db), true);
    }
  }else{
    $str = '-';
    $result = $db->prepare("INSERT INTO UnLikes (LikeID,AppUserID,ShrID,T1)
    SELECT LikeID,AppUserID,ShrID,T1 FROM Likes WHERE ShrID = :ShrID AND AppUserID = :UserID");
    $result->bindParam(":UserID", $obj['UserID'],PDO::PARAM_INT);
    $result->bindParam(":ShrID", $obj['ShrID'], PDO::PARAM_INT);
    $result->execute();
    $result2 = $db->prepare("DELETE FROM Likes WHERE ShrID = :ShrID AND AppUserID = :UserID");
    $result2->bindParam(":UserID", $obj['UserID'],PDO::PARAM_INT);
    $result2->bindParam(":ShrID", $obj['ShrID'], PDO::PARAM_INT);
    $result2->execute();
    $response['ResponseMessage'] = "Article UnLiked";
  }
  $result3 = $db->prepare("UPDATE ShareArticle SET LikesCount = (LikesCount".$str."1) WHERE ShrID = :ShrID");
  $result3->bindParam(":ShrID", $obj['ShrID'], PDO::PARAM_INT);
  $result3->execute();
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
