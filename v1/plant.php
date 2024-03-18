<?php
header('content-type:text/html;charset=utf-8');
require_once '../include/DbHandler.php';
require_once '../include/EmailService.php';
require_once '../include/SmsService.php';
require '.././libs/Slim/Slim.php';


// \Stripe\Stripe::setApiKey($stripe['secret_key']);

\Slim\Slim::registerAutoloader();

$app = new \Slim\Slim();

// User id from db - Global Variable
$user_id = NULL;
$session_token= NULL;
/**
 * Adding Middle Layer to authenticate every request
 * Checking if the request has valid api key in the 'Authorization' header
 */
function authenticate(\Slim\Route $route) {
    // Getting request headers
    $headers = apache_request_headers();
    $response = array();
    $app = \Slim\Slim::getInstance();
    // Verifying Authorization Header
    if (isset($headers['Authorization'])) 
    {
        $db = new DbHandler();
        // get the api key
        $api_key = $headers['Authorization'];
        // validating api key
       
    if (!$db->isValidApiKey($api_key)) 
    {
            $response["status"] ="error";
            $response["message"] = "Access Denied";
            //$response["message"] = "Access Denied. Invalid Api key";
            echoRespnse(401, $response);
            $app->stop();
        } 
        else 
        {
            global $user_id;
            //get user primary key id
           $user_id = $db->getUserId($api_key);
        
        }
    } 
    else 
    {
        // api key is missing in header
        $response["status"] ="error";
        //$response["message"] = "Api key is misssing";
        $response["message"] = "Access Denied";
        echoRespnse(401, $response);
        $app->stop();
    }
}


function accessToken($user_id) {
    // Getting request headers
    $headers = apache_request_headers();
    $response = array();
    $app = \Slim\Slim::getInstance();
    // Verifying Authorization Header
    if (isset($headers['sessiontoken'])) 
    {
        $db = new DbHandler();
        // get the api key
        $api_key = $headers['sessiontoken'];
        // validating api key
        if (!$db->isValidSessionToken($api_key,$user_id)) 
        {
            $response["status"] ="error";
            $response["message"] = "Token Expired";
            //$response["message"] = "Access Denied. Invalid Api key";
            echoRespnse(401, $response);
            $app->stop();
        } 
    } 
    else 
    {
        // api key is missing in header
        $response["status"] ="error";
        //$response["message"] = "Api key is misssing";
        $response["message"] = "sessiontoken key is missing";
        echoRespnse(401, $response);
        $app->stop();
    }
}

/*** Indian Date Time Generation ***/
  function getCurrentDateTime(){
    $datetime = date('Y-m-d H:i:s');
    $given = new DateTime($datetime, new DateTimeZone("UTC"));
    $given->setTimezone(new DateTimeZone("asia/kolkata"));
    $output = $given->format("Y-m-d H:i:s"); 
    return $output;
  }

function authenticatedefault(\Slim\Route $route) {
    // Getting request headers
    $headers = apache_request_headers();
    $response = array();
    $app = \Slim\Slim::getInstance();
    $APPKEY = "b8416f2680eb194d61b33f9909f94b9d";
    // Verifying Authorization Header
   //print_r($headers);exit;
    if (isset($headers['Authorization']) || isset($headers['authorization'])) 
    {
    if(isset($headers['authorization']))
    {
      $headers['Authorization']=$headers['authorization'];
    }
    
        $db = new DbHandler();

        // get the api key
        $api_key = $headers['Authorization'];
        // validating api key

        if($api_key != $APPKEY)
        {
      $response["status"] ="error";
            $response["message"] = "Access Denied";
            echoRespnse(401, $response);
            $app->stop();
    }
       else 
        {
            global $user_id;
            // get user primary key id
          //$user_id = $db->getUserId($api_key);

        }
    } 
    else 
    {
        // api key is missing in header
        $response["status"] ="error";
        //$response["message"] = "Api key is misssing";
        $response["message"] = "Access Denied";
        echoRespnse(401, $response);
        $app->stop();
    }
}

///////////////////////////////////////
/**
 * User Login
 * url - /login
 * method - POST
 * params - username, password,deviceId,pushId,latitude,longitude,platform , 'authenticatedefault'
 */

$app->post('/instanceurl', 'authenticatedefault', function() use ($app) 
{
     
            // reading post params
           
            $platform= $app->request()->post('platform');//1-Android ,2-IOS
            $url = $app->request()->post('url');
            
            // check for required params
            verifyRequiredParams(array('url','platform'));
            $req = $app->request;
            $base_url = $req->getUrl()."".$req->getRootUri()."/";
            $response = array();
            
           if ($base_url == $url) 
           {
                
                 $response["status"] =1;
                 $response['message'] = "Success";
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'Entered instance url is invalid';
                
            }
           
            echoRespnse(200, $response);
 });


$app->post('/generate/sessiontoken', 'authenticatedefault', function() use ($app) 
{
             $json = $app->request->getBody();
            $data = json_decode($json, true);
            // reading post params
            $user_id = $data['user_id'];
            
            // check for required params
            // verifyRequiredParams(array('user_id','platform'));
            
            $response = array();
            $db = new DbHandler();
            $result=$db->generateSessionToken($user_id);
           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "Session Token generated in successfully";
                 $response["session_token"]=$result['session_token'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'Session Token generation failed';
                $response["session_token"]=array();
            }
           
            echoRespnse(200, $response);
 });


$app->post('/login', 'authenticatedefault', function() use ($app) 
{         
     

            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);
                   
            $username  = $data['username'];
            $password = $data['password'];

                      
            $response = array();
      $db = new DbHandler();
      $result=$db->userLogin($username,$password);
    
           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "Logged in successfully";
                 $response["userDetails"]=$result['userDetails'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'Incorrect Passcode';
                $response["userDetails"]=array();
            }
      
        echoRespnse(200, $response);
 });

$app->post('/otpverify', 'authenticatedefault', function() use ($app) 
{

            $json = $app->request->getBody();
            $data = json_decode($json, true);

            // reading post params
            // $platform= $data['platform'];//1-Android ,2-IOS
            $user_id = $data['user_id'];
            $otp = md5($data['otp']);


            // echo $otp;
            // exit();
              accessToken($user_id); 
            // check for required params
            // verifyRequiredParams(array('user_id','otp','platform'));
            
             $req = $app->request;
            $base_url = $req->getUrl()."".$req->getRootUri()."/";
            $response = array();
            $db = new DbHandler();
            $result=$db->otpverify($user_id,$otp,$base_url);
            if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "OTP Verified";
                 $response["userDetails"]=$result['userDetails'];
            }
            else if ($result['status']==2) 
            {
                 $response["status"] =0;
                 $response['message'] = "Your entered Incorrect OTP";
                 $response["userDetails"]=array();
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'Invalid OTP';
                $response["userDetails"]=array();
            }
       
            echoRespnse(200, $response);
 });


$app->post('/setpasscode', 'authenticatedefault', function() use ($app) 
{

             $json = $app->request->getBody();
            $data = json_decode($json, true);

            $user_id = $data['user_id'];
            $passcodeentrVal = md5($data['passcode']);
            $imeino = $data['imeino'];
            // $platform = $data['platform'];
            $datetime = getCurrentDateTime();
              accessToken($user_id); 
            // check for required params
            //verifyRequiredParams(array('user_id', 'passcode','imeino','platform'));
            $req = $app->request;
            $base_url = $req->getUrl()."".$req->getRootUri()."/";
            $response = array();
            $db = new DbHandler();
            $result=$db->setpasscode($user_id,$passcodeentrVal,$base_url,$datetime,$imeino);
           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "Passcode setted successfully";
                 $response["userDetails"]=$result['userDetails'];
            }else if ($result['status']==2) 
           {
                 $response["status"] =1;
                 $response['message'] = "Passcode insertion failed";
                 $response["userDetails"]=array();
            }
            else if ($result['status']==3) 
           {
                 $response["status"] =1;
                 $response['message'] = "Passcode updation failed";
                 $response["userDetails"]=array();
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'Eorror setting passcode';
                $response["userDetails"]=array();
            }
        
            echoRespnse(200, $response);
 });

$app->post('/loginwtoutpasscode', 'authenticatedefault', function() use ($app) 
{         
     
         
            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);
           
           
            $username  = $data['username'];
            $password = $data['password'];

           
 $req = $app->request;
            $base_url = $req->getUrl()."".$req->getRootUri()."/";
            $response = array();
            $db = new DbHandler();
            $result=$db->loginWtoutPasscode($username,$password,$base_url);
                      
           
           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "Logged in successfully";
                 $response["userDetails"]=$result['userDetails'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'Incorrect Passcode';
                $response["userDetails"]=array();
            }
      
        echoRespnse(200, $response);
 });


$app->post('/passcodelogin', 'authenticatedefault', function() use ($app) 
{

            $json = $app->request->getBody();
            $data = json_decode($json, true);

            $user_id = $data['user_id'];
            $passcodeentrVal = md5($data['passcode']);
            // $platform = $data['platform'];
              accessToken($user_id); 
            // check for required params
            //verifyRequiredParams(array('user_id', 'passcode','platform'));
            $req = $app->request;
            $base_url = $req->getUrl()."".$req->getRootUri()."/";
            $response = array();
            $db = new DbHandler();
            $result=$db->passcodelogin($user_id,$passcodeentrVal,$base_url);

           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "Logged in successfully";
                 $response["userDetails"]=$result['userDetails'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'Incorrect Passcode';
                $response["userDetails"]=array();
            }
        
            echoRespnse(200, $response);
 });

// login with mob number
$app->post('/loginMobNumber', 'authenticatedefault', function() use ($app) 
{         
     

            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);
           
            // $platform   = $data['platform'];
            $mobnumber  = $data['mobnumber'];

             $username  = $data['username'];
            $response = array();
      $db = new DbHandler();
      $result=$db->loginMobNum($mobnumber,$username);


     //$user_details=$db->userDetails($user_id);
           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "Logged in successfully";
                 $response["userMobLogin"]=$result['userMobLogin'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'Incorrect Mobile Number';
                $response["userMobLogin"]=array();
            }
      
        echoRespnse(200, $response);
 });


// password login using mobile or username
$app->post('/loginWithMobNumOrUsrname', 'authenticatedefault', function() use ($app) 
{         
     

            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);
           
            // $platform   = $data['platform'];
            $mobnumber  = $data['mobnumber'];

             $username  = $data['username'];

             $password  = $data['password'];

            $response = array();

            $req = $app->request;
            $base_url = $req->getUrl()."".$req->getRootUri()."/";
      $db = new DbHandler();
      $result=$db->loginWithMobNumOrUsrname($mobnumber,$username,$password,$base_url);


     //$user_details=$db->userDetails($user_id);
           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "Logged in successfully";
                 $response["userDetails"]=$result['userDetails'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'Mobile Number or Username or Password you have entered is incorrect';
                $response["userDetails"]=array();
            }
      
        echoRespnse(200, $response);
 });


// login with mob number
$app->post('/sendOtp', 'authenticatedefault', function() use ($app) 
{         
     

            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);
           
            // $platform   = $data['platform'];
            $user_id  = $data['user_id'];
            
            
            $response = array();
      $db = new DbHandler();
      $result=$db->sendOtp($user_id);
     //$user_details=$db->userDetails($user_id);
           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "otp sent successfully";
                 $response["sendOtpDetails"]=$result['sendOtpDetails'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'otp sending is unsuccessfull';
                $response["sendOtpDetails"]=array();
            }
      
        echoRespnse(200, $response);
 });


$app->post('/logout', 'authenticatedefault', function() use ($app) 
{
            // reading post params
            // $platform= $app->request()->post('platform');//1-Android ,2-IOS
            // $user_id = $app->request()->post('user_id');
           
            $json = $app->request->getBody();
            $data = json_decode($json, true);

            $user_id = $data['user_id'];
            // check for required params
            // verifyRequiredParams(array('user_id','platform'));
                accessToken($user_id); 
            $response = array();
            $db = new DbHandler();
            $result=$db->logout($user_id);
           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "Logged out successfully";
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'Failed to logout';
            }
           
            echoRespnse(200, $response);
 });


// for upload/image we have to implement it here.
$app->post('/upload/image', 'authenticatedefault', function() use ($app) 
{           
            /*$json = $app->request->getBody();
            $data = json_decode($json, true);*/


             // reading post params
            $ticket_id = $app->request()->post('ticket_id');
            // $platform= $data['platform'];//1-Android ,2-IOS
            $user_id = $app->request()->post('user_id');
              accessToken($user_id); 
             // check for required params
              // check for required params
            // verifyRequiredParams(array('ticket_id','user_id','platform'));
               $created_on  = getCurrentDateTime();
            $response = array();
            $db = new DbHandler();
                       
            $file_content = file_get_contents($_FILES['image']['tmp_name']);
            $file_name = $_FILES['image']['name'];
            $file_type = $_FILES['image']['type'];
            $file_size = $_FILES['image']['size'];
            //verifyRequiredParams(array('ticket_id','platform'));

           $result=$db->uploadImage($ticket_id,$file_name,$file_type,$file_size,$file_content,$created_on,$user_id);
        
           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["uploadImage"]=$result['uploadImage'];
            }else {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["uploadImage"]=array();
            }
        
            echoRespnse(200, $response);
 });

// for /respupload/addAttachment we have to implement it here.
$app->post('/respupload/addAttachment', 'authenticatedefault', function() use ($app) 
{           
            /*$json = $app->request->getBody();
            $data = json_decode($json, true);*/


             // reading post params
            $ticket_id = $app->request()->post('ticket_id');
            
            $user_id = $app->request()->post('user_id');
            $created_on  = getCurrentDateTime();
             /*$user_id = $data['user_id'];
              $ticket_id = $data['ticket_id'];*/
              accessToken($user_id); 
             // check for required params
              // check for required params
            // verifyRequiredParams(array('ticket_id','user_id','platform'));
            $response = array();
            $db = new DbHandler();
                       
            $file_content = file_get_contents($_FILES['image']['tmp_name']);
            $file_name = $_FILES['image']['name'];
            $file_type = $_FILES['image']['type'];
            $file_size = $_FILES['image']['size'];
            /*verifyRequiredParams(array('ticket_id'));*/

           $result=$db->respupload($ticket_id,$file_name,$file_type,$file_size,$file_content,$created_on,$user_id);
        
           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["respuploadImage"]=$result['respuploadImage'];
            }else {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["respuploadImage"]=array();
            }
        
            echoRespnse(200, $response);
 });


$app->post('/getTicketAttachment', 'authenticatedefault', function() use ($app) 
{           

            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);

            $userIdPass  = $data['user_id'];
            $ticket_id  = $data['ticket_id'];
            


             // reading post params
          /*  $userIdPass = $app->request()->post('user_id');
            $platform= $app->request()->post('platform');*///1-Android ,2-IOS
              // accessToken($userIdPass); 
             // check for required params
            //verifyRequiredParams(array('user_id','platform'));
              $req = $app->request;
            $base_url = $req->getUrl()."".$req->getRootUri()."/";
           
            $response = array();
            $db = new DbHandler();
            $result=$db->getTicketAttachment($ticket_id,$base_url);
        
           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["getTicketAttachment"]=$result['getTicketAttachment'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["getTicketAttachment"]=array();
            }
        
            echoRespnse(200, $response);
 });

$app->post('/getTicketAttachmentActionLog', 'authenticatedefault', function() use ($app) 
{           

            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);

            $userIdPass  = $data['user_id'];
           
            $ticket_id  = $data['ticket_id'];
            

             // reading post params
          /*  $userIdPass = $app->request()->post('user_id');
            $platform= $app->request()->post('platform');*///1-Android ,2-IOS
              accessToken($userIdPass); 
             // check for required params
            //verifyRequiredParams(array('user_id','platform'));
              $req = $app->request;
            $base_url = $req->getUrl()."".$req->getRootUri()."/";

            $response = array();
            $db = new DbHandler();
            $result=$db->getTicketAttachmentActionLog($ticket_id,$base_url);
        
           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["TicketAttachmentActionLog"]=$result['getTicketAttachmentActionLog'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["TicketAttachmentActionLog"]=array();
            }
        
            echoRespnse(200, $response);
 });
// for ticketdetails we have to implement it here.
$app->post('/ticketDetails', 'authenticatedefault', function() use ($app) 
{           

            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);

            $userIdPass  = $data['user_id'];
            


             // reading post params
          /*  $userIdPass = $app->request()->post('user_id');
            $platform= $app->request()->post('platform');*///1-Android ,2-IOS
              accessToken($userIdPass); 
             // check for required params
            //verifyRequiredParams(array('user_id','platform'));
            
            $response = array();
            $db = new DbHandler();
            $result=$db->ticketDetails($userIdPass);
        
           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["ticketDetails"]=$result['ticketDetails'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["ticketDetails"]=array();
            }
        
            echoRespnse(200, $response);
 });


// for ticketdetails we have to implement it here.
$app->post('/ticketIdDetails', 'authenticatedefault', function() use ($app) 
{           
            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);

            $ticket_id  = $data['ticket_id'];
            $user_id  = $data['user_id'];

             // reading post params
           /* $ticket_id = $app->request()->post('ticket_id');
            $platform= $app->request()->post('platform');//1-Android ,2-IOS
            $user_id = $app->request()->post('user_id');*/
              accessToken($user_id); 
             // check for required params
            //verifyRequiredParams(array('ticket_id','user_id','platform'));
            
            $response = array();
            $db = new DbHandler();
            $result=$db->ticketIdDetails($ticket_id);

           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["ticket_Details"]=$result['ticket_Details'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["ticket_Details"]=array();
            }
        
            echoRespnse(200, $response);
 });

// for ticketdetails we have to implement it here.
$app->post('/resolution', 'authenticatedefault', function() use ($app) 
{           
            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);

            $ticket_id  = $data['ticket_id'];
            $user_id  = $data['user_id'];

             // reading post params
           /* $ticket_id = $app->request()->post('ticket_id');
            $platform= $app->request()->post('platform');//1-Android ,2-IOS
            $user_id = $app->request()->post('user_id');*/
              accessToken($user_id); 
             // check for required params
            //verifyRequiredParams(array('ticket_id','user_id','platform'));
            
            $response = array();
            $db = new DbHandler();
            $result=$db->resolutionDetails($ticket_id);

           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["resolutionDetails"]=$result['resolutionDetails'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["resolutionDetails"]=array();
            }
        
            echoRespnse(200, $response);
 });

