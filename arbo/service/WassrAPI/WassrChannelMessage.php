<?php
import("core.Text");
module("WassrChannel");
module("WassrUser");
/**
 * @author Kazutaka Tokushima
 * @license New BSD License
 */
class WassrChannelMessage extends Object{
	static protected $__user__ = "type=WassrUser";
	static protected $__channel__ = "type=WassrChannel";
	static protected $__created_on__ = "type=timestamp";
	protected $favorites;
	protected $body;
	protected $html;
	protected $created_on;
	protected $user;
	protected $channel;
	protected $rid;
	protected $photo_url;
	
	static public function parse_list($response){
		$result = array();
		$res = Text::parse_json($response);
		if(!empty($res)){
			foreach($res as $re){
				$obj = new self();
				$obj->user(WassrUser::parse($re["user"]));
				unset($re["user"]);
				$obj->channel(WassrChannel::parse($re["channel"]));
				unset($re["channel"]);
				
				$result[] = $obj->cp($re);
			}
		}
		return $result;
	}
	static public function parse_update($response){
		$result = array();
		$re = Text::parse_json($response);
		if(isset($re["error"])) throw new Exception($res["error"]);

		$obj = new self();
		return $obj->cp($re);
	}
}
?>