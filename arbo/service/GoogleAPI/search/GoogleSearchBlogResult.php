<?php
/**
 * @author Kazutaka Tokushima
 * @license New BSD License
 */
class GoogleSearchBlogResult extends Object{
	static protected $__published__ = "type=timestamp";
	protected $url;
	protected $title;
	protected $content;
	protected $published;
	protected $author;

	static public function parse_list($response){
		$result = array();
		$res = Text::parse_json($response);

		if(!isset($res["responseData"]["results"])) throw new Exception("Invalid data");
		foreach($res["responseData"]["results"] as $re){
			$obj = new self();
			$obj->url($re["blogUrl"]);
			$obj->title($re["titleNoFormatting"]);
			$obj->content($re["content"]);
			$obj->author($re["author"]);
			$obj->published($re["publishedDate"]);
			$result[] = $obj;
		}
		return $result;
	}
}
?>