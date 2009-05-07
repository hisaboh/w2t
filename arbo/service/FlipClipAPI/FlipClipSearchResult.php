<?php
import("core.File");
import("core.Http");
import("core.Text");
import("core.Paginator");
module("FlipClipCategory");
module("FlipClipTag");
module("FlipClipAuthor");
/**
 * @author Kazutaka Tokushima
 * @license New BSD License
 */
class FlipClipSearchResult extends Object{
	static protected $__author__ = "type=FlipClipAuthor";
	static protected $__updated__ = "type=timestamp";
	static protected $__shoot_date__ = "type=timestamp";
	static protected $__category__ = "type=FlipClipCategory";
	static protected $__subcategory__ = "type=FlipClipCategory";
	static protected $__tags__ = "type=FlipClipTag[]";
	static protected $__privacy__ = "type=number";
	static protected $__duration__ = "type=number";
	static protected $__views__ = "type=number";
	static protected $__thumbnails__ = "type=string[]";
	protected $id;
	protected $title;
	protected $url;
	protected $content_type;
	protected $author;
	protected $summary;
	protected $updated;
	protected $category;
	protected $subcategory;
	protected $tags;
	protected $latitude;
	protected $longitude;
	protected $privacy;
	protected $duration;
	protected $views;
	protected $image;
	protected $image_medium;
	protected $image_small;
	protected $thumbnail;
	protected $embed_script;

	static public function parse_list($response,$paginator){
		$result = array();
		$res = Text::parse_json($response);
		if(isset($res["error"])) throw new Exception($res["error"]);
		if(!empty($res["pager"])) $paginator = new Paginator($res["pager"]["clips_in_page"],$res["pager"]["current_page"],$res["pager"]["total_entries"]);
		if(!empty($res["entries"])){
			foreach($res["entries"] as $re){
				$obj = new self();
				$obj->id($re["id"]);
				$obj->title($re["title"]);
				$obj->url($re["permalink"]);
				$obj->author(new FlipClipAuthor($re["author"]["id"],$re["author"]["name"],$re["author"]["uri"]));			
				$obj->summary($re["summary"]);
				$obj->updated($re["updated"]);

				if(isset($re["category"])) $obj->category(new FlipClipCategory($re["category"]["term"],$re["category"]["scheme"],$re["category"]["label"]));
				if(isset($re["subcategory"])) $obj->category(new FlipClipCategory($re["subcategory"]["term"],$re["subcategory"]["scheme"],$re["subcategory"]["label"]));
				if(isset($re["tags"])){
					foreach($re["tags"] as $tag) $obj->tags(new FlipClipTag($tag["term"],$tag["scheme"],$tag["label"]));
				}
				$obj->embed_script($re["embed_script"]);
				$obj->latitude($re["latitude"]);
				$obj->longitude($re["longitude"]);
				$obj->privacy($re["privacy"]);
				$obj->duration($re["duration"]);
				$obj->image($re["image"]);
				$obj->image_medium($re["imageMedium"]);
				$obj->image_small($re["imageSmall"]);
				$obj->thumbnail($re["thumbnail"]);

				$result[] = $obj;
			}
		}
		return $result;
	}
}
?>