<?php
Rhaco::import("core.iterator.FileIterator");
Rhaco::import("core.Log");
/**
 * ファイル処理
 *
 * @author Kazutaka Tokushima
 * @license New BSD License
 */
class File extends Object{
	const TAR_TYPE_FILE = 0;
	const TAR_TYPE_DIR = 5;
	static private $SRC_LIST = array('://','/./','//');
	static private $DST_LIST = array('#REMOTEPATH#','/','/');
	static private $P_SRC_LIST = array('/^\/(.+)$/','/^(\w):\/(.+)$/');
	static private $P_DST_LIST = array('#ROOT#\\1','\\1#WINPATH#\\2','');
	static private $R_SRC_LIST = array('#REMOTEPATH#','#ROOT#','#WINPATH#');
	static private $R_DST_LIST = array('://','/',':/');
	static private $CACHE_PATH;
	static protected $__size__ = "type=number";
	static protected $__update__ = "type=timestamp";

	protected $directory;
	protected $fullname;
	protected $name;
	protected $oname;
	protected $ext;
	protected $size;
	protected $update;
	protected $mime = "application/octet-stream";
	protected $value;
	protected $tmp;
	protected $error;
	
	static public function __import__(){
		self::$CACHE_PATH = Rhaco::def("core.File@path",Rhaco::work("cache"));
	}
	final protected function __new__($fullname=null,$value=null){
		$this->fullname	= str_replace("\\","/",$fullname);
		$this->value = $value;
		$this->parse_fullname();
	}
	final protected function __str__(){
		return $this->fullname;
	}
	final protected function verifyExt($ext){
		return (".".strtolower($ext) === strtolower($this->ext()));
	}
	final protected function verifyFullname(){
		return is_file($this->fullname);
	}
	final protected function verifyTmp(){
		return is_file($this->tmp);
	}
	final protected function verifyError(){
		return (intval($this->error) > 0);
	}
	final protected function setValue($value){
		$this->value = $value;
		$this->size = sizeof($value);
	}
	/**
	 * 一時ファイルから移動する
	 * HTMLでのファイル添付の場合に使用
	 * @param string $filename
	 * @return this
	 */
	public function generate($filename){
		if(self::copy($this->tmp,$filename)){
			if(unlink($this->tmp)){
				$this->fullname = $filename;
				$this->parse_fullname();
				return $this;
			}
		}
		throw new Exception(sprintf("You don't have a permission[%s].",$filename));
	}
	/**
	 * 標準出力に出力する
	 */
	public function output(){
		Log::disable_display();
		if(empty($this->value) && @is_file($this->fullname)){
			readfile($this->fullname);
		}else{
			print($this->value);
		}
		exit;
	}
	/**
	 * 取得する
	 * @param string $filename
	 * @return string
	 */
	public function get(){
		if($this->value !== null) return $this->value;
		if(!is_file($this->fullname)) throw new Exception($this->fullname." not found");
		return file_get_contents($this->fullname);
	}
	private function parse_fullname(){
		$fullname = str_replace("\\","/",$this->fullname);
		if(preg_match("/^(.+[\/]){0,1}([^\/]+)$/",$fullname,$match)){
			$this->directory = empty($match[1]) ? "." : $match[1];
			$this->name = $match[2];
		}
		if(false !== ($p = strrpos($this->name,"."))){
			$this->ext = ".".substr($this->name,$p+1);
			$filename = substr($this->name,0,$p);
		}
		$this->oname = @basename($this->name,$this->ext);

		if(@is_file($this->fullname)){
			$this->update(@filemtime($this->fullname));
			$this->size(sprintf("%u",@filesize($this->fullname)));
		}else{
			$this->size = strlen($this->value);
		}
		$ext = strtolower(substr($this->ext,1));
		switch($ext){
			case "jpg":
			case "png":
			case "gif":
			case "bmp":
			case "tiff": $this->mime = "image/".$ext; break;
			case "css": $this->mime = "text/css"; break;
			case "txt": $this->mime = "text/plain"; break;
			case "html": $this->mime = "text/html"; break;
			case "xml": $this->mime = "application/xml"; break;
			default: $this->call_modules("set_mime_type",$this,$ext);
		}
	}
	/**
	 * クラスファイルか
	 * @return boolean
	 */
	final public function isClass(){
		return ($this->isExt("php") && ctype_upper($this->oname[0]));
	}
	/**
	 * 不過視ファイルか
	 * @return boolean
	 */
	final public function isInvisible(){
		return ($this->oname[0] == "." || strpos($this->fullname,"/.") !== false);
	}
	/**
	 * ファイルパスを生成する
	 * @param string $base
	 * @param string $path
	 * @return string
	 */
	static public function path($base,$path=""){
		/***
		 * eq("/abc/def/hig.php",File::path("/abc/def","hig.php"));
		 * eq("/xyz/abc/hig.php",File::path("/xyz/","/abc/hig.php"));
		 */
		if(!empty($path)){
			$path = self::parse_filename($path);
			if(preg_match("/^[\/]/",$path,$null)) $path = substr($path,1);
		}
		return self::absolute(self::parse_filename($base),self::parse_filename($path));
	}
	/**
	 * フォルダを作成する
	 * @param string $source
	 */
	static public function mkdir($source){
		$source = self::parse_filename($source);
		if(!(is_readable($source) && is_dir($source))){
			$path = $source;
			$dirstack = array();
			while(!is_dir($path) && $path != DIRECTORY_SEPARATOR){
				array_unshift($dirstack,$path);
				$path = dirname($path);
			}
			while($path = array_shift($dirstack)){
				if(false === mkdir($path)) throw new Exception(sprintf("You don't have a permission[%s].",$path));
				chmod($path,Rhaco::def("core.File@permission",0766));
			}
		}
	}
	/**
	 * ファイル、またはフォルダが存在しているか
	 * @param $filename
	 * @return boolean
	 */
	static public function exist($filename){
		return (is_readable($filename) && (is_file($filename) || is_dir($filename) || is_link($filename)));
	}
	/**
	 * 移動
	 * @param string $source
	 * @param string $dest
	 * @return boolean
	 */
	static public function mv($source,$dest){
		$source = self::parse_filename($source);
		$dest = self::parse_filename($dest);
		return (self::exist($source) && self::mkdir(dirname($dest))) ? rename($source,$dest) : false;
	}
	/**
	 * 最終更新時間を取得
	 * @param $filename
	 * @param $clearstatcache
	 * @return int
	 */
	static public function last_update($filename,$clearstatcache=false){
		if($clearstatcache) clearstatcache();
		if(is_dir($filename)){
			$last_update = -1;
			foreach(File::ls($filename,true) as $file){
				if($last_update < $file->update()) $last_update = $file->update();
			}
			return $last_update;
		}
		return (is_readable($filename) && is_file($filename)) ? filemtime($filename) : -1;
	}
	/**
	 * 削除
	 * $sourceが削除の場合はそれ以下も全て削除
	 * @param string $source
	 * @return boolean
	 */
	static public function rm($source){
		if($source instanceof self) $source = $source->fullname();
		$source	= self::parse_filename($source);

		if(!self::exist($source)) return true;
		if(is_writable($source)){
			if(is_dir($source)){
				if($handle = opendir($source)){
					$list = array();
					while($pointer = readdir($handle)){
						if($pointer != "." && $pointer != "..") $list[] = sprintf("%s/%s",$source,$pointer);
					}
					closedir($handle);
					foreach($list as $path){
						if(!self::rm($path)) return false;
					}
				}
				if(rmdir($source)){
					clearstatcache();
					return true;
				}
			}else if(is_file($source) && unlink($source)){
				clearstatcache();
				return true;
			}
		}
		throw new Exception(sprintf("You don't have a permission[%s].",$source));
	}
	/**
	 * コピー
	 * $sourceがフォルダの場合はそれ以下もコピーする
	 * @param string $source
	 * @param string $dest
	 * @return boolean
	 */
	static public function copy($source,$dest){
		$source	= self::parse_filename($source);
		$dest = self::parse_filename($dest);
		$dir = (preg_match("/^(.+)\/[^\/]+$/",$dest,$tmp)) ? $tmp[1] : $dest;

		if(!self::exist($source)) throw new Exception("not found ".$source);
		self::mkdir($dir);
		if(is_dir($source)){
			$boo = true;
			if($handle = opendir($source)){
				while($pointer = readdir($handle)){
					if($pointer != "." && $pointer != ".."){
						$srcname = sprintf("%s/%s",$source,$pointer);
						$destname = sprintf("%s/%s",$dest,$pointer);
						if(false === ($bool = self::cp($srcname,$destname))) break;
					}
				}
				closedir($handle);
			}
			return $bool;
		}else{
			$filename = (preg_match("/^.+(\/[^\/]+)$/",$source,$tmp)) ? $tmp[1] : "";
			$dest = (is_dir($dest))	? $dest.$filename : $dest;
			if(is_writable(dirname($dest))) copy($source,$dest);
			return self::exist($dest);
		}
	}
	/**
	 * ファイルから取得する
	 * @param string $filename
	 * @return string
	 */
	static public function read($filename){
		if($filename instanceof self) $filename = ($filename->isFullname()) ? $filename->fullname() : $filename->tmp();
		if(!is_readable($filename) || !is_file($filename)) throw new Exception(sprintf("You don't have a permission[%s].",$filename));
		return file_get_contents($filename);
	}
	/**
	 * ファイルから行分割して配列で返す
	 *
	 * @param string $filename
	 * @return string
	 */
	static public function lines($filename){
		return explode("\n",str_replace(array("\r\n","\r"),"\n",self::read($filename)));
	}
	/**
	 * ファイルに書き出す
	 * @param string $filename
	 * @param string $src
	 */
	static public function write($filename,$src=null){
		if($filename instanceof self) $filename = $filename->fullname;
		self::mkdir(dirname($filename));
		if(false === file_put_contents($filename,$src,LOCK_EX)) throw new Exception(sprintf("You don't have a permission[%s].",$filename));
		chmod($filename,Rhaco::def("core.File@permission",0766));
	}
	/**
	 * ファイルに追記する
	 * @param string $filename
	 * @param string $src
	 */
	static public function append($filename,$src){
		if($filename instanceof self) $filename = $filename->fullname;
		self::mkdir(dirname($filename));
		if(false === file_put_contents($filename,$src,FILE_APPEND|LOCK_EX)) throw new Exception(sprintf("You don't have a permission[%s].",$filename));
	}
	/**
	 * ファイルから取得する
	 * @param string $filename
	 * @return string
	 */
	static public function gzread($filename){
		if($filename instanceof self) $filename = ($filename->isFullname()) ? $filename->fullname() : $filename->tmp();
		if(!is_readable($filename) || !is_file($filename)) throw new Exception(sprintf("You don't have a permission[%s].",$filename));
		try{
			$fp = gzopen($filename,"rb");
			$buf = null;
			while(!gzeof($fp)) $buf .= gzread($fp,4096);
			gzclose($fp);
			return $buf;
		}catch(Exception $e){
			throw new Exception(sprintf("You don't have a permission[%s].",$filename));
		}
	}
	/**
	 * gz圧縮でファイルに書き出す
	 * @param string $filename
	 * @param string $src
	 */
	static public function gzwrite($filename,$src){
		if($filename instanceof self) $filename = $filename->fullname;
		self::mkdir(dirname($filename));
		try{
			$fp = gzopen($filename,"wb9");
			gzwrite($fp,$src);
			gzclose($fp);
		}catch(Exception $e){
			throw new Exception(sprintf("You don't have a permission[%s].",$filename));
		}
	}
	/**
	 * ファイル、またはディレクトリからtar圧縮のデータを作成する
	 * @param string $base
	 * @param string $path
	 * @param string $root_path
	 * @param string $ignore_pattern
	 * @return string
	 */
	static public function pack($base,$path=null,$root_path=null,$ignore_pattern=null,$endpoint=true){
		$result = null;
		$files = array();
		$base = self::parse_filename($base);
		$path = self::parse_filename($path);
		$ignore = (!empty($ignore_pattern));
		if(substr($base,0,-1) != "/") $base .= "/";
		if(!empty($root_path)){
			if(substr($root_path,-1) != "/") $root_path .= "/";
			if($root_path[0] == "/") $root_path = substr($root_path,1);
		}
		$filepath = self::absolute($base,$path);

		if(is_dir($filepath)){
			foreach(self::dirs($filepath,true) as $dir) $files[$dir] = self::TAR_TYPE_DIR;
			ksort($files);
			foreach(self::ls($filepath,true) as $file) $files[$file->fullname()] = self::TAR_TYPE_FILE;
		}else{
			$files[$filepath] = 0;
		}
		foreach($files as $filename => $type){
			$target_filename = $root_path.str_replace($base,"",$filename);
			if(!$ignore || !self::is_pattern($ignore_pattern,$target_filename)){
				switch($type){
					case self::TAR_TYPE_FILE:
						$rp = fopen($filename,"rb");
						$info = stat($filename);
						$result .= self::tar_head($type,$target_filename,filesize($filename),fileperms($filename),$info[4],$info[5],filemtime($filename));
						while(!feof($rp)) $result .= pack("a512",fread($rp,512));
						fclose($rp);
						break;
					case self::TAR_TYPE_DIR:
						$result .= self::tar_head($type,$target_filename);
						break;
				}
			}
		}
		if($endpoint) $result .= pack("a1024",null);
		return $result;
	}
	static private function is_pattern($pattern,$value){
		$pattern = (is_array($pattern)) ? $pattern : array($pattern);
		foreach($pattern as $p){
			if(preg_match("/".str_replace(array("\/","/","__SLASH__"),array("__SLASH__","\/","\/"),$p)."/",$value)) return true;
		}
		return false;
	}
	static public function text_pack($filename,$text,$endpoint=false){
		$strlen = strlen($text);
		$result = self::tar_head(0,$filename,$strlen);
		for($i=0;$i<$strlen;$i+=512){
			$result .= pack("a512",substr($text,$i,512));
		}
		if($endpoint) $result .= pack("a1024",null);
		return $result;
	}
	static private function tar_head($type,$filename,$filesize=0,$fileperms=0744,$uid=0,$gid=0,$update_date=null){
		if($update_date === null) $update_date = time();
		$checksum = 256;
		$first = pack("a100a8a8a8a12A12",$filename,
						sprintf("%8s",DecOct($fileperms)),sprintf("%8s",DecOct($uid)),sprintf("%8s",DecOct($gid)),
						sprintf("%12s",(($type === 0) ? DecOct($filesize) : 0)),sprintf("%12s",DecOct($update_date)));
		$last = pack("a1a100a6a2a32a32a8a8a155a12",$type,null,null,null,null,null,null,null,null,null);
		for($i=0;$i<strlen($first);$i++) $checksum += ord($first[$i]);
		for($i=0;$i<strlen($last);$i++) $checksum += ord($last[$i]);
		return $first.pack("a8",sprintf("%8s",DecOct($checksum))).$last;
	}
	/**
	 * tarを解凍する
	 * @param string $src
	 * @return array
	 */
	static public function unpack($src){
		$result = array();
		for($pos=0,$vsize=0,$cur="";;){
			$buf = substr($src,$pos,512);
			if(strlen($buf) < 512) break;
			$data = unpack("a100name/a8mode/a8uid/a8gid/a12size/a12mtime/"
							."a8chksum/"
							."a1typeflg/a100linkname/a6magic/a2version/a32uname/a32gname/a8devmajor/a8devminor/a155prefix",
							 $buf);
			$pos += 512;
			if(!empty($data["name"])){
				$obj = new stdClass();
				$obj->type = (int)$data["typeflg"];
				$obj->path = $data["name"];
				$obj->update = base_convert($data["mtime"],8,10);

				switch($obj->type){
					case self::TAR_TYPE_FILE:
						$obj->size = base_convert($data["size"],8,10);
						$obj->content = substr($src,$pos,$obj->size);
						$pos += (ceil($obj->size / 512) * 512);
					case self::TAR_TYPE_DIR:
				}
				$result[$obj->path] = $obj;
			}
		}
		return $result;
	}
	/**
	 * tar.gz(tgz)圧縮してファイル書き出しを行う
	 *
	 * @param string $tgz_filename
	 * @param string $base_dir
	 * @param string $target_path
	 * @param string $root_path
	 */
	static public function tgz($tgz_filename,$base_dir,$target_path=null,$root_path=null,$ignore_pattern=null){
		self::gzwrite($tgz_filename,self::pack($base_dir,$target_path,$root_path,$ignore_pattern));
	}
	/**
	 * tar.gz(tgz)を解凍してファイル書き出しを行う
	 * @param string $inpath
	 * @param string $outpath
	 */
	static public function untgz($inpath,$outpath){
		$list = self::unpack(self::gzread($inpath));
		foreach($list as $f){
			$out = self::absolute($outpath,$f->path);
			switch($f->type){
				case self::TAR_TYPE_FILE:
					self::write($out,$f->content);
					touch($out,$f->update);
					break;
				case self::TAR_TYPE_DIR:
					self::mkdir($out);
					break;
			}
		}
	}
	private static function parse_filename($filename){
		$filename = preg_replace("/[\/]+/","/",str_replace("\\","/",trim($filename)));
		return (substr($filename,-1) == "/") ? substr($filename,0,-1) : $filename;
	}
	/**
	 * 絶対パスを取得
	 * @param string $baseUrl
	 * @param string $targetUrl
	 * @return string
	 */
	static public function absolute($baseUrl,$targetUrl){
		/***
			eq("http://www.rhaco.org/doc/ja/index.html",File::absolute("http://www.rhaco.org/","/doc/ja/index.html"));
			eq("http://www.rhaco.org/doc/ja/index.html",File::absolute("http://www.rhaco.org/","../doc/ja/index.html"));
			eq("http://www.rhaco.org/doc/ja/index.html",File::absolute("http://www.rhaco.org/","./doc/ja/index.html"));
			eq("http://www.rhaco.org/doc/ja/index.html",File::absolute("http://www.rhaco.org/doc/ja/","./index.html"));
			eq("http://www.rhaco.org/doc/index.html",File::absolute("http://www.rhaco.org/doc/ja","./index.html"));
			eq("http://www.rhaco.org/doc/index.html",File::absolute("http://www.rhaco.org/doc/ja/","../index.html"));
			eq("http://www.rhaco.org/index.html",File::absolute("http://www.rhaco.org/doc/ja/","../../index.html"));
			eq("http://www.rhaco.org/index.html",File::absolute("http://www.rhaco.org/doc/ja/","../././.././index.html"));
			eq("/www.rhaco.org/doc/index.html",File::absolute("/www.rhaco.org/doc/ja/","../index.html"));
			eq("/www.rhaco.org/index.html",File::absolute("/www.rhaco.org/doc/ja/","../../index.html"));
			eq("/www.rhaco.org/index.html",File::absolute("/www.rhaco.org/doc/ja/","../././.././index.html"));
			eq("c:/www.rhaco.org/doc/index.html",File::absolute("c:/www.rhaco.org/doc/ja/","../index.html"));
			eq("http://www.rhaco.org/index.html",File::absolute("http://www.rhaco.org/doc/ja","/index.html"));

			eq("/www.rhaco.org/doc/ja/action.html/index.html",File::absolute('/www.rhaco.org/doc/ja/action.html', 'index.html'));
			eq("http://www.rhaco.org/doc/ja/index.html",File::absolute('http://www.rhaco.org/doc/ja/action.html', 'index.html'));
			eq("http://www.rhaco.org/doc/ja/sample.cgi?param=test",File::absolute('http://www.rhaco.org/doc/ja/sample.cgi?query=key', '?param=test'));
			eq("http://www.rhaco.org/doc/index.html",File::absolute('http://www.rhaco.org/doc/ja/action.html', '../../index.html'));
			eq("http://www.rhaco.org/?param=test",File::absolute('http://www.rhaco.org/doc/ja/sample.cgi?query=key', '../../../?param=test'));
			eq("/doc/ja/index.html",File::absolute("/","/doc/ja/index.html"));
			eq("/index.html",File::absolute("/","index.html"));
			eq("http://www.rhaco.org/login",File::absolute("http://www.rhaco.org","/login"));
			eq("http://www.rhaco.org/login",File::absolute("http://www.rhaco.org/login",""));
			eq("http://www.rhaco.org/login.cgi",File::absolute("http://www.rhaco.org/logout.cgi","login.cgi"));
			eq("http://www.rhaco.org/hoge/login.cgi",File::absolute("http://www.rhaco.org/hoge/logout.cgi","login.cgi"));
			eq("http://www.rhaco.org/hoge/login.cgi",File::absolute("http://www.rhaco.org/hoge/#abc/aa","login.cgi"));
			eq("http://www.rhaco.org/hoge/abc.html#login",File::absolute("http://www.rhaco.org/hoge/abc.html","#login"));
			eq("http://www.rhaco.org/hoge/abc.html#login",File::absolute("http://www.rhaco.org/hoge/abc.html#logout","#login"));
			eq("http://www.rhaco.org/hoge/abc.html?abc=aa#login",File::absolute("http://www.rhaco.org/hoge/abc.html?abc=aa#logout","#login"));
			eq("http://www.rhaco.org/hoge/abc.html",File::absolute("http://www.rhaco.org/hoge/abc.html","javascript::alert('')"));
			eq("http://www.rhaco.org/hoge/abc.html",File::absolute("http://www.rhaco.org/hoge/abc.html","mailto::hoge@rhaco.org"));
			eq("http://www.rhaco.org/hoge/login.cgi",File::absolute("http://www.rhaco.org/hoge/?aa=bb/","login.cgi"));
			eq("http://www.rhaco.org/login",File::absolute("http://rhaco.org/hoge/hoge","http://www.rhaco.org/login"));
			eq("http://localhost:8888/spec/css/style.css",File::absolute("http://localhost:8888/spec/","./css/style.css"));
		 */
		$targetUrl = str_replace("\\","/",$targetUrl);
		if(empty($targetUrl)) return $baseUrl;
		$baseUrl = str_replace("\\","/",$baseUrl);
		if(preg_match("/^[\w]+\:\/\/[^\/]+/",$targetUrl)) return $targetUrl;
		$isnet = preg_match("/^[\w]+\:\/\/[^\/]+/",$baseUrl,$basehost);
		$isroot = (substr($targetUrl,0,1) == "/");
		if($isnet){
			if(strpos($targetUrl,"javascript:") === 0 || strpos($targetUrl,"mailto:") === 0) return $baseUrl;
			$preg_cond = ($targetUrl[0] === "#") ? "#" : "#\?";
			$baseUrl = preg_replace("/^(.+?)[".$preg_cond."].*$/","\\1",$baseUrl);
			if($targetUrl[0] === "#" || $targetUrl[0] === "?") return $baseUrl.$targetUrl;
			if(substr($baseUrl,-1) !== "/"){
				if(substr($targetUrl,0,2) === "./"){
					$targetUrl = ".".$targetUrl;
				}else if($targetUrl[0] !== "." && $targetUrl[0] !== "/"){
					$targetUrl = "../".$targetUrl;
				}
			}
		}
		if(empty($baseUrl) || preg_match("/^[a-zA-Z]\:/",$targetUrl) || (!$isnet && $isroot) || preg_match("/^[\w]+\:\/\/[^\/]+/",$targetUrl)) return $targetUrl;
		if($isnet && $isroot && isset($basehost[0])) return $basehost[0].$targetUrl;

		$baseUrl = preg_replace(self::$P_SRC_LIST,self::$P_DST_LIST,str_replace(self::$SRC_LIST,self::$DST_LIST,$baseUrl));
		$targetUrl = preg_replace(self::$P_SRC_LIST,self::$P_DST_LIST,str_replace(self::$SRC_LIST,self::$DST_LIST,$targetUrl));
		$basedir = $targetdir = $rootpath = "";

		if(strpos($baseUrl,"#REMOTEPATH#")){
			list($rootpath)	= explode("/",$baseUrl);
			$baseUrl = substr($baseUrl,strlen($rootpath));
			$targetUrl = str_replace("#ROOT#","",$targetUrl);
		}
		$baseList = preg_split("/\//",$baseUrl,-1,PREG_SPLIT_NO_EMPTY);
		$targetList = preg_split("/\//",$targetUrl,-1,PREG_SPLIT_NO_EMPTY);

		for($i=0;$i<sizeof($baseList)-substr_count($targetUrl,"../");$i++){
			if($baseList[$i] != "." && $baseList[$i] != "..") $basedir .= $baseList[$i]."/";
		}
		for($i=0;$i<sizeof($targetList);$i++){
			if($targetList[$i] != "." && $targetList[$i] != "..") $targetdir .= "/".$targetList[$i];
		}
		$targetdir = (!empty($basedir)) ? substr($targetdir,1) : $targetdir;
		$basedir = (!empty($basedir) && substr($basedir,0,1) != "/" && substr($basedir,0,6) != "#ROOT#" && !strpos($basedir,"#WINPATH#")) ? "/".$basedir : $basedir;
		return str_replace(self::$R_SRC_LIST,self::$R_DST_LIST,$rootpath.$basedir.$targetdir);
	}

