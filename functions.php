<?php
/* for database connection information */
require_once(dirname(__FILE__) . "/class.pgsql.php");
require_once(dirname(__FILE__) . "/db_settings.php");

/* defaults */
setlocale(LC_MONETARY, 'en_US');
putenv("TZ=America/Chicago");

define("ERROR_LOG", dirname(dirname(__FILE__)) . '/ERROR_LOG');

function to_array($arg)
{
    if (is_array($arg)) {
        return $arg;
    } else {
        return [$arg];
    }
}

/*
 *   input string to be interpolated/translated, any params to interpolate (optional)
 *     and a class to create for new div (optional)
 *       returns gettext translated, %[variables] interpolated
 */

function __($message, $params = [], $div="")
{
    if (function_exists("gettext")) {
        $return = gettext($message);
    } else {
        $return = $message;
    }
    if (count($params)>0) {
        foreach ($params as $key => $value) {
            $return = str_replace("%[" . $key . "]", htmlentities($value), $return);
        }
    }
    if ($div!="") {
        $return="<div class='$div'>$return</div>";
    }
    return $return;
}

function create_random_password($l=10, $c = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ,./;'[]-=\\<>?:\"{}|`~!@#$%^&*()_+")
{
    $pass='';
    $char=0;
    $l=($l==0)?10:$l;
    for ($length = 0; $length < $l; $length++) {
        $temp = str_shuffle($c);

        $char = random_int(0, strlen($temp)-1);
        $pass .= '' . $temp[$char];
    }
    return $pass;
}

/*
 *   logs error to ERROR_LOG
 *   */
function logError($err)
{
    logMessage($err, "ERROR");
}

/*
 *   logs arbitrary message to ERROR_LOG
 *   */
function logMessage($err, $prefix = "LOG")
{
    $str=print_r($err, true);
    $fh = fopen(ERROR_LOG, 'a');
    if (!$fh) {
        throw new Exception("[logMessage]: Could not open log");
    }
    if (substr_count($str, "\n")>0) {
        $arr=explode("\n", $str);
        for ($i=0; $i<count($arr); $i++) {
            fwrite($fh, sprintf("%s %s %s\n", date('Ymd;H:m:s', time()), $prefix, $arr[$i]));
        }
    } else {
        fwrite($fh, sprintf("%s %s %s\n", date('Ymd;H:i:s', time()), $prefix, $str));
    }
    fclose($fh);
}

function checkRequestParameters($param = [])
{
    foreach ($param as $key => $value) {
        if (!isset($_REQUEST[$key])) {
            logMessage($key . "not set");
            return false;
        }
        $req = explode(',', $value);
        for ($i = 0; $i < count($req); $i++) {
            if ($req[$i] == 'required' && $_REQUEST[$key] == "") {
                logMessage("required failed for $key");
                return false;
            }
            if ($req[$i] == 'numeric' && !preg_match('/^\d+$/', $_REQUEST[$key])) {
                logMessage($key . " numeric failed: " . $_REQUEST[$key]);
                return false;
            }
            if ($req[$i] == 'alphanumeric' && !preg_match('/^[a-z0-9]+$/i', $_REQUEST[$key])) {
                logMessage($key . " alphanumeric failed: " . $_REQUEST[$key]);
                return false;
            }
            if ($req[$i] == 'printable' && !preg_match('/[:print]+/', $_REQUEST[$key])) {
                logMessage($key . " printable failed: " . $_REQUEST[$key]);
                return false;
            }
            if ($req[$i] == 'date' && !preg_match('/^[0-9]{4}-[0-9]{1,2}-[0-9]{1,2}$/', $_REQUEST[$key])) {
                logMessage($key . " date failed: " . $_REQUEST[$key]);
                return false;
            }
        }
    }
    return true;
}

function getFilenameWithoutExtension($file)
{
    return pathinfo($file)['filename'];
}

function getParentDirectory($file)
{
    return pathinfo($file)['dirname'];
}

function request_value ($key, $default = '') {
  $str = '';
	if (!empty($_REQUEST[$key])) {
		$str = $_REQUEST[$key];
	} elseif ($default != '') {
		$str = $default;
	}
	if ($str != '') {
		return ' value="' . htmlentities($str) . '"';
	} else {
		return '';
	}
}
