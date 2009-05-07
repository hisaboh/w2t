<?php
/**
 * @author Kazutaka Tokushima
 * @license New BSD License
 */
class WikipediaResult extends Object{
	static protected $__id__ = "type=number";
	static protected $__length__ = "type=number";
	static protected $__datetime__ = "type=timestamp";
	protected $language;
	protected $id;
	protected $url;
	protected $title;
	protected $body;
	protected $length;
	protected $redirect;
	protected $strict;
	protected $datetime;

	public function parse_list($response){
		if($response === "N;") throw new Exception("Invalid data");
		$result = array();
		foreach(unserialize($response) as $re){
			$obj = new self();
			$obj->language($re["language"]);
			$obj->id($re["id"]);
			$obj->url($re["url"]);
			$obj->title($re["title"]);
			$obj->body($re["body"]);
			$obj->length($re["length"]);
			$obj->redirect($re["redirect"]);
			$obj->strict($re["strict"]);
			$obj->datetime($re["datetime"]);
			$result[] = $obj;
		}
		return $result;
	}
}
?>