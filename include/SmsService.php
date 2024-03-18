<?php

/**
 * Handling sms
 *
 * @author manikanta sarma
 * @link URL Tutorial link
 */
class SmsService 
{
    function __construct() {   
      require_once dirname(__FILE__) . '/DbHandler.php';
        $this->dh = new DbHandler();  
        $result = $this->dh->smsConfig();
        $this->smsConfig = $result['url'].'authKey='.$result['user_name'].'&senderId='.$result['sender_id'];   
    }

    /**
     * Establishing Sms Service
     * @return database connection handler
     */
    function sendSms($url,$to)
    {

      $this->smsConfig .= str_replace(' ', '%20', $url);
      
      if($to){
          $ch = curl_init();

          curl_setopt($ch, CURLOPT_URL, $this->smsConfig);
          curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
          $json = curl_exec($ch); 
          $result = (json_decode($json));
          curl_close ($ch);

          if($result->code == 200){
              return "Success";
          }else{
              return "Failed";
          }
      }
    }

    function otpSms($mobile,$name,$rndno){
    	
    	$ch = curl_init();
      $templateId = 1470;
      $name = str_replace('&', 'amp;', $name);
      $submittedBy = "Plant Maintenance Admin";
      $url = '&tempId='.$templateId.'&Phone='.$mobile.'&F1='.$name.'&F2='.$rndno.'&F3='.$submittedBy.'&response=Y';
      $this->sendSms($url,$mobile);
    }

    function sendSmsWhenSubmitted($details){
      $templateId = 1467;
      $emplist = array();
      $enmlist=$this->dh->empList(10);
            for ($i=0; $i < sizeof($enmlist['emplist']); $i++) { 
              $emplist[] = $enmlist['emplist'][$i];
            }

      $emplist[] = $this->dh->getDepEngineer($details['id']);
      $emplist[] = $details['submittedByEmp'];

      $empSms = $this->dh->employeeEmails($emplist);
      
      
        for ($i=0; $i < sizeof($empSms['mobile']); $i++) { 
          // Set the "To address" [Use setTo method for multiple recipients, argument should be array]
          if($empSms['status'][$i] == 1){
            $url = '&tempId='.$templateId.'&Phone='.$empSms['mobile'][$i].'&F1='.$details['submittedby'].'&F2='.$details['job_id'].'&F3='.$details['subject'].'&F4='.$details['equipment_id'].'&F5='.$details['equipment_name'].'&F6='.$details['functionallocation_name'].'&F7='.$details['submittedby'].'&response=Y';
          $this->sendSms($url,$empSms['mobile'][$i]);
        }
      }
    }

    function sendSmsWhenAcknowledged($details){

      if($details['Act_forward_from'] && $details['Act_forward_to'] &&  $details['Act_status'] != 14){
        $templateId = 1472;
        $emplist = array();
        $emplist[] = $details['submittedByEmp'];
        $emplist[] = $details['Act_forward_from'];
        $emplist[] = $details['Act_forward_to'];

        $employeeDetails = $this->dh->employeeDetails($details['Act_forward_from']);
        $name1 = $employeeDetails['empdetails']['emp_name'];
        $employeeDetails = $this->dh->employeeDetails($details['Act_forward_to']);
        $name2 = $employeeDetails['empdetails']['emp_name'];

        $empSms = $this->dh->employeeEmails($emplist);
        
        
          for ($i=0; $i < sizeof($empSms['mobile']); $i++) { 
            // Set the "To address" [Use setTo method for multiple recipients, argument should be array]
            if($empSms['status'][$i] == 1){
              $url = '&tempId='.$templateId.'&Phone='.$empSms['mobile'][$i].'&F1='.$details['submittedby'].'&F2='.$details['job_id'].'&F3='.$details['subject'].'&F4='.$details['equipment_id'].'&F5='.$details['equipment_name'].'&F6='.$details['functionallocation_name'].'&F7=Forwarded&F8='.$name1.'&F9='.$name2.'&F10='.$details['submittedby'].'&response=Y';
            $this->sendSms($url,$empSms['mobile'][$i]);
          }
        }
      }

      if($details['Act_accepted_by'] || $details['Act_status'] == 14){
        $templateId = 1471;
        $emplist = array();
        $emplist[] = $details['submittedByEmp'];

        $empSms = $this->dh->employeeEmails($emplist);

        $status = $this->dh->getTicketStatusById($details['Act_status']);
        $details['statusName'] = $status['name'];
        
        
          for ($i=0; $i < sizeof($empSms['mobile']); $i++) { 
            // Set the "To address" [Use setTo method for multiple recipients, argument should be array]
            if($empSms['status'][$i] == 1){
              $url = '&tempId='.$templateId.'&Phone='.$empSms['mobile'][$i].'&F1='.$details['submittedby'].'&F2='.$details['job_id'].'&F3='.$details['subject'].'&F4='.$details['equipment_id'].'&F5='.$details['equipment_name'].'&F6='.$details['functionallocation_name'].'&F7='.$details['statusName'].'&F8='.$details['submittedByEmp'].'&F9='.$details['submittedByEmp'].'&response=Y';
            $this->sendSms($url,$empSms['mobile'][$i]);
          }
        }
      }

    }
}
    ?>