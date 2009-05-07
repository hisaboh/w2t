<?php
Rhaco::import("core.File");
Rhaco::import("core.Date");
Rhaco::import("core.Tag");
Rhaco::import("core.Text");
/**
 * HTTP関連処理
 *
 * @def code.Http::timeout 接続のタイムアウト(秒)
 * @author Kazutaka Tokushima
 * @license New BSD License
 */
class Http extends Object{
	static private $TIMEOUT;
	static private $AGENT;
	static private $LANG;
	static protected $__vars__ = "type=variable{}";
	static protected $__header__ = "type=string{}";
	static protected $__status_redirect__ = "type=boolean";

	static protected $__status__ = "type=number,set=false";
	static protected $__body__ = "type=string,set=false";
	static protected $__head__ = "type=string,set=false";
	static protected $__url__ = "type=string,set=false";

	public $vars = array();
	private $user;
	private $password;

	protected $body;
	protected $head;
	protected $url;
	protected $status = 200;
	protected $encode;
	protected $status_redirect = true;
	private $form = array();

	protected $agent;
	protected $raw;
	protected $cmd;
	protected $header = array();
	private $cookie = array();

	protected $_base_url_;
	protected $_api_key_;
	protected $_api_key_name_ = "api_key";

	static public function __import__(){
		self::$TIMEOUT = Rhaco::def("core.Http@timeout",5);
		self::$AGENT = Rhaco::def("core.Http@agent");
		self::$LANG = Rhaco::def("core.Http@lang","ja-jp");
	}
	/**
	 * URLが有効かを調べる
	 *
	 * @param string $url
	 * @return boolean
	 */
	static public function is_url($url){
		try{
			$result = self::request($url,"HEAD",array(),array(),null,false);
			return ($result->status === 200);
		}catch(Exception $e){}
		return false;
	}
	/**
	 * ヘッダ情報をハッシュで取得する
	 *
	 * @return array
	 */
	public function explode_head(){
		$result = array();
		foreach(explode("\n",$this->head) as $h){
			if(preg_match("/^(.+?):(.+)$/",$h,$match)) $result[trim($match[1])] = trim($match[2]);
		}
		return $result;
	}
	/**
	 * URL情報を返す
	 *
	 * @param string $url
	 * @param string $base_url
	 * @return Object(url,full_url,scheme,host,port,path,fragment,query)
	 */
	static public function parse_url($url,$base_url=null){
		$furl = (!empty($base_url)) ? File::absolute($base_url,$url) : $url;
		$parse_url = parse_url($furl);
		$result = new Object(array(
								"url"=>$url,
								"full_url"=>$furl,
								"scheme"=>(isset($parse_url["scheme"]) ? $parse_url["scheme"] : "http"),
								"host"=>(isset($parse_url["host"]) ? $parse_url["host"] : null),
								"port"=>(isset($parse_url["port"]) ? $parse_url["port"] : 80),
								"path"=>(isset($parse_url["path"]) ? $parse_url["path"] : "/"),
								"fragment"=>(isset($parse_url["fragment"]) ? $parse_url["fragment"] : null),
								"query"=>array(),
							));
		if(isset($parse_url["query"])){
			foreach(explode("&",$parse_url["query"]) as $q){
				$key_value = explode("=",$q,2);
				if(sizeof($key_value) == 1) $key_value = array($key_value[0],null);
				list($key,$value) = $key_value;
				$result->query[$key] = $value;
			}
		}
		return $result;
	}
	/**
	 * API keyをセットする
	 *
	 * @param string $api_key
	 */
	protected function set_api_key($api_key){
		if(isset($api_key)) $this->_api_key_ = $api_key;
	}
	private function build_url($url){
		if($this->_api_key_ !== null) $this->vars($this->_api_key_name_,$this->_api_key_);
		if($this->_base_url_ !== null) return File::absolute($this->_base_url_,$url);
		return $url;
	}
	/**
	 * getでアクセスする
	 * @param string $url
	 * @param boolean $form
	 * @return this
	 */
	public function do_get($url=null,$form=true){
		return $this->browse($this->build_url($url),"GET",$form);
	}
	/**
	 * postでアクセスする
	 * @param string $url
	 * @param boolean $form
	 * @return this
	 */
	public function do_post($url=null,$form=true){
		return $this->browse($this->build_url($url),"POST",$form);
	}
	/**
	 * ダウンロードする
	 *
	 * @param string $url
	 * @param string $download_path
	 * @return this
	 */
	public function do_download($url=null,$download_path){
		return $this->browse($this->build_url($url),"GET",false,$download_path);
	}
	/**
	 * HEADでアクセスする formの取得はしない
	 * @param string $url
	 * @return this
	 */
	public function do_head($url=null){
		return $this->browse($this->build_url($url),"HEAD",false);
	}
	/**
	 * PUTでアクセスする
	 * @param string $url
	 * @return this
	 */
	public function do_put($url=null){
		return $this->browse($this->build_url($url),"PUT",false);
	}
	/**
	 * DELETEでアクセスする
	 * @param string $url
	 * @return this
	 */
	public function do_delete($url=null){
		return $this->browse($this->build_url($url),"DELETE",false);
	}
	/**
	 * 更新状態を取得する
	 * @param string $url
	 * @param int $time
	 * @return string
	 */
	public function do_modified($url=null,$time){
		$this->header("If-Modified-Since",date("r",$time));
		return $this->browse($this->build_url($url),"GET",false)->body();
	}
	/**
	 * Basic認証
	 * @param string $user
	 * @param string $password
	 */
	public function auth($user,$password){
		$this->user = $user;
		$this->password = $password;
	}
	/**
	 * WSSE認証
	 * @param string $user
	 * @param string $password
	 */
	public function wsse($user,$password){
		$nonce = sha1(md5(time().rand()),true);
		$created = Date::format_atom(time());
		$this->header("X-WSSE",sprintf("UsernameToken Username=\"%s\", PasswordDigest=\"%s\", Nonce=\"%s\", Created=\"%s\"",
					$user,base64_encode(sha1($nonce.$created.$password,true)),base64_encode($nonce),$created));
	}
	private function browse($url,$method,$form=true,$download_path=null){
		$cookies = "";
		$variables = "";
		$headers = $this->header;
		$cookie_base_domain = preg_replace("/^[\w]+:\/\/(.+)$/","\\1",$url);

		foreach($this->cookie as $domain => $cookie_value){
			if(strpos($cookie_base_domain,$domain) === 0){
				foreach($cookie_value as $name => $value) $cookies .= sprintf("%s=%s; ",$name,$value["value"]);
			}
		}
		if(!empty($cookies)) $headers["Cookie"] = $cookies;
		if(!empty($this->agent)) $headers["User-Agent"] = $this->agent;
		if(!empty($this->user)){
			if(preg_match("/^([\w]+:\/\/)(.+)$/",$url,$match)){
				$url = $match[1].$this->user.":".$this->password."@".$match[2];
			}else{
				$url = "http://".$this->user.":".$this->password."@".$url;
			}
		}
		if($this->isRaw()) $headers["rawdata"] = $this->raw();
		$result = self::request($url,$method,$headers,$this->vars,$download_path,$this->status_redirect);
		$this->cmd = $result->cmd;
		$this->head = $result->head;
		$this->url = $result->url;
		$this->status = $result->status;
		$this->encode = $result->encode;
		$this->body = ($this->encode !== null) ? mb_convert_encoding($result->body,"UTF-8",$this->encode) : $result->body;
		$this->form = array();

		if(preg_match_all("/Set-Cookie:[\s]*(.+)/i",$this->head,$match)){
			$unsetcookie = $setcookie = array();
			foreach($match[1] as $cookies){
				$cookie_name = $cookie_value = $cookie_expires = $cookie_domain = $cookie_path = null;
				$cookie_domain = $cookie_base_domain;
				$cookie_path = "/";

				foreach(explode(";",$cookies) as $cookie){
					$cookie = trim($cookie);
					if(strpos($cookie,"=") !== false){
						list($name,$value) = explode("=",$cookie,2);
						$name = trim($name);
						$value = trim($value);
						switch(strtolower($name)){
							case "expires": $cookie_expires = ctype_digit($value) ? (int)$value : strtotime($value); break;
							case "domain": $cookie_domain = preg_replace("/^[\w]+:\/\/(.+)$/","\\1",$value); break;
							case "path": $cookie_path = $value; break;
							default:
								$cookie_name = $name;
								$cookie_value = $value;
						}
					}else if(!empty($cookie)){
						$cookie_expires = ctype_digit($cookie) ? (int)$cookie : strtotime($cookie);
					}
				}
				$cookie_domain = substr(File::absolute("http://".$cookie_domain,$cookie_path),7);
				if($cookie_expires !== null && $cookie_expires < time()){
					if(isset($this->cookie[$cookie_domain][$cookie_name])) unset($this->cookie[$cookie_domain][$cookie_name]);
				}else{
					$this->cookie[$cookie_domain][$cookie_name] = array("value"=>$cookie_value,"expires"=>$cookie_expires);
				}
			}
		}
		if($form) $this->parse_form();
		if($this->status_redirect && Tag::setof($tag,$result->body,"head")){
			foreach($tag->in("meta") as $meta){
				if(strtolower($meta->inParam("http-equiv")) == "refresh"){
					if(preg_match("/^[\d]+;url=(.+)$/i",$meta->inParam("content"),$refresh)){
						$this->vars = array();
						return $this->do_get(File::absolute(dirname($url),$refresh[1]));
					}
				}
			}
		}
		$this->vars = array();
		return $this;
	}
	private function parse_form(){
		$tag = Tag::anyhow($this->body);
		foreach($tag->in("form") as $key => $formtag){
			$form = new stdClass();
			$form->name = $formtag->inParam("name",$formtag->inParam("id",$key));
			$form->action = File::absolute($this->url,$formtag->inParam("action",$this->url));
			$form->method = strtolower($formtag->inParam("method","get"));
			$form->multiple = false;
			$form->element = array();

			foreach($formtag->in("input") as $count => $input){
				$obj = new stdClass();
				$obj->name = $input->inParam("name",$input->inParam("id","input_".$count));
				$obj->type = strtolower($input->inParam("type","text"));
				$obj->value = Text::htmldecode($input->inParam("value"));
				$obj->selected = ("selected" === strtolower($input->inParam("checked",$input->inAttr("checked"))));
				$obj->multiple = false;
				$form->element[] = $obj;
			}
			foreach($formtag->in("textarea") as $count => $input){
				$obj = new stdClass();
				$obj->name = $input->inParam("name",$input->inParam("id","textarea_".$count));
				$obj->type = "textarea";
				$obj->value = Text::htmldecode($input->value());
				$obj->selected = true;
				$obj->multiple = false;
				$form->element[] = $obj;
			}
			foreach($formtag->in("select") as $count => $input){
				$obj = new stdClass();
				$obj->name = $input->inParam("name",$input->inParam("id","select_".$count));
				$obj->type = "select";
				$obj->value = array();
				$obj->selected = true;
				$obj->multiple = ("multiple" == strtolower($input->param("multiple",$input->attr("multiple"))));

				foreach($input->in("option") as $count => $option){
					$op = new stdClass();
					$op->value = Text::htmldecode($option->inParam("value",$option->value()));
					$op->selected = ("selected" == strtolower($option->inParam("selected",$option->inAttr("selected"))));
					$obj->value[] = $op;
				}
				$form->element[] = $obj;
			}
			$this->form[] = $form;
		}
	}
	/**
	 * formをsubmitする
	 * @param string $form
	 * @param string $submit
	 * @return this
	 */
	public function submit($form=0,$submit=null){
		foreach($this->form as $key => $f){
			if($f->name === $form || $key === $form){
				$form = $key;
				break;
			}
		}
		if(isset($this->form[$form])){
			$inputcount = 0;
			$onsubmit = ($submit === null);

			foreach($this->form[$form]->element as $element){
				switch($element->type){
					case "hidden":
					case "textarea":
						if(!array_key_exists($element->name,$this->vars)){
							$this->vars($element->name,$element->value);
						}
						break;
					case "text":
					case "password":
						$inputcount++;
						if(!array_key_exists($element->name,$this->vars)) $this->vars($element->name,$element->value); break;
						break;
					case "checkbox":
					case "radio":
						if($element->selected !== false){
							if(!array_key_exists($element->name,$this->vars)) $this->vars($element->name,$element->value);
						}
						break;
					case "submit":
					case "image":
						if(($submit === null && $onsubmit === false) || $submit == $element->name){
							$onsubmit = true;
							if(!array_key_exists($element->name,$this->vars)) $this->vars($element->name,$element->value);
							break;
						}
						break;
					case "select":
						if(!array_key_exists($element->name,$this->vars)){
							if($element->multiple){
								$list = array();
								foreach($element->value as $option){
									if($option->selected) $list[] = $option->value;
								}
								$this->vars($element->name,$list);
							}else{
								foreach($element->value as $option){
									if($option->selected){
										$this->vars($element->name,$option->value);
									}
								}
							}
						}
						break;
					case "button":
						break;
				}
			}
			if($onsubmit || $inputcount == 1){
				return ($this->form[$form]->method == "post") ?
							$this->browse($this->form[$form]->action,"POST") :
							$this->browse($this->form[$form]->action,"GET");
			}
		}
		return false;
	}
	/**
	 * リファラを取得する
	 *
	 * @return string
	 */
	static public function referer(){
		return (isset($_SERVER["HTTP_REFERER"]) && strpos($_SERVER["HTTP_REFERER"],"://") !== false) ? $_SERVER["HTTP_REFERER"] : $_SERVER["HTTP_HOST"];
	}
	/**
	 * rawdataを取得する
	 * @return string
	 */
	static public function rawdata(){
		return file_get_contents("php://input");
	}
	static private function request($url,$method,array $header=array(),array $vars=array(),$download_path=null,$status_redirect=true){
		$result = (object)array("url"=>$url,"status"=>200,"head"=>null,"body"=>null,"encode"=>null,"cmd"=>null);
		$h = array_change_key_case($header,CASE_LOWER);
		$raw = null;
		$content_type = isset($h["content-type"]) ? $h["content-type"] : "application/x-www-form-urlencoded";
		$user_agent = isset($h["user-agent"]) ? $h["user-agent"] : (empty(self::$AGENT) ? (isset($_SERVER["HTTP_USER_AGENT"]) ? $_SERVER["HTTP_USER_AGENT"] : "") : self::$AGENT);
		$accept = isset($h["accept"]) ? $h["accept"] : (isset($_SERVER["HTTP_ACCEPT"]) ? $_SERVER["HTTP_ACCEPT"] : "");
		$accept_language = isset($h["accept-language"]) ? $h["accept-language"] : (isset($_SERVER["HTTP_ACCEPT_LANGUAGE"]) ? $_SERVER["HTTP_ACCEPT_LANGUAGE"] : "");
		$accept_charset = isset($h["accept-charset"]) ? $h["accept-charset"] : (isset($_SERVER["HTTP_ACCEPT_CHARSET"]) ? $_SERVER["HTTP_ACCEPT_CHARSET"] : "");
		unset($h["content-type"],$h["user-agent"],$h["accept"],$h["accept-language"],$h["accept-charset"]);

		if(isset($h["rawdata"])){
			$raw = $h["rawdata"];
			unset($h["rawdata"]);
		}else{
			$files = array();
			foreach($vars as $key => $var){
				if($var instanceof File){
					$files[$key] = $var;
					unset($vars[$key]);
				}
			}
			if(!empty($vars) && strtoupper($method) == "GET"){
				$url = (strpos($url,"?") === false) ? $url."?" : $url."&";
				$url .= self::query($vars);
				if(substr($url,-1) === "&") $url = substr($url,0,-1);
			}
			if(empty($files)){
				if($method == "POST"){
					$raw = self::query($vars);
					if(substr($raw,-1) === "&") $raw = substr($raw,0,-1);
				}
			}else{
				$boundary = "-----------------".md5(microtime());
				$content_type = "multipart/form-data;  boundary=".$boundary;
				$raws = array();

				foreach($vars as $key => $var){
					$raws[] = sprintf('Content-Disposition: form-data; name="%s"',$key)
								."\r\n\r\n"
								.$var
								."\r\n";
				}
				foreach($files as $key => $f){
					$raws[] = sprintf('Content-Disposition: form-data; name="%s"; filename="%s"',$key,$f->name())
								."\r\n".sprintf('Content-Type: %s',$f->mime())
								."\r\n".sprintf('Content-Transfer-Encoding: %s',"binary")
								."\r\n\r\n"
								.$f->get()
								."\r\n";
				}
				$raw = "--".$boundary."\r\n".implode("--".$boundary."\r\n",$raws)."\r\n--".$boundary."--\r\n"."\r\n";
			}
		}
		$ulist = parse_url(preg_match("/^([\w]+:\/\/)(.+?):(.+)(@.+)$/",$url,$m) ? ($m[1].urlencode($m[2]).":".urlencode($m[3]).$m[4]) : $url);
		$ssl = (isset($ulist["scheme"]) && ($ulist["scheme"] == "ssl" || $ulist["scheme"] == "https"));
		try{
			$fp	= fsockopen((($ssl) ? "ssl://" : "").$ulist["host"],((!isset($ulist["port"])) ? (($ssl) ? 443 : 80) : $ulist["port"]),$errorno,$errormsg,self::$TIMEOUT);
		}catch(Exception $e){
			throw new Exception("connection fail ".$url);
		}
		if($fp == false || false == stream_set_blocking($fp,1) || false == stream_set_timeout($fp,self::$TIMEOUT)) throw new Exception("connection fail [".$url."] ".$errormsg." ".$errorno);
		$cmd = sprintf("%s %s%s HTTP/1.1\r\n",$method,((!isset($ulist["path"])) ? "/" : $ulist["path"]),(isset($ulist["query"])) ? sprintf("?%s",$ulist["query"]) : "")
				.sprintf("Host: %s\r\n",$ulist["host"])
				.(empty($user_agent) ? "" : sprintf("User-Agent: %s\r\n",$user_agent))
				.(empty($accept) ? "" : sprintf("Accept: %s\r\n",$accept))
				.(empty($accept_language) ? "" : sprintf("Accept-Language: %s\r\n",$accept_language))
				.(empty($accept_charset) ? "" : sprintf("Accept-Charset: %s\r\n",$accept_charset))
				.sprintf("Content-Type: %s\r\n",$content_type)
				.sprintf("Connection: Close\r\n")
				.((isset($ulist["user"]) && isset($ulist["pass"])) ? sprintf("Authorization: Basic %s\r\n",base64_encode(sprintf("%s:%s",urldecode($ulist["user"]),urldecode($ulist["pass"])))) : "")
				;
		foreach($h as $key => $value) $cmd .= sprintf("%s: %s\r\n",$key,$value);
		if(!empty($raw)) $cmd .= "Content-length: ".strlen($raw)."\r\n\r\n".$raw;
		fwrite($fp,($result->cmd = ($cmd."\r\n")));
		stream_set_timeout($fp,self::$TIMEOUT);
		while(!feof($fp) && !preg_match("/\r\n\r\n$/",$result->head)) $result->head .= fgets($fp,4096);
		$result->status = (preg_match("/HTTP\/.+[\040](\d\d\d)/i",$result->head,$httpCode)) ? intval($httpCode[1]) : 0;
		$result->encode = (preg_match("/Content-Type.+charset[\s]*=[\s]*([\-\w]+)/",$result->head,$match)) ? trim($match[1]) : null;
		$result->url = $url;

		switch($result->status){
			case 300:
			case 301:
			case 302:
			case 303:
			case 307:
				if($status_redirect && preg_match("/Location:[\040](.*)/i",$result->head,$redirect_url)){
					fclose($fp);
					if($method === "GET") $vars = array();
					return self::request(preg_replace("/[\r\n]/","",File::absolute($url,$redirect_url[1])),"GET",$h,$vars,$download_path,$status_redirect);
				}
		}
		$download_handle = ($download_path !== null && File::mkdir(dirname($download_path)) === null) ? fopen($download_path,"wb") : null;
		if(preg_match("/^Content\-Length:[\s]+([0-9]+)\r\n/i",$result->head,$m)){
			if(0 < ($length = $m[1])){
				$rest = $length % 4096;
				$count = ($length - $rest) / 4096;

				while(!feof($fp)){
					if($count-- > 0){
						self::write_body($result,$download_handle,fread($fp,4096));
					}else{
						self::write_body($result,$download_handle,fread($fp,$rest));
						break;
					}
				}
			}
		}else if(preg_match("/Transfer\-Encoding:[\s]+chunked/i",$result->head)){
			while(!feof($fp)){
				$size = hexdec(trim(fgets($fp,4096)));
				$buffer = "";

				while($size > 0 && strlen($buffer) < $size){
					$value = fgets($fp,$size);
					if($value === feof($fp)) break;
					$buffer .= $value;
				}
				self::write_body($result,$download_handle,substr($buffer,0,$size));
			}
		}else{
			while(!feof($fp)) self::write_body($result,$download_handle,fread($fp,4096));
		}
		fclose($fp);
		if($download_handle !== null) fclose($download_handle);
		return $result;
	}
	static private function write_body(&$result,&$download_handle,$value){
		if($download_handle !== null) return fwrite($download_handle,$value);
		return $result->body .= $value;
	}
	/**
	 * inlineで出力する
	 * @param File $file
	 * @param string $contentType
	 */
	static public function inline(File $file,$contentType=""){
		if(empty($contentType)) $contentType = "image/jpeg";
		header(sprintf("Content-Type: ".$contentType."; name=%s",$file->name()));
		header(sprintf("Content-Disposition: inline; filename=%s",$file->name()));
		if($file->size() > 0) header(sprintf("Content-length: %u",$file->size()));
		$file->output();
	}
	/**
	 * attachmentで出力する
	 * @param File $file
	 * @param string $contentType
	 */
	static public function attach(File $file,$contentType=""){
		if(empty($contentType)) $contentType = "application/octet-stream";
		header(sprintf("Content-Type: ".$contentType."; name=%s",$file->name()));
		header(sprintf("Content-Disposition: attachment; filename=%s",$file->name()));
		if($file->size() > 0) header(sprintf("Content-length: %u",$file->size()));
		$file->output();
	}
	/**
	 * リダイレクトする
	 * @param string $url
	 * @param array $vars
	 */
	static public function redirect($url,array $vars=array()){
		if(!empty($vars)){
			$requestString = self::query($vars);
			if(substr($requestString,0,1) == "?") $requestString = substr($requestString,1);
			$url = sprintf("%s?%s",$url,$requestString);
		}
		header("Location: ".$url);
		exit;
	}
	/**
	 * query文字列に変換する
	 * @param mixed $var
	 * @param string $name
	 * @param boolean $null
	 * @return string
	 */
	static public function query($var,$name=null,$null=true){
		/***
			eq("req=123&",Http::query("123","req"));
			eq("req[0]=123&",Http::query(array(123),"req"));
			eq("req[0]=123&req[1]=456&req[2]=789&",Http::query(array(123,456,789),"req"));
			eq("",Http::query(array(123,456,789)));
			eq("abc=123&def=456&ghi=789&",Http::query(array("abc"=>123,"def"=>456,"ghi"=>789)));
			eq("req[0]=123&req[1]=&req[2]=789&",Http::query(array(123,null,789),"req"));
			eq("req[0]=123&req[2]=789&",Http::query(array(123,null,789),"req",false));

			uc($name,'
				public $id = 0;
				public $value = "";
				public $test = "TEST";
			');

			$obj = new $name();
			$obj->id(100);
			$obj->value("hogehoge");
			eq("req[id]=100&req[value]=hogehoge&req[test]=TEST&",Http::query($obj,"req"));
			eq("id=100&value=hogehoge&test=TEST&",Http::query($obj));
		 */
		$result = "";
		if($null === false && ($var === null || $var === "")) return "";
		if(is_object($var)) $var = ($var instanceof Object) ? $var->hash() : "";
		if(is_array($var)){
			foreach($var as $key => $v) $result .= self::query($v,(empty($name) ? $key : $name."[".$key."]"),$null);
		}else if(!is_numeric($name)){
			if(is_bool($var)) $var = ($var) ? "true" : "false";
			$result .= $name."=".urlencode($var)."&";
		}
		return $result;
	}
	static public function status_header($statuscode){
		switch($statuscode){
			case 100: header("HTTP/1.1 100 Continue"); break;
			case 101: header("HTTP/1.1 101 Switching Protocols"); break;
			case 200: header("HTTP/1.1 200 OK"); break;
			case 201: header("HTTP/1.1 201 Created"); break;
			case 202: header("HTTP/1.1 202 Accepted"); break;
			case 203: header("HTTP/1.1 203 Non-Authoritative Information"); break;
			case 204: header("HTTP/1.1 204 No Content"); break;
			case 205: header("HTTP/1.1 205 Reset Content"); break;
			case 206: header("HTTP/1.1 206 Partial Content"); break;
			case 300: header("HTTP/1.1 300 Multiple Choices"); break;
			case 301: header("HTTP/1.1 301 MovedPermanently"); break;
			case 302: header("HTTP/1.1 302 Found"); break;
			case 303: header("HTTP/1.1 303 See Other"); break;
			case 304: header("HTTP/1.1 304 Not Modified"); break;
			case 305: header("HTTP/1.1 305 Use Proxy"); break;
			case 307: header("HTTP/1.1 307 Temporary Redirect"); break;
			case 400: header("HTTP/1.1 400 Bad Request"); break;
			case 401: header("HTTP/1.1 401 Unauthorized"); break;
			case 403: header("HTTP/1.1 403 Forbidden"); break;
			case 404: header("HTTP/1.1 404 Not Found"); break;
			case 405: header("HTTP/1.1 405 Method Not Allowed"); break;
			case 406: header("HTTP/1.1 406 Not Acceptable"); break;
			case 407: header("HTTP/1.1 407 Proxy Authentication Required"); break;
			case 408: header("HTTP/1.1 408 Request Timeout"); break;
			case 409: header("HTTP/1.1 409 Conflict"); break;
			case 410: header("HTTP/1.1 410 Gone"); break;
			case 411: header("HTTP/1.1 411 Length Required"); break;
			case 412: header("HTTP/1.1 412 Precondition Failed"); break;
			case 413: header("HTTP/1.1 413 Request Entity Too Large"); break;
			case 414: header("HTTP/1.1 414 Request-Uri Too Long"); break;
			case 415: header("HTTP/1.1 415 Unsupported Media Type"); break;
			case 416: header("HTTP/1.1 416 Requested Range Not Satisfiable"); break;
			case 417: header("HTTP/1.1 417 Expectation Failed"); break;
			case 500: header("HTTP/1.1 500 Internal Server Error"); break;
			case 501: header("HTTP/1.1 501 Not Implemented"); break;
			case 502: header("HTTP/1.1 502 Bad Gateway"); break;
			case 503: header("HTTP/1.1 503 Service Unavailable"); break;
			case 504: header("HTTP/1.1 504 Gateway Timeout"); break;
			case 505: header("HTTP/1.1 505 Http Version Not Supported"); break;
			default: header("HTTP/1.1 403 Forbidden (".$statuscode.")"); break;
		}
	}

	/**
	 * GETしてbodyを取得する
	 *
	 * @param string $url
	 * @return string
	 */
	static public function read($url){
		$self = new self();
		return $self->do_get($url)->body();
	}
}
?>