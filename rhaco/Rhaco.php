<?php
/**
 * @author Kazutaka Tokushima
 * @license New BSD License
 */
class Rhaco{
	static private $IMPORTED = array();
	static private $IMPORT_BASE = array();
	static private $SHUTDOWN = array();
	static private $PATH;
	static private $LIB;
	static private $WORK;
	static private $URL;
	static private $DEF = array();
	static private $inited = false;

	/**
	 * 初期定義
	 *
	 * @param string $path
	 * @param string $lib
	 * @param string $url
	 */
	static public function init($path,$url=null,$lib=null,$work=null){
		self::$inited = true;
		if(is_file($path)) $path = dirname($path);
		self::$PATH = preg_replace("/^(.+)\/$/","\\1",str_replace("\\","/",$path))."/";

		if(isset($lib)){
			if(is_file($lib)) $lib = dirname($lib);
			self::$LIB = preg_replace("/^(.+)\/$/","\\1",str_replace("\\","/",$lib))."/";
		}else{
			self::$LIB = self::$PATH."lib/";
		}
		if(isset($work)){
			if(is_file($work)) $work = dirname($work);
			self::$WORK = preg_replace("/^(.+)\/$/","\\1",str_replace("\\","/",$work))."/";
		}else{
			self::$WORK = self::$PATH."work/";
		}
		self::$URL = preg_replace("/^(.+)\/$/","\\1",$url)."/";
		Rhaco::add(self::$LIB);
	}
	static public function path($path=null){
		if(isset($path) && $path[0] == "/") $path = substr($path,1);
		return self::$PATH.$path;
	}
	static public function lib($path=null){
		if(isset($path) && $path[0] == "/") $path = substr($path,1);
		return self::$LIB.$path;
	}
	static public function work($path=null){
		if(isset($path) && $path[0] == "/") $path = substr($path,1);
		return self::$WORK.$path;
	}
	static public function url($path=null){
		if(isset($path) && $path[0] == "/") $path = substr($path,1);
		return self::$URL.$path;
	}

