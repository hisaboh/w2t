<?php
Rhaco::import("core.Request");
Rhaco::import("core.Template");
Rhaco::import("core.Log");
Rhaco::import("core.Http");
Rhaco::import("core.File");
/**
 * リクエスト/テンプレートを処理する
 *
 * @author Kazutaka Tokushima
 * @license New BSD License
 */
class Flow extends Request{
	protected $_mixin_ = "templ=Template";
	protected $class;
	protected $method;
	protected $template;
	protected $name;
	static private $match_pattern;
	static private $request_url;
	static private $request_query;

	protected function __new__($dict=null){
		$this->dict($dict);
		parent::__new__();
		$this->templ->cp($this->vars);
		
		$port = isset($_SERVER["SERVER_PORT"]) ? $_SERVER["SERVER_PORT"] : 80;
		$server = isset($_SERVER["HTTP_HOST"]) ? $_SERVER["HTTP_HOST"] : (isset($_SERVER["SERVER_NAME"]) ? $_SERVER["SERVER_NAME"] : "");
		$path = isset($_SERVER["REQUEST_URI"]) ? $_SERVER["REQUEST_URI"] : "";
		if(preg_match("/^(.*?)(\?.*)$/",$path,$match)) list(,$path,self::$request_query) = $match;
		self::$request_url = (($port === 443) ? "https" : "http")."://".$server.$path;
	}
	protected function __cp__($obj){
		$this->templ->cp($obj);
	}
	protected function setTemplate($value){
		$this->template = $value;
		$this->templ->filename($value);
	}
	public function template_exsist($path){
		return $this->templ->isFilename($path);
	}
	/**
	 * phpinfoからattachする
	 * @param string $path
	 */
	public function attach_self($path){
		$this->url($_SERVER["PHP_SELF"]."/");
		if($this->args() != null){
			Log::disable_display();
			Http::attach(new File($path.$this->args()));
			exit;
		}
	}
	/**
	 * ファイルからattachする
	 * @param string $path
	 */
	public function attach_file($path){
		if(!is_file($path)) throw new Exception("File not fount");
		Http::attach(new File($path));
		exit;
	}
	/**
	 * 文字列からattachする
	 * @param string $path
	 * @param string $filename
	 */
	public function attach_text($src,$filename=null){
		Http::attach(new File($filename,$src));
		exit;
	}
	/**
	 * 自身をリダイレクトする
	 */
	public function redirect_self($query=true){
		Http::redirect(self::request_url($query));
	}
	/**
	 * リクエストされたURLを返す
	 *
	 * @param boolean $query
	 * @return string
	 */
	static public function request_url($query=true){
		return self::$request_url.(($query) ? self::$request_query : "");
	}
	/**
	 * 指定済みのファイルから生成する
	 * @return string
	 */
	public function read($template=null,$template_name=null){
		if($template !== null) $this->template = $template;
		return $this->templ->read($this->template,$template_name);
	}
	/**
	 * printする
	 *
	 * @param string $filename
	 */
	public function output($template=null,$template_name=null){
		if($template !== null) $this->template = $template;
		$this->templ->output($this->template,$template_name);
	}
	/**
	 * handlerでマッチしたパターン、またはnameを返す
	 *
	 * @return string
	 */
	static public function match_pattern(){
		return self::$match_pattern;
	}
	final protected function handled(){
		$this->call_modules("flow_handled",$this);
	}
	final protected function verify(){
		$this->call_modules("flow_verify",$this);
		return true;
	}
	
	/**
	 * URLのパターンからTemplateを切り替える
	 * @param array $urlconf
	 */
	public function handler(array $urlconf=array()){
		$params = array();
		foreach($urlconf as $pattern => $conf){
			if(is_int($pattern)){
				$pattern = $conf;
				$conf = null;
			}
			if(preg_match("/".str_replace(array("\/","/","__SLASH__"),array("__SLASH__","\/","\/"),$pattern)."/",$this->args(),$params)){
				if($conf !== null){
					if(is_array($conf)){
						if(isset($conf["class"])) $this->class = $conf["class"];
						if(isset($conf["method"])) $this->method = $conf["method"];
						if(isset($conf["template"])) $this->template = $conf["template"];
						if(isset($conf["name"])) $this->name = $conf["name"];
					}else{
						$this->dict($conf);
					}
				}
				self::$match_pattern = (empty($this->name)) ? $params[0] : $this->name;
				if(!empty($this->class)){
					if(false !== strrpos($this->class,".") || !class_exists($this->class)) $this->class = Rhaco::import($this->class);
					if(empty($this->method) && !empty($pattern)){
						$method_patterns = array();
						$patterns = explode("/",$pattern);
						if($patterns[0] == "^") array_shift($patterns);
						foreach($patterns as $p){
							if(!preg_match("/[\w_]/",$p)) break;
							$method_patterns[] = $p;
						}
						if(!empty($method_patterns)) $this->method = implode("_",$method_patterns);
					}
				}
				if(empty($this->method) && !empty($this->template)){
					$obj = new self();
					$obj->copy_module($this,true);
					$obj->template($this->template);
				}else{
					$method = (empty($this->method)) ? "index" : $this->method;
					if(!method_exists($this->class,$method)) throw new Exception("Not found ".$this->class."::".$method);
					array_shift($params);
					try{
						$class = $this->class;
						$action = new $class();
						$action->copy_module($this,true);
						if($action instanceof self) $action->handled();
						$obj = call_user_func_array(array($action,$method),$params);
					}catch(Exception $e){
						Log::debug($e);
						$on_error = Rhaco::def("core.Flow@on_error");
						if($on_error === null) throw $e;
						if(isset($on_error[0])) Http::status_header((int)$on_error[0]);
						if(isset($on_error[2])) Http::redirect($on_error[2]);
						if(isset($on_error[1])){
							$template = new Template();
							$template->output($on_error[1]);
						}
						exit;
					}
				}
				if($obj instanceof self) $obj = $obj->templ();
				if(!($obj instanceof Template)) throw new Exception("Forbidden ".$this->args());
				$obj->path($this->path());
				$obj->url($this->url());
				$this->templ = $obj;
				if(!$this->isTemplate()) $this->template($obj->filename());
				if(!$this->isTemplate()){
					$cs = explode(".",$this->class);
					$class = array_pop($cs);
					$class = implode("/",$cs).((!empty($cs)) ? "/" : "").strtolower($class[0]).substr($class,1);
					$this->template($class."/".$method.".html");
				}
				return $this;
			}
		}
		throw new Exception("no match pattern");
	}
	/**
	 * varsから指定のキーをdictにして返す
	 *
	 * @param string $name
	 * @return string
	 */
	public function to_dict($name){
		$result = null;
		if(is_array($this->inVars($name))){
			foreach($this->inVars($name) as $key => $value){
				$result .= $key."=".$value.",";
			}
		}
		return $result;
	}
}
?>