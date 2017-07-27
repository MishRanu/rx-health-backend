<?php
class Article {
  function __construct( $userid, $pref = null, $db ) {
    $this->basestatement = "SELECT distinct(sa.ShrID), CONCAT_WS(' ',u.FName,u.LName) AS FullName,sa.T1,a.Header,sa.Summary,a.Link,a.Details,a.Type,a.ImageLink,sa.UserID,sa.IsAuthor,sa.LikesCount,sa.CommentCount FROM ShareArticle sa INNER JOIN user u ON sa.UserID = u.UserID INNER JOIN Articles a ON a.ArID = sa.ArID";
    $this->userid = $userid;
    $this->preferences = $pref;
    $this->db = $db;
  }

  private function fetchit($typestatement = "", $type, $connect=null, $db){
    $statement = $this->basestatement;
    if($this->preferences){
      if($this->preferences['tagids']){
        $statement.=" INNER JOIN ArticleTags at ON at.ArID = sa.ArID WHERE at.TagID IN (".$this->preferences['tagids'].") ";
      }
      if($this->preferences['doctorids']){
        $statement.=($this->preferences['tagids'])?"AND ":" WHERE ";
        $statement.="sa.UserID IN (".$this->preferences['doctorids'].") ";
      }
    }
    $statement.= $typestatement." ORDER BY sa.T1 DESC";
    $result = $db->prepare($statement);
    $result->execute();
    while($row = $result->fetch()){
      $article[] = array('Author' => $row['FullName'],
      'LastEdited' => $row['T1'],
      'Header' => $row['Header'],
      'Summary' => $row['Summary'],
      'Link' => $row['Link'],
      'Details' => $row['Details'],
      'FromRxHealth' => $row['Type'],
      'Type' => $type,
      'ImageLink' => $row['ImageLink'],
      'Likes' => $row['LikesCount'],
      'Comments' => $row['CommentCount']);
      if($type != "public"){
        switch($connect[$row['ShrID']]){
          case 0:
          $action = array("Like","Share");
          break;
          case 1:
          $action = array("Comment","Like","Share");
          break;
          case 3:
          case 2:
          $action = array("Delete","Comment","Like","Share");
          break;
        }
        if($this->userid == $row['UserID']){
          if($row['IsAuthor'] == 1){
            $action[] = "EditAll";
          }else{
            $action[] = "EditSummary";
          }
        }
        $article['action'] = $action;
      }
      $articles[] = $article;
    }
    return $articles;
  }

  function getarticles($public=false,$follow=false,$connect=false,$admin=false,$creator=false, $db){
    $stat = "";
    $stat .= ($follow)?"0,":"";
    $stat .= ($connect)?"1,":"";
    $stat .= ($admin)?"2,":"";
    $stat .= ($creator)?"3,":"";
    $articles = array();
    if($stat != ""){
      $stat = substr($stat,0,-1);
      $temp = $this->getotherarticle($stat, $db);
      if($temp){
        $articles = $temp;
      }
    }
    if($public){
      $articles[] = $this->fetchit(" AND isPublic=1", "public",null, $db);
    }
    return $articles;
  }

