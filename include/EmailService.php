<?php

require_once '../libs/vendor/autoload.php';

/**
 * Handling email
 *
 * @author manikanta sarma
 * @link URL Tutorial link
 */
class EmailService 
{
    function __construct() {   
      require_once dirname(__FILE__) . '/DbHandler.php';
        $this->dh = new DbHandler();   

        $emaildata = $this->dh->emailConfig();
      
        $sent_as = $emaildata['sent_as'];
        $smtp_host = $emaildata['smtp_host'];
        $smtp_port = $emaildata['smtp_port'];
        $smtp_security_type = $emaildata['smtp_security_type'];
        $smtp_username = $emaildata['smtp_username'];
        $smtp_password = $emaildata['smtp_password'];

        $sent_as_name = 'Teejay';  
    }

    /**
     * Establishing Email Service
     * @return database connection handler
     */
    function sendEmail($details)
    {
      
      try 
      {
          // Create the SMTP Transport
          $transport = (new Swift_SmtpTransport($smtp_host, $smtp_port, $smtp_security_type))
              ->setUsername($smtp_username)
              ->setPassword($smtp_password);
       
          // Create the Mailer using your created Transport
          $mailer = new Swift_Mailer($transport);
       
          // Create a message
          $message = new Swift_Message();
       
       
          // Set the "From address"
          $message->setFrom([$sent_as => $sent_as_name]);

          $emplist = array();

          $enmlist=$this->dh->empList(10);
            for ($i=0; $i < sizeof($enmlist['emplist']); $i++) { 
              $emplist[] = $enmlist['emplist'][$i];
            }

          $emplist[] = $this->dh->getDepEngineer($details['id']);
          $emplist[] = $details['submittedByEmp'];

          $empMails = $this->dh->employeeEmails($emplist);
          
          
            for ($i=0; $i < sizeof($empMails['mail']); $i++) { 
              // Set the "To address" [Use setTo method for multiple recipients, argument should be array]
              if($empMails['status'][$i] == 1){
              $message->addTo($empMails['mail'][$i],$empMails['name'][$i]);
            }
          }
        
          $template = file_get_contents('emailTemplates/jobSubmitSubject.txt',true);
          $subject = $this->template($template,$details);
          // Set a "subject"
          $message->setSubject($subject);

          //Set a "body"
          $template = file_get_contents('emailTemplates/jobSubmitBody.txt',true);
          $body = $this->template($template,$details);
          $message->setBody($body, 'text/plain');
       
          // Send the message
          $result = $mailer->send($message);
        }catch (Exception $e) {
          $e->getMessage();
        }      
    }

    function template($template,$details){
      // $template = file_get_contents('emailTemplates/jobSubmitSubject.txt',true);
      $replacements = $this->getReplacements($details);
      $subject = $this->replaceContent($template,$replacements);

      return $subject;
    }

    // function templateBody($template,$details){
      
    //   // $template = file_get_contents('emailTemplates/jobSubmitBody.txt',true);
    //   $replacements = $this->getReplacements($details);
    //   $body = $this->replaceContent($template,$replacements);

    //   return $body;
    // }

    function getReplacements($details){
      $replacements = array();
      $replacements['performerFullName'] = $details['submittedby'];
      $replacements['recipientFirstName'] = $details['receipment'];
      $replacements['ticketNo'] = $details['job_id'];
      $replacements['subject'] = $details['subject'];
      $replacements['equipmentId'] = $details['equipment_id'];
      $replacements['equipment'] = $details['equipment_name'];
      $replacements['functionalLoction'] = $details['functionallocation_name'];

      if($details['Act_accepted_by']){
        $employeeDetails = $this->dh->employeeDetails($details['Act_accepted_by']); 
        $name = $employeeDetails['empdetails']['emp_name'];
        $replacements['acceptedFullName'] = $name;
        $replacements['statusName'] = $details['statusName'];
      }
      if($details['Act_status'] == 14){
        $replacements['acceptedFullName'] = $details['submittedby'];
        $replacements['statusName'] = $details['statusName'];
      }

      if($details['Act_forward_from']){
        $employeeDetails = $this->dh->employeeDetails($details['Act_forward_from']);
        $name = $employeeDetails['empdetails']['emp_name'];
        $replacements['forwardFromFullName'] = $name;
      }

      if($details['Act_forward_to']){
        $employeeDetails = $this->dh->employeeDetails($details['Act_forward_to']);
        $name = $employeeDetails['empdetails']['emp_name'];
        $replacements['forwardToFullName'] = $name;
      }

      return $replacements;
    }

    function replaceContent($template, $replacements, $wrapper = '%'){

      $keys = array_keys($replacements);
      foreach ($keys as $value) {
          $needls[] = $wrapper . $value . $wrapper;
      }

      return str_replace($needls, $replacements, $template);
    }

    function sendEmailWhenSubmitted($details){
      return $this->sendEmail($details);
    }

