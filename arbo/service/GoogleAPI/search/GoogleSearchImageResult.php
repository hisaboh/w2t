<?php
/**
 * @author Kazutaka Tokushima
 * @license New BSD License
 */
class GoogleSearchImageResult extends Object{
	static protected $__width__ = "type=number";
	static protected $__height__ = "type=number";
	protected $width;
	protected $height;
	protected $src;
	protected $url;
	protected $title;
	protected $content;

	static public function parse_list($response){
		$result = array();
		$res = Text::parse_json($response);

		if(!isset($res["responseData"]["results"])) throw new Exception("Invalid data");
		foreach($res["responseData"]["results"] as $re){
			$obj = new self();
			$obj->width($re["width"]);
			$obj->height($re["height"]);
			$obj->src($re["unescapedUrl"]);
			$obj->url($re["originalContextUrl"]);
			$obj->title($re["titleNoFormatting"]);
			$obj->content($re["contentNoFormatting"]);
			$result[] = $obj;
		}
		return $result;
	}
}
?>