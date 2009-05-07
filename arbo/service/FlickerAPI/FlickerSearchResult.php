<?php
import("core.File");
import("core.Http");
/**
 * FlickerAPI
 *
 * @see http://www.flickr.com/services/api/
 * @author Kazutaka Tokushima
 * @license New BSD License
 */
class FlickerSearchResult extends Object{
	static private $image_url = "http://farm1.static.flickr.com";
	static protected $__url__ = "set=false";
	static protected $__square__ = "set=false";
	static protected $__thumbnail__ = "set=false";
	static protected $__small__ = "set=false";
	static protected $__medium__ = "set=false";
	static protected $__large__ = "set=false";
	static protected $__original__ = "set=false";

	protected $id;
	protected $owner;
	protected $secret;
	protected $server;
	protected $farm;
	protected $title;
	protected $ispublic;
	protected $isfriend;
	protected $isfamily;

	protected $license;
	protected $date_upload;
	protected $date_taken;
	protected $owner_name;
	protected $icon_server;
	protected $original_format;
	protected $last_update;
	protected $geo;
	protected $tags;
	protected $machine_tags;
	protected $o_dims;
	protected $views;
	protected $media;

	protected $url;
	protected $square;
	protected $thumbnail;
	protected $small;
	protected $medium;
	protected $large;
	protected $original;

	static public function parse_list($response){
		$result = array();
		if(Tag::setof($tag,$response,"photos")){
			foreach($tag->in("photo") as $photo){
				$obj = new self();
				$params = array();
				foreach($photo->arParam() as $param) $params[$param[0]] = $param[1];
				$result[] = $obj->cp($params);
			}
		}
		return $result;
	}
	protected function getUrl(){
		return sprintf("http://www.flickr.com/photos/%s/%s",$this->owner(),$this->id());
	}
	protected function getSquare(){
		return sprintf("%s/%s/%s_%s_s.jpg",self::$image_url,$this->server(),$this->id(),$this->secret());
	}
	protected function getThumbnail(){
		return sprintf("%s/%s/%s_%s_t.jpg",self::$image_url,$this->server(),$this->id(),$this->secret());
	}
	protected function getSmall(){
		return sprintf("%s/%s/%s_%s_m.jpg",self::$image_url,$this->server(),$this->id(),$this->secret());
	}
	protected function getMedium(){
		return sprintf("%s/%s/%s_%s.jpg",self::$image_url,$this->server(),$this->id(),$this->secret());
	}
	protected function getLarge(){
		return sprintf("%s/%s/%s_%s_b.jpg",self::$image_url,$this->server(),$this->id(),$this->secret());
	}
	protected function getOriginal(){
		return sprintf("%s/%s/%s_%s_o.jpg",self::$image_url,$this->server(),$this->id(),$this->secret());
	}
}
?>