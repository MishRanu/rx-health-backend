<?php
header("Access-Control-Allow-Origin: *");
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Origin, Content-Type, Accept, Authorization, X-Request-With');
header('Access-Control-Allow-Credentials: true');
//$json=$_GET ['json'];
include('db_config.php');


$json = file_get_contents('php://input');
$obj = json_decode($json, true);

$response = array();

	try
		{
			if($obj['UserID']){
				$forgot = 0;
				$result = $db->prepare("SELECT Password from user where UserID=:UserID");
				$result->bindParam(':UserID', $obj['UserID'], PDO::PARAM_STR);
				$result->execute();
				$row = $result->fetch();
			}else{
				$forgot = 1;
				$result = $db->prepare("SELECT UserID from user where Phone = :Phone");
				$result->bindParam(':Phone', $obj['Phone'], PDO::PARAM_STR);
				$result->execute();
				$row = $result->fetch();
				$obj['UserID'] = $row['UserID'];
			}
			// code to send email
			if( $forgot === 1 || $row['Password'] == $obj['OldPassword'])
			{
				$result2 = $db->prepare("UPDATE user SET Password = :NewPassword where UserID=:UserID");
				$result2->bindParam(':NewPassword', $obj['NewPassword'], PDO::PARAM_STR);
				$result2->bindParam(':UserID', $obj['UserID'], PDO::PARAM_STR);
				$result2->execute();
				$response['ResponseCode'] = "200";
			  $response['ResponseMessage'] = "Password changed successfully";
	    }else{
	    	$response['ResponseCode'] = "500";
		    $response['ResponseMessage'] = "Old Password mismatch!";
	    }
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
