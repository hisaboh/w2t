<?php
import("core.Http");
import("core.Tag");
import("core.Text");
import("core.File");
module("TwitterUser");
module("TwitterMessage");
module("TwitterDirectMessage");
module("TwitterLimitStatus");
module("TwitpicResponse");
/**
 * Twitter
 *
 * @see http://watcher.moe-nifty.com/memo/docs/twitterAPI13.txt
 * @see http://twitpic.com/api.do#upload
 * @author Kazutaka Tokushima
 * @license New BSD License
 */
class TwitterAPI extends Http{
	protected $_base_url_ = "http://twitter.com/";
	protected $source;
	private $user;
	private $password;

	protected function __new__($user=null,$password=null){
		if(empty($user)){
			$this->user = def("arbo.service.TwitterAPI@user");
			$this->password = def("arbo.service.TwitterAPI@password");
		}else{
			$this->user = $user;
			$this->password = $password;
		}
		$this->auth($this->user,$this->password);
	}
	/**
	 * 検索
	 *
	 * @param string $query
	 * @return array TwitterMessage
	 */
	public function search($query){
		$this->vars("q",$query);
		$this->do_get("http://search.twitter.com/search.atom");
		return TwitterMessage::parse_search_list($this->body());
	}
	/**
	 * 公開タイムライン
	 *
	 * @param int $since_id
	 * @return array TwitterMessage
	 */
	public function public_timeline($since_id=null){
		if(!empty($since_id)) $this->vars("since_id",$since_id);
		$this->do_get("statuses/public_timeline.xml");
		return TwitterMessage::parse_list($this->body());
		/***
			$api = new TwitterAPI();
			$result = $api->public_timeline();
			foreach($result as $o){
				eq(true,($o instanceof TwitterMessage));
			}
		 */
	}
	/**
	 * フレンドタイムライン
	 *
	 * @param int $page
	 * @param int $since sec
	 * @return array TwitterMessage
	 */
	public function friends_timeline($page=1,$since=null){
		$this->vars("page",$page);
		if(!empty($since)) $this->vars("since",$this->format_date($since));
		$this->do_get("statuses/friends_timeline.xml");
		return TwitterMessage::parse_list($this->body());
		/***
			$api = new TwitterAPI();
			$result = $api->friends_timeline();
			foreach($result as $o){
				eq(true,($o instanceof TwitterMessage));
			}
		 */
	}
	/**
	 * 自分のタイムライン
	 *
	 * @param int $page
	 * @param int $since sec
	 * @param int $since_id
	 * @return array TwitterMessage
	 */
	public function my_timeline($page=1,$since=null,$since_id=null){
		$this->vars("page",$page);
		if(!empty($since)) $this->vars("since",$this->format_date($since));
		if(!empty($since_id)) $this->vars("since_id",$since_id);
		$this->do_get("statuses/user_timeline.xml");
		return TwitterMessage::parse_list($this->body());
		/***
			$api = new TwitterAPI();
			$result = $api->my_timeline();
			foreach($result as $o){
				eq(true,($o instanceof TwitterMessage));
			}
		 */
	}
	/**
	 * 返信
	 *
	 * @param int $page
	 * @param int $since sec
	 * @param int $since_id
	 * @return array TwitterMessage
	 */
	public function replies($page=1,$since=null,$since_id=null){
		$this->vars("page",$page);
		if(!empty($since)) $this->vars("since",$this->format_date($since));
		if(!empty($since_id)) $this->vars("since_id",$since_id);
		$this->do_get("statuses/replies.xml");
		return TwitterMessage::parse_list($this->body());
		/***
			$api = new TwitterAPI();
			$result = $api->replies();
			foreach($result as $o){
				eq(true,($o instanceof TwitterMessage));
			}
		 */
	}

	/**
	 * 特定ユーザのタイムライン
	 *
	 * @param string $user_id
	 * @param int $page
	 * @param int $since
	 * @param int $since_id
	 * @return array TwitterMessage
	 */
	public function user_timeline($user_id,$page=1,$since=null,$since_id=null){
		$this->vars("page",$page);
		if(!empty($since)) $this->vars("since",$this->format_date($since));
		if(!empty($since_id)) $this->vars("since_id",$since_id);
		$this->do_get("statuses/user_timeline/".$user_id.".xml");
		return TwitterMessage::parse_list($this->body());
	}

