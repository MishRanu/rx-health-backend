<?php
header("Access-Control-Allow-Origin: *");
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Origin, Content-Type, Accept, Authorization, X-Request-With');
header('Access-Control-Allow-Credentials: true');
include('db_config.php');
include('helperfunctions.php');

$json = file_get_contents('php://input');
$obj = json_decode($json, true);
try{
	list($myCommunities, $adminCommunities,$connectCommunities,$following) = getcommunities($obj['UserID'],$db);
	$response['ResponseMessage'] = "Sent Communities Successfully";
	$response['ResponseCode'] = "200";
    $response['myCommunities'] = $myCommunities;
    $response['adminCommunities'] = $adminCommunities;
    $response['connectCommunities'] = $connectCommunities;
    $response['following'] = $following;
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
	