// for checklist we have to implement it here.
$app->post('/checklist', 'authenticatedefault', function() use ($app) 
{           
            $json = $app->request->getBody();
            $data = json_decode($json, true);

            $user_id = $data['user_id'];
            $equipment_id = $data['equipment_id'];
          // $platform= $app->request()->post('platform');//1-Android ,2-IOS
          // $user_id = $app->request()->post('user_id');
            accessToken($user_id); 
             // check for required params
            // verifyRequiredParams(array('user_id','platform'));

            $db = new DbHandler();
            $result=$db->checklist($equipment_id);
        
           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["checklist"]=$result['checklist'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["checklist"]=array();
            }
        
            echoRespnse(200, $response);
 });


// for dateformat we have to implement it here.
$app->post('/dateFormat', 'authenticatedefault', function() use ($app) 
{           

            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);

            $userIdPass  = $data['user_id'];
            


             // reading post params
          /*  $userIdPass = $app->request()->post('user_id');
            $platform= $app->request()->post('platform');*///1-Android ,2-IOS
              accessToken($userIdPass); 
             // check for required params
            //verifyRequiredParams(array('user_id','platform'));
            
            $response = array();
            $db = new DbHandler();
            $result=$db->dateFormat();
        
           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["dateFormat"]=$result['dateFormat'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["dateFormat"]=array();
            }
        
            echoRespnse(200, $response);
 });

$app->post('/equipmentbyId', 'authenticatedefault', function() use ($app) 
{           
            $json = $app->request->getBody();
            $data = json_decode($json, true);

            $user_id = $data['user_id'];
            $equipment_id = $data['equipment_id'];
          // $platform= $app->request()->post('platform');//1-Android ,2-IOS
          // $user_id = $app->request()->post('user_id');
            accessToken($user_id); 
             // check for required params
            // verifyRequiredParams(array('user_id','platform'));

            $db = new DbHandler();
            $result=$db->equipmentbyId($equipment_id);
        
           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["equipmentbyId"]=$result['equipmentbyId'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["equipmentbyId"]=array();
            }
        
            echoRespnse(200, $response);
 });
// for routecause we have to implement it here.
$app->post('/routecause', 'authenticatedefault', function() use ($app) 
{           

            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);

             
              $user_id  = $data['user_id'];
              $type_of_issue_id  = $data['type_of_issue_id'];


             /*$platform= $app->request()->post('platform');//1-Android ,2-IOS
            $user_id = $app->request()->post('user_id');*/
              accessToken($user_id); 
             // check for required params
            //verifyRequiredParams(array('user_id','platform'));

            $db = new DbHandler();
            $result=$db->routecause($type_of_issue_id);

           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["routecause"]=$result['routecause'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["routecause"]=array();
            }
        
            echoRespnse(200, $response);
 });

// for location we have to implement it here.
$app->post('/location', 'authenticatedefault', function() use ($app) 
{           

        $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);

             
              $user_id  = $data['user_id'];

      /*$platform= $app->request()->post('platform');//1-Android ,2-IOS
        $user_id = $app->request()->post('user_id');*/
              // accessToken($user_id); 
             // check for required params
            //verifyRequiredParams(array('user_id','platform'));
          
            $response = array();
            $db = new DbHandler();
            $result=$db->location();
        
           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["location"]=$result['location'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["location"]=array();
            }
        
            echoRespnse(200, $response);
 });


// for equipmentlist we have to implement it here.
$app->post('/equipmentlist', 'authenticatedefault', function() use ($app) 
{               
               $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);

             
              $user_id  = $data['user_id'];
              $location_id   = $data['location_id'];
              $plant_id   = $data['plant_id'];
              // $department_id   = $data['department_id'];
              $functional_location_id   = $data['functional_location_id'];
            /* $platform= $app->request()->post('platform');//1-Android ,2-IOS
            $user_id = $app->request()->post('user_id');*/
              // accessToken($user_id); 
             // check for required params
            //verifyRequiredParams(array('user_id','platform'));      
            $response = array();
            $db = new DbHandler();
            $result=$db->equipmentlist($location_id,$plant_id/*,$department_id*/,$functional_location_id);

           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["equipmentlist"]=$result['equipmentlist'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["equipmentlist"]=array();
            }
        
            echoRespnse(200, $response);
 });

// for equipmentlist we have to implement it here.
$app->post('/equipmentIdDetails', 'authenticatedefault', function() use ($app) 
{           

            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);

             
              $user_id  = $data['user_id'];  
              $equipment_id  = $data['equipment_id'];  
             // reading post params
          /*  $equipment_id = $app->request()->post('equipment_id');
            $platform= $app->request()->post('platform');//1-Android ,2-IOS
            $user_id = $app->request()->post('user_id');*/
              accessToken($user_id); 
             // check for required params
            //verifyRequiredParams(array('equipment_id','user_id','platform'));
            
            $response = array();
            $db = new DbHandler();
            $result=$db->equipmentiddetails($equipment_id);

           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["equipmentiddetails"]=$result['equipmentiddetails'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["equipmentiddetails"]=array();
            }
        
            echoRespnse(200, $response);
 });


// for typeofissue we have to implement it here.
$app->post('/typeofissue', 'authenticatedefault', function() use ($app) 
{           

             $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);

             
              $user_id  = $data['user_id'];
              $equipment_id  = $data['equipment_id'];  
             // reading post params
           /* $equipment_id = $app->request()->post('equipment_id');
            $platform= $app->request()->post('platform');//1-Android ,2-IOS
            $user_id = $app->request()->post('user_id');*/
              // accessToken($user_id); 
             // check for required params
            //verifyRequiredParams(array('equipment_id','user_id','platform'));
            
            $response = array();
            $db = new DbHandler();
            $result=$db->typeofissue($equipment_id);

           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["typeofissue"]=$result['typeofissue'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["typeofissue"]=array();
            }
        
            echoRespnse(200, $response);
 });


// for plantlst we have to implement it here.
$app->post('/plantlst', 'authenticatedefault', function() use ($app) 
{           

              $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);

              $user_id  = $data['user_id'];
              $locationid  = $data['locationid'];
             // reading post params
           /* $locationid = $app->request()->post('locationid');
            $platform= $app->request()->post('platform');//1-Android ,2-IOS
            $user_id = $app->request()->post('user_id');*/
              // accessToken($user_id); 
             // check for required params
            //verifyRequiredParams(array('locationid','user_id','platform'));
            
            $response = array();
            $db = new DbHandler();
            $result=$db->plantlst($locationid);

           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["plantlst"]=$result['plantlst'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["plantlst"]=array();
            }
        
            echoRespnse(200, $response);
 });

// for funcLocation we have to implement it here.
$app->post('/funcLocation', 'authenticatedefault', function() use ($app) 
{           

             $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);
            
              $user_id  = $data['user_id'];
               $department_id   = $data['department_id'];
                $parent_id  = 0;
                $level = 0;
             // reading post params
            /*$location_id = $app->request()->post('location_id');
            $plant_id = $app->request()->post('plant_id');
            $platform= $app->request()->post('platform');//1-Android ,2-IOS
            $user_id = $app->request()->post('user_id');*/
              // accessToken($user_id); 
             // check for required params
            //verifyRequiredParams(array('location_id','plant_id','user_id','platform'));
            
            $response = array();
            $db = new DbHandler();
            $result=$db->funcLocation($department_id,$parent_id,$level);
        
           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["funcLocation"]=$result['funcLocation'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["funcLocation"]=array();
            }
        
            echoRespnse(200, $response);
 });

// for subfuncLocation we have to implement it here.
$app->post('/subfuncLocation', 'authenticatedefault', function() use ($app) 
{           

             $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);
            
              $user_id  = $data['user_id'];
               $department_id   = $data['department_id'];
                $parent_id  = $data['parent_id'];
                $level = 1;
             // reading post params
            /*$location_id = $app->request()->post('location_id');
            $plant_id = $app->request()->post('plant_id');
            $platform= $app->request()->post('platform');//1-Android ,2-IOS
            $user_id = $app->request()->post('user_id');*/
              // accessToken($user_id); 
             // check for required params
            //verifyRequiredParams(array('location_id','plant_id','user_id','platform'));
            
            $response = array();
            $db = new DbHandler();
            $result=$db->funcLocation($department_id,$parent_id,$level);
        
           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["funcLocation"]=$result['funcLocation'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["funcLocation"]=array();
            }
        
            echoRespnse(200, $response);
 });



// for unitmeasure we have to implement it here.
$app->post('/unitmeasure', 'authenticatedefault', function() use ($app) 
{                 
             $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);
            
              $user_id  = $data['user_id'];

            /*$platform= $app->request()->post('platform');//1-Android ,2-IOS
            $user_id = $app->request()->post('user_id');*/
              accessToken($user_id); 
             // check for required params
            //verifyRequiredParams(array('user_id','platform'));   
            $db = new DbHandler();
            $result=$db->unitmeasure();
               
            // $user_details=$db->userDetails($user_id);
           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["unitmeasure"]=$result['unitmeasure'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["unitmeasure"]=array();
            }
        
            echoRespnse(200, $response);
 });

// for emp list we have to implement it here.
$app->post('/empNumByLocPlnt', 'authenticatedefault', function() use ($app) 
{           

            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);

              $user_id  = $data['user_id'];
              $user_role_id  = $data['user_role_id'];
              $locationid  = $data['locationid'];
              $plantid  = $data['plantid'];
             
              accessToken($user_id); 
             
            
            $response = array();
            $db = new DbHandler();
            $result=$db->empNumByLocPlnt($locationid,$plantid,$user_id,$user_role_id);

           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["emp_details"]=$result['emp_details'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["emp_details"]=array();
            }
        
            echoRespnse(200, $response);
 });

// for parts we have to implement it here.
$app->post('/parts', 'authenticatedefault', function() use ($app) 
{       

             $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);
            
              $user_id  = $data['user_id'];
           /* $platform= $app->request()->post('platform');//1-Android ,2-IOS
            $user_id = $app->request()->post('user_id');*/
              accessToken($user_id); 
             // check for required params
            //verifyRequiredParams(array('user_id','platform')); 
            $db = new DbHandler();
            $result=$db->parts();
        
           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["parts"]=$result['parts'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["parts"]=array();
            }
        
            echoRespnse(200, $response);
 });


// for ticketstatus we have to implement it here.
$app->post('/ticketstatus', 'authenticatedefault', function() use ($app) 
{       
             $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);
            
              $user_id  = $data['user_id'];
              $user_role_id  = $data['user_role_id'];
              $ticket_id  = $data['ticket_id'];
              $response_id  = $data['response_id'];
           /* $platform= $app->request()->post('platform');//1-Android ,2-IOS
            $user_id = $app->request()->post('user_id');*/
              accessToken($user_id); 
               // check for required params
            //verifyRequiredParams(array('user_id','platform')); 
            $db = new DbHandler();
            $result=$db->ticketstatus($user_id,$user_role_id,$ticket_id,$response_id);
        
           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["ticketstatus"]=$result['ticketstatus'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["ticketstatus"]=array();
            }
        
            echoRespnse(200, $response);
 });

// for ticketpriority we have to implement it here.
$app->post('/ticketpriority', 'authenticatedefault', function() use ($app) 
{       

         $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);
            
              $user_id  = $data['user_id'];
              $type_of_issue_id  = $data['type_of_issue'];
       /* $platform= $app->request()->post('platform');//1-Android ,2-IOS
        $user_id = $app->request()->post('user_id');*/
              // accessToken($user_id); 
        //verifyRequiredParams(array('user_id','platform'));

            $db = new DbHandler();
            $result=$db->ticketpriority($type_of_issue_id);

           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["ticketpriority"]=$result['ticketpriority'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["ticketpriority"]=array();
            }
        
            echoRespnse(200, $response);
 });

// for ticketseverity we have to implement it here.
$app->post('/ticketseverity', 'authenticatedefault', function() use ($app) 
{       

             $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);
            
              $user_id  = $data['user_id'];
            /*$platform= $app->request()->post('platform');//1-Android ,2-IOS
            $user_id = $app->request()->post('user_id');*/
              // accessToken($user_id); 
            //verifyRequiredParams(array('user_id','platform'));

            $db = new DbHandler();
            $result=$db->ticketseverity();
        
           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["ticketseverity"]=$result['ticketseverity'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["ticketseverity"]=array();
            }
        
            echoRespnse(200, $response);
 });



// for machinestatus we have to implement it here.
$app->post('/machinestatus', 'authenticatedefault', function() use ($app) 
{           
         $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);
            
              $user_id  = $data['user_id'];
        /*$platform= $app->request()->post('platform');//1-Android ,2-IOS
        $user_id = $app->request()->post('user_id');*/
              accessToken($user_id); 
        //verifyRequiredParams(array('user_id','platform'));
            
        $db = new DbHandler();
        $result=$db->machineStatus();
       
        if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["machineStatus"]=$result['machineStatus'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["machineStatus"]=array();
            }
        
            echoRespnse(200, $response);
 });


// for responsecode we have to implement it here.
$app->post('/responsecode', 'authenticatedefault', function() use ($app) 
{           
         $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);
            
              $user_id  = $data['user_id'];
              $type_of_issue_id  = $data['type_of_issue_id'];
       /* $platform= $app->request()->post('platform');//1-Android ,2-IOS
        $user_id = $app->request()->post('user_id');*/
              accessToken($user_id); 
        //verifyRequiredParams(array('user_id','platform'));
        $db = new DbHandler();
        $result=$db->routecause($type_of_issue_id);
        
        $data[0]['id'] = 1;
        $data[0]['name'] = "Self";
        $data[1]['id'] = 2;
        $data[1]['name'] = "Engineer";
        $data[2]['id'] = 3;
        $data[2]['name'] = "Technician";

        $response["status"] =1;
        $response['message'] = "successful";
        $response["responsecode"]=$data;

        echoRespnse(200, $response);
 });

// for part/add we have to implement it here.
$app->post('/fiveStar/add', 'authenticatedefault', function() use ($app) 
{

            $body = $app->request->getBody();
            $starJsonAddObj = json_decode($body, true);
            //print_r($starJsonAddObj);
            //print_r($starJsonAddObj["startrating"][0]['user_id']);

             $user_id  = $starJsonAddObj["startrating"][0]['user_id'];
              accessToken($user_id); 
            $response = array();
            $db = new DbHandler();
            $result=$db->fiveStarRating($starJsonAddObj);

           
             if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["fiveStarAdd"]=$result['fiveStarAdd'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["fiveStarAdd"]=array();
            }

            echoRespnse(200, $response);
           
 });

// get star rating for particular ticket
$app->post('/starRatingByTktId', 'authenticatedefault', function() use ($app) 
{       

             $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);
            
              $user_id  = $data['user_id'];
               $ticket_id  = $data['ticket_id'];
            /*$platform= $app->request()->post('platform');//1-Android ,2-IOS
            $user_id = $app->request()->post('user_id');*/
              accessToken($user_id); 
            //verifyRequiredParams(array('user_id','platform'));

            $db = new DbHandler();
            $result=$db->starRatingByTicktId($ticket_id);
        
           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["starRatingByTicktId"]=$result['RatingByTicketId'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["starRatingByTicktId"]=array();
            }
        
            echoRespnse(200, $response);
 });
// for part/add we have to implement it here.
$app->post('/part/add', 'authenticatedefault', function() use ($app) 
{

             $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);
            
              $user_id  = $data['user_id'];
              $ticket_id  = $data['ticket_id'];
            $emp_number = $data['emp_number'];
            $part_name = $data['part_name'];
            $part_number  = $data['part_number'];
            $quantity = $data['quantity'];
            $unit_id = $data['unit_id'];
            $created_on = getCurrentDateTime();
             // reading post params

            /*$platform= $app->request()->post('platform');//1-Android ,2-IOS
            $ticket_id  = $app->request()->post('ticket_id');
            $emp_number = $app->request()->post('emp_number');
            $part_name = $app->request()->post('part_name');
            $part_number  = $app->request()->post('part_number');
            $quantity = $app->request()->post('quantity');
            $unit_id = $app->request()->post('unit_id');
            $user_id = $app->request()->post('user_id');*/
              accessToken($user_id); 
             // check for required params
            //verifyRequiredParams(array('user_id','platform','ticket_id','emp_number','part_number','part_name','quantity','unit_id'));


            $response = array();
            $db = new DbHandler();
            $result=$db->partAdd($ticket_id,$emp_number,$part_name,$part_number,$quantity,$unit_id,$created_on);

           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["parts"]=$result['parts'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["parts"]=array();
            }

            echoRespnse(200, $response);
 });

$app->post('/part/delete', 'authenticatedefault', function() use ($app) 
{

             $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);
            
              $user_id  = $data['user_id'];
              $id = $data['id'];
            
             // reading post params

            /*$platform= $app->request()->post('platform');//1-Android ,2-IOS
            $ticket_id  = $app->request()->post('ticket_id');
            $emp_number = $app->request()->post('emp_number');
            $part_name = $app->request()->post('part_name');
            $part_number  = $app->request()->post('part_number');
            $quantity = $app->request()->post('quantity');
            $unit_id = $app->request()->post('unit_id');
            $user_id = $app->request()->post('user_id');*/
              accessToken($user_id); 
             // check for required params
            //verifyRequiredParams(array('user_id','platform','ticket_id','emp_number','part_number','part_name','quantity','unit_id'));


            $response = array();
            $db = new DbHandler();
            $result=$db->partDelete($id);

           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "Parts deleted successfully";
                 $response["parts"]=$result['parts'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["parts"]=array();
            }

            echoRespnse(200, $response);
 });

// for partdetail we have to implement it here.
$app->post('/partdetail', 'authenticatedefault', function() use ($app) 
{
             $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);
            
              $user_id  = $data['user_id'];
              $ticket_id  = $data['ticket_id'];
             // reading post params
           /* $platform   = $app->request()->post('platform');//1-Android ,2-IOS
            $ticket_id  = $app->request()->post('ticket_id');
            $user_id = $app->request()->post('user_id');*/
              accessToken($user_id); 
          
             // check for required params
            //verifyRequiredParams(array('user_id','platform','ticket_id'));


            $response = array();
            $db = new DbHandler();
            $result=$db->partid($ticket_id);

           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["partdetail"]=$result['partdetail'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["partdetail"]=array();
            }

            echoRespnse(200, $response);
 });

