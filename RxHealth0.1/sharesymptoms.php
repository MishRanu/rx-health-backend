<?php
header("Access-Control-Allow-Origin: *");
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Origin, Content-Type, Accept, Authorization, X-Request-With');
header('Access-Control-Allow-Credentials: true');
//$json=$_GET ['json'];
include('db_config.php');
require('helperfunctions1.php');

date_default_timezone_set('Asia/Kolkata');
$json = file_get_contents('php://input');
$obj = json_decode($json, true);

	try
		{
			$result6 = $db->prepare("INSERT INTO appointment3 (DID, PID, PFID, Status) VALUES (:DID, :PID, :PFID, 'Active')");
			$result6->bindParam(':DID', $obj['DID'], PDO::PARAM_INT);
			$result6->bindParam(':PID', $obj['UserID'], PDO::PARAM_INT);
			$result6->bindParam(':PFID', $obj['PFID'], PDO::PARAM_INT);
			$result6->execute();
			$aid = $db->lastInsertId();

			$result4 = $db->prepare("INSERT INTO Notifications (Type,ID,UserID) VALUES (18,:ID,:UserID)");
      $result4->bindParam(":UserID", $obj['DID'],PDO::PARAM_INT);
      $result4->bindParam(":ID", $aid, PDO::PARAM_INT);
      $result4->execute();
			$nid = $db->lastInsertId();
	    $result = $db->prepare("SELECT * FROM Notifications WHERE NID = :NID"); //LIMIT ".$offset.",10");
	    $result->bindParam(":NID", $nid, PDO::PARAM_INT);
	    $result->execute();
	    $row = $result->fetch();
			$result2 = $db->prepare("UPDATE patientprofile SET CPFID = :PFID WHERE PID = :PID");
			$result2->bindParam(":PFID", $obj['PFID'], PDO::PARAM_INT);
			$result2->bindParam(":PID", $obj['UserID'], PDO::PARAM_INT);
			$result2->execute();
	    $data = getnotifications($row, $db);
	    $response['CurlResponse'] = json_decode(pushnotification($obj['DID'], 'Symptom Share Notification', "User has shared Symptoms with you", "ShareSymptom", $data, null, $db), true);

			$response['ResponseCode'] = "200";
			$response['ResponseMessage'] = "Patient Symptoms Submitted";
			$response['AID'] = $aid;


			$status['Status'] = $response;
			header('Content-type: application/json');
			echo json_encode($status);
		}
	catch(PDOException $ex)
		{
			$response['ResponseCode'] = "500";
		    $response['ResponseMessage'] = "An Error occured!" . $ex; //user friendly message
		    $status['Status'] = $response;
		    header('Content-type: application/json');
			echo json_encode($status);
		}