  private function getotherarticle($list, $db){
    $result = $db->prepare("SELECT CommuID,UserType FROM Dconnection WHERE UserID = :UserID AND UserType IN (:List)");
    $result->bindParam(":UserID",$this->userid,PDO::PARAM_INT);
    $result->bindParam(":List",$list,PDO::PARAM_INT);
    $result->execute();
    while($row = $result->fetch()){
      $result2 = $db->prepare("SELECT ShrID FROM Community WHERE CommuID = :CommuID");
      $result2->bindParam(":CommuID",$row['CommuID'],PDO::PARAM_INT);
      $result2->execute();
      while($row2 = $result2->fetch()){
        $arr = $arr.(string)$row2['ShrID'].",";
        $type[$row2['ShrID']] = $row['UserType'];
      }
    }
    if($arr){
      $arr = substr($arr,0,-1);
      return $this->fetchit("AND ShrID IN (".$arr.")","other",$type, $db);
    }else{
      return null;
    }
  }
}
function createarticle($userid,$header,$summary,$link,$details,$imagelink, $db){
  if (filter_var($link, FILTER_VALIDATE_URL) === $link) {
    $query2 = $db->prepare("SELECT SpecID FROM doctorspec WHERE DID = :DID");
    $query2->bindParam(":DID", $userid, PDO::PARAM_INT);
    $query2->execute();
    preg_match_all('/(?<!\w)#\w+/',$details.' '.$summary,$matches);
    $match = $matches[0];
    while($row = $query2->fetch()){
      for($i = 0; $i < count($match);$i++){
        $query = $db->prepare("INSERT INTO Tags (Tag,SpecID) VALUES (:Tag,:SpecID)");
        $query->bindParam(":Tag", $match[$i], PDO::PARAM_STR);
        $query->bindParam(":SpecID", $row['SpecID'], PDO::PARAM_STR);
        $query->execute();
      }
    }
    $summary = hashtag($summary);
    $details = hashtag($details);
    $result = $db->prepare("INSERT INTO Articles (UserID,Header,Summary,Link,Details,Type,ImageLink)
    VALUES (:UserID,:Header,:Summary,:Link,:Details,0,:ImageLink)");
    $result->bindParam(':UserID', $userid,PDO::PARAM_INT);
    $result->bindParam(':Header', $header,PDO::PARAM_STR);
    $result->bindParam(':Summary', $summary,PDO::PARAM_STR);
    $result->bindParam(':Link', $link,PDO::PARAM_STR);
    $result->bindParam(':Details', $details,PDO::PARAM_STR);
    $result->bindParam(':ImageLink', $imagelink,PDO::PARAM_STR);
    $result->execute();
    return $db->lastInsertId();
  } else {
    return 0;
  }
}

function createcommunity($name,$userid,$Type,$db){
  $result = $db->prepare("INSERT INTO ComDetails (Name,ComType) VALUES (:Name,:ComType)");
  $result->bindParam(":Name", $name, PDO::PARAM_STR);
  $result->bindParam(":ComType", $Type, PDO::PARAM_STR);
  $result->execute();
  $commuid = $db->lastInsertId();
  addmemberstocommunity($commuid,$userid,3,$db);
  return $commuid;
}

function addmemberstocommunity($commuid, $userid, $type,$db){
  $result = $db->prepare("INSERT INTO Dconnection (CommuID,UserID,UserType) VALUES (".(string)$commuid.",".(string)$userid.",".(string)$type.")");
  $result->execute();
  return $db->lastInsertId();
}

function sharearticle($userid,$summary = null,$aid,$author,$public,$comid,$db){
   if(!$summary){
     $query = $db->prepare("SELECT Summary FROM Articles WHERE ArID = :ArID");
     $query->bindParam(":ArID",$aid,PDO::PARAM_INT);
     $query->execute();
     $row = $query->fetch();
     $summary = $row['Summary'];
   }
   $result = $db->prepare("INSERT INTO ShareArticle (UserID,Summary,ArID,IsAuthor,isPublic,CommuID)
   VALUES (:UserID,:Summary,:ArID,:IsAuthor,:isPublic,:CommuID)");
   $result->bindParam(':UserID', $userid,PDO::PARAM_INT);
   $result->bindParam(':Summary', $summary,PDO::PARAM_STR);
   $result->bindParam(':ArID', $aid,PDO::PARAM_INT);
   $result->bindParam(':IsAuthor', $author,PDO::PARAM_INT);
   $result->bindParam(':isPublic', $public,PDO::PARAM_INT);
   $result->bindParam(':CommuID', $comid,PDO::PARAM_INT);
   $result->execute();
   $shrid = $db->lastInsertId();
   return $shrid;
}
function getcommunities($UserId,$db)
{
  $myCommunities = array();
  $adminCommunities = array();
  $connectCommunities = array();
  $following = array();
  $results = $db->prepare("SELECT * FROM Dconnection WHERE UserID = :UserID");
  $results->bindParam(":UserID", $UserId, PDO::PARAM_INT);
  $results->execute();
  while($row = $results->fetch()){
    $usertype = $row['UserType'];
    $details = $db->prepare("SELECT * FROM ComDetails WHERE CommuID= :ComID");
    $details->bindParam(":ComID",$row['CommuID'],PDO::PARAM_INT);
    $details->execute();
    $detail = $details->fetch();
    if($usertype == 3){
      $myCommunities[] = array("CID" => $row['CID'],"CommuID" => $row['CommuID'],"UserType" => "3","Type" => $detail['ComType'],"Name" => $detail['Name']);
    }
    elseif ($usertype == 2) {
      $adminCommunities[] = array("CID" => $row['CID'],"CommuID" => $row['CommuID'],"UserType" => "2","Type" => $detail['ComType'],"Name" => $detail['Name']);
    }
    elseif ( $usertype == 1) {
      $connectCommunities[] = array("CID" => $row['CID'],"CommuID" => $row['CommuID'],"UserType" => "1","Type" => $detail['ComType'], "Name" => $detail['Name']);
    }
    elseif ($usertype == 0) {
      $following[] = array("CID" => $row['CID'],"CommuID" => $row['CommuID'],"UserType" => "0","Type" => $detail['ComType'], "Name" => $detail['Name']);
    }
  }
  return [$myCommunities,$adminCommunities,$connectCommunities,$following];

}
function generatePIN($digits){
      $i = 0; //counter
      $pin = ""; //our default pin is blank.
      while($i < $digits){
          //generate a random number between 0 and 9.
        $pin .= mt_rand(0, 9);
        $i++;
      }
      return $pin;
  }
function sendotp($phone,$otp_code,$type,$doctor = null){
    $otpData = array();
    
  //$otp_code = generatePIN(4);
  if($type == "pin"){
    $message = urlencode("OTP verification code for RxHealth ". $otp_code);
  }
  if($type == "selfCreated" ){
    $message = urlencode("Your profile is successfully created on RxHealth  having userId ".$phone. " and password ". $otp_code);
  }
  else if($type == "doctorCreated"){
    $message = urlencode("Your ". $doctor." requested to created your profile on RxHealth having userId ".$phone. " and password ". $otp_code ." https://goo.gl/3zSwgJ " );
  }

  $postData = array(
      'authkey' => '120841A6OG9IViGkvK579eee11',
      'mobiles' => $phone,
      'message' => $message,
      'sender' => "RxHLTH",
      'route' => $route
  );

  //API URL
  $url="https://control.msg91.com/api/sendhttp.php";

  // init the resource
  $ch = curl_init();
  curl_setopt_array($ch, array(
      CURLOPT_URL => $url,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_POST => true,
      CURLOPT_POSTFIELDS => $postData
      //,CURLOPT_FOLLOWLOCATION => true
  ));


  //Ignore SSL certificate verification
  curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);


  //get response
  $output = curl_exec($ch);

  //Print error if any
  if(curl_errno($ch))
  {
      echo 'error:' . curl_error($ch);
  }

  curl_close($ch);

  return [$otp_code,$message];

          //$otp_code = "2527";
  //$res = sendsms($phone, $otp_code,$senderId);
}




?>
