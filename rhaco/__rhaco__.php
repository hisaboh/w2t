<?php
ini_set("display_errors","On");
ini_set("display_startup_errors","On");
ini_set("html_errors","Off");

set_error_handler("error_handler",E_ALL);
$zone = @date_default_timezone_get();
date_default_timezone_set(empty($zone) ? "Asia/Tokyo" : $zone);

require(dirname(__FILE__)."/Object.php");
require(dirname(__FILE__)."/Exceptions.php");
require(dirname(__FILE__)."/Rhaco.php");

register_shutdown_function("Rhaco::shutdown");
Rhaco::add(dirname(__FILE__));
Rhaco::textdomain();

function error_handler($errno,$errstr,$errfile,$errline){
	if(strpos($errstr,"Use of undefined constant") !== false && preg_match("/\'(.+?)\'/",$errstr,$m) && class_exists($m[1])){
		define($m[1],$m[1]);
		return true;
	}
	throw new ErrorException($errstr,0,$errno,$errfile,$errline);
	return true;
}

/**
 * extensionを読み込む
 *
 * @param string $module_name
 * @param string $doc
 */
function extension_load($module_name,$doc=null){
	if(!extension_loaded($module_name)){
		try{
			dl($module_name.".".PHP_SHLIB_SUFFIX);
		}catch(Exception $e){
			throw new Exception("undef ".$module_name."\n".$doc);
		}
	}
}

/**
 * referenceを返す
 *
 * @param object $obj
 * @return object
 */
function R(object $obj){
	return $obj;
}
/**
 * class_accessとして返す
 *
 * @param mixed $obj
 * @return object
 */
function C($obj){
	if(is_object($obj)) $obj = get_class($obj);
	$obj = new $obj("_class_access_=true");
	return $obj;
}
/**
 * あるオブジェクトが指定したインタフェースをもつか調べる
 *
 * @param mixed $object
 * @param string $inteface
 * @return boolean
 */
function is_implements_of($object,$inteface){
	$class_name = (is_object($object)) ? get_class($object) : $object;
	return in_array($inteface,class_implements($class_name));
}
/**
 * Content-Type: text/plain
 */
function header_output_text(){
	header("Content-Type: text/plain;");
}
/**
 * 改行付きで出力
 *
 * @param string $value
 */
function println($value){
	print($value."\n");
}
/**
 * Objectのgetterを指定してソート
 *
 * @param array $list
 * @param string $getter_name
 * @return array
 */
function osort(array &$list,$getter_name){
	return Object::osort($list,$getter_name);
}
/**
 * Objectのgetterを指定して逆順でソート
 *
 * @param array $list
 * @param string $getter_name
 * @return array
 */
function rosort(array &$list,$getter_name){
	return Object::osort($list,$getter_name,true);
}
/**
 * 文字列として比較してソート
 *
 * @param array $list
 * @return array
 */
function ssort(array &$list){
	return Object::ssort($list);
}
/**
 * 文字列として比較して逆順でソート
 *
 * @param array $list
 * @return array
 */
function rssort(array &$list){
	return Object::ssort($list,true);
}
/**
 * Objectのgetterを指定してマージ
 *
 * @param array $list
 * @param string $getter_name
 * @return array
 */
function omerge(array $list,$getter_name){
	return Object::omerge($list,$getter_name);
}

/**
 * importし、クラス名を返す
 * @param string $path
 * @return string
 */
function import($path){
	return Rhaco::import($path);
}
/**
 * 呼び出しもとのパッケージを起点としてimportする
 * @param string $path
 * @return string
 */
function module($path){
	return Rhaco::module($path,true);
}
/**
 * 呼び出しもとのパッケージを起点としたパスを返す
 * @param string $path
 * @return string
 */
function module_path($path){
	return Rhaco::module_path($path,true);
}

/**
 * 定義情報を設定/取得
 * @param string $name
 * @param mixed $value
 * @return mixed
 */
function def($name,$value=null){
	$args = func_get_args();
	return call_user_func_array(array("Rhaco","def"),$args);
}

/**
 * アプリケーションの環境設定を行う
 *
 * @param string $path
 * @param string $url
 * @param string $lib
 * @param string $work
 */
function application_settings($path,$url=null,$lib=null,$work=null){
	Rhaco::init($path,$url,$lib,$work);
}
/**
 * アプリケーションのurlを取得する
 *
 * @param string $path
 * @return string
 */
function url($path=null){
	return Rhaco::url($path);
}
/**
 * アプリケーションのファイルパスを取得する
 *
 * @param string $path
 * @return string
 */
function path($path=null){
	return Rhaco::path($path);
}
/**
 * アプリケーションのライブラリのファイルパスを取得する
 *
 * @param string $path
 * @return string
 */
function lib_path($path=null){
	return Rhaco::lib($path);
}
/**
 * アプリケーションのワーキング(テンポラリ)ファイルパスを取得する
 *
 * @param string $path
 * @return string
 */
function work_path($path=null){
	return Rhaco::work($path);
}
?>