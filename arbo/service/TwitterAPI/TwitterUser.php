<?php
import("core.Text");
/**
 * @author Kazutaka Tokushima
 * @license New BSD License
 */
class TwitterUser extends Object{
	static protected $__followers_count__ = "type=number";
	protected $followers_count;
	protected $description;
	protected $url;
	protected $profile_image_url;
	protected $protected;
	protected $location;
	protected $screen_name;
	protected $name;
	protected $id;

	static public function parse_list($response){
		if(Tag::setof($tag,$response,"error")) throw new Exception($tag->value());
		$result = array();
		if(Tag::setof($tag,$response,"users")){
			foreach($tag->in("user") as $user){
				$hash = $user->hash();
				$obj = new self();
				$result[] = $obj->cp($hash);
			}
		}
		return $result;
	}
	static public function parse($response){
		if(!is_array($response)){
			if(Tag::setof($tag,$response,"error")) throw new Exception($tag->value());
			Tag::setof($tag,$response,"user");
			$response = $tag->hash();
		}
		$obj = new self();
		return $obj->cp($response);
	}
	protected function __str__(){
		return $this->screen_name();
	}
}
?>