	/**
	 * 定義情報を設定/取得
	 * @param string $name
	 * @param mixed $value
	 * @return mixed
	 */
	static public function def($name,$value=null){
		if($value !== null && !isset(self::$DEF[$name])){
			if(strpos($name,"@") <= 0) throw new Exception("invalid def ".$name." > ext. def('pointer@var_name','value')");
			if(func_num_args() > 2){
				$args = func_get_args();
				array_shift($args);
				$value = $args;
			}
			return self::$DEF[$name] = $value;
		}
		return (isset(self::$DEF[$name])) ? self::$DEF[$name] : null;
	}
	/**
	 * 特定キーワードの定義情報一覧を返す
	 * @param string $path
	 * @return array
	 */
	static public function constants($path){
		$result = array();
		foreach(self::$DEF as $key => $value){
			if(strpos($key,$path) === 0) $result[$key] = $value;
		}
		return $result;
	}
	/**
	 * importし、クラス名を返す
	 * @param string $path
	 * @return string
	 */
	static public function import($path){
		if(isset(self::$IMPORTED[$path])) return self::$IMPORTED[$path][0];
		if(class_exists($path) || interface_exists($path)){
			$i = new ReflectionClass($path);
			$f = str_replace("\\","/",$i->getFileName());
			foreach(self::$IMPORTED as $r){
				if($r[0] == $path){
					if($r[1] != $f) throw new Exception("class exsist ".$path);
					return $path;
				}
			}
			return self::register_import($path,$path,$f);
		}else if(is_file($path)){
			$realpath = $path;
			$paths = explode("/",str_replace("\\","/",$path));
			$class = preg_replace("/^(.+)\..+$/","\\1",array_pop($paths));
		}
		if(!isset($realpath)){
			$class = ucfirst(substr($path,(false !== ($pos = strrpos($path,"."))) ? $pos + 1 : $pos));
			$r = str_replace(".","/",$path);

			if(!self::$inited){
				$debug = debug_backtrace();
				$root = array_pop($debug);
				self::init($root["file"]);
			}
			foreach(self::$IMPORT_BASE as $p){
				if(is_readable($realpath = $p.$r.".php")) break;
				if(is_readable($realpath = $p.$r."/".$class.".php")) break;
				$realpath = "";
			}
		}
		if(!empty($realpath)){
			ob_start();
				self::$IMPORTED[$path] = array($class,$realpath);
				$bool = include_once($realpath);
			ob_get_clean();
			if($bool) return self::register_import($path,$class,$realpath);
		}
		throw new Exception($path." not found");
	}
	/**
	 * 呼び出しもとのパッケージを起点としてimportする
	 * @param string $path
	 * @return string
	 */
	static public function module($path){
		$package = self::module_root(func_num_args() > 1 && func_get_arg(1) === true);
		$module_path = str_replace(".","/",$path);
		$realpath = $package."/".$module_path.".php";
		if(is_file($realpath)) return self::import($realpath);
		throw new Exception($path." not found");
	}
	/**
	 * 呼び出しもとのパッケージを起点としたパスを返す
	 * @param string $path
	 * @return string
	 */
	static public function module_path($path){
		$package = self::module_root(func_num_args() > 1 && func_get_arg(1) === true);
		$module_path = str_replace(".","/",$path);
		return $package."/".$path;
	}
	static private function module_root($bool){
		if($bool){
			list(,,$debug) = debug_backtrace();
		}else{
			list(,$debug) = debug_backtrace();
		}
		$package = dirname(str_replace("\\","/",$debug["file"]));
		while($package != ""){
			$package_class = ucfirst(basename($package));
			if(is_file($package."/".$package_class.".php")){
				return $package;
			}
			if($package === "/") break;
			$package = dirname($package);
		}
		throw new Exception($path." no packeage");
	}
	static private function register_import($path,$class,$realpath){
		self::$IMPORTED[$path] = array($class,$realpath);
		if(self::is_self_method($realpath,$class,"__import__")) call_user_func(array($class,"__import__"));
		if(self::is_self_method($realpath,$class,"__shutdown__")) self::$SHUTDOWN[] = array($class,"__shutdown__");
		if(self::is_self_method($realpath,$class,"__funcs__")) call_user_func(array($class,"__funcs__"));
		return $class;
	}
	static private function is_self_method($realpath,$class,$method){
		return (method_exists($class,$method) && ($i = new ReflectionMethod($class,$method)) && $i->isStatic() && str_replace("\\","/",$i->getFileName()) == $realpath);
	}
	
	/**
	 * importの際に探すベースパス
	 * @param string $path
	 */
	static public function add($path){
		$p = str_replace("\\","/",$path);
		$p = $p.((substr($p,-1) == "/") ? "" : "/");
		self::$IMPORT_BASE[$p] = $p;
	}
	static public function shutdown(){
		krsort(self::$SHUTDOWN,SORT_NUMERIC);
		foreach(self::$SHUTDOWN as $s) call_user_func($s);
	}
	static public function textdomain($path=null){
		if(extension_loaded("gettext")){
			if($path === null) $path = dirname(__FILE__)."/resources/locale";
			bindtextdomain("messages",$path);
			textdomain("messages");
			self::setlocale();
		}
	}
	static public function setlocale($locale=null){
		if($locale === null) $locale = (isset($_SERVER["HTTP_ACCEPT_LANGUAGE"])) ? str_replace("-","_",$_SERVER["HTTP_ACCEPT_LANGUAGE"]) : "ja_JP";
		list($locale) = explode(",",$locale);

		if(ini_get("safe_mode") == "") putenv("LANG=".$locale);
		setlocale(LC_ALL,$locale);
		/***
			Rhaco::setlocale("ja_JP");
			eq("エンコード文字列が不正です",gettext("an encode string is illegal"));
		 */
	}
}
?>