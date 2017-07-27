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
  $aid = $obj['ArID'];
  $valid = 1;
  switch($obj['Type']){
    case 0:
    $aid = createarticle($obj['UserID'],$obj['Header'],$obj['Summary'],$obj['Link'],$obj['Details'],$obj['ImageLink'], $db);
    if($aid == 0 && strlen($obj['Link']) != 0){
      $valid = 0;
    }
    case 0:
    case 1:
    $isAuthor = 1;
    $ispublic = $obj['IsPublic'];
    break;
    case 2:
    $isAuthor = 0;
    $ispublic = 0;
    $query = $db->prepare("SELECT ArID FROM ShareArticle WHERE ShrID = :ShrID");
    $query->bindParam(":ShrID", $obj['ShrID'], PDO::PARAM_INT);
    $query->execute();
    $row = $query->fetch();
    $aid = $row['ArID'];
  }
  if($valid === 1){
    $response['ShrId'] = sharearticle($obj['UserID'],$obj['Summary'],$aid,$isAuthor,$ispublic,$obj['CommuID'], $db);
    if($obj['Type'] == 2){
      $query2 = $db->prepare("SELECT UserID FROM Articles WHERE ArID = :ArID");
      $query2->bindParam(":ArID", $aid, PDO::PARAM_INT);
      $query2->execute();
      $row2 = $query2->fetch();
      if($row2['UserID'] != $obj['UserID']){
        $result4 = $db->prepare("INSERT INTO Notifications (Type,ID,UserID) VALUES (16,:UserID,:ID)");
        $result4->bindParam(":UserID", $response['ShrId'],PDO::PARAM_INT);
        $result4->bindParam(":ID", $row2['UserID'], PDO::PARAM_INT);
        $result4->execute();
        $nid = $db->lastInsertId();
        $result = $db->prepare("SELECT * FROM Notifications WHERE NID = :NID"); //LIMIT ".$offset.",10");
        $result->bindParam(":NID", $nid, PDO::PARAM_INT);
        $result->execute();
        $row = $result->fetch();
        $data = getnotifications($row, $db);
        $response['CurlResponse'] = json_decode(pushnotification($row2['UserID'], 'Share Notification', "User has shared your Article", "Share", $data, null, $db), true);
      }
    }
    $response['ResponseCode'] = "200";
    $response['ResponseMessage'] = "Article Shared";
  }else{
    $response['ResponseCode'] = "500";
    $response['ResponseMessage'] = "Not a valid link";
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
