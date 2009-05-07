<?php
require(dirname(__FILE__)."/__settings__.php");
import("core.Lib");
import("core.Flow");
import("core.Log");
lib("arbo.service.WassrAPI");
lib("arbo.service.TwitterAPI");
lib("arbo.service.Lou");

class Message extends Object{
	static protected $__created_at__ = "type=timestamp";
	static protected $__type__ = "type=choice(twitter,wassr)";
	protected $text;
	protected $screen_name;
	protected $profile_image_url;
	protected $created_at;
	protected $type;

	static public function parse($message){
		$obj = new self();
		$obj->text(Lou::trans_html($message->text()));
		$obj->screen_name($message->user()->screen_name());
		$obj->profile_image_url($message->user()->profile_image_url());
		$obj->created_at($message->created_at());

		if($message instanceof WassrMessage){
			$obj->type("wassr");
		}else if($message instanceof TwitterMessage){
			$obj->type("twitter");
		}
		return $obj;
	}
	public function key(){
		return $this->fmCreated_at("YmdHi").$this->text();
	}
	static public function search($query){
		$messages = array();
		foreach(R(new TwitterAPI())->search($query) as $message) $messages[] = Message::parse($message);
		rosort($messages,"created_at");
		return $messages;
	}
	static public function friends_timeline(){
		$messages = array();
//		foreach(R(new TwitterAPI())->friends_timeline() as $message) $messages[] = Message::parse($message);
		foreach(R(new WassrAPI())->friends_timeline() as $message) $messages[] = Message::parse($message);
		$messages = omerge($messages,"key");
		rosort($messages,"created_at");
		return $messages;
	}
	static public function update($text){
		R(new TwitterAPI())->update($text);
		R(new WassrAPI())->update($text);
	}
}
$flow = new Flow();
if($flow->isPost() && $flow->isVars("text")){
	Message::update($flow->inVars("text"));
	$flow->redirect_self();
}
$flow->vars("messages",Message::friends_timeline());
//$flow->output(__FILE__);
exit;
?>
<rt:template>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
</head>
<body>
<form method="post">
	<textarea rows="3" cols="50" name="text"></textarea>
	<input type="submit" value="update" />
</form>
<table rt:param="messages" rt:var="m" border="1">
<tr>
	<td><img src="{$m.profile_image_url()}" width="50" height="50" /></td>
	<td>{$m.screen_name()}</td>
	<td>{$m.text()}</td>
	<td>{$m.fmCreated_at()}</td>
	<td>{$m.type()}</td>
</tr>
</table>
</body>
</html>
</rt:template>