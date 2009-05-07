<?php
import("core.Tag");
import("core.Lib");
import("core.Command");
module("SvnRevision");
/**
 * @author Kazutaka Tokushima
 * @license New BSD License
 */
class SvnViewerShell extends Object{
	protected $url;
	protected $cmd;

	protected function __new__($url){
		$this->url = $url;
		$this->cmd = def("arbo.net.SvnLogger.driver.SvnLoggerShell@cmd","/usr/bin/svn");
	}
	public function last(){
		$src = Command::out(sprintf("%s info %s",$this->cmd,$this->url));
		if(preg_match("/Last Changed Rev: ([\d]+)/i",$src,$match)) return (int)$match[1];
		throw new Exception("undef");
	}
	public function revision($from,$to){
		$src = Command::out(sprintf("%s log %s --xml --verbose %s",$this->cmd,sprintf("-r %d:%d ",$from,$to),$this->url));
		$result = array();

		if(Tag::setof($tag,$src,"log")){
			foreach($tag->in("logentry") as $log){
				$revision = $log->inParam("revision");
				$result[$revision] = new SvnRevision($revision,
													$log->f("author.value()"),
													$log->f("msg.value()"),
													$log->f("date.value()")
												);
				foreach($log->in("paths") as $paths){
					foreach($paths->in("path") as $path){
						$result[$revision]->path($path->inParam("action"),$path->value());
					}
				}
			}
		}
		if(($to - $from) != sizeof($result) - 1){
			$last = $this->_lastShell();
			if($to > $last) $to = $last;

			for($i=$from;$i<=$to;$i++){
				if(!isset($result[$i])) $result[$i] = new SvnRevision($i,null,null,null);
			}
		}
		return $result;
	}
	function cat($path,$rev){
		$url = File::absolute($this->url,$path);
		if($this->check($rev,$url)){
			return Command::out(sprintf("%s cat -r %d %s",$this->cmd,$rev,$url));
		}
		throw new Exception("undef");
	}
	function diff($path,$revA,$revB){
		$url =File::absolute($this->url,$path);
		$result = Command::out(sprintf("%s diff -r %d:%d %s",$this->cmd,$revA,$revB,$url));
		if(empty($result)) throw new Exception("undef");
		return preg_replace("/^\+{3} .+\n/","",preg_replace("/^-{3} .+\n/","",trim(preg_replace("/^.+={60,70}\n(.+)$/ms","\\1",str_replace(array("\r\n","\r"),"\n",$result)))));
	}
	private function check($rev,$url){
		$info = Command::out(sprintf("%s info -r %d %s",$this->cmd,$rev,$url));
		return (substr_count($info,"\n") > 1);
	}
}
?>