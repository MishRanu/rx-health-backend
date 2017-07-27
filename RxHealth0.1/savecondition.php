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
			$count = count($obj['Conditions']);
			if($count<=10)
			{
					foreach($obj['Conditions'] as $conditions) 
				{
						$condprob = $conditions['CondProb']*100;
						$result5 = $db->prepare("INSERT INTO patientcondition (PFID, ConditionName, CondProb) VALUES (:PFID, :ConditionName, :CondProb)");
						$result5->bindParam(':PFID', $obj['PFID'], PDO::PARAM_STR);
						$result5->bindParam(':ConditionName', $conditions['ConditionName'], PDO::PARAM_STR);
						$result5->bindParam(':CondProb', $condprob, PDO::PARAM_STR);
						$result5->execute();
						$result15 = $db->prepare("INSERT INTO doctorcondition (PFID, ConditionName, CondProb) VALUES (:PFID, :ConditionName, :CondProb)");
						$result15->bindParam(':PFID', $obj['PFID'], PDO::PARAM_STR);
						$result15->bindParam(':ConditionName', $conditions['ConditionName'], PDO::PARAM_STR);
						$result15->bindParam(':CondProb', $condprob, PDO::PARAM_STR);
						$result15->execute();
				}
			}
			else
			{
				for ($i=0; $i < 10; $i++) 
				{ 
					$conditions = $obj['Conditions'];
				 		$condprob = $obj['Conditions'][$i]['CondProb']*100;
				 		$condname = $obj['Conditions'][$i]['ConditionName'];
						$result5 = $db->prepare("INSERT INTO patientcondition (PFID, ConditionName, CondProb) VALUES (:PFID, :ConditionName, :CondProb)");
						$result5->bindParam(':PFID', $obj['PFID'], PDO::PARAM_STR);
						$result5->bindParam(':ConditionName', $condname, PDO::PARAM_STR);
						$result5->bindParam(':CondProb', $condprob, PDO::PARAM_STR);
						$result5->execute();
						$result15 = $db->prepare("INSERT INTO doctorcondition (PFID, ConditionName, CondProb) VALUES (:PFID, :ConditionName, :CondProb)");
						$result15->bindParam(':PFID', $obj['PFID'], PDO::PARAM_STR);
						$result15->bindParam(':ConditionName', $condname, PDO::PARAM_STR);
						$result15->bindParam(':CondProb', $condprob, PDO::PARAM_STR);
						$result15->execute();
				} 
			}
			

			$response['ResponseCode'] = "200";
			$response['ResponseMessage'] = "Patient Conditions Submitted";
			
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