// for partdetail we have to implement it here.
$app->post('/getPartById', 'authenticatedefault', function() use ($app) 
{
             $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);
            
              $user_id  = $data['user_id'];
              $id  = $data['id'];
             // reading post params
           /* $platform   = $app->request()->post('platform');//1-Android ,2-IOS
            $ticket_id  = $app->request()->post('ticket_id');
            $user_id = $app->request()->post('user_id');*/
              accessToken($user_id); 
          
             // check for required params
            //verifyRequiredParams(array('user_id','platform','ticket_id'));


            $response = array();
            $db = new DbHandler();
            $result=$db->getPartById($id);

           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["getPartById"]=$result['getPartById'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["getPartById"]=array();
            }

            echoRespnse(200, $response);
 });

// for partdetail we have to implement it here.
$app->post('/getEngorTechRespByTktId', 'authenticatedefault', function() use ($app) 
{
             $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);
            
              $user_id  = $data['user_id'];
              $ticket_id  = $data['ticket_id'];
               $user_role_id  = $data['user_role_id'];
             // reading post params
           /* $platform   = $app->request()->post('platform');//1-Android ,2-IOS
            $ticket_id  = $app->request()->post('ticket_id');
            $user_id = $app->request()->post('user_id');*/
              accessToken($user_id); 
          
             // check for required params
            //verifyRequiredParams(array('user_id','platform','ticket_id'));


            $response = array();
            $db = new DbHandler();
            $result=$db->getEngorTechRespByTktId($ticket_id,$user_role_id);

           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["getEngorTechRespByTktId"]=$result['getEngorTechRespByTktId'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["getEngorTechRespByTktId"]=array();
            }

            echoRespnse(200, $response);
 });



// for partdetail we have to implement it here.
$app->post('/getReqstrRespByTktId', 'authenticatedefault', function() use ($app) 
{
             $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);
            
              $user_id  = $data['user_id'];
              $ticket_id  = $data['ticket_id'];
              //$emp_number  = $data['emp_number'];
             // reading post params
           /* $platform   = $app->request()->post('platform');//1-Android ,2-IOS
            $ticket_id  = $app->request()->post('ticket_id');
            $user_id = $app->request()->post('user_id');*/
              accessToken($user_id); 
          
             // check for required params
            //verifyRequiredParams(array('user_id','platform','ticket_id'));


            $response = array();
            $db = new DbHandler();
            $result=$db->getReqstrRespByTktId($ticket_id);

           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["getReqstrRespByTktId"]=$result['getReqstrRespByTktId'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["getReqstrRespByTktId"]=array();
            }

            echoRespnse(200, $response);
 });



// for ticket/add we have to implement it here.
$app->post('/ticket/add', 'authenticatedefault', function() use ($app) 
{           
             $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);
            
              $user_id  = $data['user_id'];
              $locationId  = $data['locationid'];
              $plantId  = $data['plantid'];
              $usrdeptId  = $data['usrdeptid'];
              $notifytoId = 1;
                $statusId   = 1;
              $funclocId  = $data['funclocid'];
              $eqipmntId  = $data['eqipmntid'];
              $typofisId  = $data['typofisId'];
              $subject  = $data['subject'];
              $description  = $data['description'];
              $prtyId  = $data['prtyid'];
              $svrtyId  = $data['svrtyid'];
              $reportedBy  = $data['reportedby'];
              $submitted_by_emp_number  = $data['subbyempnum'];
              $submitted_by_name  = $data['submittedbyname'];
              $attachmentId  = $data['attachmentId'];
            
            $reportedOn = getCurrentDateTime();
            
            $submitted_on = getCurrentDateTime();
          /*  $user_id = $app->request()->post('user_id');*/
              accessToken($user_id); 

             // check for required params
            //verifyRequiredParams(array('user_id','platform','locationid','plantid','usrdeptid','funclocid','eqipmntid','typofisId','subject','description','prtyid','svrtyid','reportedby','subbyempnum','submittedbyname'));

            $response = array();
            $db = new DbHandler();
            $result=$db->ticketAdd($locationId,$plantId,$usrdeptId,$notifytoId,$statusId,$funclocId,$eqipmntId,$typofisId,$subject,$description,$prtyId,$svrtyId,$reportedBy,$submitted_by_emp_number,$submitted_by_name,$reportedOn,$submitted_on,$user_id,$attachmentId);

            
           if ($result['status']==1) 
           {    

                $response["status"] =1;
                $response['message'] = "successful";
                $response["ticketid"]=$result['ticketid'];

                $data = $db->ticketIdDetails($result['ticketid']);

                $es = new EmailService();
                $result = $es->sendEmailWhenSubmitted($data['ticket_Details']);
                $ss = new SmsService();
                $result = $ss->sendSmsWhenSubmitted($data['ticket_Details']);

            }else  if ($result['status']==2) 
           {    

                $response["status"] =2;
                $response['message'] = "successful";
                $response["ticketid"]=$result['ticketid'];

            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["ticketid"]=array();
            }

            echoRespnse(200, $response);
 });

// for actionlog/logadd we have to implement it here.
$app->post('/actionlog/logadd', 'authenticatedefault', function() use ($app) 
{           
            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);
            
              $user_id  = $data['user_id'];
              $ticket_id = $data['ticket_id'];//1-Android ,2-IOS
              $accepted_by = $data['accepted_by'];
              $rejected_by = $data['rejected_by'];
              $forward_from = $data['forward_from'];
              $forward_to = $data['forward_to'];
              $created_by_user_id = $data['created_by_user_id'];
              $status_id = $data['status_id'];//1-Android ,2-IOS
              $priority_id = $data['priority_id'];
              $severity_id = $data['severity_id'];
              $comment = $data['comment'];
              $machine_status = $data['machine_status'];
              $ticket_id = $data['ticket_id'];//1-Android ,2-IOS
              $submitted_by_name = $data['submitted_by_name'];//1-Android ,2-IOS
              $submitted_by_emp_number = $data['submitted_by_emp_number'];
              $root_cause_id = $data['root_cause_id'];
              $response_id = $data['response_id'];

         
            $assigned_date      = getCurrentDateTime();
            $due_date           = getCurrentDateTime();
           
            $submitted_on       = getCurrentDateTime();
            //$user_id = $app->request()->post('user_id');
              accessToken($user_id); 

             // check for required params
            //verifyRequiredParams(array('user_id','platform','ticket_id','accepted_by','forward_from','forward_to','created_by_user_id','status_id','priority_id','severity_id','comment','machine_status','submitted_by_name','submitted_by_emp_number','root_cause_id','response_id'));

            $response = array();
            $db = new DbHandler();
            $result=$db->logAdd($user_id,$ticket_id,$accepted_by,$rejected_by,$forward_from,$forward_to,$created_by_user_id,$status_id,$priority_id,$severity_id,$comment,$machine_status,$assigned_date,$due_date,$submitted_by_name,$submitted_by_emp_number,$root_cause_id,$response_id,$submitted_on);
           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successful";
                 // $response["log"]=$result['log'];

                 $data = $db->ticketIdDetails($ticket_id);

                 $data['ticket_Details']['Act_status'] = $status_id;
                 $data['ticket_Details']['Act_accepted_by'] = $accepted_by;
                 $data['ticket_Details']['Act_forward_from'] = $forward_from;
                 $data['ticket_Details']['Act_forward_to'] = $forward_to;

                 echoRespnse(200, $response);

                 // print_r($data['ticket_Details']);
                 // exit();
                 $status_ids=array(1,11);
                if (!in_array($status_id, $status_ids)){
                  $es = new EmailService();
                  $result = $es->sendEmailWhenAcknowledged($data['ticket_Details']);

                  $ss = new SmsService();
                  $result = $ss->sendSmsWhenAcknowledged($data['ticket_Details']);
                }

            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["log"]=array();

                echoRespnse(200, $response);
            }

 });


// for ticketchecklist/add we have to implement it here.
$app->post('/ticketchecklist/add', 'authenticatedefault', function() use ($app) 
{
            // reading post params
            //$platform  = $app->request()->post('platform');//1-Android ,2-IOS

             $body = $app->request->getBody();
            $checkListAddObj = json_decode($body, true);

            //print_r($checkListAddObj["checklistAdd"][0]['user_id']);

           $user_id  = $checkListAddObj["checklistAdd"][0]['user_id'];
              accessToken($user_id); 
            $body  =  $app->request()->getBody();
            $input = json_decode($body,true);
            
            $response = array();
            $db = new DbHandler();
          
             $result=$db->TicketcheckListAddObj($checkListAddObj);

           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["checkListAdd"]=$result['checkListAdd'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["checkListAdd"]=array();
            }

            echoRespnse(200, $response);
 });



// for tktcnvrstns/add we have to implement it here.
$app->post('/tktcnvrstns/add', 'authenticatedefault', function() use ($app) 
{

             $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);
            
              $user_id  = $data['user_id'];
              $ticket_id  = $data['ticket_id'];
              $emp_number  = $data['emp_number'];
              $comments  = $data['comments'];

             // reading post params
            /*$platform   = $app->request()->post('platform');//1-Android ,2-IOS
            $ticket_id  = $app->request()->post('ticket_id');
            $emp_number = $app->request()->post('emp_number');*/
            $date_time  = getCurrentDateTime();
            /*$comments   = $app->request()->post('comments');
            $user_id = $app->request()->post('user_id');*/
              accessToken($user_id); 

             // check for required params
            //verifyRequiredParams(array('platform','user_id','ticket_id','emp_number','comments'));

            $response = array();
            $db = new DbHandler();
            $result=$db->tktcnvrstnsAdd($ticket_id,$emp_number,$date_time,$comments);

           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["tktcnvrstns"]=$result['tktcnvrstns'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["tktcnvrstns"]=array();
            }

            echoRespnse(200, $response);
 });


// for tktconvrstns we have to implement it here.
$app->post('/tktconvrstns', 'authenticatedefault', function() use ($app) 
{

             $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);

             $user_id  = $data['user_id'];
             $ticket_id  = $data['ticket_id'];
             // reading post params
           /* $platform   = $app->request()->post('platform');//1-Android ,2-IOS
            $ticket_id  = $app->request()->post('ticket_id');
            $user_id = $app->request()->post('user_id');*/
              accessToken($user_id); 
             // check for required params
            //verifyRequiredParams(array('platform','user_id','ticket_id'));

            $response = array();
            $db = new DbHandler();
            $result=$db->tktconvrstns($ticket_id);

           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["tktconvrstns"]=$result['tktconvrstns'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["tktconvrstns"]=array();
            }

            echoRespnse(200, $response);
 });


// for alltktconvrstns we have to implement it here.
$app->post('/alltktconvrstns', 'authenticatedefault', function() use ($app) 
{           $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);
           
            //$platform   = $result[0];
           $user_id  = $data['user_id'];

           
            accessToken($user_id); 
          
            $response = array();
            $db = new DbHandler();
            $result=$db->alltktconvrstns();

           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["alltktconvrstns"]=$result['alltktconvrstns'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["alltktconvrstns"]=array();
            }

            echoRespnse(200, $response);
 });


// for "EnMNew"Tasks we have to implement it here.
$app->post('/EnMNewTasks', 'authenticatedefault', function() use ($app) 
{
            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);

             $location_id  = $data['locationid'];
            $user_id = $data['user_id'];

            //$platform   = $result[0];
            //$location_id  = $result[2];
            //$user_id = $result[4];
              accessToken($user_id); 


            $response = array();
            $db = new DbHandler();
            $result=$db->EnMNewTasks($user_id,$location_id);

           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["emTasks"]=$result['emTasks'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["emTasks"]=array();
            }

            echoRespnse(200, $response);
 });

// for EnMEngTechTasks we have to implement it here.
$app->post('/EnMEngTechTasks', 'authenticatedefault', function() use ($app) 
{

            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);
             // reading post params
             //$platform  = $data['platform'];
            $type_id = $data['type_id'];
             $user_id = $data['user_id'];

           /* $platform   = $app->request()->post('platform');//1-Android ,2-IOS
            $type_id  = $app->request()->post('type_id');
            $user_id = $app->request()->post('user_id');*/
              accessToken($user_id); 

             // check for required params
            //verifyRequiredParams(array('platform','user_id','type_id'));

            $response = array();
            $db = new DbHandler();
            $result=$db->EnMEngTechTasks($user_id,$type_id );

           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["taskslist"]=$result['taskslist'];
           }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["taskslist"]=array();
            }

            echoRespnse(200, $response);
 });


// for EngNewTskEmpnum we have to implement it here.
$app->post('/EngNewTskEmpnum', 'authenticatedefault', function() use ($app) 
{

             $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);
             // reading post params
              $emp_number = $data['emp_number'];
             $user_id = $data['user_id'];
            //$platform   = $app->request()->post('platform');//1-Android ,2-IOS
            /*$emp_number  = $app->request()->post('emp_number');
            $user_id = $app->request()->post('user_id');*/
              accessToken($user_id); 

             // check for required params
            //verifyRequiredParams(array('platform','user_id','emp_number'));

            $response = array();
            $db = new DbHandler();
            $result=$db->EngNewTskEmpnum($emp_number);

           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["engNewTasks"]=$result['engNewTasks'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["engNewTasks"]=array();
            }

            echoRespnse(200, $response);
 });


// for InPrgTsksLstEmpnum we have to implement it here.
$app->post('/InPrgTsksLstEmpnum', 'authenticatedefault', function() use ($app) 
{

             $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);
             // reading post params
            $emp_number = $data['emp_number'];
             $user_id = $data['user_id'];
           /* $platform   = $app->request()->post('platform');//1-Android ,2-IOS
            $emp_number  = $app->request()->post('emp_number');
            $user_id = $app->request()->post('user_id');*/
              accessToken($user_id); 

             // check for required params
            //verifyRequiredParams(array('platform','emp_number','user_id'));

            $response = array();
            $db = new DbHandler();
            $result=$db->InPrgTsksLstEmpnum($emp_number);

           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["inprogressTasks"]=$result['inprogressTasks'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["inprogressTasks"]=array();
            }

            echoRespnse(200, $response);
 });




// for TechTsksLstEmpnum we have to implement it here.
$app->post('/TechTsksLstEmpnum', 'authenticatedefault', function() use ($app) 
{

            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);
             // reading post params
             $emp_number = $data['emp_number'];
             $user_id = $data['user_id'];
           /* $platform   = $app->request()->post('platform');//1-Android ,2-IOS
            $emp_number  = $app->request()->post('emp_number');
            $user_id = $app->request()->post('user_id');*/
              accessToken($user_id); 

             // check for required params
            //verifyRequiredParams(array('platform','user_id','emp_number'));

            $response = array();
            $db = new DbHandler();
            $result=$db->TechTsksLstEmpnum($emp_number);

           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["technicianTasks"]=$result['technicianTasks'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["technicianTasks"]=array();
            }

            echoRespnse(200, $response);
 });


// for TechTsksLstEmpnum we have to implement it here.
$app->post('/TechNewTsksLstEmpnum', 'authenticatedefault', function() use ($app) 
{

            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);
             // reading post params
             $emp_number = $data['emp_number'];
             $user_id = $data['user_id'];
           /* $platform   = $app->request()->post('platform');//1-Android ,2-IOS
            $emp_number  = $app->request()->post('emp_number');
            $user_id = $app->request()->post('user_id');*/
              //accessToken($user_id); 

             // check for required params
            //verifyRequiredParams(array('platform','user_id','emp_number'));

            $response = array();
            $db = new DbHandler();
            $result=$db->TechNewTsksLstEmpnum($emp_number);

           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["technicianTasks"]=$result['technicianNewTasks'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["technicianTasks"]=array();
            }

            echoRespnse(200, $response);
 });

// for ReslvdTsksLst we have to implement it here.
$app->post('/ReslvdTsksLst', 'authenticatedefault', function() use ($app) 
{

            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);
             // reading post params
             $emp_number = $data['emp_number'];
             $user_id = $data['user_id'];
           /* $platform   = $app->request()->post('platform');//1-Android ,2-IOS
            $emp_number  = $app->request()->post('emp_number');
            $user_id = $app->request()->post('user_id');*/
              accessToken($user_id); 

             // check for required params
            //verifyRequiredParams(array('platform','user_id','emp_number'));

            $response = array();
            $db = new DbHandler();
            $result=$db->ReslvdTsksLst($emp_number);

           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successfull";
                 $response["resolvedTasksLst"]=$result['resolvedTasks'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["resolvedTasksLst"]=array();
            }

            echoRespnse(200, $response);
 });

// for ReslvdTsksLst we have to implement it here.
$app->post('/RejectedTaskList', 'authenticatedefault', function() use ($app) 
{

            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);
             // reading post params
             $emp_number = $data['emp_number'];
             $user_id = $data['user_id'];
           /* $platform   = $app->request()->post('platform');//1-Android ,2-IOS
            $emp_number  = $app->request()->post('emp_number');
            $user_id = $app->request()->post('user_id');*/
              accessToken($user_id); 

             // check for required params
            //verifyRequiredParams(array('platform','user_id','emp_number'));

            $response = array();
            $db = new DbHandler();
            $result=$db->RejectTaskList($emp_number);

           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successfull";
                 $response["rejectTaskList"]=$result['rejectedTasksList'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["rejectTaskList"]=array();
            }

            echoRespnse(200, $response);
 });


// for deptList we have to implement it here.
$app->post('/deptLists', 'authenticatedefault', function() use ($app) 
{           

             $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);
            
              $user_id  = $data['user_id'];
               $location_id  = $data['location_id'];
                $plant_id  = $data['plant_id'];
             // reading post params
            /*$location_id = $app->request()->post('location_id');
            $plant_id = $app->request()->post('plant_id');
            $platform= $app->request()->post('platform');//1-Android ,2-IOS
            $user_id = $app->request()->post('user_id');*/
              accessToken($user_id); 
             // check for required params
            //verifyRequiredParams(array('location_id','plant_id','user_id','platform'));
            
            $response = array();
            $db = new DbHandler();
            $result=$db->deptLists($location_id,$plant_id);
        
           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["deptLists"]=$result['deptlst'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["deptLists"]=array();
            }
        
            echoRespnse(200, $response);
 });