    public function sendEmailWhenAcknowledged($details){ 

        $emaildata = $this->dh->emailConfig();
      
        $sent_as = $emaildata['sent_as'];
        $smtp_host = $emaildata['smtp_host'];
        $smtp_port = $emaildata['smtp_port'];
        $smtp_security_type = $emaildata['smtp_security_type'];
        $smtp_username = $emaildata['smtp_username'];
        $smtp_password = $emaildata['smtp_password'];

        $sent_as_name = 'Teejay';       

      if($details['Act_forward_from'] && $details['Act_forward_to'] &&  $details['Act_status'] != 14){
       
          $emplist = array();
              $emplist[] = $details['submittedByEmp'];
              $emplist[] = $details['Act_forward_from'];
              $emplist[] = $details['Act_forward_to'];

          $empMails = $this->dh->employeeEmails($emplist);
          
            for ($i=0; $i < sizeof($empMails['mail']); $i++) { 
              if($empMails['status'][$i] == 1){
              $details['receipment'] = $empMails['name'][$i];

              // Create the SMTP Transport
              $transport = (new Swift_SmtpTransport($smtp_host, $smtp_port, $smtp_security_type))
              ->setUsername($smtp_username)
              ->setPassword($smtp_password);

              // Create the Mailer using your created Transport
              $mailer = new Swift_Mailer($transport);

              // Create a message
              $message = new Swift_Message();
           
              // Set the "From address"
              $message->setFrom([$sent_as => $sent_as_name]);

              // Set the "To address" [Use setTo method for multiple recipients, argument should be array]
              $message->addTo($empMails['mail'][$i],$empMails['name'][$i]);

              $template = file_get_contents('emailTemplates/jobForwardSubject.txt',true);
              $subject = $this->template($template,$details);
              // Set a "subject"
              $message->setSubject($subject);

              //Set a "body"
              $template = file_get_contents('emailTemplates/jobForwardBody.txt',true);
              $body = $this->template($template,$details);
              // Set a "body"
              $message->setBody($body, 'text/plain');
           
              // Send the message
              $result = $mailer->send($message);


            }
          }

      }
            
      if($details['Act_accepted_by'] || $details['Act_status'] == 14){

        $status = $this->dh->getTicketStatusById($details['Act_status']);
        $details['statusName'] = $status['name'];

        $emplist = array();
              $emplist[] = $details['submittedByEmp'];
              $emplist[] = $details['Act_forward_from'];
              $emplist[] = $details['Act_forward_to'];


          $empMails = $this->dh->employeeEmails($emplist);
          
            for ($i=0; $i < sizeof($empMails['mail']); $i++) { 
              if($empMails['status'][$i] == 1){
              $details['receipment'] = $empMails['name'][$i];

              // Create the SMTP Transport
              $transport = (new Swift_SmtpTransport($smtp_host, $smtp_port, $smtp_security_type))
              ->setUsername($smtp_username)
              ->setPassword($smtp_password);

              // Create the Mailer using your created Transport
              $mailer = new Swift_Mailer($transport);

              // Create a message
              $message = new Swift_Message();
           
              // Set the "From address"
              $message->setFrom([$sent_as => $sent_as_name]);

              // Set the "To address" [Use setTo method for multiple recipients, argument should be array]
              $message->addTo($empMails['mail'][$i],$empMails['name'][$i]);

              $template = file_get_contents('emailTemplates/jobAcceptSubject.txt',true);
              $subject = $this->template($template,$details);
              // Set a "subject"
              $message->setSubject($subject);

              //Set a "body"
              $template = file_get_contents('emailTemplates/JobAcceptedBody.txt',true);
              $body = $this->template($template,$details);
              // Set a "body"
              $message->setBody($body, 'text/plain');
           
              // Send the message
              $result = $mailer->send($message);


            }
          }
      }          
      // return $success;
  }


    //Password Reset
    function sendPassword($usrname,$email,$password)
    {
      $data = $this->dh->emailConfig();
      
      $sent_as = $data['sent_as'];
      $smtp_host = $data['smtp_host'];
      $smtp_port = $data['smtp_port'];
      $smtp_username = $data['smtp_username'];
      $smtp_password = $data['smtp_password'];

      $sent_as_name = 'Teejay';
      try 
      {

        // Create the SMTP Transport
        if ($data['smtp_security_type'] == 'ssl' ||
            $data['smtp_security_type'] == 'tls') {
            $transport = (new Swift_SmtpTransport($smtp_host, $smtp_port,$data['smtp_security_type']))
              ->setUsername($smtp_username)
              ->setPassword($smtp_password);
        }else{
          $transport = (new Swift_SmtpTransport($smtp_host, $smtp_port))
              ->setUsername($smtp_username)
              ->setPassword($smtp_password);
        }
       
          // Create the Mailer using your created Transport
          $mailer = new Swift_Mailer($transport);
       
          // Create a message
          $message = new Swift_Message();       
       
          // Set the "From address"
          $message->setFrom([$sent_as => $sent_as_name]);

          // Set the "To address" [Use setTo method for multiple recipients, argument should be array]
          $message->setTo($email);

          // Set a "subject"
          $subject = "M2I Password reset";

          $message->setSubject($subject);

          //Set a "body"
          //Template
                $body = "Hi ".$usrname.",
                Your Password is ".$password."

Thanks,
Admin - M2I  ";

          $message->setBody($body, 'text/plain');

          // Send the message
          $result = $mailer->send($message);
        }catch (Exception $e) {
          $e->getMessage();
        }      
    }
}
?>
