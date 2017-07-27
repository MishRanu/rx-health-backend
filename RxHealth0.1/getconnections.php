<?php
header("Access-Control-Allow-Origin: *");
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Origin, Content-Type, Accept, Authorization, X-Request-With');
header('Access-Control-Allow-Credentials: true');
//$json=$_GET ['json'];
require('db_config.php');

$json = file_get_contents('php://input');
$obj = json_decode($json, true);

function getdata($commuid,$type, $db, $userid){
  $result = $db->prepare("SELECT CONCAT_WS(' ', u.FName,u.LName) AS FullName, d.UserID, u.Pic
  FROM Dconnection d
  INNER JOIN user u
  ON u.UserID = d.UserID
  WHERE d.CommuID = :CommuID
  AND UserType = :UserType AND d.UserID != :UserID");
  $result->bindParam(":CommuID", $commuid,PDO::PARAM_INT);
  $result->bindParam(":UserType", $type,PDO::PARAM_INT);
  $result->bindParam(":UserID", $userid,PDO::PARAM_INT);
  $result->execute();
  $temp = array();
  while($row = $result->fetch()){
    $pic = ($row['Pic'])?$row['Pic']:'dxhealth.esy.es/default.png';
    $temp[] = array("UserID" => $row['UserID'], "FullName" => $row['FullName'], "Pic" => $pic);
  }
  return $temp;
}

try{
  $data['Followers'] = getdata($obj['CommuID'],0, $db, $obj['UserID']);
  $data['Connection'] = getdata($obj['CommuID'],1, $db, $obj['UserID']);
  $data['Admins'] = getdata($obj['CommuID'],2, $db, $obj['UserID']);
  $data['Creator'] = getdata($obj['CommuID'],3, $db, $obj['UserID']);
  $response['ConnectionData'] = $data;
  $response['ResponseCode'] = "200";
  $response['ResponseMessage'] = "Community Created";
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
