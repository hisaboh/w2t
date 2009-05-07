<?php
Rhaco::import("core.File");
Rhaco::import("core.Log");
Rhaco::import("core.Text");
Rhaco::import("ext.Info");
Rhaco::import("db.ActiveMapper");
/**
 * テスト処理
 *
 * @author Kazutaka Tokushima
 * @license New BSD License
 */
class Test extends Object{
	const SUCCESS = 2;
	const NONE = 4;
	const FAIL = 8;
	static private $EXEC_TYPE;
	static private $TMP_DB;
	static private $each_flush = false;
	static private $included = array();
	static private $result = array();
	static private $current_class;
	static private $current_method;
	static private $current_file;
	static private $ftmp;

	final static public function exec_type($type){
		self::$EXEC_TYPE = decbin($type);
	}
	/**
	 * テストの実行毎にflushさせるようにする
	 */
	final static public function each_flush(){
		self::$each_flush = true;
	}
	/**
	 * 結果を取得する
	 * @return array
	 */
	final public static function get(){
		return self::$result;
	}
	/**
	 * 結果をクリアする
	 */
	final public static function clear(){
		self::$result = array();
	}
	/**
	 * ディエクトリパスを指定してテストを実行する
	 * @param string $path
	 * @return Test
	 */
	final public static function verifies($path){
		foreach(File::ls($path,true) as $file){
			if($file->fullname() !== __FILE__ && $file->isClass()){
				ob_start();
					include_once($file->fullname());
					Rhaco::import($file->oname());
				ob_get_clean();
				self::verify($file->oname());
			}
		}
		return new self();
	}
	/**
	 * テストを実行する
	 * @param string $class クラス名
	 * @param strgin $method メソッド名
	 */
	final public static function verify($class,$method=null,$block_name=null){
		if(!class_exists($class) && Rhaco::import($class)){
			$pos = strrpos($class,".");
			$class = substr($class,($pos !== false) ? $pos + 1 : $pos);
		}
		$ref = Info::reflection($class);
		self::$current_file = $ref->path;
		self::$current_class = $ref->name;
		foreach($ref->methods as $name => $m){
			self::$current_method = $name;
			if($method === null || $method == $name){
				self::execute($m,$block_name);
			}
		}
		self::$current_class = null;
		self::$current_file = null;
		self::$current_method = null;
		return new self();
	}
	final private static function execute(stdClass $m,$block_name){
		if(empty($m->test) && $m->public && $m->name[0] !== "_") return self::$result[self::$current_file][self::$current_class][self::$current_method][$m->line][] = array("none");
		$result = $line = "";
		try{
			foreach($m->test as $line => $test){
				if($block_name === null || $test->name === $block_name){
					ob_start();
						eval(str_repeat("\n",$line).$test->block);
					$result = ob_get_clean();
				}
			}
		}catch(Exception $e){
			if(ob_get_level() > 0) $result = ob_get_clean();
			self::$result[self::$current_file][self::$current_class][self::$current_method][$line][] = array(null,(string)$e);
			Log::warning($e);
		}
		print($result);
	}
	final static private function expvar($var){
		if(is_numeric($var)) return strval($var);
		if(is_object($var)) $var = get_object_vars($var);
		if(is_array($var)){
			foreach($var as $key => $v){
				$var[$key] = self::expvar($v);
			}
		}
		return $var;
	}
	/**
	 * 判定を行う
	 * @param mixed $arg1
	 * @param mixed $arg2
	 * @param boolean $eq
	 * @param int $line
	 * @param string $file
	 * @return boolean
	 */
	final public static function equals($arg1,$arg2,$eq,$line,$file=null){
		$result = ($eq) ? (self::expvar($arg1) === self::expvar($arg2)) : (self::expvar($arg1) !== self::expvar($arg2));
		self::$result[((empty(self::$current_file)) ? $file : self::$current_file)][self::$current_class][self::$current_method][$line][] = ($result) ? array() : array(var_export($arg1,true),var_export($arg2,true));
		if(self::$each_flush) print(new Test());
		return $result;
	}
	/**
	 * ユニークな名前でクラスを作成する
	 * @param mixed $name
	 * @param string $src
	 * @param string $extclass
	 */
	public static function uniqClass(&$name,$src,$extclass=null){
		$name = uniqid("C").uniqid("");
		if($extclass === null) $extclass = "Object";
		eval("class ".$name." extends ".$extclass."{\n".$src."\n}");
	}
	protected function __str__(){
		$result = "";
		$tab = "  ";

		foreach(self::$result as $file => $f){
			foreach($f as $class => $c){
				$result .= (empty($class) ? "*****" : $class)." [ ".$file." ]\n";
				$result .= str_repeat("-",80)."\n";

				foreach($c as $method => $m){
					foreach($m as $line => $r){
						foreach($r as $l){
							switch(sizeof($l)){
								case 0:
									if(substr(self::$EXEC_TYPE,-2,1) != "1") break;
									$result .= "[".$line."]".$method.": success\n";
									break;
								case 1:
									if(substr(self::$EXEC_TYPE,-3,1) != "1") break;
									$result .= "[".$line."]".$method.": none\n";
									break;
								case 2:
									if(substr(self::$EXEC_TYPE,-4,1) != "1") break;
									$result .= "[".$line."]".$method.": ".(empty($l) ? "success" : "fail")."\n";
									$result .= $tab.str_repeat("=",70)."\n";

									ob_start();
										var_dump($l[0]);
										$result .= $tab.str_replace("\n","\n".$tab,ob_get_contents());
									ob_end_clean();
									$result .= "\n".$tab.str_repeat("=",70)."\n";

									ob_start();
										var_dump($l[1]);
										$result .= $tab.str_replace("\n","\n".$tab,ob_get_contents());
									ob_end_clean();
									$result .= "\n".$tab.str_repeat("=",70)."\n";
									break;
							}
						}
					}
				}
			}
			$result .= "\n";
		}
		Test::clear();
		return $result;
	}
	/**
	 * テンポラリファイルを作成する
	 * デストラクタで削除される
	 * @param string $path
	 * @param string $body
	 */
	static public function ftmp($path,$body){
		$path = File::absolute(Rhaco::def("ext.Test@ftmp"),$path);
		File::write($path,Text::plain($body));
	}
	/**
	 * テンポラリファイルを保存するパスを返す
	 * @return string
	 */
	static public function ftpath(){
		if(self::$ftmp == null) throw new Exception("undef ftmp");
		return self::$ftmp;
	}
	/**
	 * テンポラリテーブルを作成する
	 * @param string $db
	 * @param string $name
	 * @param array $columns array("カラム名"=>"型",....)
	 */
	static public function create_tmp_table($db,$name,array $columns){
		if(isset(self::$TMP_DB[$db][$name])) throw new Exception($name." already exists");
		$con = Db::connection($db);
		try{
			$con->drop_table($name);
		}catch(LogicException $e){}
		$con->create_table($name,$columns);
		$con->commit();
		self::$TMP_DB[$db][$name] = $columns;
	}
	static public function __import__(){
		self::exec_type(self::SUCCESS|self::FAIL|self::NONE);
		self::$ftmp = Rhaco::def("ext.Test@ftmp",Rhaco::work("tmp"));
		if(self::$ftmp !== null && is_dir(self::ftpath())){
			foreach(File::dirs(self::ftpath()) as $dir) File::rm($dir);
		}
	}
	static public function __funcs__(){
		/**
		 * パッケージテストを実行する
		 * @param string $path
		 */
		function tests($path){
			Test::verifies($path);
			print(new Test());
			Test::clear();
		}
		/**
		 * 単体テストを実行する
		 * @param string $class
		 * @param string $method
		 * @param string $block_name
		 */
		function test($class,$method=null,$block_name=null){
			Test::verify($class,$method,$block_name);
			print(new Test());
			Test::clear();
		}
		/**
		 * Test::uniqClassのエイリアス
		 */
		function uc(&$name,$src=null,$extclass=null){
			Test::uniqClass($name,$src,$extclass);
		}
		/**
		 * $resultが$expectationが同じである事を検証する
		 * @param mixed $expectation なるべき値
		 * @param mixed $result テスト対象の値
		 */
		function eq($expectation,$result){
			list($debug) = debug_backtrace();
			return Test::equals($expectation,$result,true,$debug["line"],$debug["file"]);
		}
		/**
		 * $resultが$expectationが同じではない事を検証する
		 * @param mixed $expectation ならないはずの値
		 * @param mixed $result テスト対象の値
		 */
		function neq($expectation,$result){
			list($debug) = debug_backtrace();
			return Test::equals($expectation,$result,false,$debug["line"],$debug["file"]);
		}
		/**
		 * Test::ftmpのエイリアス
		 */
		function ftmp($path,$body){
			Test::ftmp($path,$body);
		}
		/**
		 * Test::ftpathのエイリアス
		 */
		function ftpath(){
			return Test::ftpath();
		}
		/**
		 * Test::create_tmp_tableのエイリアス
		 */
		function create_tmp_table($db,$name,array $columns){
			Test::create_tmp_table($db,$name,$columns);
		}
	}
	static public function __shutdown__(){
		if(self::$ftmp !== null && is_dir(self::ftpath())){
			foreach(File::dirs(self::ftpath()) as $dir) File::rm($dir);
		}
		if(!empty(self::$TMP_DB)){
			foreach(self::$TMP_DB as $db => $table){
				$dobj = Db::connection($db);
				foreach($table as $name => $value){
					$dobj->drop_table($name);
					$dobj->commit();
				}
			}
		}
	}
}
?>