// for funcLocationDrpDown we have to implement it here.
$app->post('/funcLocationDrpDown', 'authenticatedefault', function() use ($app) 
{           

             $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);
            
              $user_id  = $data['user_id'];
               $dept_id  = $data['dept_id'];
               
             // reading post params
            /*$location_id = $app->request()->post('location_id');
            $plant_id = $app->request()->post('plant_id');
            $platform= $app->request()->post('platform');//1-Android ,2-IOS
            $user_id = $app->request()->post('user_id');*/
              accessToken($user_id); 
             // check for required params
            //verifyRequiredParams(array('location_id','plant_id','user_id','platform'));
            
            $response = array();
            $db = new DbHandler();
            $result=$db->funcLocationDrpDown($dept_id);
        
           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["funcLocationDrpDown"]=$result['funcLocationDrpDownLst'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["funcLocationDrpDown"]=array();
            }
        
            echoRespnse(200, $response);
 });

// for enginnersortechnicianlist we have to implement it here.
$app->post('/defEngTechResponse', 'authenticatedefault', function() use ($app) 
{
            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);

             $user_id  = $data['user_id'];
             $ticket_id  = $data['ticket_id'];
         
              accessToken($user_id); 


            $response = array();
            $db = new DbHandler();
            $result=$db->defEngTechResponse($ticket_id,$user_id);

           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["defaultEngineer"]=$result['defEngforTechres'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["defaultEngineer"]=array();
            }

            echoRespnse(200, $response);
 });
// for enginnersortechnicianlist we have to implement it here.
$app->post('/EngTechList', 'authenticatedefault', function() use ($app) 
{
            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);

             $user_id  = $data['user_id'];
             $user_role_id  = $data['user_role_id'];
            
            //$platform   = $result[0];
            //$location_id  = $result[2];
            //$user_id = $result[4];
              accessToken($user_id); 


            $response = array();
            $db = new DbHandler();
            $result=$db->EngTechLists($user_role_id,$user_id);

           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["engTeclists"]=$result['EngTechLists'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["engTeclists"]=array();
            }

            echoRespnse(200, $response);
 });


// for getStarList we have to implement it here.
$app->post('/getStarList', 'authenticatedefault', function() use ($app) 
{           

            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);

            $userIdPass  = $data['user_id'];
            

              accessToken($userIdPass); 

            $response = array();
            $db = new DbHandler();
            $result=$db->getStarList();
        
           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["getStarList"]=$result['getStarList'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["getStarList"]=array();
            }
        
            echoRespnse(200, $response);
 });




// for attendance we have to implement it here.
$app->post('/attendance', 'authenticatedefault', function() use ($app) 
{           

            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);

             
              $user_id  = $data['user_id'];  
              
              accessToken($user_id); 
             
            
            $response = array();
            $db = new DbHandler();
            $result=$db->attendance($user_id);

           if ($result['status']==1) 
           {      
                 $response["attendancedetails"]=$result['attendancedetails'];
            }
            else
            {   
                $response["attendancedetails"]=$result['attendancedetails'];
            }
        
            echoRespnse(200, $response);
 });

// for punchInpunchOut we have to implement it here.
$app->post('/punchInpunchOut', 'authenticatedefault', function() use ($app) 
{           

            $json = $app->request->getBody();
        $data = json_decode($json, true);
        $result = implode(',',$data);

         
          $user_id  = $data['user_id'];  
          $id  = $data['id']; 
          $punch_note = $data['punch_note'];

           //$punch_out_utc_time1 = getCurrentDateTime();
        
        $punch_out_user_time1 = getCurrentDateTime();
       
        //$punch_in_utc_time1 = getCurrentDateTime();
       
      
        $punch_in_user_time1   = getCurrentDateTime();
       
           accessToken($user_id);  

         
             $response = array();
        $db = new DbHandler();
        $result=$db->punchInOrOut($id,$user_id,$punch_note,$punch_in_user_time1,$punch_out_user_time1);

       if ($result['status']==1) 
       {      
          $response["status"] =1;
             $response["punchInpunchOutdetails"]=$result['punchInpunchOutdetails'];
        }
        else
        {   
            $response['status'] =0;
            $response["punchInpunchOutdetails"]=$result['punchInpunchOutdetails'];
        }
    
        echoRespnse(200, $response);

       
           
 });



// machine breakdown list we have to implement it here.
$app->post('/machineBrkDwnList', 'authenticatedefault', function() use ($app) 
{           

            $json = $app->request->getBody();
        $data = json_decode($json, true);
        $result = implode(',',$data);

         
          //$machine_status_id  = $data['machine_status_id'];  
          
           $user_id  = $data['user_id'];

         
       
           accessToken($user_id);  

         
             $response = array();
        $db = new DbHandler();
        //$result=$db->machineBreakDownList($machine_status_id);

        $result=$db->machineBreakDownList();

       if ($result['status']==1) 
       {      
          $response["status"] =1;
             $response["machineBrkDwnDetails"]=$result['machineBrkDwnDetails'];
        }
        else
        {   
            $response['status'] =0;
             $response["message"]="No Records Found";
            $response["machineBrkDwnDetails"]=array();
        }
    
        echoRespnse(200, $response);

       
           
 });

// forgot pass code webservice
$app->post('/forgotPwd', 'authenticatedefault', function() use ($app) 
{
          
           
            $json = $app->request->getBody();

            $data = json_decode($json, true);

            $result = implode(',',$data);

            $user_name = $data['user_name'];
            // check for required params
            // verifyRequiredParams(array('user_id','platform'));
                
            $response = array();
            $db = new DbHandler();
            $result=$db->forgotPassword($user_name);
           if ($result['status']==1) 
           {
              if (!empty($result['email'])) {
                    $es = new EmailService();
                    $emailresult = $es->sendPassword($data['user_name'],$result['email'],$result['password']);
                    $db->updatePassword($result['user_id'],$result['password']);
                    $response["status"] =1;
                    $response["message"]="Successfully password is sent to your email";
                  }else{
                    $response["status"] =1;
                    $response["message"]="Please Contact System Admin";
                  }   
                
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'Username you have entered is Incorrect. Please check the username you have entered';
            }
           
            echoRespnse(200, $response);
 });


// verify otp for password reset
$app->post('/pwdOtpVerify', 'authenticatedefault', function() use ($app) 
{

            $json = $app->request->getBody();
            $data = json_decode($json, true);

            // reading post params
            // $platform= $data['platform'];//1-Android ,2-IOS
            $user_id = $data['user_id'];
            $otp = md5($data['otp']);

            // echo $otp;
            // exit();
              accessToken($user_id); 
            // check for required params
            // verifyRequiredParams(array('user_id','otp','platform'));
            
            $response = array();
            $db = new DbHandler();
            $result=$db->pwdOtpVerify($user_id,$otp);
            if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "OTP Verified";
                 $response["otpverified"]=$result['otpverified'];
            }
            else if ($result['status']==2) 
            {
                 $response["status"] =0;
                 $response['message'] = "Your entered Incorrect OTP";
                 $response["otpverified"]=array();
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'Invalid OTP';
                $response["otpverified"]=array();
            }
       
            echoRespnse(200, $response);
 });


// password reset function
$app->post('/pwdChange', 'authenticatedefault', function() use ($app) 
{

            $json = $app->request->getBody();
            $data = json_decode($json, true);

            // reading post params
            // $platform= $data['platform'];//1-Android ,2-IOS
            $user_id = $data['user_id'];
            $oldPassword = $data['oldPwd'];
            $newPassword = $data['newPwd'];


  
              accessToken($user_id); 
            // check for required params
            // verifyRequiredParams(array('user_id','otp','platform'));
            
            $response = array();
            $db = new DbHandler();
            $result=$db->passwordChange($user_id,$oldPassword,$newPassword);
            
            echoRespnse(200, $result);
 });


// Play Store
$app->post('/getPlayStoreUpdate', 'authenticatedefault', function() use ($app) 
{

            $json = $app->request->getBody();
            $data = json_decode($json, true);

           
            
            $response = array();
            $db = new DbHandler();
            $result=$db->getPlayStoreUpdate();

             if ($result['status']==1) 
           {
                 $response["status"] =1;
                 //$response['message'] = "Password changed successfully";
                 $response["playStoreDetails"]=$result['playStoreDetails'];
            }
            else
            {
                $response['status'] =0;
                //$response['message'] = 'Password change unsuccessfull';
                $response["playStoreDetails"]=$result['playStoreDetails'];
            }
            
            echoRespnse(200, $response);
 });



$app->post('/JobsByDepartment', 'authenticatedefault', function() use ($app) 
{           

            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);

            $userIdPass  = $data['user_id'];
            


             // reading post params
          /*  $userIdPass = $app->request()->post('user_id');
            $platform= $app->request()->post('platform');*///1-Android ,2-IOS
              accessToken($userIdPass); 
             // check for required params
            //verifyRequiredParams(array('user_id','platform'));
            
            $response = array();
            $db = new DbHandler();
            $result=$db->JobsBasedonDept($userIdPass);
        
           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["JobsByDepartment"]=$result['JobsByDept'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["JobsByDepartment"]=array();
            }
        
            echoRespnse(200, $response);
 });


// for JobsHistory we have to implement it here.
$app->post('/JobsHistory', 'authenticatedefault', function() use ($app) 
{

            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);
             // reading post params
            
             $user_id = $data['user_id'];
           /* $platform   = $app->request()->post('platform');//1-Android ,2-IOS
            $emp_number  = $app->request()->post('emp_number');
            $user_id = $app->request()->post('user_id');*/
              accessToken($user_id); 

             // check for required params
            //verifyRequiredParams(array('platform','user_id','emp_number'));

            $response = array();
            $db = new DbHandler();
            $result=$db->JobsHistory($user_id);

           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successfull";
                 $response["JobsHistory"]=$result['JobsHistory'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["JobsHistory"]=array();
            }

            echoRespnse(200, $response);
 });



// for JobsDeptHistory we have to implement it here.
$app->post('/JobsDeptHistory', 'authenticatedefault', function() use ($app) 
{

            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);
             // reading post params
            
             $user_id = $data['user_id'];
             $status_id = $data['status_id'];
           /* $platform   = $app->request()->post('platform');//1-Android ,2-IOS
            $emp_number  = $app->request()->post('emp_number');
            $user_id = $app->request()->post('user_id');*/
              accessToken($user_id); 

             // check for required params
            //verifyRequiredParams(array('platform','user_id','emp_number'));

            $response = array();
            $db = new DbHandler();
            $result=$db->JobsDeptHistory($user_id,$status_id);

           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successfull";
                 $response["JobsDeptHistory"]=$result['JobsDeptHistory'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["JobsDeptHistory"]=array();
            }

            echoRespnse(200, $response);
 });


// for JobsDeptTechHistory we have to implement it here.
$app->post('/JobsDeptTechHistory', 'authenticatedefault', function() use ($app) 
{

            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);
             // reading post params
            
             $user_id = $data['user_id'];
             $status_id = $data['status_id'];
             $deptId = $data['dept_id'];
           /* $platform   = $app->request()->post('platform');//1-Android ,2-IOS
            $emp_number  = $app->request()->post('emp_number');
            $user_id = $app->request()->post('user_id');*/
              accessToken($user_id); 

             // check for required params
            //verifyRequiredParams(array('platform','user_id','emp_number'));

            $response = array();
            $db = new DbHandler();
            $result=$db->JobsDeptTechHistory($user_id,$status_id,$deptId);

           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successfull";
                 $response["JobsDeptTechHistory"]=$result['JobsDeptTechHistory'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["JobsDeptTechHistory"]=array();
            }

            echoRespnse(200, $response);
 });


// for JobsTechncnHistory we have to implement it here.
$app->post('/JobsTechncnHistory', 'authenticatedefault', function() use ($app) 
{

            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);
             // reading post params
            
             $user_id = $data['user_id'];
             $status_id = $data['status_id'];
              $employee_number = $data['employee_number'];
           /* $platform   = $app->request()->post('platform');//1-Android ,2-IOS
            $emp_number  = $app->request()->post('emp_number');
            $user_id = $app->request()->post('user_id');*/
              accessToken($user_id); 

             // check for required params
            //verifyRequiredParams(array('platform','user_id','emp_number'));

            $response = array();
            $db = new DbHandler();
            $result=$db->JobsTechncnHistory($user_id,$status_id,$employee_number);

           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successfull";
                 $response["JobsTechncnHistory"]=$result['JobsTechncnHistory'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["JobsTechncnHistory"]=array();
            }

            echoRespnse(200, $response);
 });

// for Machine wise Break Dwon Report we have to implement it here.
$app->post('/MachineWiseBrkDwnAndCount', 'authenticatedefault', function() use ($app) 
{

            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);
             // reading post params
            
             $user_id = $data['user_id'];
             
           /* $platform   = $app->request()->post('platform');//1-Android ,2-IOS
            $emp_number  = $app->request()->post('emp_number');
            $user_id = $app->request()->post('user_id');*/
              accessToken($user_id); 

             // check for required params
            //verifyRequiredParams(array('platform','user_id','emp_number'));

            $response = array();
            $db = new DbHandler();
            $result=$db->MachineWiseBrkDwnAndCount($user_id);

           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successfull";
                 $response["machineWiseBreakdown"]=$result['machineWiseBreakdown'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["machineWiseBreakdown"]=array();
            }

            echoRespnse(200, $response);
 });


$app->post('/EqpmntJobCountEqId', 'authenticatedefault', function() use ($app) 
{

            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);
             // reading post params
            
             $user_id = $data['user_id'];
             
              $equipmntId = $data['equipmntId'];
           /* $platform   = $app->request()->post('platform');//1-Android ,2-IOS
            $emp_number  = $app->request()->post('emp_number');
            $user_id = $app->request()->post('user_id');*/
              accessToken($user_id); 

             // check for required params
            //verifyRequiredParams(array('platform','user_id','emp_number'));

            $response = array();
            $db = new DbHandler();
            $result=$db->EqpmntJobCountEqId($user_id,$equipmntId);

           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successfull";
                 $response["EqpmntJobCountEqId"]=$result['EqpmntJobCountEqId'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["EqpmntJobCountEqId"]=array();
            }

            echoRespnse(200, $response);
 });


$app->post('/EqpmntDetailsBasedOnEqId', 'authenticatedefault', function() use ($app) 
{

            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);
             // reading post params
            
             $user_id = $data['user_id'];
             
              $equipmntId = $data['equipmntId'];
           /* $platform   = $app->request()->post('platform');//1-Android ,2-IOS
            $emp_number  = $app->request()->post('emp_number');
            $user_id = $app->request()->post('user_id');*/
              accessToken($user_id); 

             // check for required params
            //verifyRequiredParams(array('platform','user_id','emp_number'));

            $response = array();
            $db = new DbHandler();
            $result=$db->EqpmntDetailsBasedOnEqId($user_id,$equipmntId);

           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successfull";
                 $response["EqpmntDetailsBasedOnEqId"]=$result['EqpmntDetailsBasedOnEqId'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["EqpmntDetailsBasedOnEqId"]=array();
            }

            echoRespnse(200, $response);
 });


$app->post('/MaintenanceTypeReport', 'authenticatedefault', function() use ($app) 
{

            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);
             // reading post params
            
             $user_id = $data['user_id'];
             
              //$equipmntId = $data['equipmntId'];
           /* $platform   = $app->request()->post('platform');//1-Android ,2-IOS
            $emp_number  = $app->request()->post('emp_number');
            $user_id = $app->request()->post('user_id');*/
              accessToken($user_id); 

             // check for required params
            //verifyRequiredParams(array('platform','user_id','emp_number'));

            $response = array();
            $db = new DbHandler();
            $result=$db->MaintenanceTypeReport($user_id);

           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successfull";
                 $response["MaintenanceTypeReport"]=$result['MaintenanceTypeReport'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["MaintenanceTypeReport"]=array();
            }

            echoRespnse(200, $response);
 });


// for ticketDetByTypeOfIssue we have to implement it here.
$app->post('/ticketDetByTypeOfIssue', 'authenticatedefault', function() use ($app) 
{           

            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);

            $userIdPass  = $data['user_id'];
            


             // reading post params
          /*  $userIdPass = $app->request()->post('user_id');
            $platform= $app->request()->post('platform');*///1-Android ,2-IOS
              accessToken($userIdPass); 
             // check for required params
            //verifyRequiredParams(array('user_id','platform'));
            
            $response = array();
            $db = new DbHandler();
            $result=$db->ticketDetByTypeOfIssue($userIdPass);
        
           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["ticketDetByTypeOfIssue"]=$result['ticketDetByTypeOfIssue'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["ticketDetByTypeOfIssue"]=array();
            }
        
            echoRespnse(200, $response);
 });


// for downTimeReport we have to implement it here.
$app->post('/downTimeReport', 'authenticatedefault', function() use ($app) 
{           

            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);

            $userIdPass  = $data['user_id'];
            

              accessToken($userIdPass); 
           
            $response = array();
            $db = new DbHandler();
            $result=$db->ticketsDownTimeReport($userIdPass);
        
           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["ticketsDownTimeReport"]=$result['ticketsDownTimeReport'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["ticketsDownTimeReport"]=array();
            }
        
            echoRespnse(200, $response);
 });


// for ticketDetByTypeOfIssue we have to implement it here.
$app->post('/machineDownTimeReport', 'authenticatedefault', function() use ($app) 
{           

            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);

            $userIdPass  = $data['user_id'];
            


             // reading post params
          /*  $userIdPass = $app->request()->post('user_id');
            $platform= $app->request()->post('platform');*///1-Android ,2-IOS
              accessToken($userIdPass); 
             // check for required params
            //verifyRequiredParams(array('user_id','platform'));
            
            $response = array();
            $db = new DbHandler();
            $result1=$db->machineDownTimeReport($userIdPass);


        
           if ($result1['status']==1) 
           {
                //echo "if";
                 
                 $response1["status"] =1;
                 $response1['message'] = "successful";
                 $response1["machineDownTime"] = $result1['machineDownTimeReport'];

                /*  echo "<pre>";
            print_r( $response1["machineDownTime"]);
            echo "</pre>";*/

            }
            else
            {
                //echo "else";
                $response1['status'] =0;
                $response1['message'] = 'No Records Found';
                $response1["machineDownTime"]=array();
            }
        
            echoRespnse(200, $response1);
 });


