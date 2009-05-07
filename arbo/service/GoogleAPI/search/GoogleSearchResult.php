<?php
/**
 * @author Kazutaka Tokushima
 * @license New BSD License
 */
class GoogleSearchResult extends Object{
	protected $url;
	protected $cache_url;
	protected $title;
	protected $content;

	static public function parse_list($response){
		$result = array();
		$res = Text::parse_json($response);

		if(!isset($res["responseData"]["results"])) throw new Exception("Invalid data");
		foreach($res["responseData"]["results"] as $re){
			$obj = new self();
			$obj->cache_url($re["cacheUrl"]);
			$obj->url($re["unescapedUrl"]);
			$obj->title($re["titleNoFormatting"]);
			$obj->content($re["content"]);
			$result[] = $obj;
		}
		return $result;
	}
}
?>