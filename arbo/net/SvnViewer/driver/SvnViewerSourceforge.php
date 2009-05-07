<?php
import("core.Http");
import("core.Tag");
import("core.Text");
import("core.Lib");
module("SvnRevision");
/**
 * @author Kazutaka Tokushima
 * @license New BSD License
 */
class SvnViewerSourceforge extends Http{
	protected $project;

	protected function __new__($project){
		$this->project = $project;
	}
	public function last(){
		$this->do_get(sprintf("http://%s.svn.sourceforge.net/viewvc/%s/?view=rev",$this->project,$this->project));
		if(Tag::setof($tag,$this->body(),"title")){
			return (int)(preg_replace("/^.+Revision ([\d]+).*$/","\\1",$tag->value()));
		}
		throw new Exception("undef");
	}
	public function revision($from,$to){
		$result = array();
		$url_format = sprintf("http://%s.svn.sourceforge.net/viewvc/%s/?view=rev&revision=%%d",$this->project,$this->project);

		for($i=$from;$i<=$to;$i++){
			if(Tag::setof($tag,$this->do_get(sprintf($url_format,$i))->body(),"body")){
				$result[$i] = new SvnRevision($i,
											$tag->f("table[0].tr[1].td[0].value()"),
											Text::htmldecode($tag->f("table[0].tr[3].td[0].pre.value()")),
											preg_replace("/^(.+?)<.+$/","\\1",$tag->f("table[0].tr[2].td[0].value()")),
											sprintf("http://%s.svn.sourceforge.net/viewvc/%s?view=rev&revision=%d",$this->project,$this->project,$i)
										);
				foreach($tag->f("table[1].tbody.in(tr)") as $tr){
					$result[$i]->path($tr->f("td[1].a.value()"),
										preg_replace("/^.+>/","",$tr->f("td[0].a.value()")));
				}
			}
		}
		return $result;
	}
	public function cat($path,$rev){
		$url = sprintf("http://%s.svn.sourceforge.net/viewvc/%s/%s?pathrev=%d",$this->project,$this->project,$path,$rev);
		$src = $this->do_get($url)->body();
		if($this->status() == 404 || strrpos($src,"ViewVC Help") !== false) throw new Exception("undef");
		return $src;
	}
	public function diff($path,$revA,$revB){
		$url = sprintf("http://%s.svn.sourceforge.net/viewvc/%s/%s?r1=%d&r2=%d&diff_format=u",$this->project,$this->project,$path,$revA,$revB);

		if(Tag::setof($tag,$this->do_get($url)->body(),"body")){
			$result = Text::htmldecode(preg_replace("/^\+{3} .+\n/","",preg_replace("/^-{3} .+\n/","",trim($tag->f("pre.value()")))));
			if($result !== "400 Bad Request") return $result;
		}
		throw new Exception("undef");
	}
}
?>