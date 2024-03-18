<?php
/* Change This Parameter */
 // eg. 91987654**** (with country code without '+' sign)
/* Change This Parameter */
/**
*	Class: WhatsAppAPI
*
* 	Class for send whatsapp message using https://www.whatsappapi.in API
*/
class WhatsAppAPI
{
	public $apiUrl;
	function __construct()
	{
		$this->apiUrl = 'https://www.whatsappapi.in/api';
	}
	/**
	* @param int $country_code to user country code
	* @param int $mobile to user mobile number without country code
	* @param string $message message for text message which you want to send on users whatsapp
	*/
	public function sendText($country_code, $mobile, $message)
	{
		
				
		return $this->connect($country_code, $mobile, $message);
	}
	
	private function connect($country_code, $mobile, $message, $type = 'text')
	{
		//global $apiToken;
		//global $fromNumber;
		$type = trim(strtolower($type));
		
		/* Check passed type is correct or not */
		if($type != 'text')
		{
			//echo "if";
			//exit();
			return false;
		}
		else
		{

			$apiToken = '25846661210626682701454197775e16cd6521362'; // eg. 6846532456354354
			$fromNumber = '917777877080';
			//echo $apiToken.''.$fromNumber;

			//exit();
		$curl = curl_init();
		curl_setopt_array($curl, array(
		  /*CURLOPT_URL => "https://www.whatsappapi.in/api"."?token=".$apiToken."&action=".$type."&from=".$fromNumber."&country=".$country_code."&to=".$mobile."&uid=".uniqid()."&".$type."=".urlencode($message),*/

		  CURLOPT_URL => "https://www.whatsappapi.in/api?token=25846661210626682701454197775e16cd6521362&action=text&from=917777877080&country=91&to=$mobile&uid=5e184cb2bb645&text=$message",
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_TIMEOUT => 30,
		  CURLOPT_SSL_VERIFYHOST => 0,
		  CURLOPT_SSL_VERIFYPEER => 0,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "POST",
		  CURLOPT_POSTFIELDS => "",
		));
		$apiResponse = curl_exec($curl);
		$err = curl_error($curl);
		curl_close($curl);
		if ($err) {
			//echo "error";
			//exit();
		  return "Failed";
		} else {
			/*echo "else not error";
			exit();*/
			//echo $apiResponse;
		  return $apiResponse;
		}

		}
	}
}