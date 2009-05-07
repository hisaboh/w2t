<?php
Rhaco::import("core.Log");
Rhaco::import("core.Lib");
/**
 * @author Kazutaka Tokushima
 * @license New BSD License
 */
class Db extends Object implements Iterator{
	static private $SESSION = array();
	static private $is_init = false;
	static protected $__resultset__ = "type=string[]";
	static protected $__port__ = "type=number";
	static protected $__upper__ = "type=boolean";
	static protected $__lower__ = "type=boolean";
	protected $type;
	protected $host;
	protected $dbname;
	protected $user;
	protected $password;
	protected $port;
	protected $sock;
	protected $prefix;
	protected $lower;
	protected $upper;
	protected $con;

	private $connection;
	private $statement;
	private $resultset;
	private $resultset_counter;

	protected function __new__(){
		if(func_num_args() == 1) $this->dict(func_get_arg(0));
		if(!empty($this->con)){
			$db = self::connection($this->con);
			$this->type = $db->type;
			$this->host = $db->host;
			$this->dbname = $db->dbname;
			$this->user = $db->user;
			$this->password = $db->password;
			$this->port = $db->port;
			$this->sock = $db->sock;
			$this->connection = $db->connection;
			$this->copy_module($db,true);
		}else{
			try{
				$type = ucfirst(strtolower($this->type));
				Rhaco::import("db.controller.".$type);
			}catch(Exception $e){
				try{
					Lib::import($this->type);
					$type = preg_replace("/^.+\.([^\.]+)$/","\\1",$this->type);
				}catch(Exception $e){
					throw new Exception("not support ".$this->type);
				}
			}
			$this->type = $type;
			$this->add_modules(new $type());
			try{
				$this->connection = new PDO($this->call_module("dsn",$this->dbname,$this->host,$this->port,$this->user,$this->password,$this->sock),$this->user,$this->password);
			}catch(Exception $e){
				throw new Exception("access denied for ".$this->dbname);
			}
			$this->connection->beginTransaction();
		}
	}
	protected function __del__(){
		if($this->connection !== null && $this->con !== null) $this->connection->rollBack();
	}
	/**
	 * 接続先を取得する
	 * @param string $name
	 * @return Db
	 */
	static public function connection($name=null){
		if(empty($name)){
			if(empty(self::$SESSION)) self::search_connections();
			reset(self::$SESSION);
			$name = key(self::$SESSION);
			if(empty($name)) throw new Exception("not found connection");
		}
		if(isset(self::$SESSION[$name])) return self::$SESSION[$name];
		if(Rhaco::def("db.Db@".$name) !== null) return self::$SESSION[$name] = new self(Rhaco::def("db.Db@".$name));
		throw new Exception("connection fail db.Db@".$name);
	}
	static public function connections(){
		if(empty(self::$SESSION)) self::search_connections();
		return self::$SESSION;
	}
	static private function search_connections(){
		foreach(Rhaco::constants("db.Db@") as $key => $dsn){
			if(isset($key[6])) self::connection(substr($key,6));
		}
		if(empty(self::$SESSION)) throw new Exception("undef connection");		
	}
	/**
	 * テーブルを作成する
	 * @param string $name
	 * @param array $columns array("カラム名"=>"型",....)
	 */
	public function create_table($name,array $columns){
		$this->query($this->call_module("create_table",$name,$columns));
	}
	/**
	 * テーブルを削除する
	 * @param string $name
	 */
	public function drop_table($name){
		$this->query($this->call_module("drop_table",$name));
	}
	/**
	 * テーブル名の一覧を取得する
	 * @return array
	 */
	public function tables(){
		$tables = array();
		$sql = $this->call_module("show_tables",$this->dbname);
		$statement = $this->prepare($sql->sql);
		$statement->execute();
		$errors = $statement->errorInfo();
		if(sizeof($errors) == 3) throw new LogicException("[".$errors[1]."] ".$errors[2]);
		while($result = $statement->fetch(PDO::FETCH_ASSOC)){
			$tables[$result[$sql->field_name]] = $result[$sql->field_name];
		}
		return $tables;
	}

	/**
	 * コミットする
	 */
	public function commit(){
		Log::debug("commit");
		$this->connection->commit();
		$this->connection->beginTransaction();
	}
	/**
	 * ロールバックする
	 */
	public function rollback(){
		Log::debug("rollback");
		$this->connection->rollBack();
		$this->connection->beginTransaction();
	}
	/**
	 * 文を実行する準備を行う
	 * @param string $sql
	 * @return PDOStatement
	 */
	public function prepare($sql){
		Log::debug($sql);
		return $this->connection->prepare($sql);
	}
	/**
	 * SQL ステートメントを実行する
	 * @param string $sql 実行するSQL
	 * @param array $vars プリペアドステートメントへセットする値
	 */
	public function query($sql,array $vars=array()){
		$this->statement = $this->prepare($sql);
		if($this->statement === false) throw new LogicException($sql);
		$this->statement->execute($vars);
		$errors = $this->statement->errorInfo();
		if(sizeof($errors) == 3) throw new LogicException("[".$errors[1]."] ".$errors[2]." : ".$sql);
		/***
			create_tmp_table("test_1","test_db_query",array("id"=>"serial","value"=>"string"));
			$db = Db::connection("test_1");
			$db->query("insert into test_db_query(value) value(?)",array("abcedf"));
			$db->query("insert into test_db_query(value) value(?)",array("ghijklm"));

			$db->query("select value from test_db_query");
			$list = array("abcedf","ghijklm");
			$i = 0;
			while($result = $db->next_result()){
				eq($list[$i],$result["value"]);
				$i++;
			}

			$db->query("select value from test_db_query");
			$list = array("abcedf","ghijklm");
			foreach($db as $key => $result){
				eq($list[$key],$result["value"]);
			}
		 */
	}
	/**
	 * 直前に実行したSQL ステートメントに値を変更して実行する
	 * @param array $vars プリペアドステートメントへセットする値
	 */
	public function re(array $vars=array()){
		if(!isset($this->statement)) throw new Exception();
		$this->statement->execute($vars);
		$errors = $this->statement->errorInfo();
		if(sizeof($errors) == 3) throw new Exception("[".$errors[1]."] ".$errors[2]);
	}
	/**
	 * 結果セットから次の行を取得する
	 * @param string $name 特定のカラム名
	 * @return string/arrray
	 */
	public function next_result($name=null){
		$this->resultset = $this->statement->fetch(PDO::FETCH_ASSOC);
		if($this->resultset !== false){
			if($name === null) return $this->resultset;
			return (isset($this->resultset[$name])) ? $this->resultset[$name] : null;
		}
		return null;
	}
	
	public function rewind(){
		$this->resultset_counter = 0;
		$this->resultset = $this->statement->fetch(PDO::FETCH_ASSOC);
	}
	public function current(){
		return $this->resultset;
	}
	public function key(){
		return $this->resultset_counter++;
	}
	public function valid(){
		return ($this->resultset !== false);
	}
	public function next(){
		$this->resultset = $this->statement->fetch(PDO::FETCH_ASSOC);
	}
}
?>