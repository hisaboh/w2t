<?php
Rhaco::import("core.Request");
Rhaco::import("core.File");
Rhaco::import("core.Http");
Rhaco::import("core.Tag");
/**
 *
 * @author Kazutaka Tokushima
 * @license New BSD License
 */
class Repository{
	static private $tgz_dir;
	
	static public function __import__(){
		self::$tgz_dir = Rhaco::def("generic.Installer@path",Rhaco::work("tgz"));
	}
	/**
	 * キャッシュされたtgzの削除
	 */
	static public function rm(){
		if(is_dir(self::$tgz_dir)){
			foreach(File::ls(self::$tgz_dir,true) as $file) File::rm($file->fullname());
			foreach(File::dirs(self::$tgz_dir,true) as $dir) File::rm($dir);
		}
	}
	
	/**
	 * install serverを立ち上げる
	 *
	 * @param string $package_root パッケージ名
	 */
	static public function handler(){
		$base_dir = Rhaco::path();
		$request = new Request();

		$tag = new Tag("rest");
		if(preg_match("/^\/state\/(.+)$/",$request->args(),$match)){
			$tag->add(new Tag("package",$match[1]));

			if(self::parse_package($base_dir,$match[1],$tgz_filename)){
				$tag->add(new Tag("status","success"));
				$tag->output();
			}
		}else if(preg_match("/^\/download\/(.+)$/",$request->args(),$match)){
			if(self::parse_package($base_dir,$match[1],$tgz_filename)) Http::attach(new File($tgz_filename));
		}
		Http::status_header(403);
		$tag->add(new Tag("status","fail"));
		$tag->output();
		exit;
	}
	static private function parse_package($base_dir,$name,&$tgz_filename){
		$tgz_filename = File::absolute(self::$tgz_dir,$name.".tgz");
		if(is_file($tgz_filename)) return true;
		$search_path = File::absolute($base_dir,$name);

		if(is_dir($search_path)){
			$ignore = array("__\w+__\.php$","^work/","^work$");
			File::tgz($tgz_filename,$search_path,$search_path,null,$ignore);
			return true;
		}else if(is_file($search_path.".php")){
			File::tgz($tgz_filename,$base_dir,$search_path.".php");
			return true;
		}
		return false;
	}
}
?>