<?php

require_once('whatsapp_class.php');


$whatsapp_obj = new WhatsAppAPI();
$apiResponse = $whatsapp_obj->sendText($country_code = '91', $to_mobile = '987654****', $message = 'Simple Text Message');

?>