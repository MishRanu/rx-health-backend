<?php
header("Access-Control-Allow-Origin: *");
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Origin, Content-Type, Accept, Authorization, X-Request-With');
header('Access-Control-Allow-Credentials: true');
//$json=$_GET ['json'];
require('db_config.php');
require('helperfunctions1.php');

$json = file_get_contents('php://input');
$obj = json_decode($json, true);

try{
      if($obj['Data']){
        $str = "%".(string)$obj['Data']."%";
        $query2 = $db->prepare("SELECT TagID, Speciality from Tags where Speciality like :Data");
        $query2->bindParam(':Data', $str, PDO::PARAM_STR);
        $query2->execute();
        while ($specs = $query2->fetch())
        {
          $obj['tagids'] = $obj['tagids'].(string)$specs['TagID'].",";
        }
        if($obj['tagids']){
          $obj['tagids'] = substr($obj['tagids'],0,-1);
        }
        $query = $db->prepare("SELECT UserID FROM user where CONCAT_WS(' ', 'Dr.', FName, LName) like :Data and IsDoctor=1");
        $query->bindParam(':Data', $str, PDO::PARAM_STR);
        $query->execute();
        while ($doc = $query->fetch())
        {
          $obj['doctorids'] = $obj['doctorids'].(string)$doc['UserID'].",";
        }
        if($obj['doctorids']){
          $obj['doctorids'] = substr($obj['doctorids'],0,-1);
        }
        if($obj['tagids'] || $obj['doctorids']){
          $pref = array("tagids" => $obj['tagids'],"doctorids" => $obj['doctorids']);
        }
      }
      $article = new Article($obj['UserID'],$pref,$db);
      if($obj['CommuID']){
        $response['Articles'] = $article->getcommunityarticles($obj['CommuID']);
      }else{
        $response['Articles'] = $article->getallarticles();
      }
      $response['ResponseCode'] = "200";
			$response['ResponseMessage'] = "Article List Data";
      $status['Status'] = $response;
			header('Content-type: application/json');
			echo json_encode($status);
}catch(PDOException $ex)
{
	$response['ResponseCode'] = "500";
    $response['ResponseMessage'] = "An Error occured!" . $ex; //user friendly message
    $status['Status'] = $response;
    header('Content-type: application/json');
	echo json_encode($status);
}
