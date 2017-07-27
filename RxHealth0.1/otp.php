<?php
header("Access-Control-Allow-Origin: *");
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Origin, Content-Type, Accept, Authorization, X-Request-With');
header('Access-Control-Allow-Credentials: true');
//$json=$_GET ['json'];
include('db_config.php');
//include('helperfunctions1.php');
include('helperfunctions.php');
$json = file_get_contents('php://input');
$obj = json_decode($json, true);
$senderId = "RxHLTH";
$route = "4";

$response = array();

try{
	$check = true;
	if($obj['forgot']){
		$query = $db->prepare("SELECT UserID FROM user WHERE Phone = :Phone");
		$query->bindParam(":Phone", $obj['Phone'], PDO::PARAM_STR);
		$query->execute();
		$que = $query->fetch();
		if(!$que){
			$check = false;
		}
	}
	if($check){
                $pin = generatePIN(4);
		list($otp_code,$message) = sendotp( $obj['Phone'],$pin,"pin");
	        //$otp_code = "2527";
	//$res = sendsms($phone, $otp_code,$senderid = "RxHealth");
	$response['val'] = $otp_code;
	//$response['otp'] = $output;
	$response['number'] = $obj['Phone'];
	$response['ResponseMessage'] = "Sent SMS Successfully";
	$response['ResponseCode'] = "200";
}else{
	$response['ResponseMessage'] = "Number not registered";
	$response['ResponseCode'] = "500";
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
    echo json_encode($response);
}
?>
