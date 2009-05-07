<?php
Rhaco::import("core.Tag");
/**
 * @author Kazutaka Tokushima
 * @license New BSD License
 */
class AtomSummary extends Object{
	protected $type;
	protected $lang;
	protected $value;

	protected function __getattr__($arg,$param){
		if(is_string($arg)) return Tag::xmltext($arg);
		return $arg;
	}
	protected function __str__(){
		if($this->none()) return "";
		$result = new Tag("summary");
		foreach($this->access_members() as $name => $value){
			if(!empty($value)){
				switch($name){
					case "type":
						$result->param($name,$value);
						break;
					case "lang":
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
		if(Tag::setof($tag,$src,"summary")){
			$result = new self();
			$result->type($tag->inParam("type","text"));
			$result->lang($tag->inParam("xml:lang"));
			$result->value($tag->value());
			
			$src = str_replace($tag->plain(),"",$src);
		}
		return $result;
	}
}