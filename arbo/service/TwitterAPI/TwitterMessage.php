<?php
import("core.Text");
import("core.Tag");
module("TwitterUser");
/**
 * @author Kazutaka Tokushima
 * @license New BSD License
 */
class TwitterMessage extends Object{
	static protected $__user__ = "type=TwitterUser";
	static protected $__created_at__ = "type=timestamp";
	static protected $__truncated__ = "type=boolean";
	static protected $__favorited__ = "type=boolean";
	protected $user;
	protected $text;
	protected $truncated;
	protected $favorited;
	protected $created_at;
	protected $source;
	protected $in_reply_to_user_id;
	protected $in_reply_to_status_id;
	protected $id;

	protected function setText($value){
		$this->text = Text::htmldecode($value);
	}
	static public function parse_search_list($response){
		$results = array();
		
		if(Tag::setof($atom,$response,"feed")){
			foreach($atom->in("entry") as $entry){
				$self = new self();
				$self->id(preg_replace("/^.+\:([\d]+)$/","\\1",$entry->f("id.value()")));
				$self->text($entry->f("title.value()"));
				$self->source(Text::htmldecode($entry->f("twitter:source.value()")));
				$self->created_at($atom->f("published.value()"));
				
				$user = new TwitterUser();
				$user->name(str_replace("http://twitter.com/","",Text::htmldecode($entry->f("author.uri.value()"))));
				$user->screen_name(Text::htmldecode($entry->f("author.name.value()")));
				$user->profile_image_url(Text::htmldecode($entry->f("link[1].param(href)")));
				$self->user($user);
				$results[] = $self;
			}
		}
		return $results;
	}
	static public function parse_list($response){
		if(Tag::setof($tag,$response,"error")) throw new Exception($tag->value());
		$result = array();
		if(Tag::setof($tag,$response,"statuses")){
			foreach($tag->in("status") as $user){
				$hash = $user->hash();
				$obj = new self();
				$obj->user(TwitterUser::parse($hash["user"]));
				unset($hash["user"]);
				$result[] = $obj->cp($hash);
			}
		}
		return $result;
	}
	static public function parse($response){
		if(Tag::setof($tag,$response,"error")) throw new Exception($tag->value());
		if(Tag::setof($tag,$response,"status")){
			$hash = $tag->hash();
			$obj = new self();
			$obj->user(TwitterUser::parse($hash["user"]));
			unset($hash["user"]);
			return $obj->cp($hash);
		}
		throw new Exception("invalid data");
	}
}
?>