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
		$result3 = $db->prepare("DELETE from doctorfinalsymptom where PFID = :PFID");
		$result3->bindParam(':PFID', $obj['PFID'], PDO::PARAM_STR);
		$result3->execute();
		foreach($obj['Symptoms'] as $symptoms) 
		{	
			$result5 = $db->prepare("INSERT INTO doctorfinalsymptom (PFID, Symptom, SymptomChoice) VALUES (:PFID, :Symptom, :SymptomChoice)");
			$result5->bindParam(':PFID', $obj['PFID'], PDO::PARAM_STR);
			$result5->bindParam(':Symptom', $symptoms['name'], PDO::PARAM_STR);
			$result5->bindParam(':SymptomChoice', $symptoms['choice_id'], PDO::PARAM_STR);
			$result5->execute();	
		}

		$result = $db->prepare("DELETE from doctorcondition where PFID = :PFID");
		$result->bindParam(':PFID', $obj['PFID'], PDO::PARAM_STR);
		$result->execute();

		foreach($obj['Conditions'] as $conditions) 
		{
			$result2 = $db->prepare("INSERT INTO doctorcondition (PFID, ConditionName, CondProb) VALUES (:PFID, :ConditionName, :CondProb)");
			$result2->bindParam(':PFID', $obj['PFID'], PDO::PARAM_STR);
			$result2->bindParam(':ConditionName', $conditions['ConditionName'], PDO::PARAM_STR);
			$result2->bindParam(':CondProb', $conditions['CondProb'], PDO::PARAM_STR);
			$result2->execute();
		}

	$response['ResponseCode'] = "200";
	$response['ResponseMessage'] = "Patient Symptoms Updated";

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
