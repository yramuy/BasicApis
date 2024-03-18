<?php
//Index.php

//Chandra sekhar //
$app->post('/leaveCount', 'authenticatedefault', function() use ($app) 
{      

        $json = $app->request->getBody();
            $data = json_decode($json, true);
            //$result = implode(',',$data);
             
              $user_id  = $data['user_id'];
              $emp_number  = $data['emp_number'];
              $leaveType  = $data['leaveType'];

              // accessToken($user_id);           
          
            $response = array();
            $db = new DbHandler();
            $result=$db->leaveCount($emp_number,$leaveType);
        
           if ($result['status']==1) 
           {
                 $response["status"] =1;
                 $response['message'] = "successful";
                 $response["leaveCount"]=$result['leaveCount'];
            }
            else
            {
                $response['status'] =0;
                $response['message'] = 'No Records Found';
                $response["leaveCount"]=array();
            }
        
            echoRespnse(200, $response);
});


// Chandra sekhar

///////////////// RAMU START ///////////////////////

function leaveCount($emp_number,$leaveType)
  {

    $data= array();
    $query="SELECT * FROM erp_leave_entitlement WHERE emp_number = 4 AND leave_type_id  = 1";
    $count=mysqli_query($this->conn, $query);   
    if(mysqli_num_rows($count) > 0)
    {
          $row=mysqli_fetch_assoc($count);
        
          do{ 

            $data['id']=$row['id'];
            $data['emp_number']=$row['emp_number'];
            $data['no_of_days']=$row['no_of_days'];
            $data['days_used']=$row['days_used'];
            $data['leave_type_id']=$row['leave_type_id'];
            $data['from_date']=$row['from_date'];
            $data['to_date']=$row['to_date'];
            $data['credited_date']=$row['credited_date'];
            $data['note']=$row['note'];
            $data['entitlement_type']=$row['entitlement_type'];
            $data['deleted']=$row['deleted'];
            $data['created_by_id']=$row['created_by_id'];
            $data['created_by_name']=$row['created_by_name'];
              
            $data1[] = $data;
          }while($row = mysqli_fetch_assoc($count));        
            $data['leaveCount']=$data1;
            $data['status']=1;
              
    }else{
        $data['status']=0;
      }
    return $data;    
  }

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

// DB Handeler

///////////// Ramu Start /////////////////////////////////////

function getLeaveTypes()
{
    $data=array();
      
  $query="SELECT * FROM erp_leave_type WHERE deleted = 0 ";
  $count=mysqli_query($this->conn, $query);

  if(mysqli_num_rows($count) > 0)
  {
      $row=mysqli_fetch_assoc($count);
      
        do {    
          
          $data['id'] = $row['id'];
          $data['name'] = $row['name'];

          $data1[] = $data;
        }while($row = mysqli_fetch_assoc($count));
          $data['leaveType']=$data1;
        $data['status'] = 1;
        
  }else{
    $data['status'] = 0;
  }
  return $data;
}

function applyLeave($user_id,$leave_type_id,$emp_number,$comments,$from_date,$to_date,$duration,$start_time,$end_time,$status_id)
{
  $created_by_name = $this->getEmpnameByEmpNumber($emp_number);
  $user = $this->getUserRoleByEmpNumber($emp_number);

  $created_by_id = $user['userId'];
  
  $date_applied  = date('Y-m-d');

  if($duration == 0){
    $length_hours = 8;
    $length_days = 1;
  }
  if($duration == 1){
    $length_hours = 4;
    $length_days = 0.5;
  }
  if($duration == 2){
    $start_time = $start_time;
    $end_time = $end_time;
  }else{
    $start_time = '00:00:00';
    $end_time = '00:00:00';
  }
  $status = $status_id;
  $createddate = date('Y-m-d H:i:s'); 

  $data=array();

  $sql = "INSERT INTO erp_leave_request (leave_type_id,date_applied,emp_number,comments) VALUES (?,?,?,?)";
    
    if($stmt = mysqli_prepare($this->conn, $sql)){
        mysqli_stmt_bind_param($stmt, "isis" ,$leave_type_id,$date_applied,$emp_number,$comments);
                 
      if(mysqli_stmt_execute($stmt)){

            $leave_request_id = $this->conn->insert_id;

            $query="INSERT INTO erp_leave (date,length_hours,length_days,status,comments,leave_request_id,leave_type_id,emp_number,
            start_time,end_time,duration_type) VALUES (?,?,?,?,?,?,?,?,?,?,?)";
          
            if($stmt = mysqli_prepare($this->conn, $query)){
              mysqli_stmt_bind_param($stmt, "sssisiiissi" ,$date_applied,$length_hours,$length_days,$status,$comments,$leave_request_id,$leave_type_id,$emp_number,$start_time,$end_time,$duration);
                   
              if(mysqli_stmt_execute($stmt)){
                $data['status']=1;
              } 
          }else{
            $data['status']=0;
          }

          $query="INSERT INTO erp_leave_request_comment (leave_request_id,created,created_by_name,created_by_id,
          created_by_emp_number,comments) VALUES (?,?,?,?,?,?)";
          
            if($stmt = mysqli_prepare($this->conn, $query)){
              mysqli_stmt_bind_param($stmt, "issiis" ,$leave_request_id,$createddate,$created_by_name,$created_by_id,$emp_number,$comments);
                   
              if(mysqli_stmt_execute($stmt)){
                $data['status']=1;
              } 
          }else{
            $data['status']=0;
          }
      }else{
          $data['status']=0;
      }

    }else{
        $data['status']=0;
    }
    
    return $data;
}

function leaveApprovals($user_id,$status_id,$leave_id){

  $data=array();

  if($status_id == 2)
  {

    $updatesql = "UPDATE erp_leave SET status = $status_id WHERE id = $leave_id";
    if($result2 = mysqli_query($this->conn, $updatesql)){

      $data['log'] = "Successfully Approved";
      $data['status']=1;

    }
    else{
    //echo "ERROR: Could not execute query: $sql. " . mysqli_error($this->conn);
    $data['status']=0;
    }

  }
  if($status_id == 0)
  {

    $updatesql = "UPDATE erp_leave SET status = $status_id WHERE id = $leave_id";
    if($result2 = mysqli_query($this->conn, $updatesql)){

      $data['log'] = "Successfully Cancelled";
      $data['status']=1;

    }
    else{
    //echo "ERROR: Could not execute query: $sql. " . mysqli_error($this->conn);
    $data['status']=0;
    }

  }
  if($status_id == -1)
  {

    $updatesql = "UPDATE erp_leave SET status = $status_id WHERE id = $leave_id";
    if($result2 = mysqli_query($this->conn, $updatesql)){

      $data['log'] = "Successfully Rejected";
      $data['status']=1;

    }
    else{
    //echo "ERROR: Could not execute query: $sql. " . mysqli_error($this->conn);
    $data['status']=0;
    }

  }
  if($status_id == 3)
  {

    $updatesql = "UPDATE erp_leave SET status = $status_id WHERE id = $leave_id";
    if($result2 = mysqli_query($this->conn, $updatesql)){

      $data['log'] = "Successfully Taken";
      $data['status']=1;

    }
    else{
    //echo "ERROR: Could not execute query: $sql. " . mysqli_error($this->conn);
    $data['status']=0;
    }

  }

  return $data;
}

//////////// Ramu End    ////////////////////////////////////

