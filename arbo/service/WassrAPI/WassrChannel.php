<?php
import("core.Text");
/**
 * @author Kazutaka Tokushima
 * @license New BSD License
 */
class WassrChannel extends Object{
	static protected $__last_messaged_at__ = "type=timestamp";
	protected $title;
	protected $name_en;
	protected $onmitsu_fg;
	protected $image_url;
	protected $last_messaged_at;
	
	static public function parse(array $channel){
		$obj = new self();
		return $obj->cp($channel);
	}
	static public function parse_list($response){
		$result = array();
		$res = Text::parse_json($response);
		if(!isset($res)) throw new Exception($response);
		
		if(isset($res["channels"])){
			foreach($res["channels"] as $re){
				$obj = new self();
				$result[] = $obj->cp($re);
			}
		}
		return $result;
	}
}
?>