// for jobsByStatus we have to implement it here.
$app->post('/jobsByStatus', 'authenticatedefault', function() use ($app) 
{           

            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);

            $userIdPass  = $data['user_id'];
            


             // reading post params
          /*  $userIdPass = $app->request()->post('user_id');
            $platform= $app->request()->post('platform');*///1-Android ,2-IOS
              accessToken($userIdPass); 
             // check for required params
            //verifyRequiredParams(array('user_id','platform'));
            
            $response = array();
            $db = new DbHandler();
            $result1=$db->jobsByStatus($userIdPass);


        
           if ($result1['status']==1) 
           {
                //echo "if";
                 
                 $response1["status"] =1;
                 $response1['message'] = "successful";
                 $response1["jobsByStatus"] = $result1['jobsByTicketStatus'];

                /*  echo "<pre>";
            print_r( $response1["machineDownTime"]);
            echo "</pre>";*/

            }
            else
            {
                //echo "else";
                $response1['status'] =0;
                $response1['message'] = 'No Records Found';
                $response1["jobsByStatus"]=array();
            }
        
            echoRespnse(200, $response1);
 });


// for jobsHandledByEngineer we have to implement it here.
$app->post('/jobsHandledByEngineer', 'authenticatedefault', function() use ($app) 
{           

            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);

            $userIdPass  = $data['user_id'];
            

              accessToken($userIdPass); 
             
            
            $response = array();
            $db = new DbHandler();
            $result1=$db->jobsHandledByEngineer($userIdPass);


        
           if ($result1['status']==1) 
           {
                
                 
                 $response1["status"] =1;
                 $response1['message'] = "successful";
                 $response1["jobsHandledByEngineer"] = $result1['jobsHandledByEngineer'];

                

            }
            else
            {
                //echo "else";
                $response1['status'] =0;
                $response1['message'] = 'No Records Found';
                $response1["jobsHandledByEngineer"]=array();
            }
        
            echoRespnse(200, $response1);
 });


// for jobsHandledByEngineer we have to implement it here.
$app->post('/jobsHandledByTechnician', 'authenticatedefault', function() use ($app) 
{           

            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);

            $userIdPass  = $data['user_id'];
            

              accessToken($userIdPass); 
             
            
            $response = array();
            $db = new DbHandler();
            $result1=$db->jobsHandledByTechnician($userIdPass);


        
           if ($result1['status']==1) 
           {
                
                 
                 $response1["status"] =1;
                 $response1['message'] = "successful";
                 $response1["jobsHandledByTechnician"] = $result1['jobsHandledByTechnician'];

                

            }
            else
            {
                //echo "else";
                $response1['status'] =0;
                $response1['message'] = 'No Records Found';
                $response1["jobsHandledByTechnician"]=array();
            }
        
            echoRespnse(200, $response1);
 });



$app->post('/pdfDwnLoad', 'authenticatedefault', function() use ($app) 
{         
     

            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);
           
            // $platform   = $data['platform'];
            //$mobnumber  = $data['mobnumber'];

             //$id  = $data['id'];
            // $response = array();
            $user_id  = $data['user_id'];
            accessToken($user_id); 

            $req = $app->request;
            $index_url = $req->getUrl()."".$req->getRootUri();
            $base_url = implode('/',array_slice(explode('/',$index_url),0,-1));
            $pdf_url = $base_url."/include/pdfs/";
            $response = array();
          $db = new DbHandler();
          $result=$db->pdfDwnLoad($user_id);
   

          /*if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response["pdf"]=$pdf_url."pdftest.php";
                 $response["tktdetforTypeofIssue"]=$result['tktdetforTypeofIssue'];

            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["tktdetforTypeofIssue"]=array();
            }*/

            //$response["status"] =1;
            //$response["tktdetailsforTypeofIssue"]=$result['tktdetailsforTypeofIssue'];
            $response["pdf"]=$pdf_url."pdftest.php";
      
        echoRespnse(200, $response);
 });


$app->post('/xlsDwnLoad', 'authenticatedefault', function() use ($app) 
{         
     

            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);
           
            // $platform   = $data['platform'];
            //$mobnumber  = $data['mobnumber'];

             //$user_id  = $data['user_id'];
            // $response = array();
            $user_id  = $data['user_id'];
            accessToken($user_id); 

            $req = $app->request;
            $index_url = $req->getUrl()."".$req->getRootUri();
            $base_url = implode('/',array_slice(explode('/',$index_url),0,-1));
            $pdf_url = $base_url."/include/excel/";
            $response = array();
          $db = new DbHandler();
          $result=$db->xlsDwnLoad($user_id);
   

          /*if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response["pdf"]=$pdf_url."pdftest.php";
                 $response["tktdetforTypeofIssue"]=$result['tktdetforTypeofIssue'];

            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["tktdetforTypeofIssue"]=array();
            }*/

            //$response["status"] =1;
            //$response["tktdetailsforTypeofIssue"]=$result['tktdetailsforTypeofIssue'];
            $response["pdf"]=$pdf_url."exceltest.php";
      
        echoRespnse(200, $response);
 });

$app->post('/pdfStatusDwnLoad', 'authenticatedefault', function() use ($app) 
{         
     

            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);
           
            // $platform   = $data['platform'];
            //$mobnumber  = $data['mobnumber'];

             //$id  = $data['id'];
            // $response = array();
            $user_id  = $data['user_id'];
            accessToken($user_id); 

            $req = $app->request;
            $index_url = $req->getUrl()."".$req->getRootUri();
            $base_url = implode('/',array_slice(explode('/',$index_url),0,-1));
            $pdf_url = $base_url."/include/pdfs/";
            $response = array();
          $db = new DbHandler();
          $result=$db->pdfStatusDwnLoad($user_id);
   

          /*if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response["pdf"]=$pdf_url."pdftest.php";
                 $response["tktdetforTypeofIssue"]=$result['tktdetforTypeofIssue'];

            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["tktdetforTypeofIssue"]=array();
            }*/

            //$response["status"] =1;
            //$response["tktdetailsforTypeofIssue"]=$result['tktdetailsforTypeofIssue'];
            $response["pdf"]=$pdf_url."pdfstatus.php";
      
        echoRespnse(200, $response);
 });

$app->post('/excelStatusDwnLoad', 'authenticatedefault', function() use ($app) 
{         
     

            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);
           
            // $platform   = $data['platform'];
            //$mobnumber  = $data['mobnumber'];

             //$user_id  = $data['user_id'];
            // $response = array();
            $user_id  = $data['user_id'];
            accessToken($user_id); 

            $req = $app->request;
            $index_url = $req->getUrl()."".$req->getRootUri();
            $base_url = implode('/',array_slice(explode('/',$index_url),0,-1));
            $pdf_url = $base_url."/include/excel/";
            $response = array();
          $db = new DbHandler();
          $result=$db->excelStatusDwnLoad($user_id);
   

          /*if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response["pdf"]=$pdf_url."pdftest.php";
                 $response["tktdetforTypeofIssue"]=$result['tktdetforTypeofIssue'];

            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["tktdetforTypeofIssue"]=array();
            }*/

            //$response["status"] =1;
            //$response["tktdetailsforTypeofIssue"]=$result['tktdetailsforTypeofIssue'];
            $response["pdf"]=$pdf_url."excelstatus.php";
      
        echoRespnse(200, $response);
 });

$app->post('/pdfMainTypRepDwnLoad', 'authenticatedefault', function() use ($app) 
{         
     

            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);
           
            // $platform   = $data['platform'];
            //$mobnumber  = $data['mobnumber'];

             //$id  = $data['id'];
            // $response = array();
            $user_id  = $data['user_id'];
            accessToken($user_id); 

            $req = $app->request;
            $index_url = $req->getUrl()."".$req->getRootUri();
            $base_url = implode('/',array_slice(explode('/',$index_url),0,-1));
            $pdf_url = $base_url."/include/pdfs/";
            $response = array();
          $db = new DbHandler();
          $result=$db->pdfMainTypRepDwnLoad($user_id);
   

          /*if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response["pdf"]=$pdf_url."pdftest.php";
                 $response["tktdetforTypeofIssue"]=$result['tktdetforTypeofIssue'];

            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["tktdetforTypeofIssue"]=array();
            }*/

            //$response["status"] =1;
            //$response["tktdetailsforTypeofIssue"]=$result['tktdetailsforTypeofIssue'];
            $response["pdf"]=$pdf_url."pdfmainTypeRep.php";
      
        echoRespnse(200, $response);
 });


$app->post('/excelMainTypRepDwnLoad', 'authenticatedefault', function() use ($app) 
{         
     

            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);
           
           
            $user_id  = $data['user_id'];
            accessToken($user_id); 

            $req = $app->request;
            $index_url = $req->getUrl()."".$req->getRootUri();
            $base_url = implode('/',array_slice(explode('/',$index_url),0,-1));
            $pdf_url = $base_url."/include/excel/";
            $response = array();
          $db = new DbHandler();
          $result=$db->excelMainTypRepDwnLoad($user_id);
   

         
            $response["pdf"]=$pdf_url."excelmainTypeRep.php";
      
        echoRespnse(200, $response);
 });


$app->post('/EqpmntDetailsBasedOnEqIdAll', 'authenticatedefault', function() use ($app) 
{         
     

             $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);
             // reading post params
            
             $user_id = $data['user_id'];
             
          
              accessToken($user_id); 

             // check for required params
            //verifyRequiredParams(array('platform','user_id','emp_number'));

            $response = array();
            $db = new DbHandler();
            $result=$db->EqpmntDetailsBasedOnEqIdAll($user_id);

           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successfull";
                 $response["EqpmntDetailsBasedOnEqIdAll"]=$result['EqpmntDetailsBasedOnEqIdAll'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["EqpmntDetailsBasedOnEqIdAll"]=array();
            }

            
           
      
        echoRespnse(200, $response);
 });

$app->post('/pdfMchnwiseBrkDwnLoad', 'authenticatedefault', function() use ($app) 
{         
     

            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);
           
            $user_id  = $data['user_id'];
            accessToken($user_id); 

            $req = $app->request;
            $index_url = $req->getUrl()."".$req->getRootUri();
            $base_url = implode('/',array_slice(explode('/',$index_url),0,-1));
            $pdf_url = $base_url."/include/pdfs/";
            $response = array();
          $db = new DbHandler();
          $result=$db->pdfMchnwiseBrkDwnLoad($user_id);
   

           $response["pdf"]=$pdf_url."pdfmchnwsBrkRep.php";
      
        echoRespnse(200, $response);
 });


$app->post('/excelMchnwiseBrkDwnLoad', 'authenticatedefault', function() use ($app) 
{         
     

            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);
           
           
            $user_id  = $data['user_id'];
            accessToken($user_id); 

            $req = $app->request;
            $index_url = $req->getUrl()."".$req->getRootUri();
            $base_url = implode('/',array_slice(explode('/',$index_url),0,-1));
            $pdf_url = $base_url."/include/excel/";
            $response = array();
          $db = new DbHandler();
          $result=$db->excelMchnwiseBrkDwnLoad($user_id);
   

         
            $response["pdf"]=$pdf_url."excelmchnwsBrkRep.php";
      
        echoRespnse(200, $response);
 });



$app->post('/pdfJobsHndlByTechDwnLoad', 'authenticatedefault', function() use ($app) 
{         
     

            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);
           
            $user_id  = $data['user_id'];
            accessToken($user_id); 

            $req = $app->request;
            $index_url = $req->getUrl()."".$req->getRootUri();
            $base_url = implode('/',array_slice(explode('/',$index_url),0,-1));
            $pdf_url = $base_url."/include/pdfs/";
            $response = array();
          $db = new DbHandler();
          $result=$db->pdfJobsHndlByTechDwnLoad($user_id);
   

           $response["pdf"]=$pdf_url."pdfjbshandlByTech.php";
      
        echoRespnse(200, $response);
 });


$app->post('/excelJobsHndlByTechDwnLoad', 'authenticatedefault', function() use ($app) 
{         
     

            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);
           
           
            $user_id  = $data['user_id'];
            accessToken($user_id); 

            $req = $app->request;
            $index_url = $req->getUrl()."".$req->getRootUri();
            $base_url = implode('/',array_slice(explode('/',$index_url),0,-1));
            $pdf_url = $base_url."/include/excel/";
            $response = array();
          $db = new DbHandler();
          $result=$db->excelJobsHndlByTechDwnLoad($user_id);
   

         
            $response["pdf"]=$pdf_url."exceljbshndlByTech.php";
      
        echoRespnse(200, $response);
 });


$app->post('/pdfJobsHndlByEngDwnLoad', 'authenticatedefault', function() use ($app) 
{         
     

            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);
           
            $user_id  = $data['user_id'];
            accessToken($user_id); 

            $req = $app->request;
            $index_url = $req->getUrl()."".$req->getRootUri();
            $base_url = implode('/',array_slice(explode('/',$index_url),0,-1));
            $pdf_url = $base_url."/include/pdfs/";
            $response = array();
          $db = new DbHandler();
          $result=$db->pdfJobsHndlByEngDwnLoad($user_id);
   

           $response["pdf"]=$pdf_url."pdfjbshndlByEng.php";
      
        echoRespnse(200, $response);
 });


$app->post('/excelJobsHndlByEngDwnLoad', 'authenticatedefault', function() use ($app) 
{         
     

            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);
           
           
            $user_id  = $data['user_id'];
            accessToken($user_id); 

            $req = $app->request;
            $index_url = $req->getUrl()."".$req->getRootUri();
            $base_url = implode('/',array_slice(explode('/',$index_url),0,-1));
            $pdf_url = $base_url."/include/excel/";
            $response = array();
          $db = new DbHandler();
          $result=$db->excelJobsHndlByEngDwnLoad($user_id);
   

         
            $response["pdf"]=$pdf_url."exceljbshndlByEng.php";
      
        echoRespnse(200, $response);
 });


$app->post('/pdfDwnTimeRptDwnLoad', 'authenticatedefault', function() use ($app) 
{         
      //echo "pdfDwnTimeRptDwnLoad";

            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);
           
            $user_id  = $data['user_id'];
            accessToken($user_id); 

            $req = $app->request;
            $index_url = $req->getUrl()."".$req->getRootUri();
            $base_url = implode('/',array_slice(explode('/',$index_url),0,-1));
            $pdf_url = $base_url."/include/pdfs/";
            
            $response = array();
          $db = new DbHandler();
          $result=$db->pdfDwnTimeRptDwnLoad($user_id);
   
          //echo $result['tktdetails'];
           $response["pdf"]=$pdf_url."pdfdwnTimeRep.php";

            echoRespnse(200, $response);
      
 });


$app->post('/excelDowntimeReportDwnLoad', 'authenticatedefault', function() use ($app) 
{         
     

            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);
           
           
            $user_id  = $data['user_id'];
            accessToken($user_id); 

            $req = $app->request;
            $index_url = $req->getUrl()."".$req->getRootUri();
            $base_url = implode('/',array_slice(explode('/',$index_url),0,-1));
            $pdf_url = $base_url."/include/excel/";
            $response = array();
          $db = new DbHandler();
          $result=$db->excelDowntimeReportDwnLoad($user_id);
   

         
            $response["pdf"]=$pdf_url."exceldwnTimeRprt.php";
      
        echoRespnse(200, $response);
 });


// for ticket/add we have to implement it here.
$app->post('/ticket/edit', 'authenticatedefault', function() use ($app) 
{           
             $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);
            

              $user_id  = $data['user_id'];
              $job_id = $data['job_id'];
              $locationId  = $data['locationid'];
              $plantId  = $data['plantid'];
              $usrdeptId  = $data['usrdeptid'];
              $notifytoId = 1;
              $statusId   = $data['status_id'];
              $funclocId  = $data['funclocid'];
              $eqipmntId  = $data['eqipmntid'];
              $typofisId  = $data['typofisId'];
              $subject  = $data['subject'];
              $description  = $data['description'];
              $prtyId  = $data['prtyid'];
              $svrtyId  = $data['svrtyid'];
              $reportedBy  = $data['reportedby'];
              $submitted_by_emp_number  = $data['subbyempnum'];
              $submitted_by_name  = $data['submittedbyname'];
              $attachmentId  = $data['attachmentId'];
            
            $reportedOn = getCurrentDateTime();
            
            $submitted_on = getCurrentDateTime();

        
              accessToken($user_id); 


            $response = array();
            $db = new DbHandler();
            $result=$db->ticketUpd($job_id,$locationId,$plantId,$usrdeptId,$notifytoId,$statusId,$funclocId,$eqipmntId,$typofisId,$subject,$description,$prtyId,$svrtyId,$reportedBy,$submitted_by_emp_number,$submitted_by_name,$reportedOn,$submitted_on,$user_id,$attachmentId);

            
           if ($result['status']==1) 
           {    

                $response["status"] =1;
                $response['message'] = "update successful";
                $response["ticketupdid"]=$result['ticketupdid'];

              }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["ticketupdid"]=array();
            }

            echoRespnse(200, $response);
 });


// for persnlDetails we have to implement it here.
$app->post('/persnlDetails', 'authenticatedefault', function() use ($app) 
{           

            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);

            $userIdPass  = $data['user_id'];
            


              accessToken($userIdPass); 
             
            
            $response = array();
            $db = new DbHandler();
            $result1=$db->persnlDetails($userIdPass);


        
           if ($result1['status']==1) 
           {
                //echo "if";
                 
                 $response1["status"] =1;
                 $response1['message'] = "successful";
                 $response1["persnlDetails"] = $result1['persnlDetails'];

            

            }
            else
            {
                //echo "else";
                $response1['status'] =0;
                $response1['message'] = 'No Records Found';
                $response1["persnlDetails"]=array();
            }
        
            echoRespnse(200, $response1);
 });

