<?php
Rhaco::import("core.Tag");
/**
 * @author Kazutaka Tokushima
 * @license New BSD License
 */
class AtomContent extends Object{
	protected $type;
	protected $mode;
	protected $lang;
	protected $base;
	protected $value;

	protected function __getattr__($arg,$param){
		if(is_string($arg)) return Tag::xmltext($arg);
		return $arg;
	}
	protected function __str__(){
		if($this->none()) return "";
		$result = new Tag("content");
		foreach($this->access_members() as $name => $value){
			if(!empty($value)){
				switch($name){
					case "type":
					case "mode":
						$result->param($name,$value);
						break;
					case "lang":
					case "base":
						$result->param("xml:".$name,$value);
						break;
					case "value":
						$result->value($value);
						break;
				}
			}
		}
		return $result->get();
	}
	static public function parse(&$src){
		$result = null;
		if(Tag::setof($tag,$src,"content")){
			$result = new self();
			$result->type($tag->inParam("type"));
			$result->mode($tag->inParam("mode"));
			$result->lang($tag->inParam("xml:lang"));
			$result->base($tag->inParam("xml:base"));
			$result->value($tag->value());
			$src = str_replace($tag->plain(),"",$src);
		}
		return $result;
	}
}