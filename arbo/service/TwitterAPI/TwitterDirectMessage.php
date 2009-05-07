<?php
import("core.Tag");
module("TwitterUser");
/**
 * @author Kazutaka Tokushima
 * @license New BSD License
 */
class TwitterDirectMessage extends Object{
	static protected $__recipient__ = "type=TwitterUser";
	static protected $__sender__ = "type=TwitterUser";
	static protected $__created_at__ = "type=timestamp";
	protected $recipient;
	protected $sender;
	protected $recipient_screen_name;
	protected $recipient_id;
	protected $sender_screen_name;
	protected $sender_id;
	protected $created_at;
	protected $text;
	protected $id;
	
	static public function parse_list($response){
		if(Tag::setof($tag,$response,"error")) throw new Exception($tag->value());
		$result = array();
		if(Tag::setof($tag,$response,"direct-messages")){
			foreach($tag->in("direct_message") as $status){
				$re = $status->hash();
				$obj = new self();
				$obj->sender(TwitterUser::parse($re["sender"]));
				$obj->recipient(TwitterUser::parse($re["recipient"]));
				unset($re["recipient"],$re["sender"]);
				
				$result[] = $obj->cp($re);
			}
		}
		return $result;
	}
	static public function parse($response){
		if(Tag::setof($tag,$response,"error")) throw new Exception($tag->value());
		$result = array();
		if(Tag::setof($tag,$response,"direct_message")){
			$re = $status->hash();
			$obj = new self();
			$obj->sender(TwitterUser::parse($re["sender"]));
			$obj->recipient(TwitterUser::parse($re["recipient"]));
			unset($re["recipient"],$re["sender"]);
			
			return $obj->cp($re);
		}
		throw new Exception("invalid data");
	}
}
?>