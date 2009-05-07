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
class SvnViewerGooglecode extends Http{
	protected $project;

	protected function __new__($project){
		$this->project = $project;
	}
	public function last(){
		$this->do_get(sprintf("http://code.google.com/p/%s/source/list",$this->project));
		if(Tag::setof($tag,$this->body(),"body")){
			return (int)(preg_replace("/[^\d]/","",$tag->f("table[4].tr[1].td[0].a.value()")));
		}
		throw new Exception("undef");
	}
	public function revision($from,$to){
		$result = array();
		$url_format = sprintf("http://code.google.com/p/%s/source/detail?r=%%d",$this->project);
		for($i=$from;$i<=$to;$i++){
			$this->do_get(sprintf($url_format,$i));

			if(Tag::setof($tag,$this->body(),"body")){
				$block = $tag->f("table[3].tr[0]");
				$result[$i] = new SvnRevision($i,
										trim(strip_tags($block->f("td[0].table[0].tr[0].td[0].value()"))),
										$block->f("td[1].pre.value()"),
										trim(strip_tags($block->f("td[0].table[0].tr[1].td[0].span[0].param('title')"))),
										sprintf("http://code.google.com/p/%s/source/detail?r=%d",$this->project,$i)
									);
				foreach($block->f("td[1].table[0].in('tr')") as $tr){
					$result[$i]->setPath($tr->f("td[1].value()"),$tr->f("td[2].a.value()"));
				}
			}
		}
		return $result;
	}
	public function cat($path,$rev){
		$url = sprintf("http://%s.googlecode.com/svn-history/r%d/%s",$this->project,$rev,$path);
		$src = $this->do_get($url)->body();
		if($this->status() == 404) throw new Exception("undef");
		return $src;
	}
	public function diff($path,$revA,$revB){
		$url = sprintf("http://code.google.com/p/%s/source/diff?old=%d&r=%d&format=unidiff&path=%s",$this->project,$revA,$revB,$path);
		$src = $this->do_get($url)->body();

		if(Tag::setof($tag,$src,"body")){
			$lines = "";
			$block = "";
			$fs = $ts = $fl = $tl = 0;

			$blocks = $tag->f("table[5].in(tr)");
			foreach($blocks as $tr){
				$from = $tr->f("th[0].value()");

				if($from == "..."){
					if(!empty($block)) $lines .= sprintf("@@ -%d,%d +%d,%d @@\n%s",$fs,$fl,$ts,$tl,$block);
					$block = "";
					$fs = $ts = $fl = $tl = 0;
				}else{
					$to = $tr->f("th[1].value()");
					if($fs == 0 && !empty($from)) $fs = $from;
					if($ts == 0 && !empty($to)) $ts = $to;
					if(!empty($from)) $fl++;
					if(!empty($to)) $tl++;
					$block .= Text::htmldecode(strip_tags($tr->f("th[2].value()").$tr->f("td.value()"))."\n");
				}
			}
			return $lines;
		}
		throw new Exception("undef");
	}
}
?>