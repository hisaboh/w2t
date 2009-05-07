<?php
/**
 * @author Kazutaka Tokushima
 * @license New BSD License
 */
class RssSource extends Object{
	protected $url;
	protected $value;

	protected function __getattr__($arg,$param){
		if(is_string($arg)) return Tag::xmltext($arg);
		return $arg;
	}
	protected function __str__(){
		$result = new Tag("source");
		foreach($this->access_members() as $name => $value){
			if(!empty($value)){
				switch($name){
					case "url":
					case "value":
						$result->param($name,$value);
						break;
				}
			}
		}
		return $result->get();
	}
	static public function parse(&$src){
		$result = array();
		foreach(Tag::anyhow($src)->in("source") as $in){
			$src = str_replace($in->plain(),"",$src);
			$o = new self();
			$o->url($in->inParam("url"));
			$o->value($in->value());
			$result[] = $o;
		}
		return $result;
	}
}