<?php
import("core.Http");
import("core.Tag");
import("core.Text");
import("core.File");
module("WassrMessage");
module("WassrTodo");
module("WassrChannelMessage");
/**
 * Wassr
 *
 * @author Kazutaka Tokushima
 * @license New BSD License
 */
class WassrAPI extends Http{
	protected $_base_url_ = "http://api.wassr.jp/";
	protected $source;
	private $id;

	protected function __new__($user=null,$password=null){
		$this->id = (isset($user)) ? $user : def("arbo.service.WassrAPI@user");
		$password = (isset($password)) ? $password : def("arbo.service.WassrAPI@password");
		if(isset($this->id)) $this->auth($this->id,$password);
	}
	/**
	 * 日本中のひとこと
	 *
	 * @param int $page
	 * @return array WassrMessage
	 */
	public function public_timeline($page=1){
        print("hogehogehogehoge");
		$this->vars("page",$page);
		$this->do_get("statuses/public_timeline.json");
		return WassrMessage::parse_list($this->body());
		/***
			$api = new WassrAPI();
			$result = $api->public_timeline();
			foreach($result as $r){
				eq(true,($r instanceof WassrMessage));
			}
		 */
	}
	/**
	 * 友達のひとこと
	 *
	 * @return array WassrMessage
	 */
	public function friends_timeline($page=1){
		$this->vars("page",$page);
		$this->vars("id",$this->id);
		$this->do_get("statuses/friends_timeline.json");
		return WassrMessage::parse_list($this->body());
		/***
			$api = new WassrAPI();
			$result = $api->friends_timeline();
			foreach($result as $r){
				eq(true,($r instanceof WassrMessage));
			}
		 */
	}
	/**
	 * ユーザの発言
	 *
	 * @return array WassrMessage
	 */
	public function user_timeline(){
		$this->vars("id",$this->id);
		$this->do_get("statuses/user_timeline.json");
		return WassrMessage::parse_list($this->body());
		/***
			$api = new WassrAPI();
			$result = $api->user_timeline();
			foreach($result as $r){
				eq(true,($r instanceof WassrMessage));
			}
		 */
	}

