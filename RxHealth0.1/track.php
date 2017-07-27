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
  $query = $db->prepare("SELECT pp.CPFID,pf.CSNID FROM patientprofile pp INNER JOIN patientform pf ON pp.CPFID = pf.PFID WHERE pp.PID = :PID");
  $query->bindParam(":PID", $obj['UserID'], PDO::PARAM_INT);
  $query->execute();
  $row = $query->fetch();
  $report = $obj['Report'];
  $snid = $row['CSNID']+1;
  $statement = "INSERT INTO SymTracker (PFID, SNID, SID, Strength) VALUES (".$row['CPFID'].",".$snid.",";
  for($i = 0;$i < count($report);$i++){
    $temp = $report[$i];
    $a = $db->prepare($statement."'".$temp['SID']."'".",".$temp['Strength'].")");
    $a->execute();
  }
  $query1 = $db->prepare("UPDATE patientform SET CSNID = CSNID+1 WHERE PFID = :PFID");
  $query1->bindParam(":PFID", $row['CPFID'], PDO::PARAM_INT);
  $query1->execute();
  $response['ResponseCode'] = "200";
  $response['ResponseMessage'] = "added track";
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
