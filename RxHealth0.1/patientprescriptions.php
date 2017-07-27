<?php
header("Access-Control-Allow-Origin: *");
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Origin, Content-Type, Accept, Authorization, X-Request-With');
header('Access-Control-Allow-Credentials: true');
//$json=$_GET ['json'];
include('db_config.php');


$json = file_get_contents('php://input');
$obj = json_decode($json, true);

$previous = array();
	try
		{
			$result4 = $db->prepare(" SELECT bh.AID, bh.DID, bh.PFID, dm.MedDate from appointment3 bh inner join doctormedicine dm where bh.AID = dm.AID and bh.PID=:PID group by bh.AID order by bh.AID desc ");
			$result4->bindParam(':PID', $obj['UserID'], PDO::PARAM_INT);
			$result4->execute();
			while ($row4 = $result4->fetch())
			{
				$result5 = $db->prepare(" SELECT u.FName, u.LName, u.Pic from user u inner join doctorprofile dp where UserID = :UserID and u.UserID = dp.DID");
				$result5->bindParam(':UserID', $row4['DID'], PDO::PARAM_INT);
				$result5->execute();
				$row5 = $result5->fetch();

				$result6 = $db->prepare(" SELECT Symptom from patientfinalsymptom where PFID = :PFID and SymptomChoice ='present' ");
				$result6->bindParam(':PFID', $row4['PFID'], PDO::PARAM_INT);
				$result6->execute();
				$symptoms = "";
				while ($row6 = $result6->fetch())
				{
					$symptoms = $symptoms.$row6['Symptom'].", ";
				}
				$symptoms = substr($symptoms, 0, -2);

				if(is_null($row4['Pic']))
							$fpic = "http://ec2-52-37-68-149.us-west-2.compute.amazonaws.com/default.png";
						else
							$fpic = $row4['Pic'];

				$date = date("d/m/y", strtotime($row4['MedDate']));

				$previous[] = array('FName' => (string)$row5['FName'], 'LName' => (string)$row5['LName'], 'Pic' => (string)$fpic, 'AID' => (string)$row4['AID'], 'Date' => (string)$date, 'Symptom' => (string)$symptoms );

			}


			$response['ResponseCode'] = "200";
			$response['ResponseMessage'] = "Patient Prescription List";
			$response['PreviousRecords'] = $previous;

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
// }
