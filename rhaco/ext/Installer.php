<?php
Rhaco::import("core.File");
Rhaco::import("core.Http");
Rhaco::import("core.Tag");
/**
 *
 * @author Kazutaka Tokushima
 * @license New BSD License
 */
class Installer{
	static private $PATH;
	static private $INST_URLS = array();

	static public function __import__(){
		$server = Rhaco::def("ext.Installer@server");
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
		if(!in_array($path,self::$INST_URLS)) array_unshift(self::$INST_URLS,$path);
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
	static public function install($name,$install_server=null){
		if($install_server !== null) $install_server = self::add($install_server);
		$tgz = File::absolute(self::$PATH,str_replace(".","_",$name).".tgz");
		$http = new Http();

		if($install_server !== null){
			if(self::read_server($http,$install_server,$name,$tgz,$name)) return;
		}else{
			foreach(self::$INST_URLS as $server){
				if(self::read_server($http,$server,$name,$tgz,$name)) return;
			}
		}
		throw new Exception($name." not found");
	}
	static private function read_server($http,$server,$uri,$tgz,$package_path){
		if($http->do_get($server."__repository__.php"."/state/".$uri)->status() === 200){
			if(Tag::setof($tag,$http->body(),"rest") && $tag->f("status.value()") == "success"){
				$http->do_download($server."__repository__.php"."/download/".$uri,$tgz);
				File::untgz($tgz,getcwd());
				File::rm($tgz);
				return true;
			}
		}
		return false;
	}
}
?>