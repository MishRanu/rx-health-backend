<?php
/*
Dconnection Table :
UserType :
0 : follower
1 : connection
2 : admin
3 : creator
*/

define( 'API_ACCESS_KEY', 'AIzaSyByIOl-mW0pu8SEXFeutB8jq59hhiau0wI' );
define( 'USERNAME', 'kapbulk' );
define( 'PASSWORD', 'kap@user!23' );

function hashtag($str){
  return preg_replace("/#([A-Za-z0-9\/\.]*)/", "<font style='color : blue'>#$1</font>", $str);
}

function sendsms($senderid = "RxHealth",$dest_mobileno, $sms){
  $url = sprintf("http://123.63.33.43/blank/sms/user/urlsmstemp.php?username=XXXXXX&pass=XXXXX&senderid=XXXXX&dest_mobileno=XXXXX&message=XXXXXX&mtype=UNI&response=Y", USERNAME, PASSWORD, $senderid, $dest_mobileno, $message, urlencode($sms) );
  $ch=curl_init();
  curl_setopt($ch,CURLOPT_URL,$url);
  curl_setopt($ch, CURLOPT_POST, 1);
  curl_setopt($ch,CURLOPT_POSTFIELDS,$postvars);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch,CURLOPT_TIMEOUT, '3');
  $content = trim(curl_exec($ch));
  curl_close($ch);
  return $content;
}

