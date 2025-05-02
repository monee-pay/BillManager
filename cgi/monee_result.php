#!/usr/bin/php
<?php
set_include_path(get_include_path() . PATH_SEPARATOR . "/usr/local/mgr5/include/php");
define('__MODULE__', "pmmonee");
require_once 'bill_func_monee.php';

echo "Content-Type: text/html; charset=utf-8\n\n";

$ctx = stream_context_create([
	'http' => [
        'timeout' => 10
    ]
]);

$ips = json_decode(file_get_contents('https://api.monee.pro/ips', false, $ctx));
$ip = getIP();

if (isset($ips->list) && !in_array($ip, $ips->list)) {
	Debug("Unknown IP: " . $ip);
	die("Unknown IP: " . $ip);
}

$params = json_decode(CgiInput(), true);

if ($params) {
	$check_params = ['status', 'invoice_id', 'shop_id', 'amount', 'custom_fields'];

	foreach($check_params as $check_param) {
		if(empty($params[$check_param])) {
			Debug("Empty param: '" . $check_param . "'");
			die("Empty param: '" . $check_param . "'");
		}
	}
	
	$info = LocalQuery('payment.info', ['elid' => $params['custom_fields']]);
	if(empty($info['payment'][0])) {
		Debug("Order '" . $params['invoice_id'] . "' not found");
		die("Order '" . $params['invoice_id'] . "' not found");
	}
		
	LocalQuery('payment.setpaid', ['elid' => $params['custom_fields'], 'info' => print_r($params, true)]);
	Debug("Order '" . $params['custom_fields'] . "' is paid");
			
	die("OK");
} else {
	Debug('Params is empty!');
	die("Params is empty!");
}
?>