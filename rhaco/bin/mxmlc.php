<?php
/**
 * 更新を監視してmxmlcする
 */
include_once(dirname(__FILE__)."/__settings__.php");
import("core.Command");
import("core.File");
import("core.Request");

$req = new Request();
$cmd = new Command();
$cmd->open(File::absolute(def("mxmlc@flex_bin"),"fcsh"),null,work_path("mxmlc_error"));


$src = new File(path("flex/src/".$req->inVars("f","index.mxml")));
$bin = path("flex/bin/".$src->oname().".swf");
$lib = path("flex/lib/");
$rsl = path("flex/rsl/");

while(true){
	$lib_last_update = $src_last_update = $rsl_last_update = $id = 0;
	$mxmlc = sprintf("mxmlc -output %s --file-specs %s ",$bin,$src);

	if($lib_last_update < ($lib_update = File::last_update($lib,true))){
		$lib_last_update = $lib_update;
		$files = array();
		foreach(File::ls($lib) as $f) $files[] = $f->fullname();
		$mxmlc .= "-library-path+=".implode(",",$files)." ";
	}
	if($rsl_last_update < ($rsl_update = File::last_update($rsl,true))){
		$rsl_last_update = $rsl;
		$files = array();
		foreach(File::ls($rsl) as $f){
			$files[] = $f->fullname();
			$names[] = "../rsl/".$f->name();
		}
		$mxmlc .= "-runtime-shared-libraries=".implode(",",$names)." ";
		$mxmlc .= "-external-library-path=".implode(",",$files)." ";
	}
	$cmd->write($mxmlc);
	
	while(true){
		$line = $cmd->gets();
		if(strpos($line,"(fcsh)") === 0){
			if(preg_match("/(\d+)/",$line,$match)) $id = $match[1];
			break;
		}
	}
	while(true){
		if($lib_last_update < File::last_update($lib,true)) break;
		if(($src_update = File::last_update($src,true)) > $src_last_update){
			$cmd->write(sprintf("compile %s",$id));
			println("compile ".date("Y/m/d H:i:s"));
			$src_last_update = $src_update;
		}
		sleep(1);
	}
}
?>