$app->post('/contactDetails', 'authenticatedefault', function() use ($app) 
{           

            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);

            $userIdPass  = $data['user_id'];
            


              accessToken($userIdPass); 
             
            
            $response = array();
            $db = new DbHandler();
            $result1=$db->contactDetails($userIdPass);


        
           if ($result1['status']==1) 
           {
                //echo "if";
                 
                 $response1["status"] =1;
                 $response1['message'] = "successful";
                 $response1["contactDetails"] = $result1['contactDetails'];

            

            }
            else
            {
                //echo "else";
                $response1['status'] =0;
                $response1['message'] = 'No Records Found';
                $response1["contactDetails"]=array();
            }
        
            echoRespnse(200, $response1);
 });


$app->post('/emergencyContacts', 'authenticatedefault', function() use ($app) 
{           

            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);

            $userIdPass  = $data['user_id'];
            


              accessToken($userIdPass); 
             
            
            $response = array();
            $db = new DbHandler();
            $result1=$db->emergencyContacts($userIdPass);


        
           if ($result1['status']==1) 
           {
                //echo "if";
                 
                 $response1["status"] =1;
                 $response1['message'] = "successful";
                 $response1["emergencyContacts"] = $result1['emergencyContacts'];

            

            }
            else
            {
                //echo "else";
                $response1['status'] =0;
                $response1['message'] = 'No Records Found';
                $response1["emergencyContacts"]=array();
            }
        
            echoRespnse(200, $response1);
 });


$app->post('/asigndDepdents', 'authenticatedefault', function() use ($app) 
{           

            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);

            $userIdPass  = $data['user_id'];
            


              accessToken($userIdPass); 
             
            
            $response = array();
            $db = new DbHandler();
            $result1=$db->asigndDepdents($userIdPass);


        
           if ($result1['status']==1) 
           {
                //echo "if";
                 
                 $response1["status"] =1;
                 $response1['message'] = "successful";
                 $response1["asigndDepdents"] = $result1['asigndDepdents'];

            

            }
            else
            {
                //echo "else";
                $response1['status'] =0;
                $response1['message'] = 'No Records Found';
                $response1["asigndDepdents"]=array();
            }
        
            echoRespnse(200, $response1);
 });



$app->post('/imgrtnRcrds', 'authenticatedefault', function() use ($app) 
{           

            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);

            $userIdPass  = $data['user_id'];
            


              accessToken($userIdPass); 
             
            
            $response = array();
            $db = new DbHandler();
            $result1=$db->imgrtnRcrds($userIdPass);


        
           if ($result1['status']==1) 
           {
                //echo "if";
                 
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["imgrtnRcrds"] = $result1['imgrtnRcrds'];

            

            }
            else
            {
                //echo "else";
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["imgrtnRcrds"]=array();
            }
        
            echoRespnse(200, $response);
 });


$app->post('/jobDetails', 'authenticatedefault', function() use ($app) 
{           

            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);

            $userIdPass  = $data['user_id'];
            


              accessToken($userIdPass); 
             
            
            $response = array();
            $db = new DbHandler();
            $result=$db->jobDetails($userIdPass);


        
           if ($result['status']==1) 
           {
                //echo "if";
                 
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["jobDetails"] = $result['jobDetails'];

            

            }
            else
            {
                //echo "else";
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["jobDetails"]=array();
            }
        
            echoRespnse(200, $response);
 });




$app->post('/salaryComponents', 'authenticatedefault', function() use ($app) 
{           

            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);

            $userIdPass  = $data['user_id'];
            


              accessToken($userIdPass); 
             
            
            $response = array();
            $db = new DbHandler();
            $result=$db->salaryComponents($userIdPass);


        
           if ($result['status']==1) 
           {
                //echo "if";
                 
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["salaryComponents"] = $result['salaryComponents'];
           

            }
            else
            {
                //echo "else";
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["salaryComponents"]=array();
            }
        
            echoRespnse(200, $response);
 });


$app->post('/reportTo', 'authenticatedefault', function() use ($app) 
{           

            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);

            $userIdPass  = $data['user_id'];
            


              accessToken($userIdPass); 
             
            
            $response = array();
            $db = new DbHandler();
            $result=$db->reportTo($userIdPass);


        
           if ($result['status']==1) 
           {
                //echo "if";
                 
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["reportTo"] = $result['reportTo'];
           

            }
            else
            {
                //echo "else";
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["reportTo"]=array();
            }
        
            echoRespnse(200, $response);
 });


$app->post('/empSubordinatesrepTo', 'authenticatedefault', function() use ($app) 
{           

            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);

            $userIdPass  = $data['user_id'];
            


              accessToken($userIdPass); 
             
            
            $response = array();
            $db = new DbHandler();
            $result=$db->empSubordinatesrepTo($userIdPass);


        
           if ($result['status']==1) 
           {
                //echo "if";
                 
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["empSubordinates"] = $result['empSubordinates'];
           

            }
            else
            {
                //echo "else";
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["empSubordinates"]=array();
            }
        
            echoRespnse(200, $response);
 });


$app->post('/workExp', 'authenticatedefault', function() use ($app) 
{           

            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);

            $userIdPass  = $data['user_id'];
            


              accessToken($userIdPass); 
             
            
            $response = array();
            $db = new DbHandler();
            $result=$db->workExp($userIdPass);


        
           if ($result['status']==1) 
           {
                //echo "if";
                 
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["workExp"] = $result['workExp'];
           

            }
            else
            {
                //echo "else";
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["workExp"]=array();
            }
        
            echoRespnse(200, $response);
 });


$app->post('/emplEductn', 'authenticatedefault', function() use ($app) 
{           

            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);

            $userIdPass  = $data['user_id'];
            


              accessToken($userIdPass); 
             
            
            $response = array();
            $db = new DbHandler();
            $result=$db->emplEductn($userIdPass);


        
           if ($result['status']==1) 
           {
                //echo "if";
                 
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["emplEductn"] = $result['emplEductn'];
           

            }
            else
            {
                //echo "else";
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["emplEductn"]=array();
            }
        
            echoRespnse(200, $response);
 });


$app->post('/empSkills', 'authenticatedefault', function() use ($app) 
{           

            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);

            $userIdPass  = $data['user_id'];
            


              accessToken($userIdPass); 
             
            
            $response = array();
            $db = new DbHandler();
            $result=$db->empSkills($userIdPass);


        
           if ($result['status']==1) 
           {
                //echo "if";
                 
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["empSkills"] = $result['empSkills'];
           

            }
            else
            {
                //echo "else";
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["empSkills"]=array();
            }
        
            echoRespnse(200, $response);
 });


$app->post('/empLang', 'authenticatedefault', function() use ($app) 
{           

            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);

            $userIdPass  = $data['user_id'];
            


              accessToken($userIdPass); 
             
            
            $response = array();
            $db = new DbHandler();
            $result=$db->empLang($userIdPass);


        
           if ($result['status']==1) 
           {
                //echo "if";
                 
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["empLang"] = $result['empLang'];
           

            }
            else
            {
                //echo "else";
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["empLang"]=array();
            }
        
            echoRespnse(200, $response);
 });


$app->post('/empLicense', 'authenticatedefault', function() use ($app) 
{           

            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);

            $userIdPass  = $data['user_id'];
            


              accessToken($userIdPass); 
             
            
            $response = array();
            $db = new DbHandler();
            $result=$db->empLicense($userIdPass);


        
           if ($result['status']==1) 
           {
                //echo "if";
                 
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["empLicense"] = $result['empLicense'];
           

            }
            else
            {
                //echo "else";
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["empLicense"]=array();
            }
        
            echoRespnse(200, $response);
 });


$app->post('/empMbrshp', 'authenticatedefault', function() use ($app) 
{           

            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);

            $userIdPass  = $data['user_id'];
            


              accessToken($userIdPass); 
             
            
            $response = array();
            $db = new DbHandler();
            $result=$db->empMbrshp($userIdPass);


        
           if ($result['status']==1) 
           {
                //echo "if";
                 
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["empMbrshp"] = $result['empMbrshp'];
           

            }
            else
            {
                //echo "else";
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["empMbrshp"]=array();
            }
        
            echoRespnse(200, $response);
 });



$app->post('/deleteAttachment', 'authenticatedefault', function() use ($app) 
{           

            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);

            $userIdPass  = $data['user_id'];
            $ticket_id  = $data['ticket_id'];
            $attach_id  = $data['attach_id'];

              accessToken($userIdPass); 
             
            
            $response = array();
            $db = new DbHandler();
            $result=$db->deleteAttachment($ticket_id,$attach_id);


        
           if ($result['status']==1) 
           {
                //echo "if";
                 
                 $response["status"] =1;
                 $response['message'] = "deleted successfully";
                 $response["delAttach"] = $result['delAttach'];
           

            }
            else
            {
                //echo "else";
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["delAttach"]=array();
            }
        
            echoRespnse(200, $response);
 });


$app->post('/deleteActionLogAttachment', 'authenticatedefault', function() use ($app) 
{           

            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);

            $userIdPass  = $data['user_id'];
            $ticket_action_log_id  = $data['ticket_action_log_id'];
            $attach_id  = $data['attach_id'];

              accessToken($userIdPass); 
             
            
            $response = array();
            $db = new DbHandler();
        $result=$db->deleteActionLogAttachment($ticket_action_log_id,$attach_id);


        
           if ($result['status']==1) 
           {
                //echo "if";
                 
                 $response["status"] =1;
                 $response['message'] = "deleted successfully";
                 $response["delActnLogAttach"] = $result['delActnLogAttach'];
           

            }
            else
            {
                //echo "else";
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["delActnLogAttach"]=array();
            }
        
            echoRespnse(200, $response);
 });


$app->post('/jobCountAll', 'authenticatedefault', function() use ($app) 
{           


      //echo "jobs";
            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);

            $userId  = $data['user_id'];
 
              accessToken($userId); 
            
            $response = array();
            $db = new DbHandler();
            $result=$db->jobCountAll($userId);
        
           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["jobCountAll"]=$result['jobCountAll'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["jobCountAll"]=array();
            }
        
            echoRespnse(200, $response);
 });


// for TechTsksLstEmpnum we have to implement it here.
$app->post('/EngTechTsksLst', 'authenticatedefault', function() use ($app) 
{

            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);
             // reading post params
             $emp_number = $data['emp_number'];
             $user_id = $data['user_id'];
           /* $platform   = $app->request()->post('platform');//1-Android ,2-IOS
            $emp_number  = $app->request()->post('emp_number');
            $user_id = $app->request()->post('user_id');*/
              accessToken($user_id); 

             // check for required params
            //verifyRequiredParams(array('platform','user_id','emp_number'));

            $response = array();
            $db = new DbHandler();
            $result=$db->EngTechTsksLst($emp_number);

           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["EngTechTasks"]=$result['EngTechTasks'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["EngTechTasks"]=array();
            }

            echoRespnse(200, $response);
 });



// for MyjbandDeptJbsCount we have to implement it here.
$app->post('/MyjbsandDeptJbsCount', 'authenticatedefault', function() use ($app) 
{

            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);
            
             $user_id = $data['user_id'];
          
              accessToken($user_id); 

            $response = array();
            $db = new DbHandler();
            $result=$db->MyjbsandDeptJbsCount($user_id);

           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["MyjbsandDeptJbsCount"]=$result['MyjbsandDeptJbsCount'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["MyjbsandDeptJbsCount"]=array();
            }

            echoRespnse(200, $response);
 });


// for ShiftJbsCount we have to implement it here.
$app->post('/ShiftJobsList', 'authenticatedefault', function() use ($app) 
{

            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);
            
             $user_id = $data['user_id'];
          
              accessToken($user_id); 

            $response = array();
            $db = new DbHandler();
            $result=$db->ShiftJobsList($user_id);

           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["ShiftJobsList"]=$result['ShiftJobsList'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["ShiftJobsList"]=array();
            }

            echoRespnse(200, $response);
 });


// for overAllJobs we have to implement it here.
$app->post('/overAllNewJobs', 'authenticatedefault', function() use ($app) 
{

            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);
            
             $user_id = $data['user_id'];
          
              accessToken($user_id); 

            $response = array();
            $db = new DbHandler();
            $result=$db->overAllNewJobs($user_id);

           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["overAllNewJobs"]=$result['overAllNewJobs'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["overAllNewJobs"]=array();
            }

            echoRespnse(200, $response);
 });


$app->post('/overAllPendingJobs', 'authenticatedefault', function() use ($app) 
{
  
            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);
            
             $user_id = $data['user_id'];
          
              accessToken($user_id); 

            $response = array();
            $db = new DbHandler();
            $result=$db->overAllPendingJobs($user_id);

           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["overAllPendingJobs"]=$result['overAllPendingJobs'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["overAllPendingJobs"]=array();
            }

            echoRespnse(200, $response);
 });

// for overAllJobs we have to implement it here.
$app->post('/overAllCompletedJobs', 'authenticatedefault', function() use ($app) 
{

            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);
            
             $user_id = $data['user_id'];
          
              accessToken($user_id); 

            $response = array();
            $db = new DbHandler();
            $result=$db->overAllCompletedJobs($user_id);

           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["overAllCompletedJobs"]=$result['overAllCompletedJobs'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["overAllCompletedJobs"]=array();
            }

            echoRespnse(200, $response);
 });



// for overAllTotalJobs we have to implement it here.
$app->post('/overAllTotalJobs', 'authenticatedefault', function() use ($app) 
{

            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);
            
             $user_id = $data['user_id'];
          
              accessToken($user_id); 

            $response = array();
            $db = new DbHandler();
            $result=$db->overAllTotalJobs($user_id);

           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["overAllTotalJobs"]=$result['overAllTotalJobs'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["overAllTotalJobs"]=array();
            }

            echoRespnse(200, $response);
 });


$app->post('/overAllJobsAll', 'authenticatedefault', function() use ($app) 
{

            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);
            
             $user_id = $data['user_id'];
          
              //accessToken($user_id); 

            $response = array();
            $db = new DbHandler();
            $result=$db->overAllJobsAll($user_id);

           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["overAllJobsAll"]=$result['overAllJobsAll'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["overAllJobsAll"]=array();
            }

            echoRespnse(200, $response);
 });


$app->post('/overAllJobsGrtr30', 'authenticatedefault', function() use ($app) 
{

            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);
            
             $user_id = $data['user_id'];
          
              //accessToken($user_id); 

            $response = array();
            $db = new DbHandler();
            $result=$db->overAllJobsGrtr30($user_id);

           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["overAllJobsGrtr30"]=$result['overAllJobsGrtr30'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["overAllJobsGrtr30"]=array();
            }

            echoRespnse(200, $response);
 });


$app->post('/overAllJobsWthn30', 'authenticatedefault', function() use ($app) 
{

            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);
            
             $user_id = $data['user_id'];
          
              //accessToken($user_id); 

            $response = array();
            $db = new DbHandler();
            $result=$db->overAllJobsWthn30($user_id);

           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["overAllJobsWthn30"]=$result['overAllJobsWthn30'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["overAllJobsWthn30"]=array();
            }

            echoRespnse(200, $response);
 });


$app->post('/overAllJobsWthn15', 'authenticatedefault', function() use ($app) 
{

            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);
            
             $user_id = $data['user_id'];
          
              //accessToken($user_id); 

            $response = array();
            $db = new DbHandler();
            $result=$db->overAllJobsWthn15($user_id);

           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["overAllJobsWthn15"]=$result['overAllJobsWthn15'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["overAllJobsWthn15"]=array();
            }

            echoRespnse(200, $response);
 });


$app->post('/overAllJobsWthn7', 'authenticatedefault', function() use ($app) 
{

            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);
            
             $user_id = $data['user_id'];
          
              //accessToken($user_id); 

            $response = array();
            $db = new DbHandler();
            $result=$db->overAllJobsWthn7($user_id);

           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["overAllJobsWthn7"]=$result['overAllJobsWthn7'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["overAllJobsWthn7"]=array();
            }

            echoRespnse(200, $response);
 });

$app->post('/overAllJobsWthn24Hrs', 'authenticatedefault', function() use ($app) 
{

            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);
            
             $user_id = $data['user_id'];
          
              //accessToken($user_id); 

            $response = array();
            $db = new DbHandler();
            $result=$db->overAllJobsWthn24Hrs($user_id);

           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["overAllJobsWthn24Hrs"]=$result['overAllJobsWthn24Hrs'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["overAllJobsWthn24Hrs"]=array();
            }

            echoRespnse(200, $response);
 });


$app->post('/engnrJobsSmryAll', 'authenticatedefault', function() use ($app) 
{

            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);
            
             $user_id = $data['user_id'];
          
              //accessToken($user_id); 

            $response = array();
            $db = new DbHandler();
            $result=$db->engnrJobsSmryAll($user_id);

           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["engnrJobsSmryAll"]=$result['engnrJobsSmryAll'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["engnrJobsSmryAll"]=array();
            }

            echoRespnse(200, $response);
 });


$app->post('/engnrJobsSmryGrtr30', 'authenticatedefault', function() use ($app) 
{

            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);
            
             $user_id = $data['user_id'];
          
              //accessToken($user_id); 

            $response = array();
            $db = new DbHandler();
            $result=$db->engnrJobsSmryGrtr30($user_id);

           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["engnrJobsSmryGrtr30"]=$result['engnrJobsSmryGrtr30'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["engnrJobsSmryGrtr30"]=array();
            }

            echoRespnse(200, $response);
 });


$app->post('/engnrJobsSmryBtwn30', 'authenticatedefault', function() use ($app) 
{

            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);
            
             $user_id = $data['user_id'];
          
              //accessToken($user_id); 

            $response = array();
            $db = new DbHandler();
            $result=$db->engnrJobsSmryBtwn30($user_id);

           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["engnrJobsSmryBtwn30"]=$result['engnrJobsSmryBtwn30'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["engnrJobsSmryBtwn30"]=array();
            }

            echoRespnse(200, $response);
 });

$app->post('/engnrJobsSmryBtwn15', 'authenticatedefault', function() use ($app) 
{

            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);
            
             $user_id = $data['user_id'];
          
              //accessToken($user_id); 

            $response = array();
            $db = new DbHandler();
            $result=$db->engnrJobsSmryBtwn15($user_id);

           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["engnrJobsSmryBtwn15"]=$result['engnrJobsSmryBtwn15'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["engnrJobsSmryBtwn15"]=array();
            }

            echoRespnse(200, $response);
 });


