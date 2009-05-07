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
class Packager{
	static private $tgz_dir;
	
	static public function __import__(){
		self::$tgz_dir = Rhaco::def("generic.Packager@path",Rhaco::work("tgz"));
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
	 * package serverを立ち上げる
	 *
	 * @param string $package_root パッケージ名
	 */
	static public function handler($package_root=null){
		if(empty($package_root) || Rhaco::path() == ""){
			$debug = debug_backtrace();
			$first_action = array_pop($debug);
			if($package_root === null) $package_root = basename(dirname($first_action["file"]));
			if(Rhaco::path() == "") Rhaco::init($first_action["file"]);
		}
		$base_dir = Rhaco::path();
		$request = new Request();
		$package_root_path = str_replace(".","/",$package_root);
		$preg_quote = ((empty($package_root_path)) ? "" : preg_quote($package_root_path,"/")."\/")."(.+)";

		$tag = new Tag("rest");
		if(preg_match("/^\/state\/".$preg_quote."$/",$request->args(),$match)){
			$tag->add(new Tag("package",$match[1]));

			if(self::parse_package($package_root_path,$base_dir,$match[1],$tgz_filename)){
				$tag->add(new Tag("status","success"));
				$tag->output();
			}
		}else if(preg_match("/^\/download\/".$preg_quote."$/",$request->args(),$match)){
			if(self::parse_package($package_root_path,$base_dir,$match[1],$tgz_filename)) Http::attach(new File($tgz_filename));
		}
		Http::status_header(403);
		$tag->add(new Tag("status","fail"));
		$tag->output();
		exit;
	}
	static private function parse_package($package_root_path,$base_dir,$package,&$tgz_filename){
		$tgz_filename = File::absolute(self::$tgz_dir,str_replace("/","_",$package).".tgz");
		if(is_file($tgz_filename)) return true;
		$package_name = basename($base_dir);
		$search_path = File::absolute($base_dir,preg_replace("/^".$package_name."\//","",$package));
		$base_name = basename($search_path);

		if(is_file($search_path.".php")){
			$isdir = is_dir($search_path);
			$tar = File::pack($base_dir,$search_path.".php",$package_root_path,!$isdir);
			if($isdir){
				$tar .= File::pack($base_dir,$search_path,$package_root_path);
			}else{
				$parent_dir = basename(dirname($search_path));
				$parent_search = dirname(dirname($search_path))."/".$parent_dir.".php";
				if(is_file($parent_search)) $tar .= File::pack($base_dir,$parent_search,$package_root_path);
			}
			File::gzwrite($tgz_filename,$tar);
			return true;
		}else if(is_file($search_path."/".$base_name.".php")){
			File::tgz($tgz_filename,$base_dir,$search_path,$package_root_path);
			return true;
		}
		return false;
	}
}
?>