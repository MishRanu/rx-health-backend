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
      $pref = $obj['Pref'];
      $book = ($pref['Bookmark'])?1:0;
      $article = new Article($obj['UserID'],$pref,$db, $obj['count']);
      if($obj['CommuID']){
        $response['Articles'] = $article->getcommunityarticles($obj['CommuID']);
      }elseif($obj['ShrID']){
        $response['Articles'] = $article->getsharedarticle($obj['ShrID']);
      }else if($obj['Tag']){
        $response['Articles'] = $article->gettagarticle((string)$obj['Tag']);
      }else{
        $response['Articles'] = $article->getallarticles($book);
      }
      $obj['count']++;
      $response['count'] = $obj['count'];
      $response['ResponseCode'] = "200";
			$response['ResponseMessage'] = "Article List Data";
      $status['Status'] = $response;
			header('Content-type: application/json');
			echo json_encode($status);
}catch(PDOException $ex){
	$response['ResponseCode'] = "500";
    $response['ResponseMessage'] = "An Error occured!" . $ex; //user friendly message
    $status['Status'] = $response;
    header('Content-type: application/json');
	echo json_encode($status);
}
