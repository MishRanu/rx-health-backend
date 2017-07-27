<?php
header("Access-Control-Allow-Origin: *");
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Origin, Content-Type, Accept, Authorization, X-Request-With');
header('Access-Control-Allow-Credentials: true');
//$json=$_GET ['json'];
require('db_config.php');

$json = file_get_contents('php://input');
$obj = json_decode($json, true);

// function doit($aid,$shrid,$db,$author){
//   if($author){
//     $result = $db->prepare("SELECT ShrID FROM ShareArticle WHERE ArID = :ArID");
//     $result->bindParam(":ArID", $aid,PDO::PARAM_INT);
//     $result->execute();
//     $list = "";
//     while($row = $result->fetch()){
//       $list = $list.(string)$row['ShrID'].",";
//     }
//     $list = substr($list,0,-1);
//     $statement = "DELETE FROM SharedInCommunity WHERE ShrID IN (".$list.")";
//     if($list != ""){
//       $result2 = $db->prepare($statement);
//       $result2->execute();
//     }
//     $result3 = $db->prepare("DELETE FROM ShareArticle WHERE ArID = :ArID");
//     $result3->bindParam(":ArID", $aid,PDO::PARAM_INT);
//     $result3->execute();
//   }else{
//     $result2 = $db->prepare("DELETE FROM SharedInCommunity WHERE ShrID = :ShrID");
//     $result2->bindParam(":ShrID", $shrid,PDO::PARAM_INT);
//     $result2->execute();
//     $result3 = $db->prepare("DELETE FROM ShareArticle WHERE ShrID = :ShrID");
//     $result3->bindParam(":ShrID", $shrid,PDO::PARAM_INT);
//     $result3->execute();
//   }
// }

try{
  $shrid = $obj['ShrID'];
  $result = $db->prepare("SELECT UserID,IsAuthor,ArID FROM ShareArticle WHERE ShrID = :ShrID");
  $result->bindParam(":ShrID", $obj['ShrID'],PDO::PARAM_INT);
  $result->execute();
  $row = $result->fetch();
  $userID = $row['UserID'];
  if($userID == $obj['UserID']){
    $item = ($row['IsAuthor'])?"ArID":"ShrID";
    $item2 = ($row['IsAuthor'])?$row['ArID']:$shrid;
    if($row['IsAuthor']){
      $result1 = $db->prepare("SELECT ShrID FROM ShareArticle WHERE ".$item." = :ShrID");
      $result1->bindParam(":ShrID", $item2,PDO::PARAM_INT);
      $result1->execute();
      while($row1 = $result1->fetch()){
        $result2 = $db->prepare("INSERT INTO ShareArticleArchive (ShrID,UserID,Summary,ArID,T1,T2,IsAuthor,isPublic,LikesCount,CommentCount,CommuID)
        SELECT ShrID,UserID,Summary,ArID,T1,T2,IsAuthor,isPublic,LikesCount,CommentCount,CommuID
        FROM ShareArticle
        WHERE ShrID = :ShrID");
        $result2->bindParam(":ShrID", $row1['ShrID'],PDO::PARAM_INT);
        $result2->execute();
      }
    }else{
      $result0 = $db->prepare("INSERT INTO ShareArticleArchive (ShrID,UserID,Summary,ArID,T1,T2,IsAuthor,isPublic,LikesCount,CommentCount,CommuID)
      SELECT ShrID,UserID,Summary,ArID,T1,T2,IsAuthor,isPublic,LikesCount,CommentCount,CommuID
      FROM ShareArticle
      WHERE ShrID = :ShrID");
      $result0->bindParam(":ShrID", $shrid,PDO::PARAM_INT);
      $result0->execute();
    }
    $result3 = $db->prepare("DELETE FROM ShareArticle WHERE ".$item." = :ShrID");
    $result3->bindParam(":ShrID", $item2,PDO::PARAM_INT);
    $result3->execute();
    $response['ResponseMessage'] = "Article Deleted Successfully";
  }else{
    $response['ResponseMessage'] = "Error: userid doesnt match";
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
