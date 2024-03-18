<?php

/**
 * Class to handle all db operations
 * This class will have CRUD methods for database tables
 *
 * @author arun kumar,Pavan Kumar,Ramu,Sreekanth
 * @link URL Tutorial link
 */
ini_set("allow_url_fopen", 1);

// define(APPROVE, 2);

use Twilio\Rest\Client;

class DbHandler
{
	private $conn;

	function __construct()
	{
		require_once dirname(__FILE__) . '/DbConnect.php';
		require_once dirname(__FILE__) . '/SmsService.php';
		require_once dirname(__FILE__) . '/PasswordHash.php';
		require_once dirname(__FILE__) . '/WhatsappService.php';
		// require_once '../vendor/autoload.php';
		// require_once '../vendor/twilio/sdk/src/Twilio/Rest/Client.php';


		// opening db connection
		date_default_timezone_set('UTC');
		$db = new DbConnect();
		$this->conn = $db->connect();

		// echo $this->conn;die();
		$this->apiUrl = 'https://www.whatsappapi.in/api';
	}

	/************function for check is valid api key*******************************/
	function isValidApiKey($token)
	{
		//echo 'SELECT userId FROM registerCustomers WHERE apiToken="'.$token.'"';exit;
		$query = 'SELECT userId FROM registerCustomers WHERE apiToken="' . $token . '"'; // AND password = $userPass";
		$result = mysqli_query($this->conn, $query);
		$num = mysqli_num_rows($result);
		return $num;
	}

	/************function for check is valid api key*******************************/
	function isValidSessionToken($token, $user_id)
	{
		//echo 'SELECT userId FROM registerCustomers WHERE apiToken="'.$token.'"';exit;
		$query = 'SELECT * FROM erp_user_token WHERE userid = "' . $user_id . '" and session_token ="' . $token . '"'; // AND password = $userPass";
		$result = mysqli_query($this->conn, $query);
		$num = mysqli_num_rows($result);
		return $num;
	}
	/**
	 * Generating random Unique MD5 String for user Api key
	 */
	function generateApiKey()
	{
		return md5(uniqid(rand(), true));
	}
	/** Password Encryption Algorithim*/
	function encrypt($str)
	{
		$key = 'grubvanapp1#20!8';
		$block = mcrypt_get_block_size('rijndael_128', 'ecb');
		$pad = $block - (strlen($str) % $block);
		$str .= str_repeat(chr($pad), $pad);
		$rst = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $str, MCRYPT_MODE_ECB, str_repeat("\0", 16)));
		return str_ireplace('+', '-', $rst);
	}

	/************function for check is valid api key*******************************/

	function generateSessionToken($user_id)
	{
		$data = array();
		$token = $this->generateApiKey();
		$query = "SELECT * FROM erp_user_token WHERE userid = $user_id";
		$count = mysqli_query($this->conn, $query);

		if (mysqli_num_rows($count) > 0) {
			$row = mysqli_fetch_assoc($count);
			$token_userid = $row['userid'];
			if ($token_userid == $user_id) {
				$updatesql = "UPDATE erp_user_token SET session_token='$token' WHERE userid=$user_id";
				if ($result2 = mysqli_query($this->conn, $updatesql)) {
					$data['session_token'] = $token;
					$data['status'] = 1;
				} else {
					$data['status'] = 0;
				}
			} else {
				$data['status'] = 0;
			}
		}
		return $data;
	}

	function userLogin($username, $password)
	{
		$data = array();
		$query = "SELECT * FROM tbl_user WHERE (email ='$username' OR mobile_number = '$username')";
		$sql = mysqli_query($this->conn, $query);
		if (mysqli_num_rows($sql) > 0) {
			$row = mysqli_fetch_assoc($sql);
			$user_password = $row['user_password'];
			$state = $row['state_id'];
			$city = $row['city_id'];
			$verify = password_verify($password, $user_password);
			if ($verify) {
				$locQry = "SELECT s.name as state,c.city FROM cities c LEFT JOIN states s ON c.state_id = s.id WHERE c.state_id = '$state' AND c.id = '$city'";
				$locSql = mysqli_query($this->conn, $locQry);

				$data['user_name'] = $row['user_name'];
				$data['user_id'] = $row['id'];
				if ($row['user_role_id'] == 1) {
					$data['role_name'] = 'Admin';
				}
				$data['user_role_id'] = $row['user_role_id'];
				$data['mobileno'] = $row['mobile_number'];
				$data['email'] = $row['email'];

				if (mysqli_num_rows($locSql) > 0) {
					$locRow = mysqli_fetch_assoc($locSql);
					$data['state_id'] = $row['state_id'];
					$data['state'] = $locRow['state'];
					$data['city_id'] = $row['city_id'];
					$data['city'] = $locRow['city'];
					$data['pincode'] = $row['pincode'];
				} else {
					$data['state_id'] = "";
					$data['state'] = "";
					$data['city_id'] = "";
					$data['city'] = "";
					$data['pincode'] = "";
				}

				$data['status'] = $row['status'];
				$data['userDetails'] = $data;
			} else {
				$data['status'] = 0;
				$data['userDetails'] = [];
			}
		} else {
			$data['status'] = 0;
			$data['userDetails'] = [];
		}
		return $data;
	}
	function saveUser($data)
	{
		$output = array();

		$hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);
		$roleId = 2;
		$firstname = $data['first_name'];
		$lastname = $data['last_name'];
		$username = $firstname . '' . $lastname;
		$email = $data['email'];
		$mobnumber = $data['mobile_number'];
		$state = $data['state_id'];
		$city = $data['city_id'];
		$pincode = $data['pincode'];

		$sql = "INSERT INTO tbl_user(user_role_id,user_name,email,mobile_number,user_password,state_id,city_id,pincode) VALUES(?,?,?,?,?,?,?,?)";
		if ($stmt = mysqli_prepare($this->conn, $sql)) {
			mysqli_stmt_bind_param($stmt, "issisiii", $roleId, $username, $email, $mobnumber, $hashed_password, $state, $city, $pincode);
			if (mysqli_stmt_execute($stmt)) {
				$output["status"] = 1;
				$output["message"] = "Signup successfully";
			} else {
				$output["status"] = 0;
				$output["message"] = "Signup failed";
			}
		} else {
			$output["status"] = 0;
			$output["message"] = "Signup failed1";
		}

		return $output;
	}

	function getCategories($path)
	{

		$output = array();

		$query = "SELECT * FROM tbl_categories";
		$sql = mysqli_query($this->conn, $query);

		if (mysqli_num_rows($sql) > 0) {

			while ($row = mysqli_fetch_assoc($sql)) {
				$output['id'] = $row['id'];
				$output['name'] = $row['name'];
				$output1[] = $output;
			}

			$output['status'] = 1;
			$output['category'] = $output1;
		} else {
			$output['status'] = 0;
			$output['category'] = array();
		}

		return $output;
	}
	/* ------------------------------ END API's-----------------------*/
}
