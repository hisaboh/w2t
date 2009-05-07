<?php
Rhaco::import("core.Tag");
Rhaco::import("core.Date");
Rhaco::import("net.feed.atom.AtomAuthor");
Rhaco::import("net.feed.atom.AtomLink");
Rhaco::import("net.feed.atom.AtomContent");
Rhaco::import("net.feed.atom.AtomSummary");
/**
 * @author Kazutaka Tokushima
 * @license New BSD License
 */
class AtomEntry extends Object{
	static protected $__published__ = "type=timestamp";
	static protected $__updated__ = "type=timestamp";
	static protected $__issued__ = "type=timestamp";
	static protected $__content__ = "type=AtomContent";
	static protected $__summary__ = "type=AtomSummary";
	static protected $__link__ = "type=AtomLink[]";
	static protected $__author__ = "type=AtomAuthor[]";
	protected $id;
	protected $title;
	protected $published;
	protected $updated;
	protected $issued;
	protected $xmlns;

	protected $content;
	protected $summary;
	protected $link;
	protected $author;
	
	protected $extra;

	protected function __init__(){
		$this->published = time();
		$this->updated = time();
		$this->issued = time();
	}
	protected function __getattr__($arg,$param){
		if(is_string($arg)) return Tag::xmltext($arg);
		return $arg;
	}
	public function get($enc=false){
		$value = sprintf("%s",$this);
		return (($enc) ? (sprintf("<?xml version=\"1.0\" encoding=\"%s\"?>\n",mb_detect_encoding($value))) : "").$value;
	}
	protected function __str__(){
		if($this->none()) return "";
		$result = new Tag("entry");
		foreach($this->access_members() as $name => $value){
			if(!empty($value)){
				switch($name){
					case "xmlns":
						$result->param("xmlns",$value);
						break;
					case "id":
					case "title":
						$result->add(new Tag($name,$value));
						break;
					case "published":
					case "updated":
					case "issued":
						$result->add(new Tag($name,Date::format_atom($value)));
						break;
					default:
						if(is_array($this->{$name})){
							foreach($this->{$name} as $o) $result->add($o);
							break;
						}else if(is_object($this->{$name})){
							$result->add($value);
							break;
						}else{
							$result->add(new Tag($name,$value));
							break;
						}
				}
			}
		}
		return $result->get();
	}
	public function first_href(){
		return (!empty($this->link)) ? current($this->link)->href() : null;
	}
	protected function formatContent(){
		return (isset($this->content)) ? $this->content->value() : null;
	}
	public function parse_extra(&$src){
		$this->call_modules("parse",$src,$this->extra);
	}
	static public function parse(&$src){
		$args = func_get_args();
		array_shift($args);

		$result = array();
		foreach(Tag::anyhow($src)->in("entry") as $in){
			$o = new self();
			foreach($args as $module) $o->add_modules($module);
			$o->id($in->f("id.value()"));
			$o->title($in->f("title.value()"));
			$o->published($in->f("published.value()"));
			$o->updated($in->f("updated.value()"));
			$o->issued($in->f("issued.value()"));

			$value = $in->value();
			$o->content = AtomContent::parse($value);
			$o->summary = AtomSummary::parse($value);
			$o->link = AtomLink::parse($value);
			$o->author = AtomAuthor::parse($value);
			$o->parse_extra($value);

			$result[] = $o;
			$src = str_replace($in->plain(),"",$src);
		}
		return $result;
	}
}