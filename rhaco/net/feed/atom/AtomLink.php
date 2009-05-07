<?php
Rhaco::import("core.Tag");
/**
 * @author Kazutaka Tokushima
 * @license New BSD License
 */
class AtomLink extends Object{
	protected $rel;
	protected $type;
	protected $href;

	protected function __getattr__($arg,$param){
		if(is_string($arg)) return Tag::xmltext($arg);
		return $arg;
	}
	protected function __str__(){
		if($this->none()) return "";
		$result = new Tag("link");
		foreach($this->access_members() as $name => $value){
			if(!empty($value)){
				switch($name){
					case "href":
					case "rel":
					case "type":
						$result->param($name,$value);
						break;
				}
			}
		}
		return $result->get();
	}
	static public function parse(&$src){
		$result = array();
		foreach(Tag::anyhow($src)->in("link") as $in){
			$o = new self();
			$o->href($in->inParam("href"));
			$o->rel($in->inParam("rel"));
			$o->type($in->inParam("type"));
			$result[] = $o;
			$src = str_replace($in->plain(),"",$src);
		}
		return $result;
	}
}