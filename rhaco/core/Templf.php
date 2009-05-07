<?php
Rhaco::import("core.Tag");
Rhaco::import("core.Http");
Rhaco::import("core.Flow");
/**
 * テンプレートで利用するフォーマットツール
 *
 * @author Kazutaka Tokushima
 * @license New BSD License
 */
class Templf{
	/**
	 * フォーマットした日付を取得
	 * @param int $value
	 * @param string $format
	 * @return string
	 */
	final static public function df($value,$format="Y/m/d H:i:s"){
		return date($format,$value);
	}
	/**
	 * HTML表現を返す
	 * @param string $value
	 * @param int $letgth
	 * @param int $lines
	 * @return string
	 */
	final static public function html($value,$length=0,$lines=0){
		/***
		 * eq("&lt;hoge&gt;hoge&lt;/hoge&gt;<br />\n&lt;hoge&gt;hoge&lt;/hoge&gt;",Templf::html("<hoge>hoge</hoge>\n<hoge>hoge</hoge>"));
		 * eq("aaa<br />\nb",Templf::html("aaa\nbbb\nccc",5));
		 * eq("aaa<br />\nbbb",Templf::html("aaa\nbbb\nccc",0,2));
		 * eq("aaa<br />\nb",Templf::html("aaa\nbbb\nccc",5,2));
		 */
		$value = Tag::cdata(str_replace(array("\r\n","\r"),"\n",$value));
		if($length > 0) $value = mb_substr($value,0,$length,mb_detect_encoding($value));
		if($lines > 0){
			$ln = array();
			$l = explode("\n",$value);
			for($i=0;$i<$lines;$i++) $ln[] = $l[$i];
			$value = implode("\n",$ln);
		}
		return nl2br(str_replace(array("<",">","'","\""),array("&lt;","&gt;","&#039;","&quot;"),$value));
	}
	final static public function text($value,$length=0,$lines=0){
		return self::html(preg_replace("/<.+?>/","",$value),$length,$lines);
	}
	/**
	 * htmlエンコードをする
	 * @param string $value
	 * @return string
	 */
	final static public function htmlencode($value){
		return Text::htmlencode(Tag::cdata($value));
	}
	/**
	 * htmlデコードをする
	 * @param string $value
	 * @return string
	 */
	final static public function htmldecode($value){
		return Text::htmldecode(Tag::cdata($value));
	}
	final static public function cdata($value){
		return Tag::cdata($value);
	}
	/**
	 * $numberが偶数だった場合にeven奇数の場合にoddを返す
	 * @param int $number
	 * @return string
	 */
	final static public function evenodd($number,$even="even",$odd="odd"){
		return (($number % 2) == 0) ? $even : $odd;
	}
	/**
	 * $end_number番目か
	 *
	 * @param int $number
	 * @param int $end_number
	 * @return boolean
	 */
	final static public function is_block_end($number,$end_number){
		return ($number > 0 && ($number % $end_number === 0));
	}
	/**
	 * 改行を削除(置換)する
	 *
	 * @param string $value
	 * @param string $glue
	 * @return string
	 */
	final static public function one_liner($value,$glue=" "){
		return str_replace(array("\r\n","\r","\n","<br>","<br />"),$glue,$value);
	}
	/**
	 * query文字列に変換する
	 * Http::queryのエイリアス
	 *
	 * @param mixed $var
	 * @param string $name
	 * @param boolean $null
	 * @return string
	 */
	final static public function query($var,$name=null,$null=true){
		return Http::query($var,$name,$null);
	}
	/**
	 * Flow::handlerでマッチしたパターン（名）を返す
	 *
	 * @return string
	 */
	final static public function match_pattern(){
		return Flow::match_pattern();
	}
	/**
	 * リクエストされたURLを返す
	 *
	 * @return string
	 */
	final static public function request_url(){
		return Flow::request_url(false);
	}
	/**
	 * urlパスを返す
	 *
	 * @param string $path
	 * @return string
	 */
	final static public function url($path=null){
		return Rhaco::url($path);
	}
	/**
	 * refererを返す
	 *
	 * @return string
	 */
	final static public function referer(){
		return Http::referer();
	}
	
	final static public function def($key){
		return Rhaco::def($key);
	}
}
?>