	/**
	 * 特定のステータス
	 *
	 * @param int $status_id
	 * @return TwitterMessage
	 */
	public function show($status_id){
		$this->do_get("statuses/show/".$status_id.".xml");
		return TwitterMessage::parse($this->body());
	}
	/**
	 * ステータスの削除
	 *
	 * @param int $status_id
	 * @return TwitterMessage
	 */
	public function destroy($status_id){
		$this->do_post("statuses/destroy/".$status_id.".xml");
		return TwitterMessage::parse($this->body());
	}
	/**
	 * ステータスの更新（発言）
	 *
	 * @param string $status
	 * @return TwitterMessage
	 */
	public function update($status){
		if(isset($this->source)) $this->vars("source",$this->source);
		$this->vars("status",$status);
		$this->do_post("statuses/update.xml");
		return TwitterMessage::parse($this->body());
		/***
		 	try{
				$api = new TwitterAPI();
				$api->update("doc test");
				eq(true,true);
			}catch(Exception $e){
				eq(false,$e->getMessage());
			}
		 */
	}
	/**
	 * ステータスの更新(画像添付)
	 *
	 * @param File $image_data
	 * @param string $status
	 * return TwitpicResponse
	 */
	public function image_update(File $image_data,$status=null){
		$this->vars("media",$image_data);
		$this->vars("message",$status);
		$this->vars("username",$this->user);
		$this->vars("password",$this->password);
		$this->do_post("http://twitpic.com/api/uploadAndPost");
		return TwitpicResponse::parse($this->body());
	}
	/**
	 * friends
	 *
	 * @param int $page
	 * @param int $since sec
	 * @return array TwitterUser
	 */
	public function friends($page=1,$since=null){
		$this->vars("page",$page);
		$this->vars("list",false);
		if(!empty($since)) $this->vars("since",$this->format_date($since));
		$this->do_get("statuses/friends.xml");
		return TwitterUser::parse_list($this->body());
	}
	/**
	 * followers
	 *
	 * @param int $page
	 * @param int $since sec
	 * @return array TwitterUser
	 */
	public function followers($page=1,$since=null){
		$this->vars("page",$page);
		$this->vars("list",false);
		if(!empty($since)) $this->vars("since",$this->format_date($since));
		$this->do_get("statuses/followers.xml");
		return TwitterUser::parse_list($this->body());
	}
	/**
	 * ダイレクト受信メッセージの取得
	 *
	 * @param int $page
	 * @param int $since sec
	 * @return array TwitterDirectMessage
	 */
	public function direct_messages($page=1,$since=null){
		$this->vars("page",$page);
		if(!empty($since_id)) $this->vars("since_id",$since_id);
		if(!empty($since)) $this->vars("since",$this->format_date($since));
		$this->do_get("direct_messages.xml");
		return TwitterDirectMessage::parse_list($this->body());
	}
	/**
	 * ダイレクト送信メッセージの取得
	 *
	 * @param int $page
	 * @param int $since sec
	 * @return array TwitterDirectMessage
	 */
	public function direct_sents($page=1,$since=null){
		$this->vars("page",$page);
		if(!empty($since_id)) $this->vars("since_id",$since_id);
		if(!empty($since)) $this->vars("since",$this->format_date($since));
		$this->do_get("direct_messages/sent.xml");
		return TwitterDirectMessage::parse_list($this->body());
	}
	/**
	 * ダイレクトメッセージの送信
	 *
	 * @param string $user
	 * @param string $text
	 * @return TwitterDirectMessage
	 */
	public function direct_new($user,$text){
		$this->vars("user",$user);
		$this->vars("text",$text);
		$this->do_post("direct_messages/new.xml");
		return TwitterDirectMessage::parse($this->body());
	}
	/**
	 * ダイレクトメッセージの削除
	 *
	 * @param int $message_id
	 * @return TwitterDirectMessage
	 */
	public function direct_destroy($message_id){
		$this->do_post("direct_messages/destroy/".$message_id.".xml");
		return TwitterDirectMessage::parse($this->body());
	}
	/**
	 * 既にフレンドか
	 *
	 * @param string $user_a
	 * @param string $user_b
	 * @return boolean
	 */
	public function friend_exists($user_a,$user_b){
		$this->vars("user_a",$user_a);
		$this->vars("user_b",$user_b);
		$this->do_get("friendships/exists.xml");
		return ($this->body() === "true");
	}
	/**
	 * フレンドから除外
	 *
	 * @param stirng $user_id
	 * @return TwitterUser
	 */
	public function friend_destroy($user_id){
		$this->do_post("friendships/destroy/".$user_id.".xml");
		return TwitterUser::parse($this->body());
	}
	/**
	 * フレンドに登録
	 *
	 * @param string $user_id
	 * @return TwitterUser
	 */
	public function friend_create($user_id){
		$this->do_post("friendships/create/".$user_id.".xml");
		return TwitterUser::parse($this->body());
	}
	/**
	 * 現在位置の更新
	 *
	 * @param string $location
	 * @return TwitterUser
	 */
	public function update_location($location){
		$this->vars("location",$location);
		$this->do_post("account/update_location.xml");
		return TwitterUser::parse($this->body());
	}
	/**
	 * デバイスの更新
	 *
	 * @param string $device sms, im, none
	 * @return TwitterUser
	 */
	public function update_device($device){
		$this->vars("device",$device); //sms, im, none
		$this->do_post("account/update_delivery_device.xml");
		return TwitterUser::parse($this->body());
	}
	/**
	 * API実行可能情報
	 *
	 * @return TwitterLimitStatus
	 */
	public function rate_limit_status(){
		$this->do_get("account/rate_limit_status.xml");
		return TwitterLimitStatus::parse($this->body());
		/***
			$api = new TwitterAPI();
			eq(true,($api->rate_limit_status() instanceof TwitterLimitStatus));
		 */
	}
	/**
	 * ユーザをブロックする
	 *
	 * @param string $user_id
	 * @return TwitterUser
	 */
	public function blocks_create($user_id){
		$this->do_post("blocks/create/".$user_id.".xml");
		return TwitterUser::parse($this->body());
	}
	/**
	 * ブロックしたユーザを解除する
	 *
	 * @param string $user_id
	 * @return TwitterUser
	 */
	public function blocks_destroy($user_id){
		$this->do_post("blocks/destroy/".$user_id.".xml");
		return TwitterUser::parse($this->body());
	}

	private function format_date($sec){
		return date("D, d M Y H:i:s T",$sec);
	}
}
?>