function pushnotification($userid, $title, $message, $tag = null , $data = null,$priority = "high"){
      $query10 = $db->prepare("SELECT Token from user where UserID = :UserID");
      $query10->bindParam(":UserID", $userid, PDO::PARAM_INT);
			$query10->execute();
      $que = $query10->fetch();
      $token = $que['Token'];

      $notification = array(
        "title" => $title,  //Any value
        "body" => $message,  //Any value
        "sound" => "default", //If you want notification sound
        "click_action" => "FCM_PLUGIN_ACTIVITY",  //Must be present for Android
        "icon" => "fcm_push_icon",
        "badge" => "m"
      );
			$fields = array(
        'to' => $token,
        'priority' => $priority,
        'content_available' => true,
        'notification' => $notification
			);
      if($tag){
        $fields['collapse_key'] = $tag;
        $fields['notification']['tag'] = $tag;
      }
      if($data){
        $fields['data'] = $data;
      }
			$headers = array(
				'Authorization: key=' . API_ACCESS_KEY,
				'Content-Type: application/json'
			);

			$ch = curl_init();
			curl_setopt( $ch,CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send' );
			curl_setopt( $ch,CURLOPT_POST, true );
			curl_setopt( $ch,CURLOPT_HTTPHEADER, $headers );
			curl_setopt( $ch,CURLOPT_RETURNTRANSFER, true );
			curl_setopt( $ch,CURLOPT_SSL_VERIFYPEER, false );
			curl_setopt( $ch,CURLOPT_POSTFIELDS, json_encode( $fields ) );
			$result5 = curl_exec($ch );
			curl_close( $ch );
			return $result5;
}

class Article {
  function __construct( $userid, $pref = null, $db, $offset) {
    $this->basestatement = "SELECT distinct(sa.ShrID),sa.CommuID,sa.isPublic, u.Pic,u.UserID, CONCAT_WS(' ',u.FName,u.LName) AS FullName,u.IsDoctor,sa.T1,a.Header,sa.Summary,a.Link,a.Details,a.Type,a.ImageLink,sa.UserID,sa.IsAuthor,sa.LikesCount,sa.CommentCount FROM ShareArticle sa INNER JOIN user u ON sa.UserID = u.UserID INNER JOIN Articles a ON a.ArID = sa.ArID";
    $this->userid = $userid;
    $this->preferences = $pref;
    $this->db = $db;
    $offset = (int)$offset;
    if($offset == 0){
      $this->numberofarticles = "3";
    }else{
      $this->numberofarticles = "1";
    }
    $this->offset = 1*$offset;
  }

  private function getliked(){
    $result2 = $this->db->prepare("SELECT ShrID FROM Likes WHERE AppUserID = :UserID");
    $result2->bindParam(":UserID", $this->userid,PDO::PARAM_INT);
    $result2->execute();
    $liked = array();
    while($row2 = $result2->fetch()){
      $liked[] = $row2['ShrID'];
    }
    return $liked;
  }

  private function getbookmarked(){
    $result = $this->db->prepare("SELECT ShrID FROM bookmark WHERE UserID = :UserID");
    $result->bindParam(":UserID", $this->userid, PDO::PARAM_INT);
    $result->execute();
    $bookmarked = array();
    while($row = $result->fetch()){
      $bookmarked[] = $row['ShrID'];
      $this->bookstring.=(string)$row['ShrID'].",";
    }
    $this->bookstring = substr($this->bookstring,0,-1);
    return $bookmarked;
  }
  private function fetchit($all,$coid=null,$shrit = null,$bookmarkenabled = 0,$artids = null){
    $statement = $this->basestatement;
    $result1 = $this->db->prepare("SELECT CommuID,UserType FROM Dconnection WHERE UserID = :UserID");
    $result1->bindParam(":UserID",$this->userid,PDO::PARAM_INT);
    $result1->execute();
    $connect = array();
    while($row1 = $result1->fetch()){
      $connect[$row1['CommuID']] = $row1['UserType'];
      $list.=$row1['CommuID'].",";
    }
    if($list){
      $list = substr($list,0,-1);
    }
    $liked = $this->getliked();
    $bookmarked = $this->getbookmarked();
    if($this->preferences){
      if($this->preferences['tagids']){
        $statement.=" INNER JOIN ArticleTags at ON at.ArID = sa.ArID WHERE at.TagID IN (".$this->preferences['tagids'].") ";
      }
      if($this->preferences['doctorids']){
        $statement.=($this->preferences['tagids'])?"AND ":" WHERE ";
        $statement.="sa.UserID IN (".$this->preferences['doctorids'].") ";
      }
      $statement.=" AND ";
    }else{
      $statement.=" WHERE ";
    }
    if($all){
      if($list){
        $statement.= "(isPublic = 1 OR CommuID IN (".$list."))";
      }else{
        $statement.= "isPublic = 1";
      }
      if($artids){
        $statement.= " AND a.ArID IN (".$artids.") ";
      }
    }else if($shrit){
      $statement.="ShrID = ".$shrit;
    }else{
      $statement.= "CommuID = ".$coid;
    }
    $statement.=" ORDER BY sa.T1 DESC LIMIT ".$this->offset.",".$this->numberofarticles;
    $result = $this->db->prepare($statement);
    $result->execute();
    $articles = array();
    // $articles[] = $statement;
    while($row = $result->fetch()){
      $ifliked = (in_array($row['ShrID'],$liked))?1:0;
      $ifbookmarked = (in_array($row['ShrID'],$bookmarked))?1:0;
      if(!$bookmarkenabled || $ifbookmarked){
        if($connect[$row['CommuID']] !== null){
          switch($connect[$row['CommuID']]){
            case 0:
            $temp = 2;
            break;
            case 1:
            $temp = 3;
            break;
            case 3:
            case 2:
            $temp = 4;
            break;
          }
        }else{
          $temp = 1;
        }
        $pic = $this->pics($row['Pic']);
        $time = abs(time() - strtotime($row['T1']));
        $articles[] = array('ShrID' => $row['ShrID'],
        'Pic' => $pic,
        'Author' => $row['FullName'],
        'UserID' => $row['UserID'],
        'LastEdited' => $time,
        'Header' => $row['Header'],
        'Summary' => $row['Summary'],
        'Link' => $row['Link'],
        'Details' => $row['Details'],
        'FromRxHealth' => $row['Type'],
        'isPublic' => $row['isPublic'],
        'IsAuthor' =>$row['IsAuthor'],
        'action' => $temp,
        'ImageLink' => $row['ImageLink'],
        'Liked' => $ifliked,
        'Bookmarked' => $ifbookmarked,
        'Likes' => $row['LikesCount'],
        'Comments' => $row['CommentCount'],
        'CommuID' => $row['CommuID']);
      }
    }
    return $articles;
  }

  private function pics($pic){
    return is_null($pic)?'https://www.reincubate.com/res/reincubate/i/icon_avatar-female.png':$pic;
  }

  function getallarticles($book,$artids = null){
    if($artids){
      return$this->fetchit(true,null,null,0,$artids);
    }
    return $this->fetchit(true,null,null,$book);
  }

  function getcommunityarticles($commuid){
    return $this->fetchit(false,$commuid);
  }

  function getsharedarticle($id){
    return $this->fetchit(false,null,$id);
  }

  function gettagarticle($tag){
    $result = $this->db->prepare("SELECT distinct(a.ArID) FROM Tags t INNER JOIN doctorspec ds ON ds.SpecID = t.SpecID INNER JOIN Articles a ON a.UserID = ds.DID WHERE Tag = :Tag");
    $result->bindParam(":Tag", $tag, PDO::PARAM_STR);
    $result->execute();
    while($row = $result->fetch()){
      $artids.=$row['ArID'].",";
    }
    $artids = substr($artids,0,-1);
    getallarticles(0,$artids);
  }

}

class Search{
  function __construct( $data, $db) {
    $this->query = "%".$data."%";
    $this->db = $db;
  }

  function gettags(){
    $result = $this->db->prepare("SELECT distinct(Tag),TagID FROM Tags WHERE Tag LIKE :Data LIMIT 0,5");
    $result->bindParam(":Data", $this->query,PDO::PARAM_STR);
    $result->execute();
    $tags = array();
    while($row = $result->fetch()){
      $temp = substr($row['Tag'], 1);
      $tags[] = array('Tag' => $row['Tag'], 'TagID' => $row['TagID']);
    }
    return $tags;
  }

  function getpeople($isdoc){
    $h = !$isdoc;
    $result = $this->db->prepare("SELECT UserID,CONCAT_WS(' ',FName,LName) AS FullName FROM user WHERE CONCAT_WS(' ',FName,LName) LIKE :Data AND IsDoctor = :is LIMIT 0,5");
    $result->bindParam(":Data", $this->query,PDO::PARAM_STR);
    $result->bindParam(":is", $h,PDO::PARAM_STR);
    $result->execute();
    $people = array();
    while($row = $result->fetch()){
      $people[] = array('FullName' => $row['FullName'], 'UserID' => $row['UserID']);
    }
    return $people;
  }

  function getcommunities(){
    $result = $this->db->prepare("SELECT Name,CommuID FROM ComDetails WHERE Name LIKE :Data LIMIT 0,5");
    $result->bindParam(":Data", $this->query,PDO::PARAM_STR);
    $result->execute();
    $communities = array();
    while($row = $result->fetch()){
      $communities[] = array('Name' => $row['Name'], 'CommuID' => $row['CommuID']);
    }
    return $communities;
  }
}

function createarticle($userid,$header,$summary,$link,$details,$imagelink, $db){
  $link = filter_var($link, FILTER_SANITIZE_URL);
  if (!filter_var($link, FILTER_VALIDATE_URL) === false) {
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
     $query = $db->prepare("SELECT UserID,Summary FROM Articles WHERE ArID = :ArID");
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
  $otherCommunities = array();
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
      $myCommunities[] = array("CID" => $row['CID'],"ComID" => $row['CommuID'],"Type" => $detail['ComType'],"Name" => $detail['Name']);
    }
    elseif ($usertype == 2) {
      $otherCommunities[] = array("CID" => $row['CID'],"ComID" => $row['CommuID'],"Type" => $detail['ComType'],"Name" => $detail['Name'], "IsAdmin" => "true");
    }
    elseif ( $usertype == 1) {
      $otherCommunities[] = array("CID" => $row['CID'],"ComID" => $row['CommuID'],"Type" => $detail['ComType'], "Name" => $detail['Name'], "IsAdmin" => "false");
    }
    elseif ($usertype == 0) {
      $following[] = array("CID" => $row['CID'],"ComID" => $row['CommuID'],"Type" => $detail['ComType'], "Name" => $detail['Name']);
    }
  }
  return [$myCommunities,$otherCommunities,$following];

}




?>
