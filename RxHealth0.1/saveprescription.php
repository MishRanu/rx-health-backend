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
			$query = $db->prepare("SELECT * from doctortempmedicine where AID = :AID");
			$query->bindParam(':AID', $obj['AID'], PDO::PARAM_STR);
			$query->execute();
			while ($prescription = $query->fetch()) 
			{
				$result = $db->prepare("INSERT into doctormedicine (AID, PFID, MID, Dosage, Type, Morning, Afternoon, Night, IsAfter, OnNeed, Days, MedDate)
				 values (:AID, :PFID, :MID, :Dosage, :Type, :Morning, :Afternoon, :Night, :IsAfter, :OnNeed, :Days, Now())");
				$result->bindParam(':AID', $prescription['AID'], PDO::PARAM_STR);
				$result->bindParam(':PFID', $prescription['PFID'], PDO::PARAM_STR);
				$result->bindParam(':MID', $prescription['MID'], PDO::PARAM_STR);
				$result->bindParam(':Dosage', $prescription['Dosage'], PDO::PARAM_STR);
				$result->bindParam(':Type', $prescription['Type'], PDO::PARAM_STR);
				$result->bindParam(':Morning', $prescription['Morning'], PDO::PARAM_STR);
				$result->bindParam(':Afternoon', $prescription['Afternoon'], PDO::PARAM_STR);
				$result->bindParam(':Night', $prescription['Night'], PDO::PARAM_STR);
				$result->bindParam(':IsAfter', $prescription['IsAfter'], PDO::PARAM_STR);
				$result->bindParam(':OnNeed', $prescription['OnNeed'], PDO::PARAM_STR);
				$result->bindParam(':Days', $prescription['Days'], PDO::PARAM_STR);
				$result->execute();
			}
			$query2 = $db->prepare("DELETE from doctortempmedicine where AID = :AID");
			$query2->bindParam(':AID', $obj['AID'], PDO::PARAM_STR);
			$query2->execute();

			$query3 = $db->prepare("SELECT * from doctortemptest where AID = :AID");
			$query3->bindParam(':AID', $obj['AID'], PDO::PARAM_STR);
			$query3->execute();
			while($test = $query3->fetch())
			{
				$result2 = $db->prepare("INSERT into doctortest (AID, PFID, TID, TestDate)
				 values (:AID, :PFID, :TID, Now())");
				$result2->bindParam(':AID', $test['AID'], PDO::PARAM_STR);
				$result2->bindParam(':PFID', $test['PFID'], PDO::PARAM_STR);
				$result2->bindParam(':TID', $test['TID'], PDO::PARAM_STR);
				$result2->execute();
			}
			$query4 = $db->prepare("DELETE from doctortemptest where AID = :AID");
			$query4->bindParam(':AID', $obj['AID'], PDO::PARAM_STR);
			$query4->execute();
				
			$query5 = $db->prepare("SELECT * from doctortempcomment where AID = :AID");
			$query5->bindParam(':AID', $obj['AID'], PDO::PARAM_STR);
			$query5->execute();
			while($comment = $query5->fetch())
			{
				$result3 = $db->prepare("INSERT into doctorcomment (AID, PFID, Comment)
				 values (:AID, :PFID, :Comment)");
				$result3->bindParam(':AID', $comment['AID'], PDO::PARAM_STR);
				$result3->bindParam(':PFID', $comment['PFID'], PDO::PARAM_STR);
				$result3->bindParam(':Comment', $comment['Comment'], PDO::PARAM_STR);
				$result3->execute();
			}
			$query6 = $db->prepare("DELETE from doctortempcomment where AID = :AID");
			$query6->bindParam(':AID', $obj['AID'], PDO::PARAM_STR);
			$query6->execute();

			$query7 = $db->prepare("SELECT * from doctortempnotes where AID = :AID");
			$query7->bindParam(':AID', $obj['AID'], PDO::PARAM_STR);
			$query7->execute();
			while($notes = $query7->fetch())
			{
				$result4 = $db->prepare("INSERT into doctornotes (AID, Notes)
				 values (:AID, :Notes)");
				$result4->bindParam(':AID', $notes['AID'], PDO::PARAM_STR);
				$result4->bindParam(':Notes', $notes['Notes'], PDO::PARAM_STR);
				$result4->execute();
			}
			$query8 = $db->prepare("DELETE from doctortempnotes where AID = :AID");
			$query8->bindParam(':AID', $obj['AID'], PDO::PARAM_STR);
			$query8->execute();
			// API access key from Google API's Console
			define( 'API_ACCESS_KEY', 'AIzaSyBKh75Fb7Ly6njtZYviL-CIN9ewkhPpTeM' );

			$query9 = $db->prepare("SELECT DID, PID from appointment3 where AID = :AID");
			$query9->bindParam(':AID', $obj['AID'], PDO::PARAM_STR);
			$query9->execute();
			$row = $query9->fetch();

			$query11 = $db->prepare("UPDATE appointment3 SET Status='Complete' where AID = :AID");
			$query11->bindParam(':AID', $obj['AID'], PDO::PARAM_STR);
			$query11->execute();

			$query10 = $db->prepare("SELECT RegistrationID from registrationid where UserID = :UserID");
			$query10->bindParam(':UserID', $row['PID'], PDO::PARAM_STR);
			$query10->execute();
			$row2 = $query10->fetch();

			$registrationIds[] = $row2['RegistrationID'];
			// prep the bundle
			// $msg = array(
			//   "notification":{
			//     "title":"Notification title",  //Any value 
			//     "body":"Notification body",  //Any value 
			//     "sound":"default", //If you want notification sound 
			//     "click_action":"FCM_PLUGIN_ACTIVITY",  //Must be present for Android 
			//     "icon":"fcm_push_icon"  //White icon Android resource 
			//   },
			//   "data":{
			//     "param1":"value1",  //Any data to be retrieved in the notification callback 
			//     "param2":"value2"
			//   },
			//     "registration_ids":$registrationIds, //Topic or single device 
			//     "priority":"high", //If not set, notification won't be delivered on completely closed iOS app 
			//     "restricted_package_name":"" //Optional. Set for application filtering 
			// );

			// $ch = curl_init("https://fcm.googleapis.com/fcm/send");
			// $header=array('Authorization: key=' . API_ACCESS_KEY,
			// 	'Content-Type: application/json');
			// curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
			// curl_setopt( $ch,CURLOPT_SSL_VERIFYPEER, false );

			// curl_setopt($ch, CURLOPT_POST, 1);
			// curl_setopt($ch, CURLOPT_POSTFIELDS, $msg);

			// curl_exec($ch);
			// curl_close($ch);
			// $msg = array
			// (
			// 	'message' 	=> 'You have got a new Prescription from your doctor',
			// 	'title'		=> 'New Prescription Received',
			// 	'subtitle'	=> '',
			// 	'tickerText'	=> '',
			// 	'vibrate'	=> 1,
			// 	'sound'		=> 1,
			// 	'largeIcon'	=> 'large_icon',
			// 	'smallIcon'	=> 'small_icon'
			// );
			// $fields = array
			// (
			// 	'to' 	=> $registrationIds,
			// 	'data'			=> $msg
			// );
			 
			// $headers = array
			// (
			// 	'Authorization: key=' . API_ACCESS_KEY,
			// 	'Content-Type: application/json'
			// );
			 
			// $ch = curl_init();
			// curl_setopt( $ch,CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send' );
			// curl_setopt( $ch,CURLOPT_POST, true );
			// curl_setopt( $ch,CURLOPT_HTTPHEADER, $headers );
			// curl_setopt( $ch,CURLOPT_RETURNTRANSFER, true );
			// curl_setopt( $ch,CURLOPT_SSL_VERIFYPEER, false );
			// curl_setopt( $ch,CURLOPT_POSTFIELDS, json_encode( $fields ) );
			// $result5 = curl_exec($ch );
			// curl_close( $ch );
			$message = "You have received a prescription from your doctor";

			$url = 'https://fcm.googleapis.com/fcm/send';
			//api_key available in Firebase Console -> Project Settings -> CLOUD MESSAGING -> Server key
			$server_key = 'AIzaSyBKh75Fb7Ly6njtZYviL-CIN9ewkhPpTeM';
						
			define("GOOGLE_API_KEY", "AIzaSyBKh75Fb7Ly6njtZYviL-CIN9ewkhPpTeM");
			 define("GOOGLE_GCM_URL", "https://fcm.googleapis.com/fcm/send");
			 
			 $fields = array(
			 
			 "registration_ids" => $registrationIds ,
			 "priority" => "high",
			 "notification" => array( "title" => "Prescription received", "body" => $message, "sound" =>"default", "click_action" =>"FCM_PLUGIN_ACTIVITY", "icon" =>"fcm_push_icon", "iconColor" => "blue" ),
			 "data" => array("message" =>$message, "title" => "Prescription received", "image"=> $img_url),
			 );
			 
			 $headers = array(
			 GOOGLE_GCM_URL,
			 'Content-Type: application/json',
			 'Authorization: key=' . GOOGLE_API_KEY 
			 );
			 
			 echo "<br>";

			 $ch = curl_init();
			 curl_setopt($ch, CURLOPT_URL, GOOGLE_GCM_URL);
			 curl_setopt($ch, CURLOPT_POST, true);
			 curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			 curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			 curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			 curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
			 
			 $result5 = curl_exec($ch);
			 if ($result5 === FALSE) {
			 die('Problem occurred: ' . curl_error($ch));
			 }
			 
			 curl_close($ch);

			$response['ResponseCode'] = "200";
			$response['ResponseMessage'] = "Data Saved";
			$response['CurlResponse'] = $result5;
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
