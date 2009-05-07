<?php
Rhaco::import("core.File");
Rhaco::import("core.Http");
Rhaco::import("core.Tag");
/**
 *
 * @author Kazutaka Tokushima
 * @license New BSD License
 */
class Lib{
	static private $PATH;
	static private $IMPORT_URLS = array();

	static public function __funcs__(){
		function lib($package_path,$package_server=null){
			return Lib::import($package_path,$package_server);
		}
	}
	static public function __import__(){
		$path = str_replace("\\","/",Rhaco::def("core.Lib@path",Rhaco::work("extlib")));
		self::$PATH = $path.((substr($path,-1) == "/") ? "" : "/");

		Rhaco::add(self::$PATH);
		if(strpos(get_include_path(),self::$PATH) === false) set_include_path(get_include_path().PATH_SEPARATOR.self::$PATH);

		$server = Rhaco::def("core.Lib@server");
		if(!empty($server)){
			if(is_array($server)){
				foreach($server as $s) self::add($s);
			}else{
				self::add($server);
			}
		}
	}
	static private function add($path){
		$path = self::path($path);
		if(!in_array($path,self::$IMPORT_URLS)) array_unshift(self::$IMPORT_URLS,$path);
		return $path;
	}
	static private function path($path){
		$path = $path.((substr($path,-1) == "/") ? "" : "/");
		if(strpos($path,"://") === false) $path = "http://".$path;
		return $path;
	}
	/**
	 * libraryをimportする
	 * 必要であればダウンロードを試みる
	 *
	 * @param string $package_path
	 */
	static public function import($package_path,$package_server=null){
		if($package_server !== null) $package_server = self::add($package_server);
		$expand_path = self::$PATH.str_replace(".","/",$package_path);
		try{
			return self::import_include($expand_path,$package_path);
		}catch(Exception $e){
			$uri = str_replace(".","/",$package_path);
			$tgz = File::absolute(self::$PATH,str_replace(".","_",$package_path).".tgz");
			$http = new Http();

			if($package_server !== null){
				if(null !== ($result = self::read_server($http,$package_server,$uri,$tgz,$package_path,$expand_path))) return $result;
			}else{
				foreach(self::$IMPORT_URLS as $server){
					if(null !== ($result = self::read_server($http,$server,$uri,$tgz,$package_path,$expand_path))) return $result;
				}
			}
		}
		throw new Exception($package_path." not found");
	}
	static private function read_server($http,$server,$uri,$tgz,$package_path,$expand_path){
		if($http->do_get($server."__package__.php"."/state/".$uri)->status() === 200){
			if(Tag::setof($tag,$http->body(),"rest") && $tag->f("status.value()") == "success"){
				$http->do_download($server."__package__.php"."/download/".$uri,$tgz);
				File::untgz($tgz,self::$PATH);
				File::rm($tgz);
				return self::import_include($expand_path,$package_path);
			}
		}
	}
	static private function import_include($expand_path,$package_path){
		if(is_file($expand_path.".php")) return Rhaco::import($expand_path.".php");
		if(is_dir($expand_path)) return Rhaco::import($expand_path."/".preg_replace("/^.+\.(.+)$/","\\1",$package_path).".php");
		throw new Exception($expand_path." not found");
	}
	
	/**
	 * importされたライブラリの削除
	 */
	static public function rm(){
		if(is_dir(self::$PATH)){
			foreach(File::ls(self::$PATH,true) as $file) File::rm($file->fullname());
			foreach(File::dirs(self::$PATH,true) as $dir) File::rm($dir);
		}
	}
}
?>