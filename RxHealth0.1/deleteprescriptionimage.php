<?php  
header("Access-Control-Allow-Origin: *");
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Origin, Content-Type, Accept, Authorization, X-Request-With');
header('Access-Control-Allow-Credentials: true');
//$json=$_GET ['json'];
include('db_config.php');

$json = file_get_contents('php://input');
$obj = json_decode($json, true);


	try 
		{
			$result = $db->prepare("DELETE FROM prescriptionimages WHERE PresID = :PresID AND UserID = :UserID");
			$result->bindParam(':PresID', $obj['PresID'], PDO::PARAM_INT);
			$result->bindParam(':UserID', $obj['UserID'], PDO::PARAM_INT); 
			$result->execute();

            $response['ResponseCode'] = "200";
		    $response['ResponseMessage'] = " Image Deleted Successfully";
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
