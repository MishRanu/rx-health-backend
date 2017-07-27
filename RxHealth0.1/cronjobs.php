<?php
// case 3 : symtracker notification
header("Access-Control-Allow-Origin: *");
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Origin, Content-Type, Accept, Authorization, X-Request-With');
header('Access-Control-Allow-Credentials: true');
//$json=$_GET ['json'];
include('db_config.php');


$json = file_get_contents('php://input');
$obj = json_decode($json, true);

define( 'API_ACCESS_KEY', 'AIzaSyDMpCteoqv4_yZueLxW9cy4zKLw_BAA6II' );

function booking($bid){
	$result6 = $db->prepare("SELECT * FROM Booking WHERE BID = :BID");
	$result6->bindParam(':BID', $bid, PDO::PARAM_INT);
	$result6->execute();
	$res = $result6->fetch();
	$result5 = $db->prepare("DELETE FROM Booking WHERE BID = :BID");
	$result5->bindParam(':BID', $obj['BID'], PDO::PARAM_INT);
	$result5->execute();
	$result4 = $db->prepare("INSERT INTO `bookinghistory`(`PID`, `DID`, `PFID`, `ClinicID`, `SlotID`, `Reason`, `BookingDate`)
	VALUES (:PID,:DID,:PFID,:ClinicID,:SlotID,2,:BookingDate)");
	$result4->bindParam(':PID', $res['PID'], PDO::PARAM_INT);
	$result4->bindParam(':DID', $res['DID'], PDO::PARAM_INT);
	$result4->bindParam(':PFID', $res['PFID'], PDO::PARAM_INT);
	$result4->bindParam(':ClinicID', $res['ClinicID'], PDO::PARAM_INT);
	$result4->bindParam(':SlotID', $res['SlotID'], PDO::PARAM_INT);
	$result4->bindParam(':BookingDate', $res['BookingDate'], PDO::PARAM_INT);
	$result4->execute();
	$bhid = $db->lastInsertId();
	return $bhid;
}

try
	{
    $result = $db->prepare("SELECT * FROM CronJob");
    $result->execute();
    while($row = $result->fetch()){
      switch($row['JobType']){
        case 0:
          $slotid = $row['ID'];
          $result2 = $db->prepare("SELECT BID1,BID2,BID3 FROM Slots WHERE SlotID = :SlotID");
          $result2->bindParam(":SlotID",$slotid,PDO::PARAM_INT);
          $result2->execute();
          $bids = $result2->fetch();
          if(!is_null($bids['BID1'])){
						booking($bids['BID1']);
          }
					if(!is_null($bids['BID2'])){
						booking($bids['BID2']);
          }
					if(!is_null($bids['BID3'])){
						booking($bids['BID3']);
          }
					$d=strtotime("+1 week");
					$result3 = $db->prepare("SELECT BID FROM Bookings WHERE SlotID = $slotid and BookingDate = :Booking")
					$result3->bindParam(":SlotID", $slotid, PDO::PARAM_INT);
					$result3->bindParam(":Booking", date("Y-m-d",$d), PDO::PARAM_STR);
					$result3->execute();
					while($row2 = $result3->fetch()){
						$ara[] = $row2;
					}
					if(count($ara) == 1){
						$result1 = $db->prepare("UPDATE Slots SET Filled=1,BID1 = :BID1, BID2 = NULL, BID3 = NULL WHERE SlotID = :SlotID");
						$result1->bindParam(":SlotID",$slotid,PDO::PARAM_INT);
						$result1->bindParam(":BID1",$ara[0]['BID'],PDO::PARAM_INT);
	          $result1->execute();
					}else if(count($ara) == 2){
						$result1 = $db->prepare("UPDATE Slots SET Filled=2,BID1 = :BID1, BID2 = :BID2, BID3 = NULL WHERE SlotID = :SlotID");
						$result1->bindParam(":SlotID",$slotid,PDO::PARAM_INT);
						$result1->bindParam(":BID1",$ara[0]['BID'],PDO::PARAM_INT);
						$result1->bindParam(":BID2",$ara[1]['BID'],PDO::PARAM_INT);
	          $result1->execute();
					}else if(count($ara) == 3){
						$result1 = $db->prepare("UPDATE Slots SET Filled=3,BID1 = :BID1, BID2 = :BID2, BID3 = :BID3 WHERE SlotID = :SlotID");
						$result1->bindParam(":SlotID",$slotid,PDO::PARAM_INT);
						$result1->bindParam(":BID1",$ara[0]['BID'],PDO::PARAM_INT);
						$result1->bindParam(":BID2",$ara[1]['BID'],PDO::PARAM_INT);
						$result1->bindParam(":BID3",$ara[2]['BID'],PDO::PARAM_INT);
	          $result1->execute();
					}else{
						$result1 = $db->prepare("UPDATE Slots SET Filled=0,BID1 = NULL, BID2 = NULL, BID3 = NULL WHERE SlotID = :SlotID");
						$result1->bindParam(":SlotID",$slotid,PDO::PARAM_INT);
	          $result1->execute();
					}
					$result7 = $db->prepare("DELETE FROM CronJob WHERE CJID = :CJID");
					$result7->bindParam(":CJID", $row['CJID'], PDO::PARAM_INT);
					$result7->execute();
					$slotid++;
					$result7 = $db->prepare("INSERT INTO CronJob (JobType,ID) VALUES (0,:SlotID)");
					$result7->bindParam(":SlotID", $slotid, PDO::PARAM_INT);
					$result7->execute();
          break;
        case 1:
					$aid = $row['ID'];
					$result3 = $db->prepare("SELECT * FROM appointment2 WHERE AID = :AID");
					$result3->bindParam(':AID', $aid, PDO::PARAM_INT);
					$result3->execute();
					$res = $result3->fetch();
					$limit = $res['SlotID'] + $res['DTimelimit']*2;
					$min = (idate('i') < 30)?0:1;
					$current = idate('w')*48 + idate('H')*2 + $min;
					if($current > $limit){
			      $result2 = $db->prepare("DELETE FROM appointment2 WHERE AID = :AID");
			      $result2->bindParam(':AID', $aid, PDO::PARAM_INT);
			      $result2->execute();
						$result1 = $db->prepare("INSERT INTO `failedappointments`('AID', `DTimeLimit`, `PTimeLimit`, `PID`, `DID`, `PFID`, `ClinicID`, `SlotID`, `Reason`, `ShareDate`, `AppointDate`, `IsDViewed`)
						VALUES (:AID,:DTimeLimit,:PTimeLimit,:PID,:DID,:PFID,:ClinicID,:SlotID,2,:ShareDate,:AppointDate,:IsDViewed)");
						$result1->bindParam(':AID', $res['AID'], PDO::PARAM_INT);
						$result1->bindParam(':DTimeLimit', $res['DTimelimit'], PDO::PARAM_INT);
			      $result1->bindParam(':PTimeLimit', $res['PTimeLimit'], PDO::PARAM_INT);
						$result1->bindParam(':PID', $res['PID'], PDO::PARAM_INT);
			      $result1->bindParam(':DID', $res['DID'], PDO::PARAM_INT);
						$result1->bindParam(':PFID', $res['PFID'], PDO::PARAM_INT);
			      $result1->bindParam(':ClinicID', $res['ClinicID'], PDO::PARAM_INT);
						$result1->bindParam(':SlotID', $res['SlotID'], PDO::PARAM_INT);
			      $result1->bindParam(':ShareDate', $res['ShareDate'], PDO::PARAM_INT);
						$result1->bindParam(':AppointDate', $res['AppointDate'], PDO::PARAM_INT);
			      $result1->bindParam(':IsDViewed', $res['IsDViewed'], PDO::PARAM_INT);
			      $result1->execute();
						$result4 = $db->prepare("DELETE FROM CronJob WHERE CJID = :CJID");
						$result4->bindParam(":CJID", $row['CJID'], PDO::PARAM_INT);
						$result4->execute();
					}
          break;
        case 2:
					$aid = $row['ID'];
					$result4 = $db->prepare("SELECT * FROM appointment2 WHERE AID = :AID");
		      $result4->bindParam(':AID', $aid, PDO::PARAM_INT);
		      $result4->execute();
					$res = $result4->fetch();
					$t = strtotime($res['ShareDate']) + $res['DTimelimit']*3600;
					$now = strtotime("now");
					if($now > $t){
						$result = $db->prepare("DELETE FROM reschedule WHERE AID = :AID");
						$result->bindParam(":AID",$aid,PDO::PARAM_INT);
						$result->execute();
						$result2 = $db->prepare("DELETE FROM appointment2 WHERE AID = :AID");
			      $result2->bindParam(':AID', $aid, PDO::PARAM_INT);
			      $result2->execute();
						$result1 = $db->prepare("INSERT INTO `failedappointments`('AID', `DTimeLimit`, `PTimeLimit`, `PID`, `DID`, `PFID`, `ClinicID`, `SlotID`, `Reason`, `ShareDate`, `AppointDate`, `IsDViewed`)
						VALUES (:AID,:DTimeLimit,:PTimeLimit,:PID,:DID,:PFID,:ClinicID,:SlotID,3,:ShareDate,:AppointDate,:IsDViewed)");
						$result1->bindParam(':AID', $res['AID'], PDO::PARAM_INT);
						$result1->bindParam(':DTimeLimit', $res['DTimelimit'], PDO::PARAM_INT);
			      $result1->bindParam(':PTimeLimit', $res['PTimeLimit'], PDO::PARAM_INT);
						$result1->bindParam(':PID', $res['PID'], PDO::PARAM_INT);
			      $result1->bindParam(':DID', $res['DID'], PDO::PARAM_INT);
						$result1->bindParam(':PFID', $res['PFID'], PDO::PARAM_INT);
			      $result1->bindParam(':ClinicID', $res['ClinicID'], PDO::PARAM_INT);
						$result1->bindParam(':SlotID', $res['SlotID'], PDO::PARAM_INT);
			      $result1->bindParam(':ShareDate', $res['ShareDate'], PDO::PARAM_INT);
						$result1->bindParam(':AppointDate', $res['AppointDate'], PDO::PARAM_INT);
			      $result1->bindParam(':IsDViewed', $res['IsDViewed'], PDO::PARAM_INT);
			      $result1->execute();
						$result3 = $db->prepare("DELETE FROM CronJob WHERE CJID = :CJID");
						$result3->bindParam(":CJID", $row['CJID'], PDO::PARAM_INT);
						$result3->execute();
					}
          break;
        case 3:
					
          break;

      }
	}
catch(PDOException $ex)
	{
		$response['ResponseCode'] = "500";
	    $response['ResponseMessage'] = "An Error occured!" . $ex; //user friendly message
	    $status['Status'] = $response;
	    header('Content-type: application/json');
		echo json_encode($status);
	}
