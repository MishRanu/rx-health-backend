<?php  
header("Access-Control-Allow-Origin: *");
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Origin, Content-Type, Accept, Authorization, X-Request-With');
header('Access-Control-Allow-Credentials: true');
//$json=$_GET ['json'];
require('db_config.php');


// Path to move uploaded files
$target_path = "/home/u824038781/public_html/img/";
 
// array for final json respone
$response = array();

$userid = isset($_POST['UserID']) ? $_POST['UserID'] : '';
 
if (isset($_FILES['image']['name'])) {
 
    // reading other post parameters
    $userid = isset($_POST['UserID']) ? $_POST['UserID'] : '';
    $t = time(); 
    $response['UserID'] = $userid;

    $filename = $_FILES['image']['name'];
    $ext = pathinfo($filename, PATHINFO_EXTENSION);
    $target_path = $target_path.$userid."_".$t.".".$ext;
    
    $response['file_name'] = $userid .$t. $ext;
 
    try {
        // Throws exception incase file is not being moved
        if (!move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
            // make error flag true
            $response['error'] = true;
            $response['message'] = 'Could not move the file!';
        }



        $path = "http://dxhealth.esy.es/img/".$userid."_".$t.".".$ext;

        $response['ResponseCode'] = "200";
        $response['ResponseMessage'] = "File uploaded successfully!";
        $response['error'] = false;
        $response['Url'] = $path;
  $status['Status'] = $response;
  header('Content-type: application/json');
  echo json_encode($status);
    } catch (Exception $e) {
        // Exception occurred. Make error flag true
        $response['error'] = true;
        $response['message'] = $e->getMessage();
    }
} else {
    // File parameter is missing
    $response['error'] = true;
    $response['message'] = 'Not received any file!F';
}
 

?>		