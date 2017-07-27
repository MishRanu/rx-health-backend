<?php
header("Access-Control-Allow-Origin: *");
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Origin, Content-Type, Accept, Authorization, X-Request-With');
header('Access-Control-Allow-Credentials: true');
//$json=$_GET ['json'];

include('db_config.php');
include('helperfunctions.php');


$json = file_get_contents('php://input');
$obj = json_decode($json, true);

$query = $db->prepare("SELECT * from user where Email=:Email OR Phone=:Phone");
$query->bindParam(':Email', $obj['Email'], PDO::PARAM_STR);
$query->bindParam(':Phone', $obj['Phone'], PDO::PARAM_INT);
$query->execute();
$count = $query->rowCount();
$response = array();

if($count<1)
{
	try
		{
                        $password = generatePIN(8);

			$result = $db->prepare("INSERT INTO user (FName, LName, Email, Phone, Password, IsDoctor, IsRegistered, IsLoggedin, IsUpdated, DeviceID, RegDate)
			VALUES (:FName, :LName, :Email, :Phone, :Password, :IsDoctor, 1, 0, 0, :DeviceID, Now())");
			$result->bindParam(':FName', $obj['FName'], PDO::PARAM_STR);
			$result->bindParam(':LName', $obj['LName'], PDO::PARAM_STR);
			$result->bindParam(':Email', $obj['Email'], PDO::PARAM_STR);
			$result->bindParam(':Phone', $obj['Phone'], PDO::PARAM_STR);
			$result->bindParam(':Password', $password, PDO::PARAM_STR);
			$result->bindParam(':IsDoctor', $obj['IsDoctor'], PDO::PARAM_INT);
			$result->bindParam(':DeviceID', $obj['DeviceID'], PDO::PARAM_STR);
			$result->execute();
			$userid = $db->lastInsertId();
                        list($otp_code,$message) = sendotp( $obj['Phone'],$password,"selfCreated");
                        $result0 = $db->prepare("INSERT INTO Dconnection (CommuID, UserID, UserType) VALUES (1, :UserID, 1)");
		        $result0->bindParam(":UserID", $userid, PDO::PARAM_STR);
			$result0->execute();


			if ($obj['IsDoctor']==1)
			{
				$result2 = $db->prepare("INSERT INTO doctorprofile (DID, RegNo, RegAssoc, RegYear ) VALUES (:DID, :RegNo, :RegAssoc, :RegYear)");
				$result2->bindParam(':DID', $userid, PDO::PARAM_INT);
				$result2->bindParam(':RegNo', $obj['RegNo'], PDO::PARAM_INT);
				$result2->bindParam(':RegAssoc', $obj['RegAssoc'], PDO::PARAM_STR);
				$result2->bindParam(':RegYear', $obj['RegYear'], PDO::PARAM_STR);
				$result2->execute();
				$fullname = 'Dr. '.$obj['FName'].' '.$obj['LName'].' Community';
				$result3 = $db->prepare("INSERT INTO ComDetails (Name, ComType) VALUES (:Name, 0)");
				$result3->bindParam(":Name", $fullname, PDO::PARAM_STR);
				$result3->execute();
				$commuid = $db->lastInsertId();
				$result4 = $db->prepare("INSERT INTO Dconnection (CommuID, UserID, UserType) VALUES (:CommuID, :UserID, 3)");
				$result4->bindParam(":CommuID", $commuid, PDO::PARAM_STR);
				$result4->bindParam(":UserID", $userid, PDO::PARAM_STR);
				$result4->execute();
			}
			else
			{
				$result2 = $db->prepare("INSERT INTO patientprofile (PID) VALUES (:PID)");
				$result2->bindParam(':PID', $userid, PDO::PARAM_INT);
				$result2->execute();
				// $result3 = $db->prepare("UPDATE family Set PID=:PID, IsExisting=1 where Phone=:Phone");
				// $result3->bindParam(':PID', $row['UserID'], PDO::PARAM_INT);
				// $result3->bindParam(':Phone', $obj['Phone'], PDO::PARAM_INT);
				// $result3->execute();
			}

			$response['ResponseCode'] = "200";
			$response['ResponseMessage'] = "User Registered Successfully";
			$response['UserID'] = (string)$userid;
			$response['IsDoctor'] = (string)$obj['IsDoctor'];
                        $response['pwd'] = $password;
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
			echo json_encode($response);
		}
}
else
{
		$response['ResponseCode'] = "202";
		$response['ResponseMessage'] = "User already Registered, Please Login";
		$status['Status'] = $response;
		header('Content-type: application/json');
		echo json_encode($status);
}
