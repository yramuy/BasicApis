<?php
///////////////// RAMU START ///////////////////////

$app->post('/leaveType', 'authenticatedefault', function() use ($app) 
{         
     

            $json = $app->request->getBody();
            $data = json_decode($json, true);
          //  $result = implode(',',$data);
           
            // $platform   = $data['platform'];
            $user_id  = $data['user_id'];

           
            $response = array();
      $db = new DbHandler();
      
      $result=$db->getLeaveTypes();
     //$user_details=$db->userDetails($user_id);
           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "success";
                 $response["leaveType"]=$result['leaveType'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'unsuccessfull';
                $response["leaveType"]=array();
            }
      
        echoRespnse(200, $response);
});

$app->post('/applyLeave', 'authenticatedefault', function() use ($app) 
{           
            $json = $app->request->getBody();
            $data = json_decode($json, true);
            $result = implode(',',$data);
            
              $user_id  = $data['user_id'];
              $leave_type_id  = $data['leave_type_id'];
              $from_date  = $data['from_date'];
              $to_date  = $data['to_date'];              
              $duration  = $data['duration'];              
              $emp_number   = $data['emp_number'];
              $comments   = $data['comments'];
              $start_time   = $data['start_time'];
              $end_time   = $data['end_time'];
              $status_id   = $data['status_id'];

              //accessToken($user_id); 

            $response = array();
            $db = new DbHandler();
            $result = $db->applyLeave($user_id,$leave_type_id,$emp_number,$comments,$from_date,$to_date,$duration,$start_time,$end_time,$status_id);

            
           if ($result['status']==1) 
           {    

                $response["status"] = 1;
                $response['message'] = "successfully submitted";
         
            }
            else
            {
                $response['status'] = 0;
                $response['message'] = 'No Records Found';
                // $response["taskid"]=array();
            }

            echoRespnse(200, $response);
 });

$app->post('/leaveApprovals', 'authenticatedefault', function() use ($app) 
{           

            $json = $app->request->getBody();
            $data = json_decode($json, true);
            // $result = implode(',',$data);

            $user_id  = $data['user_id'];            
            $status_id  = $data['status_id'];
            $leave_id  = $data['leave_id'];
            
            $response = array();
            $db = new DbHandler();
            $result=$db->leaveApprovals($user_id,$status_id,$leave_id);
        
           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = $result['log'];
                 // $response["leaveApprovals"] = $result['leaveApprovals'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                // $response["leaveApprovals"]=array();
            }
        
            echoRespnse(200, $response);
 });

    

///////////////// RAMU END  ///////////////////////