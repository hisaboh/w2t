<?php
Rhaco::import("core.Tag");
/**
 * @author Kazutaka Tokushima
 * @license New BSD License
 */
class OpmlOutline extends Object{
	static protected $__comment__ = "type=boolean";
	static protected $__breakpoint__ = "type=boolean";
	static protected $__outline__ = "type=OpmlOutline[]";
	
	protected $text;
	protected $type;
	protected $value;
	protected $comment;
	protected $breakpoint;
	protected $htmlUrl;
	protected $xmlUrl;
	protected $title;
	protected $description;
	protected $outline;
	protected $tags;

	protected function __getattr__($arg,$param){
		if(is_string($arg)) return Tag::xmltext($arg);
		return $arg;
	}
	public function html(){
		$list = array();
		if($this->isHtmlUrl()) $list[] = $this;
		foreach($this->arOutline() as $outline) $list = array_merge($list,$outline->html());
		return $list;
	}
	public function xml(){
		$list = array();
		if($this->isXmlUrl()) $list[] = $this;
		foreach($this->arOutline() as $outline) $list = array_merge($list,$outline->xml());
		return $list;
	}
	protected function __str__(){
		/***
		 * $src = '<outline title="りあふ の にっき" htmlUrl="http://riaf.g.hatena.ne.jp/riaf/" type="rss" xmlUrl="http://riaf.g.hatena.ne.jp/riaf/rss2" />';
		 * $xml = OpmlOutline::parse($src);
		 * eq($src,(string)$xml);
		 */
		$outTag	= new Tag("outline");
		if($this->isTitle()) $outTag->param("title",$this->title());
		if($this->isHtmlUrl()) $outTag->param("htmlUrl",$this->htmlUrl());
		if($this->isType()) $outTag->param("type",$this->type());
		if($this->isXmlUrl()) $outTag->param("xmlUrl",$this->xmlUrl());
		if($this->isComment()) $outTag->param("isComment",$this->isComment());
		if($this->isBreakpoint()) $outTag->param("isBreakpoint",$this->isBreakpoint());
		if($this->isText()) $outTag->param("text",$this->text());
		if($this->isDescription()) $outTag->param("description",$this->description());
		if($this->isTags()) $outTag->param("tags",$this->tags());
		$outTag->add($this->value());
		foreach($this->arOutline() as $outline) $outTag->add($outline);
		return $outTag->get();
	}
	
	static public function parse($src,$tags=""){
		$result = null;
		if(Tag::setof($tag,$src,"outline")){
			$result = new self();
			$result->text($tag->inParam("text"));
			$result->type($tag->inParam("type"));
			$result->comment($tag->inParam("isComment",false));
			$result->breakpoint($tag->inParam("isBreakpoint",false));		

			$result->htmlUrl($tag->inParam("htmlUrl"));
			$result->xmlUrl($tag->inParam("xmlUrl"));
			$result->title($tag->inParam("title"));
			$result->description($tag->inParam("description"));
			$result->tags($tags);

			foreach($tag->in("outline") as $outlinetag){
				$result->outline(self::parse($outlinetag->plain(),$tags));
			}
		}
		return $result;
		/***
		 * $src = '<outline title="りあふ の にっき" htmlUrl="http://riaf.g.hatena.ne.jp/riaf/" type="rss" xmlUrl="http://riaf.g.hatena.ne.jp/riaf/rss2" />';
		 * $xml = OpmlOutline::parse($src);
		 * eq("りあふ の にっき",$xml->title());
		 * eq("http://riaf.g.hatena.ne.jp/riaf/rss2",$xml->xmlUrl());
		 * eq("http://riaf.g.hatena.ne.jp/riaf/",$xml->htmlUrl());
		 * eq("rss",$xml->type());
		 * 
		 */
	}
}
?>