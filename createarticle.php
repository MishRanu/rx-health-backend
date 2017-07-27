<?php
header("Access-Control-Allow-Origin: *");
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Origin, Content-Type, Accept, Authorization, X-Request-With');
header('Access-Control-Allow-Credentials: true');
//$json=$_GET ['json'];
require('db_config.php');
require('helperfunctions.php');

$json = file_get_contents('php://input');
$obj = json_decode($json, true);

try{
  $response['ArticleId'] = createarticle($obj['UserID'],$obj['Header'],$obj['Summary'],$obj['Link'],$obj['Details'],$obj['ImageLink'], $db);
  if($response['ArticleId'] == 0 && strlen($obj['Link']) != 0){
    $response['ResponseCode'] = 500;
    $response['ResponseMessage'] = "Not a valid link";
  }else{
    $response['ResponseCode'] = "200";
    $response['ResponseMessage'] = "Article Inserted";
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