$app->post('/engnrJobsSmryBtwn7', 'authenticatedefault', function() use ($app) 
{

            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);
            
             $user_id = $data['user_id'];
          
              //accessToken($user_id); 

            $response = array();
            $db = new DbHandler();
            $result=$db->engnrJobsSmryBtwn7($user_id);

           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["engnrJobsSmryBtwn7"]=$result['engnrJobsSmryBtwn7'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["engnrJobsSmryBtwn7"]=array();
            }

            echoRespnse(200, $response);
 });


$app->post('/engnrJobsSmryBtwn24Hrs', 'authenticatedefault', function() use ($app) 
{

            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);
            
             $user_id = $data['user_id'];
          
              //accessToken($user_id); 

            $response = array();
            $db = new DbHandler();
            $result=$db->engnrJobsSmryBtwn24Hrs($user_id);

           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["engnrJobsSmryBtwn24Hrs"]=$result['engnrJobsSmryBtwn24Hrs'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["engnrJobsSmryBtwn24Hrs"]=array();
            }

            echoRespnse(200, $response);
 });

$app->post('/agingreport', 'authenticatedefault', function() use ($app) 
{

            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);
            
             $user_id = $data['user_id'];
          
              //accessToken($user_id); 

            $response = array();
            $db = new DbHandler();
            $result=$db->agingreport($user_id);

           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["agingreport"]=$result['agingreport'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["agingreport"]=array();
            }

            echoRespnse(200, $response);
 });


$app->post('/prevMainPlndHrsReport', 'authenticatedefault', function() use ($app) 
{

            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);
            
             $user_id = $data['user_id'];
          
              //accessToken($user_id); 

            $response = array();
            $db = new DbHandler();
            $result=$db->prevMainPlndHrsReport($user_id);

           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["prevMainPlndHrsReport"]=$result['prevMainPlndHrsReport'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["prevMainPlndHrsReport"]=array();
            }

            echoRespnse(200, $response);
 });

$app->post('/prevMainActJbReport', 'authenticatedefault', function() use ($app) 
{

            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);
            
             $user_id = $data['user_id'];
          
              //accessToken($user_id); 

            $response = array();
            $db = new DbHandler();
            $result=$db->prevMainActJbReport($user_id);

           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["prevMainActJbReport"]=$result['prevMainActJbReport'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["prevMainActJbReport"]=array();
            }

            echoRespnse(200, $response);
 });

$app->post('/prevMainMchPlndReport', 'authenticatedefault', function() use ($app) 
{

            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);
            
             $user_id = $data['user_id'];
          
              //accessToken($user_id); 

            $response = array();
            $db = new DbHandler();
            $result=$db->prevMainMchPlndReport($user_id);

           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["prevMainMchPlndReport"]=$result['prevMainMchPlndReport'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["prevMainMchPlndReport"]=array();
            }

            echoRespnse(200, $response);
 });


$app->post('/prevMainMchActulReport', 'authenticatedefault', function() use ($app) 
{

            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);
            
             $user_id = $data['user_id'];
          
              //accessToken($user_id); 

            $response = array();
            $db = new DbHandler();
            $result=$db->prevMainMchActulReport($user_id);

           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["prevMainMchActulReport"]=$result['prevMainMchActulReport'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["prevMainMchActulReport"]=array();
            }

            echoRespnse(200, $response);
 });


$app->post('/prevMainReport', 'authenticatedefault', function() use ($app) 
{

            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);
            
             $user_id = $data['user_id'];
          
              //accessToken($user_id); 

            $response = array();
            $db = new DbHandler();
            $result=$db->prevMainReport($user_id);

           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["prevMainReport"]=$result['prevMainReport'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["prevMainReport"]=array();
            }

            echoRespnse(200, $response);
 });

$app->post('/breakDownDasBrdReport', 'authenticatedefault', function() use ($app) 
{

            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);
            
             $user_id = $data['user_id'];
          
              //accessToken($user_id); 

            $response = array();
            $db = new DbHandler();
            $result=$db->breakDownDasBrdReport($user_id);

           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["breakDownDasBrdReport"]=$result['breakDownDasBrdReport'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["breakDownDasBrdReport"]=array();
            }

            echoRespnse(200, $response);
 });
$app->post('/depDetlsNew', 'authenticatedefault', function() use ($app) 
{

            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);
            
             $user_id = $data['user_id'];
          
              //accessToken($user_id); 

            $response = array();
            $db = new DbHandler();
            $result=$db->depDetlsNew($user_id);

           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["departmntdetails"]=$result['departmntdetails'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["departmntdetails"]=array();
            }

            echoRespnse(200, $response);
 });

$app->post('/departmentsList', 'authenticatedefault', function() use ($app) 
{

            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);
            
             $user_id = $data['user_id'];
          
              //accessToken($user_id); 

            $response = array();
            $db = new DbHandler();
            $result=$db->departmentsList($user_id);

           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["departmntdetails"]=$result['departmntdetails'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["departmntdetails"]=array();
            }

            echoRespnse(200, $response);
 });


$app->post('/sendWhatsapp_msg', 'authenticatedefault', function() use ($app) 
{         
     

            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);
           
            // $platform   = $data['platform'];
            $user_id  = $data['user_id'];

            $country_code = '91';

            $mobile = '9703762356';

            $message = 'whatsapp testing';

            
            
            $response = array();
      $db = new DbHandler();
      $result=$db->sendWhatsapp_msg($country_code, $mobile, $message, $type = 'text');
     //$user_details=$db->userDetails($user_id);
           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "otp sent successfully";
                 $response["sendOtpDetails"]=$result['sendOtpDetails'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'otp sending is unsuccessfull';
                $response["sendOtpDetails"]=array();
            }
      
        echoRespnse(200, $response);
 });


$app->post('/whatsapp_test', 'authenticatedefault', function() use ($app) 
{         
     

            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);
           
            // $platform   = $data['platform'];
            $user_id  = $data['user_id'];

           
            $response = array();
      $db = new DbHandler();
      $result=$db->whatsapp_test();
     //$user_details=$db->userDetails($user_id);
           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "msg sent successfully";
                 $response["whtsMsgDetails"]=$result['whtsMsgDetails'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'msg sending is unsuccessfull';
                $response["whtsMsgDetails"]=array();
            }
      
        echoRespnse(200, $response);
 });


$app->post('/SupplierList', 'authenticatedefault', function() use ($app) 
{         
     

            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);
           
            // $platform   = $data['platform'];
            $user_id  = $data['user_id'];

           
            $response = array();
      $db = new DbHandler();
      $result=$db->SupplierList();
     //$user_details=$db->userDetails($user_id);
           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "success";
                 $response["SupplierListDetails"]=$result['SupplierListDetails'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'unsuccessfull';
                $response["SupplierListDetails"]=array();
            }
      
        echoRespnse(200, $response);
 });


$app->post('/CustomerList', 'authenticatedefault', function() use ($app) 
{         
     

            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);
           
            // $platform   = $data['platform'];
            $user_id  = $data['user_id'];

           
            $response = array();
      $db = new DbHandler();
      $result=$db->CustomerList();
     //$user_details=$db->userDetails($user_id);
           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "success";
                 $response["CustomerListDetails"]=$result['CustomerListDetails'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'unsuccessfull';
                $response["CustomerListDetails"]=array();
            }
      
        echoRespnse(200, $response);
 });


$app->post('/employeeDetailsAll', 'authenticatedefault', function() use ($app) 
{         
     

            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);
           
            // $platform   = $data['platform'];
            $user_id  = $data['user_id'];

           
            $response = array();
      $db = new DbHandler();
      $result=$db->employeeDetailsAll();
     //$user_details=$db->userDetails($user_id);
           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "success";
                 $response["employeeDetailsAll"]=$result['employeeDetailsAll'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'unsuccessfull';
                $response["employeeDetailsAll"]=array();
            }
      
        echoRespnse(200, $response);
 });



// for ticket/add we have to implement it here.
$app->post('/vehicle/book', 'authenticatedefault', function() use ($app) 
{           
             $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);
            
              $user_id  = $data['user_id'];
              $booked_by_id  = $data['booked_by_id'];
              $response_id  = $data['response_id'];
              $booked_for_id  = $data['booked_for_id'];
              $booked_for_value  = $data['booked_for_value'];
              $origin  = $data['origin'];
              $destination  = $data['destination'];
              $pick_up_point  = $data['pick_up_point'];
              $latitude  = $data['latitude'];
              $longitude  = $data['longitude'];
              $reason  = $data['reason'];
              $from_date  = $data['from_date'];
              $from_time  = $data['from_time'];
              $to_date  = $data['to_date'];
              $to_time  = $data['to_time'];
              $passengers_id  = $data['passengers_id'];
              $round_trip  = $data['round_trip'];
              $status_id  = $data['status_id'];
              $submitted_on  = getCurrentDateTime();
            
           
              accessToken($user_id); 


            $response = array();
            $db = new DbHandler();
            $result=$db->bookVehicle($user_id,$booked_by_id,$response_id,$booked_for_id,$booked_for_value,$origin,$destination,$pick_up_point,$latitude,$longitude,$reason,$from_date,$from_time,$to_date,$to_time,$passengers_id,$round_trip,$status_id,$submitted_on);

            
           if ($result['status']==1) 
           {    

                $response["status"] =1;
                $response['message'] = "successful";
                /*$response["ticketid"]=$result['ticketid'];

                $data = $db->ticketIdDetails($result['ticketid']);

                $es = new EmailService();
                $result = $es->sendEmailWhenSubmitted($data['ticket_Details']);
                $ss = new SmsService();
                $result = $ss->sendSmsWhenSubmitted($data['ticket_Details']);*/

            }else  if ($result['status']==2) 
           {    

                $response["status"] =2;
                $response['message'] = "successful";
                //$response["ticketid"]=$result['ticketid'];

            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                //$response["ticketid"]=array();
            }

            echoRespnse(200, $response);
 });

// for actionlog/logadd we have to implement it here.
$app->post('/actionlog/vehicleLogAdd', 'authenticatedefault', function() use ($app) 
{           
            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);
            
              $user_id  = $data['user_id'];
              $bookVehicle_id = $data['bookVehicle_id'];//1-Android ,2-IOS
              $notes = $data['notes'];
              $status_id = $data['status_id'];
              $performed_by_id = $data['performed_by_id'];
              $performed_by_name = $data['performed_by_name'];
              $created_by_user_id = $data['created_by_user_id'];
            $submitted_on       = getCurrentDateTime();
            //$user_id = $app->request()->post('user_id');
              accessToken($user_id); 

            $response = array();
            $db = new DbHandler();
            $result=$db->bkVehlogAdd($user_id,$bookVehicle_id,$notes,$status_id,$performed_by_id,$performed_by_name,
                                      $created_by_user_id,$submitted_on);
           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successful";
                 // $response["log"]=$result['log'];

                /* $data = $db->ticketIdDetails($ticket_id);

                 $data['ticket_Details']['Act_status'] = $status_id;
                 $data['ticket_Details']['Act_accepted_by'] = $accepted_by;
                 $data['ticket_Details']['Act_forward_from'] = $forward_from;
                 $data['ticket_Details']['Act_forward_to'] = $forward_to;
*/
                 echoRespnse(200, $response);

                 // print_r($data['ticket_Details']);
                 // exit();
                /* $status_ids=array(1,11);
                if (!in_array($status_id, $status_ids)){
                  $es = new EmailService();
                  $result = $es->sendEmailWhenAcknowledged($data['ticket_Details']);

                  $ss = new SmsService();
                  $result = $ss->sendSmsWhenAcknowledged($data['ticket_Details']);
                }*/

            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["log"]=array();

                echoRespnse(200, $response);
            }

 });


// for bookVehicledetails we have to implement it here.
$app->post('/bookVehicleNotifDetails', 'authenticatedefault', function() use ($app) 
{           

            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);

            $userIdPass  = $data['user_id'];
            


             // reading post params
          /*  $userIdPass = $app->request()->post('user_id');
            $platform= $app->request()->post('platform');*///1-Android ,2-IOS
              accessToken($userIdPass); 
             // check for required params
            //verifyRequiredParams(array('user_id','platform'));l
            
            $response = array();
            $db = new DbHandler();
            $result=$db->bkVhclNotfctnDtls($userIdPass);
        
           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["bkVhclNotfctnDtls"]=$result['bkVhclNotfctnDtls'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["bkVhclNotfctnDtls"]=array();
            }
        
            echoRespnse(200, $response);
 });


$app->post('/bookVehicleDetails', 'authenticatedefault', function() use ($app) 
{           

            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);

            $userIdPass  = $data['user_id'];
            


             // reading post params
          /*  $userIdPass = $app->request()->post('user_id');
            $platform= $app->request()->post('platform');*///1-Android ,2-IOS
              accessToken($userIdPass); 
             // check for required params
            //verifyRequiredParams(array('user_id','platform'));
            
            $response = array();
            $db = new DbHandler();
            $result=$db->bookVehicleDetails($userIdPass);
        
           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["bookVehicleDetails"]=$result['bookVehicleDetails'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["bookVehicleDetails"]=array();
            }
        
            echoRespnse(200, $response);
 });


$app->post('/approveOrReject', 'authenticatedefault', function() use ($app) 
{           

            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);

            $userIdPass  = $data['user_id'];
            
            $status_id  = $data['status_id'];

            $bookVehicle_id  = $data['bookVehicle_id'];

            $comment  = $data['comment'];

            $submitted_on = getCurrentDateTime();

             // reading post params
          /*  $userIdPass = $app->request()->post('user_id');
            $platform= $app->request()->post('platform');*///1-Android ,2-IOS
              accessToken($userIdPass); 
             // check for required params
            //verifyRequiredParams(array('user_id','platform'));
            
            $response = array();
            $db = new DbHandler();
            $result=$db->approveOrReject($userIdPass,$bookVehicle_id,$status_id,$comment,$submitted_on);
        
           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "Successful";
                 $response["approveOrReject"]=$result['approveOrReject'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["approveOrReject"]=array();
            }
        
            echoRespnse(200, $response);
 });



$app->post('/bkVhclDetlsById', 'authenticatedefault', function() use ($app) 
{           

            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);

            $userIdPass  = $data['user_id'];
            
             $bookVehicle_id  = $data['bookVehicle_id'];

             // reading post params
          /*  $userIdPass = $app->request()->post('user_id');
            $platform= $app->request()->post('platform');*///1-Android ,2-IOS
              accessToken($userIdPass); 
             // check for required params
            //verifyRequiredParams(array('user_id','platform'));
            
            $response = array();
            $db = new DbHandler();
            $result=$db->bkVhclDetlsById($userIdPass,$bookVehicle_id);
        
           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["bkVhclDetlsById"]=$result['bkVhclDetlsById'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["bkVhclDetlsById"]=array();
            }
        
            echoRespnse(200, $response);
 });

$app->post('/vehicleList', 'authenticatedefault', function() use ($app) 
{           

            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);

            $userIdPass  = $data['user_id'];
            
            $bookVehicle_id = $data['bookVehicle_id'];

             // reading post params
          /*  $userIdPass = $app->request()->post('user_id');
            $platform= $app->request()->post('platform');*///1-Android ,2-IOS
              accessToken($userIdPass); 
             // check for required params
            //verifyRequiredParams(array('user_id','platform'));
            
            $response = array();
            $db = new DbHandler();
            $result=$db->vehicleList($userIdPass,$bookVehicle_id);
        
           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["vehicleList"]=$result['vehicleList'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["vehicleList"]=array();
            }
        
            echoRespnse(200, $response);
 });


$app->post('/assignOrReject', 'authenticatedefault', function() use ($app) 
{           

            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);

            $userIdPass  = $data['user_id'];
            
            $status_id  = $data['status_id'];

            $bookVehicle_id  = $data['bookVehicle_id'];

            $vehicle_id  = $data['vehicle_id'];

            $driver_id = $data['driver_id'];

            $status_id = $data['status_id'];

            $comment  = $data['comment'];

            $submitted_on = getCurrentDateTime();

              accessToken($userIdPass); 
          
            
            $response = array();
            $db = new DbHandler();
            $result=$db->assignOrReject($userIdPass,$bookVehicle_id,$vehicle_id,$driver_id,$status_id,$comment,$submitted_on);
        
           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "Successful";
                 $response["assignOrReject"]=$result['assignOrReject'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["assignOrReject"]=array();
            }
        
            echoRespnse(200, $response);
 });


$app->post('/vehicleTrack', 'authenticatedefault', function() use ($app) 
{           
           $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);
            
              $user_id  = $data['user_id'];
              $status_id  = $data['status_id'];
              $book_vehicle_id  = $data['book_vehicle_id'];
              $vehicle_id  = $data['vehicle_id'];
              $s_latitude  = $data['s_latitude'];
              $s_longitude  = $data['s_longitude'];
              $d_latitude  = $data['d_latitude'];
              $d_longitude  = $data['d_longitude'];
              $c_latitude = $data['c_latitude'];
              $c_longitude = $data['c_latitude'];
              
         
              accessToken($user_id); 

            $response = array();
            $db = new DbHandler();
            $result=$db->vehicleTrack($user_id,$status_id,$book_vehicle_id,$vehicle_id,$s_latitude,$s_longitude,$d_latitude,$d_longitude,$c_latitude,$d_longitude);

            
           if ($result['status']==1) 
         
           {    

                $response["status"] =1;
                $response['message'] = "successful";
                $response["trip_id"]=$result['trip_id'];

            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["trip_id"]=array();
            }

            echoRespnse(200, $response);
           
 });


$app->post('/vehicleTrackLog', 'authenticatedefault', function() use ($app) 
{           


           $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);
            
              $user_id  = $data['user_id'];
              $trip_id = $data['trip_id'];//1-Android ,2-IOS
              $status_id = $data['status_id'];
              $latitude = $data['latitude'];
              $longitude = $data['longitude'];
              $book_vehicle_id = $data['book_vehicle_id'];
             
         
            //$user_id = $app->request()->post('user_id');
              accessToken($user_id); 

            
            $response = array();
            $db = new DbHandler();
            $result=$db->vehicleTrackLog($user_id,$trip_id,$status_id,$latitude,$longitude,$book_vehicle_id);

          if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successful";
                
                 echoRespnse(200, $response);

            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["log"]=array();

                echoRespnse(200, $response);
            }
           
 });

