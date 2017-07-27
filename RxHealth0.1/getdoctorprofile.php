<?php
header("Access-Control-Allow-Origin: *");
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Origin, Content-Type, Accept, Authorization, X-Request-With');
header('Access-Control-Allow-Credentials: true');
//$json=$_GET ['json'];
include('db_config.php');

$json = file_get_contents('php://input');
$obj = json_decode($json, true);

function haversineGreatCircleDistance($latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo, $earthRadius = 6371000)
{
  // convert from degrees to radians
  $latFrom = deg2rad($latitudeFrom);
  $lonFrom = deg2rad($longitudeFrom);
  $latTo = deg2rad($latitudeTo);
  $lonTo = deg2rad($longitudeTo);

  $latDelta = $latTo - $latFrom;
  $lonDelta = $lonTo - $lonFrom;

  $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
    cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
  return $angle * $earthRadius;
}

	try
		{
			$result = $db->prepare("SELECT u.FName,u.LName,u.Phone, u.Email, u.Gender,u.DOB,dp.Summary,dp.ExStart,dp.ExEnd,dp.Fee,dp.RegNo,dp.RegAssoc,dp.RegYear,u.Pic,dp.DoctorSign,dp.IsVerified from doctorprofile dp inner join user u where dp.DID = u.UserID and dp.DID=:UserID");
			$result->bindParam(':UserID', $obj['DID'], PDO::PARAM_INT);
			$result->execute();
			$row = $result->fetch();
			$result2 = $db->prepare(" SELECT ds.SpecID, s.Speciality FROM doctorspec ds inner join speciality s where ds.SpecID = s.SpecID and ds.DID=:UserID");
			$result2->bindParam(':UserID', $obj['DID'], PDO::PARAM_STR);
			$result2->execute();
			if(is_null($row['Pic']))
				$fpic = "http://ec2-52-37-68-149.us-west-2.compute.amazonaws.com/default.png";
			else
				$fpic = $row['Pic'];
			//$speciality=array();
			while ($row2 = $result2->fetch())
			{
				$speciality[] = array('name' => (string)$row2['Speciality']);
				//$speciality = $speciality.$row2['Speciality']." | ";
			}
			//$speciality = substr($speciality, 0, -3);

			$result4 = $db->prepare(" SELECT dd.DegreeID, Degree FROM doctordegree dd inner join degree d on dd.DegreeID
				where dd.DegreeID = d.DegreeID and DID=:UserID");
			$result4->bindParam(':UserID', $obj['DID'], PDO::PARAM_STR);
			$result4->execute();
			//$degree = "";
			while ($row4 = $result4->fetch())
			{
				$degree[] = array('name' => (string)$row4['Degree']);
			}
			//$degree = substr($degree, 0, -3);

			$result5 = $db->prepare(" SELECT DocAffil FROM doctoraffil where DID=:UserID");
			$result5->bindParam(':UserID', $obj['DID'], PDO::PARAM_STR);
			$result5->execute();
			//$affiliation = "";
			while ($row5 = $result5->fetch())
			{
				$affiliation[] = array('name' => (string)$row5['DocAffil'] );
				//$affiliation = $affiliation.$row5['DocAffil']." | ";
			}
			//$affiliation = substr($affiliation, 0, -3);
			$result6 = $db->prepare("SELECT * FROM clinics where DID=:UserID");
			$result6->bindParam(':UserID', $obj['DID'], PDO::PARAM_STR);
			$result6->execute();
			while ($row6 = $result6->fetch())
			{
				$temp = haversineGreatCircleDistance((float)$row6['ClinicLat'],(float)$row6['ClinicLong'],(float)$obj['Lat'],(float)$obj['Long']);
				$clinics[] = array("ClinicID" => $row6['ClinicID'], "ClinicName" => $row6['ClinicName'], "ClinicSpec" => $row6['ClinicSpec'],"Summary" => $row6['Summary'],"Address" => (string)$row6['Address1']." ".$row6['Address2'],
				"City" => $row6['City'], "PinCode" => $row6['PinCode'], "ClinicEmail" => $row6['ClinicEmail'], "ClinicPhone" => $row6['ClinicPhone'], "ClinicLogo" => $row6['ClinicLogo'], "Distance" => $temp);
			}
			$datetime = new DateTime(date("Y-m-d H:i:s"));
			$datetime1 = new DateTime($row['ExStart']);
			$interval = $datetime1->diff($datetime);
			$interval = $interval->format('%y');

			$response['DoctorData'] = array("DID" => $obj['DID'],
			"Name" => "Dr. ".(string)$row['FName']." ".(string)$row['LName'],
			"FName" => $row['FName'],
			"LName" => $row['LName'],
			"Phone" => $row['Phone'],
			"Email" => $row['Email'],
			"Sex" => $row['Gender'],
			"DOB" => $row['DOB'],
			"Summary" => $row['Summary'],
			"Experience" => $interval,
			"ExStart" => $row['ExStart'],
			"ExEnd" => $row['ExEnd'],
			"Fee" => $row['Fee'],
			"RegNo" => $row['RegNo'],
			"RegAssoc" => $row['RegAssoc'],
			"RegYear" => $row['RegYear'],
			"Pic" => $fpic,
			"DoctorSign" => $row['DoctorSign'],
			"IsVerified" => $row['IsVerified'],
			"Speciality" => $speciality,
			"Degree" => $degree,
			"Affiliation" => $affiliation,
			"Clinics" => $clinics);
			$response['ResponseCode'] = "200";
			$response['ResponseMessage'] = "Doctor-Data";
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
