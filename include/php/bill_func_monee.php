<?php
date_default_timezone_set('UTC');
$log_file = fopen('/usr/local/mgr5/var/'. __MODULE__ .'.log', 'a');
$default_xml_string = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<doc/>\n";

function Debug($str) {
	global $log_file;
	fwrite($log_file, date("M j H:i:s") ." [". getmypid() ."] ". __MODULE__ ." \033[1;33mDEBUG ". $str ."\033[0m\n\n");
}

function Error($str) {
	global $log_file;
	fwrite($log_file, date("M j H:i:s") ." [". getmypid() ."] ". __MODULE__ ." \033[1;31mERROR ". $str ."\033[0m\n\n");
}

function tmErrorHandler($errno, $errstr, $errfile, $errline) {
	Error($errno .': '. $errstr .'. In file: '. $errfile .'. On line: '. $errline);
	return true;
}
set_error_handler('tmErrorHandler');

function tmExceprionHandler($exception) {
	Error($exception->getMessage());
	return true;
}
set_exception_handler('tmExceprionHandler');

function LocalQuery($function, $params = [], $auth = null) {
	$cmd = '/usr/local/mgr5/sbin/mgrctl -m billmgr -o sjson ' . escapeshellarg($function);
	foreach ($params as $key => $value) {
		$cmd .= ' '.escapeshellarg($key).'='.escapeshellarg($value);
	}
	if ($auth) {
		$cmd .= ' auth='.escapeshellarg($auth);
	}
	$out = [];
	exec($cmd, $out);
	$out = implode('', $out);
	$out = json_decode($out, true);
	return $out['doc'];
}

function CgiInput() {
	if ($_SERVER["REQUEST_METHOD"] == 'POST'){
		$input = file_get_contents ("php://stdin",null,null,0,$_SERVER['CONTENT_LENGTH']);
	} elseif ($_SERVER["REQUEST_METHOD"] == 'GET'){
		$input = $_SERVER["QUERY_STRING"];
	}

	$param = $input;

	Debug('CgiInput: '.$input.print_r($param,true));

	return $param;
}

function CgiInputPayment() {
	if (!$skip_auth) {
		$input = $_SERVER["QUERY_STRING"];
	} else {
		if ($_SERVER["REQUEST_METHOD"] == 'POST'){
			$input = file_get_contents ("php://stdin",null,null,0,$_SERVER['CONTENT_LENGTH']);
		} elseif ($_SERVER["REQUEST_METHOD"] == 'GET'){
			$input = $_SERVER["QUERY_STRING"];
		}
	}

	$param = array();
	parse_str($input, $param);
	if ($skip_auth == false && (!array_key_exists("auth", $param) || $param["auth"] == "")) {
		if (array_key_exists("billmgrses5", $_COOKIE)) {
			$cookies_bill = $_COOKIE["billmgrses5"];
			$param["auth"] = $cookies_bill;
		} elseif (array_key_exists("HTTP_COOKIE", $_SERVER)) {
			$cookies = explode("; ", $_SERVER["HTTP_COOKIE"]);
			foreach ($cookies as $cookie) {
				$param_line = explode("=", $cookie);
				if (count($param_line) > 1 && $param_line[0] == "billmgrses5") {
					$cookies_bill = explode(":", $param_line[1]);
					$param["auth"] = $cookies_bill[0];
				}
			}
		}
	}
	Debug('CgiInput: '.$input.print_r($param,true));

	return $param;
}

if (!function_exists('hash_equals')) {
	function hash_equals($str1, $str2) {
		if (strlen($str1) != strlen($str2)) return false;
		$res = $str1 ^ $str2;
		$ret = 0;
		for ($i = strlen($res) - 1; $i >= 0; $i--) {
			$ret |= ord($res[$i]);
		}
		return !$ret;
	}
}

function getIP() {
	$ip = $_SERVER['REMOTE_ADDR'];
		
	if(isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
		$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
	}
		
	if(isset($_SERVER['HTTP_X_REAL_IP'])) {
		$ip = $_SERVER['HTTP_X_REAL_IP'];
	}
	
	if(isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {
		$ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
	}

	$explode = explode(',', $ip);
		
	if(count($explode) > 1) {
		$ip = $explode[0];
	}
	
	return trim($ip);
}

function _get_locale_lang($cookie_name) {
    $lang = null;

    if (isset($_COOKIE[$cookie_name])) {
        Debug("Language by standart cookie");
        $cookie = $_COOKIE[$cookie_name];
        list($area, $lang) = explode(":", $cookie);
    } elseif (isset($_SERVER["HTTP_COOKIE"])) {
        Debug("Language by server cookie");
        $cookies = explode("; ", $_SERVER["HTTP_COOKIE"]);
        foreach ($cookies as $cookie) {
            $param_line = explode("=", $cookie);
            if (count($param_line) > 1 && $param_line[0] == $cookie_name) {
                list($area, $lang) = explode(":", $param_line[1]);
            }
        }
    }

    return $lang;
}
?>