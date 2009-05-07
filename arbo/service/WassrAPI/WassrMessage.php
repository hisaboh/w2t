<?php
import("core.Text");
module("WassrUser");
/**
 * @author Kazutaka Tokushima
 * @license New BSD License
 */
class WassrMessage extends Object{
	static protected $__user__ = "type=WassrUser";
	static protected $__epoch__ = "type=timestamp";
	static protected $__created_at__ = "type=timestamp";
	protected $favorites;
	protected $user_login_id;
	protected $areacode;
	protected $photo_thumbnail_url;
	protected $html;
	protected $text;
	protected $reply_status_url;
	protected $user;
	protected $id;
	protected $reply_user_login_id;
	protected $link;
	protected $epoch;
	protected $rid;
	protected $photo_url;
	protected $reply_message;
	protected $reply_user_nick;
	protected $slurl;
	protected $areaname;
	protected $created_at;
	
	protected function setText($value){
		$this->text = Text::htmldecode($value);
	}
	protected function setEpoch($time){
		$this->epoch = $time;
		$this->created_at = $time;
	}
	static public function parse_list($response){
		$result = array();
		$res = Text::parse_json($response);
		if(!isset($res)) throw new Exception($response);
		
		foreach($res as $re){
			$obj = new self();
			$obj->user(WassrUser::parse($re["user"]));
			unset($re["user"]);
			
			$result[] = $obj->cp($re);
		}
		return $result;
	}
}
?>