	/**
	 * ユーザの最新1件の発言
	 *
	 * @return WassrMessage
	 */
	public function show(){
		$this->vars("id",$this->id);
		$this->do_get("statuses/show.json");
		$result = WassrMessage::parse_list($this->body());
		if(empty($result)) throw new Exception("not found messege");
		return $result[0];
		/***
			$api = new WassrAPI();
			eq(true,($api->show() instanceof WassrMessage));
		 */
	}
	/**
	 * 返信
	 *
	 * @return array WassrMessage
	 */
	public function replies(){
		$this->do_get("statuses/replies.json");
		return WassrMessage::parse_list($this->body());
		/***
			$api = new WassrAPI();
			$result = $api->replies();
			foreach($result as $r){
				eq(true,($r instanceof WassrMessage));
			}
		 */
	}
	/**
	 * ステータスの更新
	 *
	 * @param string $status
	 * @param string $reply_status_rid
	 */
	public function update($status,$reply_status_rid=null){
		if(!empty($this->source)) $this->vars("source",$this->source);
		if(!empty($reply_status_rid)) $this->vars("reply_status_rid",$reply_status_rid);
		$this->vars("status",$status);
		$this->post("statuses/update.json");
		/***
		 	try{
				$api = new WassrAPI();
				$api->update("doc test");
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
	 * @param string $reply_status_rid
	 */
	public function image_update(File $image_data,$status=null,$reply_status_rid=null){
		$this->vars("image",$image_data);
		if(!empty($this->source)) $this->vars("source",$this->source);
		if(!empty($reply_status_rid)) $this->vars("reply_status_rid",$reply_status_rid);
		$this->vars("status",(empty($status) ? $image_data->name() : $status));
		$this->post("statuses/update.json");
	}

	/**
	 * 自分のユーザ情報を取得
	 *
	 * @return WassrUser
	 */
	public function my(){
		$this->do_post("user/edit.json");
		return WassrUser::parse($this->body());
	}
	/**
	 * ニックネームを更新する
	 *
	 * @param string $nickname
	 * @return WassrUser
	 */
	public function update_nickname($nickname){
		$this->vars("nick",$nickname);
		$this->do_post("user/edit.json");
		return WassrUser::parse($this->body());
	}
	/**
	 * 足跡取得
	 *
	 * @return array WassrUser
	 */
	public function footmark(){
		$this->do_get("footmark/recent.json");
		return WassrUser::parse_list($this->body());
	}
	/**
	 * 友達一覧
	 *
	 * @return array WassrUser
	 */
	public function friends(){
		$this->do_get("statuses/friends.json");
		return WassrUser::parse_list($this->body());
	}

	/**
	 * todoの一覧
	 *
	 * @param int $done_fg 0:終了していないタスク 1:終了しているタスク
	 * @param int $page
	 * @return array WassrTodo
	 */
	public function todo_list($done_fg=0,$page=1){
		$this->vars("page",$page);
		$this->vars("done_fg",$done_fg);
		$this->do_get("todo/list.json");
		return WassrTodo::parse_list($this->body());
	}
	/**
	 * todoの追加
	 *
	 * @param string $body
	 * @return WassrTodo
	 */
	public function todo_add($body){
		$this->vars("body",$body);
		$this->post("todo/add.json");
	}
	/**
	 * todoの開始
	 *
	 * @param string $todo_rid
	 */
	public function todo_start($todo_rid){
		$this->vars("todo_rid",$todo_rid);
		$this->post("todo/start.json");
	}
	/**
	 * todoの一時停止
	 *
	 * @param string $todo_rid
	 */
	public function todo_stop($todo_rid){
		$this->vars("todo_rid",$todo_rid);
		$this->post("todo/stop.json");
	}
	/**
	 * todoの終了
	 *
	 * @param string $todo_rid
	 */
	public function todo_done($todo_rid){
		$this->vars("todo_rid",$todo_rid);
		$this->post("todo/done.json");
	}
	/**
	 * todoの削除
	 *
	 * @param string $todo_rid
	 */
	public function todo_delete($todo_rid){
		$this->vars("todo_rid",$todo_rid);
		$this->post("todo/delete.json");
	}
	/**
	 * チャンネル
	 *
	 * @param string $name_en
	 * @return array WassrChannelMessage
	 */
	public function channel_messages($name_en){
		$this->vars("name_en",$name_en);
		$this->do_get("channel_message/list.json");
		return WassrChannelMessage::parse_list($this->body());
	}
	/**
	 * チャンネルへの発言
	 *
	 * @param string $name_en
	 * @param string $body
	 */
	public function channel_update($name_en,$body){
		$this->vars("name_en",$name_en);
		$this->vars("body",$body);
		$this->post("channel_message/update.json");
	}
	/**
	 * チャンネルへの発言(画像添付)
	 *
	 * @param string $name_en
	 * @param File $image_data
	 * @param string $body
	 */
	public function channel_image_update($name_en,File $image_data,$body=null){
		$this->vars("name_en",$name_en);
		$this->vars("image",$image_data);
		$this->vars("body",(empty($body) ? $image_data->name() : $body));
		$this->post("channel_message/update.json");
	}
	/**
	 * 購読
	 *
	 * @param string $login_id
	 */
	public function friendships($login_id){
		$this->post("friendships/create/".$login_id.".json");
	}
	/**
	 * 購読解除
	 *
	 * @param string $login_id
	 */
	public function friendships_destroy($login_id){
		$this->post("friendships/destroy/".$login_id.".json");
	}
	/**
	 * イイネ!をつける
	 *
	 * @param string $rid
	 */
	public function favorites($rid){
		$this->post("favorites/create/".$rid.".json");
	}
	/**
	 * イイネ!をけす
	 *
	 * @param string $rid
	 */
	public function favorites_destroy($rid){
		$this->post("favorites/destroy/".$rid.".json");
	}
	public function user_channel($login_id){
		$this->do_get("channel_user/user_list.json");
		return WassrChannel::parse_list($this->body());
	}
	private function post($url){
		$this->do_post($url);
		$res = Text::parse_json($this->body());
		if(isset($res["error"])) throw new Exception($res["error"]);
	}
}
?>