<?php
Rhaco::import("core.Tag");
/**
 * @author Kazutaka Tokushima
 * @license New BSD License
 */
class AtomAuthor extends Object{
	protected $name;
	protected $url;
	protected $email;

	protected function __getattr__($arg,$param){
		if(is_string($arg)) return Tag::xmltext($arg);
		return $arg;
	}
	protected function __str__(){
		if($this->none()) return "";
		$result = new Tag("author");
		foreach($this->access_members() as $name => $value){
			if(!empty($value)){
				switch($name){
					case "name":
					case "url":
					case "email":
						$result->add(new Tag($name,$value));
						$bool = true;
						break;
				}
			}
		}
		return $result->get();
	}
	static public function parse(&$src){
		$result = array();
		foreach(Tag::anyhow($src)->in("author") as $in){
			$src = str_replace($in->plain(),"",$src);
			$o = new self();
			$o->name($in->f("name.value()"));
			$o->url($in->f("url.value()"));
			$o->email($in->f("email.value()"));
			$result[] = $o;
		}
		return $result;
	}
}