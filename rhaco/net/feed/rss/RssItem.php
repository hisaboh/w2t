<?php
Rhaco::import("net.feed.rss.RssEnclosure");
Rhaco::import("net.feed.rss.RssSource");
/**
 * @author Kazutaka Tokushima
 * @license New BSD License
 */
class RssItem extends Object{
	static protected $__pubDate__ = "type=timestamp";
	static protected $__enclosure__ = "type=RssEnclosure[]";
	static protected $__source__ = "type=RssSource[]";
	protected $title;
	protected $link;
	protected $description;
	protected $author;
	protected $category;
	protected $comments;
	protected $pubDate;
	protected $guid;
	protected $enclosure;
	protected $source;

	protected function __getattr__($arg,$param){
		if(is_string($arg)) return Tag::xmltext($arg);
		return $arg;
	}
	protected function __str__(){
		$result = new Tag("item");
		foreach($this->access_members() as $name => $value){
			if(!empty($value)){
				switch($name){
					case "title":
					case "link":
					case "description":
					case "author":
					case "category":
					case "comments":
					case "guid":
						$result->add(new Tag($name,$value));
						break;
					case "pubDate":
						$result->add(new Tag($name,$this->formatDate($value)));
						break;
					default:
						if(is_array($this->{$name})){
							foreach($this->{$name} as $o) $channel->add($o);
							break;
						}else if(is_object($this->{$name})){
							$channel->add($value);
							break;
						}else{
							$channel->add(new Tag($name,$value));
							break;
						}
				}
			}
		}
		return $result->get();
	}
	private function formatDate($time){
		$tzd = date("O",$time);
		$tzd = $tzd[0].substr($tzd,1,2).":".substr($tzd,3,2);
		return date("Y-m-d\TH:i:s".$tzd,$time);
	}
	static public function parse($src){
		$result = array();
		foreach(Tag::anyhow($src)->in("item") as $in){
			$o = new self();
			$o->title($in->f("title.value()"));
			$o->link($in->f("link.value()"));
			$o->description($in->f("description.value()"));
			$o->author($in->f("author.value()"));
			$o->category($in->f("category.value()"));
			$o->comments($in->f("comments.value()"));
			$o->pubDate($in->f("pubDate.value()"));
			$o->guid($in->f("guid.value()"));
	
			$value = $in->value();
			$o->enclosure = RssEnclosure::parse($value);
			$o->source = RssSource::parse($src);
			$result[] = $o;
		}
		return $result;
	}
}