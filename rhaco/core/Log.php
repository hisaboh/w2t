<?php
Rhaco::import("core.File");
/**
 * ログ処理
 *
 * @author Kazutaka Tokushima
 * @license New BSD License
 */
class Log extends Object{
	static private $START = 0;
	static private $DISP = true;
	static private $DISP_LEVEL = 0;
	static private $FILE_LEVEL = 0;
	static private $PATH = "";
	static private $MAXLEVEL = null;
	static private $LOG = array();
	static private $FILTER = array();

	protected $level;
	protected $time;
	protected $file;
	protected $line;
	protected $value;

	static public function __import__(){
		$level = array("none"=>0,"error"=>1,"warning"=>2,"info"=>3,"debug"=>4);
		self::$DISP_LEVEL = $level[Rhaco::def("core.Log@disp","none")];
		self::$FILE_LEVEL = $level[Rhaco::def("core.Log@file","none")];
		self::$PATH = Rhaco::def("core.Log@path",Rhaco::work("log"));
		self::$MAXLEVEL = null;
		Log::$START = microtime(true);
	}
	static public function __shutdown__(){
		self::flush();
	}
	final protected function __new__($level,$value,$file=null,$line=null,$time=null){
		if($file === null){
			$debugs = debug_backtrace();
			if(sizeof($debugs) > 4){
				list($dumy,$dumy,$dumy,$debug,$op) = debug_backtrace();
			}else{
				list($dumy,$debug) = debug_backtrace();
			}
			$file = File::path(isset($debug["file"]) ? $debug["file"] : $dumy["file"]);
			$line = (isset($debug["line"]) ? $debug["line"] : $dumy["line"]);
			$class = (isset($op["class"]) ? $op["class"] : $dumy["class"]);
		}
		$this->level = $level;
		$this->value = (string)$value;
		$this->file = (strpos($file,"eval()'d") !== false) ? $class : $file;
		$this->line = intval($line);
		$this->time = ($time === null) ? time() : $time;
		$this->class = $class;
	}
	final protected function getTime($format="Y/m/d H:i:s"){
		return (empty($format)) ? $this->time : date($format,$this->time);
	}
	/**
	 * 格納されたログを出力する
	 */
	final static public function flush(){
		if(self::isPublishLevel() >= 4){
			self::$LOG[] = new self(4,"use memory: ".number_format(memory_get_usage())."byte / ".number_format(memory_get_peak_usage())."byte");
			self::$LOG[] = new self(4,sprintf("------- end logger ( %f sec ) ------- ",microtime(true) - (float)self::$START));
		}
		if(!empty(self::$LOG)){
			$level = array("none","error","warning","info","debug");
			foreach(self::$LOG as $log){
				$value = $log->value();
				if(Rhaco::def("core.Log@expression") === true){
					ob_start();
						var_dump($value);
					$value = substr(ob_get_clean(),0,-1);
				}
				$value = "[".$level[$log->level()]." ".$log->time()."]:[".$log->file().":".$log->line()."] ".$value."\n";
				if(self::$DISP_LEVEL >= $log->level() && self::$DISP) print($value);
				if(self::$FILE_LEVEL >= $log->level()){
					if(empty(self::$PATH)) throw new Exception("not found path");
					File::append(sprintf("%s/%s.log",File::path(self::$PATH),date("Ymd")),$value);
				}
				self::call_filter($level[$log->level()],$log);
			}
			self::call_filter("flush",self::$LOG);
		}
		self::$LOG = array();
	}
	private static function isPublishLevel(){
		if(self::$MAXLEVEL === null){
			$level = array(self::$DISP_LEVEL,self::$FILE_LEVEL);
			rsort($level);
			self::$MAXLEVEL = $level[0];
		}
		return self::$MAXLEVEL;
	}
	/**
	 * 一時的に無効にされた標準出力へのログ出力を有効にする
	 * ログのモードに依存する
	 */
	static public function enable_display(){
		self::debug("log display on");
		self::$DISP = true;
	}

	/**
	 * 標準出力へのログ出力を一時的に無効にする
	 */
	static public function disable_display(){
		self::debug("log display off");
		self::$DISP = false;
	}
	/**
	 * errorを生成
	 * @param string $value
	 */
	static public function error(){
		if(self::isPublishLevel() >= 1){
			foreach(func_get_args() as $value) self::$LOG[] = new self(1,$value);
		}
	}
	/**
	 * warningを生成
	 * @param string $value
	 */
	static public function warning($value){
		if(self::isPublishLevel() >= 2){
			foreach(func_get_args() as $value) self::$LOG[] = new self(2,$value);
		}
	}
	/**
	 * infoを生成
	 * @param string $value
	 */
	static public function info($value){
		if(self::isPublishLevel() >= 3){
			foreach(func_get_args() as $value) self::$LOG[] = new self(3,$value);
		}
	}
	/**
	 * debugを生成
	 * @param string $value
	 */
	static public function debug($value){
		if(self::isPublishLevel() >= 4){
			foreach(func_get_args() as $value) self::$LOG[] = new self(4,$value);
		}
	}
	/**
	 * var_dumpで出力する
	 * @param mixed $value
	 */
	static public function d(){
		list($debug_backtrace) = debug_backtrace();
		$args = func_get_args();
		var_dump(array_merge(array($debug_backtrace["file"].":".$debug_backtrace["line"]),$args));
	}
	/**
	 * フィルタを追加する
	 */
	static public function filter(){
		$args = func_get_args();
		foreach($args as $arg){
			if(is_object($arg)) self::$FILTER[] = $arg;
		}
	}
	static private function call_filter($method,$value){
		$args = func_get_args();
		$method = array_shift($args);

		foreach(self::$FILTER as $obj){
			if(method_exists($obj,$method)) $args[0] = call_user_func_array(array($obj,$method),$args);
		}
		return $args[0];
	}
}
?>