$app->post('/vehicleLatLongUpdate', 'authenticatedefault', function() use ($app) 
{           


           $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);
            
              $user_id  = $data['user_id'];
              $trip_id = $data['trip_id'];//1-Android ,2-IOS
              $status_id = $data['status_id'];
              $latitude = $data['latitude'];
              $longitude = $data['longitude'];
              $book_vehicle_id = $data['book_vehicle_id'];
             
         
            //$user_id = $app->request()->post('user_id');
              accessToken($user_id); 

            
            $response = array();
            $db = new DbHandler();
            $result=$db->vehicleLatLongUpdate($user_id,$trip_id,$status_id,$latitude,$longitude,$book_vehicle_id);

           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successful";
                
                 echoRespnse(200, $response);

            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["log"]=array();

                echoRespnse(200, $response);
            }
           
 });


// for payment/add we have to implement it here.
$app->post('/payment/add', 'authenticatedefault', function() use ($app) 
{           
             $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);
            
              $user_id  = $data['user_id'];
              $vendor_id  = $data['vendor_id'];
              $customer_id  = $data['customer_id'];
              $invoice_id  = $data['invoice_id'];
              
              $statusId  = $data['statusId'];
              $submitted_by  = $data['submitted_by'];
              
            
            $submitted_on = getCurrentDateTime();

              //accessToken($user_id); 

            $response = array();
            $db = new DbHandler();
            $result=$db->paymentAdd($user_id,$vendor_id,$customer_id,$invoice_id,$statusId,$submitted_by,$submitted_on);

            
           if ($result['status']==1) 
           {    

                $response["status"] =1;
                $response['message'] = "successful";
                $response["paymentid"]=$result['paymentid'];

         
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["paymentid"]=array();
            }

            echoRespnse(200, $response);
 });

// for actionlog/logadd we have to implement it here.
$app->post('/actionlog/paylogadd', 'authenticatedefault', function() use ($app) 
{           
            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);
            
              $user_id  = $data['user_id'];
              $pay_id = $data['pay_id'];//1-Android ,2-IOS
              $submitted_by = $data['submitted_by'];
              $submitted_on = $data['submitted_on'];
              $status_id = $data['status_id'];//1-Android ,2-IOS
              
            //$submitted_on       = getCurrentDateTime();
            //$user_id = $app->request()->post('user_id');
              accessToken($user_id); 


            $response = array();
            $db = new DbHandler();
            $result=$db->payLogAdd($user_id,$pay_id,$submitted_by,$submitted_on,$status_id);
           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successful";
                 

                 echoRespnse(200, $response);


            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["log"]=array();

                echoRespnse(200, $response);
            }

 });

$app->post('/paymentDetails', 'authenticatedefault', function() use ($app) 
{           

            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);

             
              $user_id  = $data['user_id'];
              
            $db = new DbHandler();
            $result=$db->paymentDetails($user_id);

           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["paymentDetails"]=$result['paymentDetails'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["paymentDetails"]=array();
            }
        
            echoRespnse(200, $response);
 });

$app->post('/paymentDetailsById', 'authenticatedefault', function() use ($app) 
{           

            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);

             
              $user_id  = $data['user_id'];
              $pay_id  = $data['pay_id'];
              
            $db = new DbHandler();
            $result=$db->paymentDetailsById($user_id,$pay_id);

           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["paymentDetailsById"]=$result['paymentDetailsById'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["paymentDetailsById"]=array();
            }
        
            echoRespnse(200, $response);
 });


$app->post('/permsnAdd', 'authenticatedefault', function() use ($app) 
{           
             $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);
            
              $user_id  = $data['user_id'];
              $date  = $data['date'];
              $from_time  = $data['from_time'];
              $to_time  = $data['to_time'];
              
              $reason  = $data['reason'];
              $statusId  = $data['statusId'];
              $submitted_by = $data['submitted_by'];

            
            $submitted_on = getCurrentDateTime();
            $comment = $data['comment'];

              accessToken($user_id); 

            $response = array();
            $db = new DbHandler();
            $result=$db->permsnAdd($user_id,$date,$from_time,$to_time,$reason,$statusId,$submitted_by,$submitted_on,$comment);

            
           if ($result['status']==1) 
           {    

                $response["status"] =1;
                $response['message'] = "successful";
                //$response["permissionAdd"]=$result['permissionAdd'];

         
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                //$response["permissionAdd"]=array();
            }

            echoRespnse(200, $response);
 });


$app->post('/permsnLogAdd', 'authenticatedefault', function() use ($app) 
{           
            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);
            
              $user_id  = $data['user_id'];
              $permission_id = $data['permission_id'];
              $date = $data['date'];//1-Android ,2-IOS
              $from_time = $data['from_time'];
              $to_time = $data['to_time'];
              $statusId = $data['statusId'];
              $submitted_by = $data['submitted_by'];
              
              
            $submitted_on       = getCurrentDateTime();
            //$user_id = $app->request()->post('user_id');
              accessToken($user_id); 

             // check for required params
            //verifyRequiredParams(array('user_id','platform','ticket_id','accepted_by','forward_from','forward_to','created_by_user_id','status_id','priority_id','severity_id','comment','machine_status','submitted_by_name','submitted_by_emp_number','root_cause_id','response_id'));

            $response = array();
            $db = new DbHandler();
            $result=$db->permsnLogAdd($user_id,$permission_id,$statusId,$submitted_by,$submitted_on,$date,$comment);
           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["log"]=$result['log'];

                

            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["log"]=array();

                echoRespnse(200, $response);
            }

 });



$app->post('/permissionReason', 'authenticatedefault', function() use ($app) 
{           

        $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);

             
              $user_id  = $data['user_id'];

      /*$platform= $app->request()->post('platform');//1-Android ,2-IOS
        $user_id = $app->request()->post('user_id');*/
              accessToken($user_id); 
             // check for required params
            //verifyRequiredParams(array('user_id','platform'));
          
            $response = array();
            $db = new DbHandler();
            $result=$db->permissionReason();
        
           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["permissionReason"]=$result['permissionReason'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["permissionReason"]=array();
            }
        
            echoRespnse(200, $response);
 });


$app->post('/permissionStatus', 'authenticatedefault', function() use ($app) 
{           

        $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);

             
              $user_id  = $data['user_id'];

      /*$platform= $app->request()->post('platform');//1-Android ,2-IOS
        $user_id = $app->request()->post('user_id');*/
              accessToken($user_id); 
             // check for required params
            //verifyRequiredParams(array('user_id','platform'));
          
            $response = array();
            $db = new DbHandler();
            $result=$db->permissionStatus();
        
           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["permissionStatus"]=$result['permissionStatus'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["permissionStatus"]=array();
            }
        
            echoRespnse(200, $response);
 });


$app->post('/inductionQusns', 'authenticatedefault', function() use ($app) 
{           

        $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);

             
              $user_id  = $data['user_id'];

      /*$platform= $app->request()->post('platform');//1-Android ,2-IOS
        $user_id = $app->request()->post('user_id');*/
              accessToken($user_id); 
             // check for required params
            //verifyRequiredParams(array('user_id','platform'));
          
            $response = array();
            $db = new DbHandler();
            $result=$db->inductionQusns();
        
           if ($result['status']==1) 
           {
            //echo "if";
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["inductionQusns"]=$result['inductionQusns'];
            }
            else
            {
              //echo "else";
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["inductionQusns"]=array();
            }
        
            echoRespnse(200, $response);
 });


$app->post('/Questions', 'authenticatedefault', function() use ($app) 
{           

        $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);

             
              $user_id  = $data['user_id'];

              accessToken($user_id); 
           
          
            $response = array();
            $db = new DbHandler();
            $result=$db->Questions();
        
           if ($result['status']==1) 
           {
            //echo "if";
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["Questions"]=$result['Questions'];
            }
            else
            {
              //echo "else";
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["Questions"]=array();
            }
        
            echoRespnse(200, $response);
 });


// for unitmeasure2 we have to implement it here.
$app->post('/inductionQuestions', 'authenticatedefault', function() use ($app) 
{                 
             $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);
            
              $user_id  = $data['user_id'];

            /*$platform= $app->request()->post('platform');//1-Android ,2-IOS
            $user_id = $app->request()->post('user_id');*/
              accessToken($user_id); 
             // check for required params
            //verifyRequiredParams(array('user_id','platform'));   
            $db = new DbHandler();
            $result=$db->inductionQuestions();
               
            // $user_details=$db->userDetails($user_id);
           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["inductionQuestions"]=$result['inductionQuestions'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["inductionQuestions"]=array();
            }
        
            echoRespnse(200, $response);
 });



$app->post('/inductionQuestionById', 'authenticatedefault', function() use ($app) 
{           

            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);

             
              $user_id  = $data['user_id'];
              $indctnQsId  = $data['indctnQsId'];
              $is_checkedId  = $data['is_checkedId'];
              
            $db = new DbHandler();
            $result=$db->inductionQuestionById($user_id,$indctnQsId,$is_checkedId);

           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["inductionQuestionById"]=$result['inductionQuestionById'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["inductionQuestionById"]=array();
            }
        
            echoRespnse(200, $response);
 });


$app->post('/indcutnIsCondition', 'authenticatedefault', function() use ($app) 
{
             $json = $app->request->getBody();
            $data = json_decode($json, true);
            // reading post params
            $user_id = $data['user_id'];
            $indctnQsId  = $data['indctnQsId'];
            // check for required params
            // verifyRequiredParams(array('user_id','platform'));
            
            $response = array();
            $db = new DbHandler();
            $result=$db->indcutnIsCondition($user_id,$indctnQsId);
           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successfull";
                 //$response["session_token"]=$result['session_token'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'not successfull';
                $response["session_token"]=array();
            }
           
            echoRespnse(200, $response);
 });


$app->post('/emplyResig', 'authenticatedefault', function() use ($app) 
{           
             $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);
            
              $user_id  = $data['user_id'];
              $notice_period  = $data['notice_period'];
              $start_date  = $data['start_date'];
              $end_date  = $data['end_date'];
              $submitted_by = $data['submitted_by'];

            
            $submitted_on = getCurrentDateTime();
            $flag = $data['flag'];

              //accessToken($user_id); 

            $response = array();
            $db = new DbHandler();
            $result=$db->emplyResig($user_id,$notice_period,$start_date,$end_date,$submitted_by,$submitted_on,$flag);

            
           if ($result['status']==1) 
           {    

                $response["status"] =1;
                $response['message'] = "successful";
                //$response["permissionAdd"]=$result['permissionAdd'];

         
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                //$response["permissionAdd"]=array();
            }

            echoRespnse(200, $response);
 });

$app->post('/exitIntQuestions', 'authenticatedefault', function() use ($app) 
{           

        $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);

             
              $user_id  = $data['user_id'];

              accessToken($user_id); 
           
          
            $response = array();
            $db = new DbHandler();
            $result=$db->exitIntQuestions();
        
           if ($result['status']==1) 
           {
            //echo "if";
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["exitIntQuestions"]=$result['exitIntQuestions'];
            }
            else
            {
              //echo "else";
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["exitIntQuestions"]=array();
            }
        
            echoRespnse(200, $response);
 });

$app->post('/exitIntQuestionsByEmpId', 'authenticatedefault', function() use ($app) 
{           

        $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);

             
              $user_id  = $data['user_id'];
              $emp_number  = $data['emp_number'];

              accessToken($user_id); 
           
          
            $response = array();
            $db = new DbHandler();
            $result=$db->exitIntQuestionsByEmpId($emp_number);
        
           if ($result['status']==1) 
           {
            //echo "if";
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["exitIntQuestionsByEmpId"]=$result['exitIntQuestionsByEmpId'];
            }
            else
            {
              //echo "else";
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["exitIntQuestionsByEmpId"]=array();
            }
        
            echoRespnse(200, $response);
 });


$app->post('/question/assign', 'authenticatedefault', function() use ($app) 
{

            $body = $app->request->getBody();
            $quesJsonAddObj = json_decode($body, true);
            //print_r($starJsonAddObj);
            //print_r($starJsonAddObj["startrating"][0]['user_id']);

             $user_id  = $quesJsonAddObj["quesAssign"][0]['user_id'];
              accessToken($user_id); 
            $response = array();
            $db = new DbHandler();
            $result=$db->quesAssign($quesJsonAddObj);

           
             if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["quesAssign"]=$result['quesAssign'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["quesAssign"]=array();
            }

            echoRespnse(200, $response);
           
 });



$app->post('/taskList', 'authenticatedefault', function() use ($app) 
{           

        $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);

             
              $user_id  = $data['user_id'];

              //accessToken($user_id); 
           
          
            $response = array();
            $db = new DbHandler();
            $result=$db->taskList();
        
           if ($result['status']==1) 
           {
            //echo "if";
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["taskList"]=$result['taskList'];
            }
            else
            {
              //echo "else";
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["taskList"]=array();
            }
        
            echoRespnse(200, $response);
 });


$app->post('/task/add', 'authenticatedefault', function() use ($app) 
{           
             $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);
            
              $user_id  = $data['user_id'];
              $project_id  = $data['project_id'];
              $activity_id  = $data['activity_id'];
              $task  = $data['task'];
              
              $created_by  = $data['created_by'];
              $assigned_to  = $data['assigned_to'];
              $start_date  = $data['start_date'];
              $duration  = $data['duration'];

              $end_date  = $data['end_date'];

              $status  = $data['status'];

            $created_on = getCurrentDateTime();

              //accessToken($user_id); 

            $response = array();
            $db = new DbHandler();
            $result=$db->taskAdd($user_id,$project_id,$activity_id,$task,$created_by,$created_on,$assigned_to,$start_date,$duration,$end_date,$status);

            
           if ($result['status']==1) 
           {    

                $response["status"] =1;
                $response['message'] = "successful";
                $response["taskid"]=$result['taskId'];

         
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["taskid"]=array();
            }

            echoRespnse(200, $response);
 });


$app->post('/taskLogAdd', 'authenticatedefault', function() use ($app) 
{           
            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);
            
              $user_id  = $data['user_id'];
              $task_id = $data['task_id'];//1-Android ,2-IOS
              $submitted_by = $data['created_by'];
              $submitted_on = $data['created_on'];
              $status = $data['status'];//1-Android ,2-IOS
              
              
            //$submitted_on       = getCurrentDateTime();
            //$user_id = $app->request()->post('user_id');
              accessToken($user_id); 


            $response = array();
            $db = new DbHandler();
            $result=$db->taskLogAdd($user_id,$task_id,$status,$submitted_on,$submitted_by);
           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successful";
                 

                 echoRespnse(200, $response);


            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["log"]=array();

                echoRespnse(200, $response);
            }

 });

$app->post('/taskByEmpNum', 'authenticatedefault', function() use ($app) 
{           

        $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);

             
              $user_id  = $data['user_id'];
              $emp_number  = $data['emp_number'];

              //accessToken($user_id); 
           
          
            $response = array();
            $db = new DbHandler();
            $result=$db->taskByEmpNum($emp_number);
        
           if ($result['status']==1) 
           {
            //echo "if";
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["taskByEmpNum"]=$result['taskByEmpNum'];
            }
            else
            {
              //echo "else";
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["taskByEmpNum"]=array();
            }
        
            echoRespnse(200, $response);
 });


$app->post('/projectList', 'authenticatedefault', function() use ($app) 
{           

        $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);

             
              $user_id  = $data['user_id'];
             
          
            $response = array();
            $db = new DbHandler();
            $result=$db->projectList();
        
           if ($result['status']==1) 
           {
            //echo "if";
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["projectList"]=$result['projectList'];
            }
            else
            {
              //echo "else";
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["projectList"]=array();
            }
        
            echoRespnse(200, $response);
 });


$app->post('/leaveDetails', 'authenticatedefault', function() use ($app) 
{    echo "string";       
         $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);
            
              //$user_id  = $data['user_id'];
              $emp_number = $data['emp_number'];
        /*$platform= $app->request()->post('platform');//1-Android ,2-IOS
        $user_id = $app->request()->post('user_id');*/
              //accessToken($user_id); 
        //verifyRequiredParams(array('user_id','platform'));
            
        $db = new DbHandler();
        $result=$db->leaveDetails($emp_number);
       
        if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["leaveDetails"]=$result['leaveDetails'];

            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["leaveDetails"]=array();
            }
        
            echoRespnse(200, $response);
 });


///////////////////////////////////////////////////
/**
 * Verifying required params posted or not
 */
 
function verifyRequiredParams($required_fields) {
    $error = false;
    $error_fields = "";
    $request_params = array();
    $request_params = $_REQUEST;
    // Handling PUT request params
    if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
        $app = \Slim\Slim::getInstance();
        parse_str($app->request()->getBody(), $request_params);
    }
    foreach ($required_fields as $field) {
        if (!isset($request_params[$field]) || strlen(trim($request_params[$field])) <= 0) {
            $error = true;
            $error_fields .= $field . ', ';
        }
    }
//print_r($error);
//exit;
    if ($error) {
        // Required field(s) are missing or empty
        // echo error json and stop the app
        $response = array();
        $app = \Slim\Slim::getInstance();
        //$response["error"] = true;
        $response["status"] =0;
        $response["message"] = 'Required field(s) ' . substr($error_fields, 0, -2) . ' is missing or empty';
        echoRespnse(200, $response);
        $app->stop();
    }
}

/**
 * Validating email address
 */
function validateEmail($email) {
    $app = \Slim\Slim::getInstance();
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response["error"] = true;
        $response["message"] = 'Email address is not valid';
        echoRespnse(200, $response);
        $app->stop();
    }
}

/**
 * Echoing json response to client
 * @param String $status_code Http response code
 * @param Int $response Json response
 */
function echoRespnse($status_code, $response) {
    $app = \Slim\Slim::getInstance();
    // Http response code
    $app->status($status_code);

    // setting response content type to json
    $app->contentType('application/json');

    echo json_encode($response);
}
$app->run();
?>
