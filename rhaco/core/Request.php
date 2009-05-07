<?php
Rhaco::import("core.File");
/**
 * リクエストを処理する
 *
 * @author Kazutaka Tokushima
 * @license New BSD License
 */
class Request extends Object{
	static protected $__user__ = "type=variable";
	static protected $__vars__ = "type=variable{}";
	static protected $__sessions__ = "type=variable{}";
	static protected $__files__ = "type=File[]";
	static protected $__args__ = "type=string";
	protected $vars = array();
	protected $sessions = array();
	protected $files = array();
	protected $args;
	protected $user;
	private $loginId;
	private $expire = 1209600;

	static public function __import__(){
		/** (none/nocache/private/private_no_expire/public) */
		session_cache_limiter(Rhaco::def("core.Request@limiter","nocache"));
		session_cache_expire(Rhaco::def("core.Request@expire",2592000));
		session_start();
	}
	protected function setSessions($key,$value){
		$this->sessions[$key] = $value;
		$_SESSION[$key] = $value;
	}
	protected function __del__(){
		foreach($_SESSION as $key => $value){
			if(!isset($_SESSION[$key])) unset($_SESSION[$key]);
		}
	}
	final protected function setFiles($key,$req){
		$file = new File($req["name"]);
		$file->tmp(isset($req["tmp_name"]) ? $req["tmp_name"] : "");
		$file->size(isset($req["size"]) ? $req["size"] : "");
		$file->error($req["error"]);
		$this->files[$key] = $file;
	}
	final public function write($name,$expire=1209600){
		setcookie($name,$this->inVars($name),time() + $expire);
	}
	final public function delete($name){
		setcookie($name,false,time() - 3600);
	}
	protected function __new__($dict=null){
		if(isset($_FILES) && is_array($_FILES)){
			foreach($_FILES as $key => $files) $this->files($key,$files);
		}
		if(isset($_SESSION) && is_array($_SESSION)){
			foreach($_SESSION as $key => $value) $this->sessions[$key] = $this->mq_off($value);
		}
		if(isset($_COOKIE) && is_array($_COOKIE)){
			foreach($_COOKIE as $key => $value){
				$this->sessions[$key] = $this->mq_off($value);
				$this->vars[$key] = $this->mq_off($value);
			}
		}
		if(isset($_GET) && is_array($_GET)){
			foreach($_GET as $key => $value) $this->vars[$key] = $this->mq_off($value);
		}
		if(isset($_POST) && is_array($_POST)){
			foreach($_POST as $key => $value) $this->vars[$key] = $this->mq_off($value);
		}
		if(empty($this->vars) && isset($_SERVER["argv"])){
			$argv = $_SERVER["argv"];
			array_shift($argv);
			if(isset($argv[0]) && $argv[0][0] != "-") $this->args = array_shift($argv);
			$size = sizeof($argv);
			for($i=0;$i<$size;$i++){
				if($argv[$i][0] == "-" && isset($argv[$i+1]) && $argv[$i+1][0] != "-"){
					$this->vars[substr($argv[$i],1)] = $argv[$i+1];
				}
			}
		}
		if("" != ($pathinfo = (array_key_exists("PATH_INFO",$_SERVER)) ?
			( (empty($_SERVER["PATH_INFO"]) && array_key_exists("ORIG_PATH_INFO",$_SERVER)) ?
					$_SERVER["ORIG_PATH_INFO"] : $_SERVER["PATH_INFO"] ) : $this->inVars("pathinfo"))
		){
			if($pathinfo[0] != "/") $pathinfo = "/".$pathinfo;
			$this->args = preg_replace("/(.*?)\?.*/","\\1",$pathinfo);
		}
		parent::__new__($dict);
	}
	protected function __cp__($obj){
		if($obj instanceof Object){
			foreach($obj->access_members() as $name => $value) $this->vars($name,$value);
		}else if(is_array($obj)){
			$this->vars = array_merge($this->vars,$obj);
		}else{
			throw new InvalidArgumentException("cp");
		}
	}
	/**
	 * POSTされたか
	 * @return boolean
	 */
	public function isPost(){
		return (isset($_SERVER["REQUEST_METHOD"]) && $_SERVER["REQUEST_METHOD"] == "POST");
	}
	private function mq_off($value){
		return (get_magic_quotes_gpc() && is_string($value)) ? stripslashes($value) : $value;
	}
	protected function setUser($arg){
		$_SESSION[$this->loginId."USER"] = $arg;
	}
	/**
	 * ログインする
	 * @return boolean
	 */
	public function login(){
		if($this->isLogin()) return true;
		if($this->call_modules("condition",$this) === false){
			$this->call_modules("invalid",$this);
			return false;
		}
		$_SESSION[$this->loginId] = session_id();
		$this->call_modules("after",$this);
		return true;
	}
	/**
	 * ログイン済みか
	 * @return boolean
	 */
	public function isLogin(){
		return isset($_SESSION[$this->loginId]);
	}
	/**
	 * ログインする
	 * @return mixed
	 */
	public function silent(){
		if($this->isLogin()) return true;
		return $this->call_modules("condition",$this);
	}
	/**
	 * ログアウトする
	 */
	public function logout(){
		$this->call_modules("before_logout",$this);
		unset($_SESSION[$this->loginId],$_SESSION[$this->loginId."USER"]);
		setcookie($this->loginId,"",time() - 3600);
	}
}
?>