<?php
import("core.Http");
module("MixiUser");
module("MixiContent");
/**
 * @author Kazutaka Tokushima
 * @license New BSD License
 */
class Mixi extends Http{
	private $member_id;
	
	protected function __new__($user,$password){
		$this->wsse($user,$password);
		$this->member_id = preg_replace("/^.+\?id=(\d+)$/","\\1",
										Tag::anyhow($this->do_post("http://mixi.jp/atom/tracks")->body())->f("atom:author.atom:uri.value()")
									);
	}
	public function footprint(){
		$results = array();
		if(Tag::setof($feed,$this->do_get("http://mixi.jp/atom/tracks/r=2/member_id=".$this->member_id)->body(),"feed")){
			foreach($feed->in("entry") as $entry) $results[] = MixiUser::parse($entry);
		}
		return $results;
	}
	public function friends(){
		$results = array();
		if(Tag::setof($feed,$this->do_get("http://mixi.jp/atom/friends/r=1/member_id=".$this->member_id)->body(),"feed")){
			foreach($feed->in("entry") as $entry) $results[] = MixiUser::parse($entry);
		}
		return $results;
			
	}
	public function notify(){
		$results = array();
		if(Tag::setof($feed,$this->do_get("http://mixi.jp/atom/notify/r=2/member_id=".$this->member_id)->body(),"feed")){
			foreach($feed->in("entry") as $entry) $results[] = MixiContent::parse($entry);
		}
		return $results;
	}

	public function updates(){
		$results = array();
		if(Tag::setof($feed,$this->do_get("http://mixi.jp/atom/updates/r=1/member_id=".$this->member_id)->body(),"feed")){
			foreach($feed->in("entry") as $entry) $results[] = MixiContent::parse($entry);
		}
		return $results;
	}
	
	public function post_diary($title,$summary){
		$tag = new Tag("entry");
		$tag->param("xmlns","http://www.w3.org/2007/app");
		$tag->add(new Tag("title",$title));
		$tag->add(new Tag("summary",$summary));
		$this->raw($tag->get("utf-8"));
		$this->do_post("http://mixi.jp/atom/diary/member_id=".$this->member_id);
	}
}
?>