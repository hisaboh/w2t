<?php
import("core.Lib");
/**
 * @author Kazutaka Tokushima
 * @license New BSD License
 */
class SvnViewer extends Object{
	protected function __new__($driver,$project){
		$driver = "SvnViewer".ucfirst($driver);
		module("driver.".$driver);
		$this->add_modules(new $driver($project));
	}
	/**
	 * 最後のリビジョン番号を取得
	 *
	 * @return int
	 */
	public function last(){
		return $this->call_module("last");
		/***
			# sourceforge
			if(def("arbo.net.SvnViewer@test_sourceforge_project") !== null){
				$api = new SvnViewer("sourceforge",def("arbo.net.SvnViewer@test_sourceforge_project","rhaco"));
				eq(true,($api->last() > 0));
			}
		 */
		/***
			# google
			if(def("arbo.net.SvnViewer@test_google_project") !== null){
				$api = new SvnViewer("googlecode",def("arbo.net.SvnViewer@test_google_project","rhaco"));
				eq(true,($api->last() > 0));
			}
		 */
		/***
			# shell
			if(def("arbo.net.SvnViewer@test_shell_project") !== null){
				$api = new SvnViewer("shell",def("arbo.net.SvnViewer@test_shell_project","/Users/kazutaka/Documents/workspace/rhaco"));
				eq(true,($api->last() > 0));
			}
		 */
	}
	/**
	 * 特定のリビジョンを取得
	 *
	 * @param int $from
	 * @param int $to
	 * @return unknown
	 */
	public function revision($from,$to=null){
		if(empty($to)) $to = $from;
		return $this->call_module("revision",$from,$to);
		/***
			# sourceforge
			if(def("arbo.net.SvnViewer@test_sourceforge_project") !== null){
				$api = new SvnViewer("sourceforge",def("arbo.net.SvnViewer@test_sourceforge_project","rhaco"));
				$last = $api->last();
				eq(true,($last > 0));
				$result = $api->revision($last);
				eq(true,sizeof($result) == 1);
				eq(true,($result[$last] instanceof SvnRevision));
			}
		 */
		/***
			# google
			if(def("arbo.net.SvnViewer@test_google_project") !== null){
				$api = new SvnViewer("googlecode",def("arbo.net.SvnViewer@test_google_project","rhaco"));
				$last = $api->last();
				eq(true,($last > 0));
				$result = $api->revision($last);
				eq(true,sizeof($result) == 1);
				eq(true,($result[$last] instanceof SvnRevision));
			}
		 */
		/***
			# shell
			if(def("arbo.net.SvnViewer@test_shell_project") !== null){
				$api = new SvnViewer("shell",def("arbo.net.SvnViewer@test_shell_project","/Users/kazutaka/Documents/workspace/rhaco"));
				$last = $api->last();
				eq(true,($last > 0));
				$result = $api->revision($last);
				eq(true,sizeof($result) == 1);
				eq(true,($result[$last] instanceof SvnRevision));
			}
		 */
	}
	/**
	 * 内容を取得する
	 *
	 * @param string $path
	 * @param int $rev
	 * @return string
	 */
	public function cat($path,$rev=null){
		if(substr($path,0,1) == "/") $path = substr($path,1);
		if($rev === null) $rev = $this->last();
		return $this->call_module("cat",$path,$rev);
		/***
			# sourceforge
			if(def("arbo.net.SvnViewer@test_sourceforge_project") !== null){
				$api = new SvnViewer("sourceforge",def("arbo.net.SvnViewer@test_sourceforge_project","rhaco"));
				$result = $api->cat(def("arbo.net.SvnViewer@test_sourceforge_path"));
				eq(true,is_string($result));
			}
		 */
		/***
			# google
			if(def("arbo.net.SvnViewer@test_google_project") !== null){
				$api = new SvnViewer("googlecode",def("arbo.net.SvnViewer@test_google_project","rhaco"));
				$result = $api->cat(def("arbo.net.SvnViewer@test_google_path"));
				eq(true,is_string($result));
			}
		 */
		/***
			# shell
			if(def("arbo.net.SvnViewer@test_shell_project") !== null){
				$api = new SvnViewer("shell",def("arbo.net.SvnViewer@test_shell_project","/Users/kazutaka/Documents/workspace/rhaco"));
				$result = $api->cat(def("arbo.net.SvnViewer@test_shell_path"));
				eq(true,is_string($result));
			}
		 */
	}
	public function diff($path,$revA,$revB=null){
		if(substr($path,0,1) == "/") $path = substr($path,1);
		if($revB === null) $revB = $this->last();
		if($revA > $revB) list($revB,$revA) = array($revA,$revB);
		return $this->call_module("diff",$path,$revA,$revB);
		/***
			# google
			if(def("arbo.net.SvnViewer@test_google_project") !== null){
				$api = new SvnViewer("googlecode",def("arbo.net.SvnViewer@test_google_project","rhaco"));
				$result = $api->diff(def("arbo.net.SvnViewer@test_google_path")
									,def("arbo.net.SvnViewer@test_google_from")
									,def("arbo.net.SvnViewer@test_google_to")
							);
				eq(true,is_string($result));
			}
		 */
		/***
			# sourceforge
			if(def("arbo.net.SvnViewer@test_sourceforge_project") !== null){
				$api = new SvnViewer("sourceforge",def("arbo.net.SvnViewer@test_sourceforge_project","rhaco"));
				$result = $api->diff(def("arbo.net.SvnViewer@test_sourceforge_path")
									,def("arbo.net.SvnViewer@test_sourceforge_from")
									,def("arbo.net.SvnViewer@test_sourceforge_to")
							);
				eq(true,is_string($result));
			}
		 */
		/***
			# shell
			if(def("arbo.net.SvnViewer@test_shell_project") !== null){
				$api = new SvnViewer("shell",def("arbo.net.SvnViewer@test_shell_project","/Users/kazutaka/Documents/workspace/rhaco"));
				$result = $api->diff(def("arbo.net.SvnViewer@test_shell_path")
									,def("arbo.net.SvnViewer@test_shell_from")
									,def("arbo.net.SvnViewer@test_shell_to")
							);
				eq(true,is_string($result));
			}
		 */
	}
}
?>