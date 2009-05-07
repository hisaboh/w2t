<?php
import("core.Text");
/**
 * @author Kazutaka Tokushima
 * @license New BSD License
 */
class WassrUser extends Object{
	static protected $__profimg_last_updated_at__ = "type=timestamp";
	protected $profimg_last_updated_at;
	protected $profile_image_url;
	protected $protected;
	protected $screen_name;
	protected $name;
	protected $nick;
	protected $login_id;

	static public function parse_list($response){
		$result = array();
		$res = Text::parse_json($response);
		if(!isset($res)) throw new Exception($response);
		
		foreach($res as $re){
			$obj = new self();
			$result[] = $obj->cp($re);
		}
		return $result;
	}
	static public function parse($response){
		if(!is_array($response)) $response = Text::parse_json($response);
		$obj = new self();
		return $obj->cp($response);
	}
	protected function __str__(){
		return $this->screen_name();
	}
}
?>