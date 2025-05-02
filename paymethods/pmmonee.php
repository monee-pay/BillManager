#!/usr/bin/php
<?php
set_include_path(get_include_path() . PATH_SEPARATOR . '/usr/local/mgr5/include/php');
define('__MODULE__', 'pmmonee');
require_once 'bill_func_monee.php';

$longopts  = [
	'command:',
	'payment:',
	'amount:',
];
$options = getopt('', $longopts);

try {
	$command = $options['command'];

	if ($command === 'config') {
		$config_xml = simplexml_load_string($default_xml_string);

		$feature_node = $config_xml->addChild('feature');
		$feature_node->addChild('redirect', 'on');
		$feature_node->addChild('notneedprofile', 'on');

		$param_node = $config_xml->addChild('param');
		$param_node->addChild('payment_script', '/mancgi/monee_payment.php');

		echo $config_xml->asXML();
	} elseif ($command === 'pmtune') {
		$paymethod_form = simplexml_load_string(file_get_contents('php://stdin'));
		echo $paymethod_form->asXML();
	} elseif ($command === 'pmvalidate') {
		$paymethod_form = simplexml_load_string(file_get_contents('php://stdin'));
		echo $paymethod_form->asXML();
	} else {
		throw new Error("unknown command");
	}
} catch (Exception $e) {
	echo $e;
}

?>