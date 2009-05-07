<?php
/**
 * @author Kazutaka Tokushima
 * @license New BSD License
 */
class GoogleSearchNewsResult extends Object{
	static protected $__published__ = "type=timestamp";
	protected $url;
	protected $publisher;
	protected $title;
	protected $content;
	protected $location;
	protected $published;

	static public function parse_list($response){
		$result = array();
		$res = Text::parse_json($response);

		if(!isset($res["responseData"]["results"])) throw new Exception("Invalid data");
		foreach($res["responseData"]["results"] as $re){
			$obj = new self();
			$obj->location($re["location"]);
			$obj->publisher($re["publisher"]);
			$obj->url($re["unescapedUrl"]);
			$obj->title($re["titleNoFormatting"]);
			$obj->content($re["content"]);
			$obj->published($re["publishedDate"]);
			$result[] = $obj;
		}
		return $result;
	}
}
?>