	/**
	 * 相対パスを取得
	 * @param string $baseUrl
	 * @param string $targetUrl
	 * @return string
	 */
	static public function relative($baseUrl,$targetUrl){
		/***
			eq("./overview.html",File::relative("http://www.rhaco.org/doc/ja/","http://www.rhaco.org/doc/ja/overview.html"));
			eq("../overview.html",File::relative("http://www.rhaco.org/doc/ja/","http://www.rhaco.org/doc/overview.html"));
			eq("../../overview.html",File::relative("http://www.rhaco.org/doc/ja/","http://www.rhaco.org/overview.html"));
			eq("../en/overview.html",File::relative("http://www.rhaco.org/doc/ja/","http://www.rhaco.org/doc/en/overview.html"));
			eq("./doc/ja/overview.html",File::relative("http://www.rhaco.org/","http://www.rhaco.org/doc/ja/overview.html"));
			eq("./ja/overview.html",File::relative("http://www.rhaco.org/doc/","http://www.rhaco.org/doc/ja/overview.html"));
			eq("http://www.goesby.com/user.php/rhaco",File::relative("http://www.rhaco.org/doc/ja/","http://www.goesby.com/user.php/rhaco"));
			eq("./doc/ja/overview.html",File::relative("/www.rhaco.org/","/www.rhaco.org/doc/ja/overview.html"));
			eq("./ja/overview.html",File::relative("/www.rhaco.org/doc/","/www.rhaco.org/doc/ja/overview.html"));
			eq("/www.goesby.com/user.php/rhaco",File::relative("/www.rhaco.org/doc/ja/","/www.goesby.com/user.php/rhaco"));
			eq("./ja/overview.html",File::relative("c:/www.rhaco.org/doc/","c:/www.rhaco.org/doc/ja/overview.html"));
			eq("c:/www.goesby.com/user.php/rhaco",File::relative("c:/www.rhaco.org/doc/ja/","c:/www.goesby.com/user.php/rhaco"));
			eq("./Documents/workspace/prhagger/__settings__.php",File::relative("/Users/kaz/","/Users/kaz/Documents/workspace/prhagger/__settings__.php"));
			eq("./",File::relative("C:/xampp/htdocs/rhaco/test/template/sub","C:/xampp/htdocs/rhaco/test/template/sub"));
			eq("./",File::relative('C:\xampp\htdocs\rhaco\test\template\sub','C:\xampp\htdocs\rhaco\test\template\sub'));
		 */
		$baseUrl = preg_replace(self::$P_SRC_LIST,self::$P_DST_LIST,str_replace(self::$SRC_LIST,self::$DST_LIST,str_replace("\\","/",$baseUrl)));
		$targetUrl = preg_replace(self::$P_SRC_LIST,self::$P_DST_LIST,str_replace(self::$SRC_LIST,self::$DST_LIST,str_replace("\\","/",$targetUrl)));
		$filename = $url = "";
		$counter = 0;

		if(preg_match("/^(.+\/)[^\/]+\.[^\/]+$/",$baseUrl,$null)) $baseUrl = $null[1];
		if(preg_match("/^(.+\/)([^\/]+\.[^\/]+)$/",$targetUrl,$null)) list($tmp,$targetUrl,$filename) = $null;
		if(substr($baseUrl,-1) == "/") $baseUrl = substr($baseUrl,0,-1);
		if(substr($targetUrl,-1) == "/") $targetUrl = substr($targetUrl,0,-1);
		$baseList = explode("/",$baseUrl);
		$targetList = explode("/",$targetUrl);
		$baseSize = sizeof($baseList);

		if($baseList[0] != $targetList[0]) return str_replace(self::$R_SRC_LIST,self::$R_DST_LIST,$targetUrl);
		foreach($baseList as $key => $value){
			if(!isset($targetList[$key]) || $targetList[$key] != $value) break;
			$counter++;
		}
		for($i=sizeof($targetList)-1;$i>=$counter;$i--) $filename = $targetList[$i]."/".$filename;
		if($counter == $baseSize) return sprintf("./%s",$filename);
		return sprintf("%s%s",str_repeat("../",$baseSize - $counter),$filename);
	}
	/**
	 * $urlsが生存期間を超えているか
	 * @param array / string $urls
	 * @param 生存時間 $expiryTime
	 * @return boolean
	 */
	static public function isExpiry($urls,$expiryTime=86400){
		$path = self::cfilepath($urls);
		$time = (is_file($path)) ? self::last_update($path) : (is_file($path."_s") ? self::last_update($path."_s") : 0);
		return (($time + $expiryTime) < time());
	}
	/**
	 * キャッシュファイルを作成する
	 * @param string/array $urls
	 * @param mixed $source
	 * @return $source
	 */
	static public function cwrite($urls,$source){
		$path = self::cfilepath($urls);
		if(!is_string($source)){
			$source = serialize($source);
			$path = $path."_s";
		}
		self::gzwrite($path,$source);
		return $source;
	}
	/**
	 * キャッシュを取得する
	 * @param array / string $urls
	 * @return string / false
	 */
	static public function cread($urls){
		$path = self::cfilepath($urls);
		if(is_file($path)) return self::gzread($path);
		if(is_file($path."_s")) return unserialize(self::gzread($path."_s"));
		return null;
	}
	/**
	 * キャッシュを削除する
	 * @param array / string $urls
	 * @return boolean
	 */
	static public function crm($urls=null){
		if($urls !== null) return self::rm(self::cfilepath($urls));
		foreach(self::ls(self::$CACHE_PATH) as $file) self::rm($file);
	}
	static private function cfilepath($urls){
		if(empty(self::$CACHE_PATH)) throw new Exception("not found path");
		$path = md5(implode("",(is_array($urls)) ? $urls : array($urls)));
		return self::absolute(self::$CACHE_PATH,$path);
	}
	/**
	 * フォルダ名の配列を取得
	 * @param string $directory
	 * @param boolean $recursive 階層を潜って取得するか
	 * @param boolean $a 隠しファイルも参照するか
	 * return array string
	 */
	static public function dirs($directory,$recursive=false,$a=false){
		$directory = self::parse_filename($directory);
		if(is_file($directory)) $directory = dirname($directory);
		if(is_readable($directory) && is_dir($directory)){
			return new FileIterator($directory,0,$recursive,$a);
		}
		throw new Exception();
	}
	/**
	 * 指定された$directory内のファイル情報をFileとして配列で取得
	 * @param string $directory
	 * @param boolean $recursive 階層を潜って取得するか
	 * @param boolean $a 隠しファイルも参照するか
	 * @return array File
	 */
	static public function ls($directory,$recursive=false,$a=false){
		$directory = self::parse_filename($directory);
		if(is_file($directory)) $directory = dirname($directory);
		if(is_readable($directory) && is_dir($directory)){
			return new FileIterator($directory,1,$recursive,$a);
		}
		throw new Exception("Invalid path ".$directory);
	}
}
?>