#!/usr/bin/php
<?php
set_include_path(get_include_path() . PATH_SEPARATOR . '/usr/local/mgr5/include/php');
define('__MODULE__', 'pmmonee');
require_once 'bill_func_monee.php';

header('Content-Type: text/html; charset=utf-8');

$input = CgiInputPayment();

if (empty($input['elid'])) {
	throw new Exception('Empty elid');
}

$info = LocalQuery('payment.info', ['elid' => $input['elid']]);
if (empty($info['payment'][0])) {
	throw new Exception('Empty info');
}

$payment = $info['payment'][0];

$paymethod = $payment['paymethod'][1];
$shop_uuid = $paymethod['shop_uuid']['$'];
if (is_null($shop_uuid)) $error = 1; $err_shop_uuid = 1;

$order_id = $payment['id']['$'];
if (is_null($order_id)) $error = 1; $err_order_id = 1;

$amount = (float)$payment['paymethodamount']['$'];
if (!is_float($amount)) $error = 1; $err_is_float = 1;

$currency = $payment['currency'][1]['iso']['$'];
if ($currency !== 'RUB') $error = 1; $err_currency = 1;

$desc = $payment['project']['name']['$'] . ' #' . $order_id;
$webhook_url = str_replace('/billmgr', '', $payment['manager_url']['$']).'/mancgi/monee_result.php';

if ($error > 0) {
die('<!DOCTYPE html>
	<html lang="ru">
		<head>
			<title>Error</title>
			<meta charset="UTF-8">
		</head>
		<body>
			<h2>Во время создания платежа произошла ошибка:</h2>
			'.((isset($err_shop_uuid))?'<h3>UUID мерчанта не указан!</h3>':'').'
			'.((isset($err_order_id))?'<h3>Платеж в биллинге не найден!</h3>':'').'
			'.((isset($err_is_float))?'<h3>Сумма платежа должна быть в формате float!</h3>':'').'
			'.((isset($err_currency))?'<h3>Валютой платежа может быть только RUB!</h3>':'').'
		</body>
	</html>');
}

$url = 'https://api.monee.pro/payment/create';

$data = [
	'shop_to' => $shop_uuid,
	'sum' => $amount,
	'comment' => $desc,
	'expire' => 1900,
	'hook_url' => $webhook_url,
	'custom_fields' => $order_id
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, false);
$response = json_decode(curl_exec($ch),true);
curl_close($ch);

if ($response['status'] == 'error') {
die('<!DOCTYPE html>
	<html lang="ru">
		<head>
			<title>Error</title>
			<meta charset="UTF-8">
		</head>
		<body>
			<h2>Во время создания платежа произошла ошибка:</h2>
			'.$response['message'].'
		</body>
	</html>');
} else {
die('<!DOCTYPE html>
	<html lang="ru">
		<head>
			<title>Loading...</title>
			<meta charset="UTF-8">
		</head>
		<body>
			<script>location.href="'.$response['url'].'"</script>
		</body>
	</html>');
}
?>