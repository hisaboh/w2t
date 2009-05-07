<?php
/**
 * @author Kazutaka Tokushima
 * @license New BSD License
 */
class RssEnclosure extends Object{
	static protected $__length__ = "type=number";
	protected $url;
	protected $type;
	protected $length;

	protected function __str__(){
		$result = new Tag("enclosure");
		foreach($this->access_members() as $name => $value){
			if(!empty($value)){
				switch($name){
					case "url":
					case "type":
					case "length":
						$result->param($name,$value);
						break;
				}
			}
		}
		return $result->get();
	}

	static public function parse(&$src){
		$result = array();
		foreach(Tag::anyhow($src)->in("enclosure") as $in){
			$src = str_replace($in->plain(),"",$src);
			$o = new self();
			$o->url($in->inParam("url"));
			$o->type($in->inParam("type"));
			$o->length($in->inParam("length"));
			$result[] = $o;
		}
		return $result;
	}
}